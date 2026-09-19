<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AgentSocketConnection
{
    public string $id;

    private $socket;

    public bool $authenticated = false;

    public ?string $agentUuid = null;

    public string $type;

    public ?string $targetAgentUuid = null;

    public ?string $terminalSessionId = null;

    public function __construct(
        string $id,
        $socket,
        string $type = 'agent'
    ) {
        $this->id = $id;
        $this->socket = $socket;
        $this->type = $type;

        // Socket должен работать без дополнительной буферизации.
        if (is_resource($this->socket)) {
            stream_set_blocking($this->socket, false);
        }
    }

    /**
     * Отправить WebSocket text frame.
     */
    public function send(array $payload): bool
    {
        if (!is_resource($this->socket)) {
            Log::warning('[WebSocket] Cannot send: socket is not resource', [
                'connection' => $this->id,
                'type' => $payload['type'] ?? null,
            ]);

            return false;
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            Log::error('[WebSocket] JSON encode failed', [
                'connection' => $this->id,
                'error' => json_last_error_msg(),
            ]);

            return false;
        }

        $frame = $this->encodeFrame($json);

        $total = strlen($frame);
        $written = 0;

        while ($written < $total) {
            $remaining = substr($frame, $written);

            $result = @fwrite(
                $this->socket,
                $remaining
            );

            if ($result === false) {
                Log::error('[WebSocket] Failed to write frame', [
                    'connection' => $this->id,
                    'type' => $payload['type'] ?? null,
                    'written' => $written,
                    'total' => $total,
                ]);

                return false;
            }

            if ($result === 0) {
                /*
                 * Socket может временно не принимать данные.
                 *
                 * Не считаем это успешной отправкой.
                 */
                Log::warning('[WebSocket] fwrite returned 0', [
                    'connection' => $this->id,
                    'type' => $payload['type'] ?? null,
                    'written' => $written,
                    'total' => $total,
                ]);

                return false;
            }

            $written += $result;
        }

        /*
         * Принудительно сбрасываем PHP stream buffer.
         */
        @fflush($this->socket);

        Log::debug('[WebSocket] Frame sent', [
            'connection' => $this->id,
            'type' => $payload['type'] ?? null,
            'bytes' => $written,
        ]);

        return true;
    }

    /**
     * Попытаться извлечь ОДИН WebSocket frame из буфера.
     *
     * Возвращает:
     *
     * [
     *     'payload' => string,
     *     'consumed' => int,
     * ]
     *
     * либо null, если полного frame ещё нет.
     */
    public function receiveFrame(string $buffer): ?array
    {
        $bufferLength = strlen($buffer);

        /*
         * Минимальный frame:
         *
         * 2 bytes header
         * 4 bytes mask
         *
         * = 6 bytes
         */
        if ($bufferLength < 2) {
            return null;
        }

        $firstByte = ord($buffer[0]);
        $secondByte = ord($buffer[1]);

        $fin = ($firstByte & 0x80) !== 0;
        $opcode = $firstByte & 0x0F;

        $masked = ($secondByte & 0x80) !== 0;
        $length = $secondByte & 0x7F;

        /*
         * Нам пока нужны обычные завершённые frames.
         */
        if (!$fin) {
            Log::warning('[WebSocket] Fragmented frame is not supported', [
                'connection' => $this->id,
            ]);

            return null;
        }

        /*
         * Close frame.
         */
        if ($opcode === 0x08) {
            return [
                'payload' => '',
                'consumed' => 2,
                'opcode' => $opcode,
            ];
        }

        /*
         * Ping.
         */
        if ($opcode === 0x09) {
            $headerLength = 2;

            if ($length === 126) {
                $headerLength += 2;
            } elseif ($length === 127) {
                $headerLength += 8;
            }

            if ($masked) {
                $headerLength += 4;
            }

            if ($bufferLength < $headerLength) {
                return null;
            }

            return $this->parseFrame(
                $buffer,
                $opcode
            );
        }

        /*
         * Pong.
         */
        if ($opcode === 0x0A) {
            return $this->parseFrame(
                $buffer,
                $opcode
            );
        }

        /*
         * Нас интересует text frame.
         */
        if ($opcode !== 0x01) {
            Log::debug('[WebSocket] Ignoring unsupported opcode', [
                'connection' => $this->id,
                'opcode' => $opcode,
            ]);

            $frame = $this->parseFrame(
                $buffer,
                $opcode
            );

            return $frame;
        }

        return $this->parseFrame(
            $buffer,
            $opcode
        );
    }

    /**
     * Старый интерфейс оставляем для совместимости.
     *
     * Важно:
     * новый сервер должен использовать receiveFrame().
     */
    public function receive(string $data): string
    {
        $frame = $this->receiveFrame($data);

        if ($frame === null) {
            return '';
        }

        return $frame['payload'];
    }

    /**
     * Разбор одного полного WebSocket frame.
     */
    private function parseFrame(
        string $buffer,
        int $opcode
    ): ?array {
        $bufferLength = strlen($buffer);

        if ($bufferLength < 2) {
            return null;
        }

        $secondByte = ord($buffer[1]);

        $masked = ($secondByte & 0x80) !== 0;
        $length = $secondByte & 0x7F;

        $offset = 2;

        /*
         * Payload length = 126.
         */
        if ($length === 126) {
            if ($bufferLength < $offset + 2) {
                return null;
            }

            $unpacked = unpack(
                'n',
                substr($buffer, $offset, 2)
            );

            $length = $unpacked[1];

            $offset += 2;
        }

        /*
         * Payload length = 127.
         *
         * PHP 64-bit ожидается для нормальной работы
         * с большими frames.
         */
        elseif ($length === 127) {
            if ($bufferLength < $offset + 8) {
                return null;
            }

            $parts = unpack(
                'N2',
                substr($buffer, $offset, 8)
            );

            $length = ($parts[1] << 32) | $parts[2];

            $offset += 8;
        }

        /*
         * Client -> Server WebSocket frames должны быть masked.
         */
        if (!$masked) {
            Log::warning('[WebSocket] Received unmasked client frame', [
                'connection' => $this->id,
                'opcode' => $opcode,
            ]);

            return null;
        }

        /*
         * Mask.
         */
        if ($bufferLength < $offset + 4) {
            return null;
        }

        $mask = substr(
            $buffer,
            $offset,
            4
        );

        $offset += 4;

        /*
         * Полный frame ещё не получен.
         */
        if ($bufferLength < $offset + $length) {
            return null;
        }

        $payload = substr(
            $buffer,
            $offset,
            $length
        );

        /*
         * Unmask.
         */
        $decoded = '';

        for ($i = 0; $i < $length; $i++) {
            $decoded .= $payload[$i]
                ^ $mask[$i % 4];
        }

        $consumed = $offset + $length;

        return [
            'payload' => $decoded,
            'consumed' => $consumed,
            'opcode' => $opcode,
        ];
    }

    /**
     * Создание server -> client text frame.
     *
     * Server frames НЕ должны быть masked.
     */
    private function encodeFrame(string $payload): string
    {
        $length = strlen($payload);

        $frame = chr(0x81);

        if ($length <= 125) {
            $frame .= chr($length);
        } elseif ($length <= 65535) {
            $frame .= chr(126);
            $frame .= pack('n', $length);
        } else {
            /*
             * 64-bit payload length.
             */
            $frame .= chr(127);

            $high = intdiv($length, 4294967296);
            $low = $length % 4294967296;

            $frame .= pack(
                'N2',
                $high,
                $low
            );
        }

        return $frame . $payload;
    }

    /**
     * Отвечаем на WebSocket ping.
     */
    public function sendPong(string $payload = ''): bool
    {
        if (!is_resource($this->socket)) {
            return false;
        }

        $length = strlen($payload);

        if ($length > 125) {
            $payload = substr($payload, 0, 125);
            $length = strlen($payload);
        }

        $frame =
            chr(0x8A) .
            chr($length) .
            $payload;

        $written = @fwrite(
            $this->socket,
            $frame
        );

        @fflush($this->socket);

        return $written === strlen($frame);
    }
    
    public function sendPing(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        $frame = chr(0x89) . chr(0x00);

        $written = @fwrite(
            $this->socket,
            $frame
        );

        if ($written !== false) {
            @fflush($this->socket);
            return true;
        }

        return false;
    }

    

    public function isOpen(): bool
    {
        return is_resource($this->socket)
            && !feof($this->socket);
    }

    public function close(): void
    {
        if (!is_resource($this->socket)) {
            return;
        }

        $socket = $this->socket;

        $this->socket = null;

        @fclose($socket);
    }

    /**
     * Получить raw socket.
     *
     * Используется сервером для stream_select().
     */
    public function getSocket()
    {
        return $this->socket;
    }

    
}