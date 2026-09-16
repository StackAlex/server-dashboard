<?php

namespace App\Services;
use SplMutex;

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
    }

    public function send(array $payload): bool
    {
        if (!is_resource($this->socket)) {
            return false;
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return false;
        }

        $frame = $this->encodeFrame($json);

        $total = strlen($frame);
        $written = 0;

        while ($written < $total) {
            $result = @fwrite(
                $this->socket,
                substr($frame, $written)
            );

            if ($result === false || $result === 0) {
                Log::error('[WebSocket] Failed to write frame', [
                    'connection' => $this->id,
                    'type' => $payload['type'] ?? null,
                    'written' => $written,
                    'total' => $total,
                ]);

                return false;
            }

            $written += $result;
        }

        return true;
    }

    public function receive(string $data): string
    {
        return $this->decodeFrame($data);
    }

    public function isOpen(): bool
    {
        return is_resource($this->socket) && !feof($this->socket);
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
    protected function handleTerminalOutput(
        AgentSocketConnection $agent,
        array $message
    ): void {
        $terminal = $this->findTerminalBySession($message);

        if (!$terminal) {
            \Log::warning('[terminal] Browser connection not found', [
                'session_id' => $message['payload']['session_id'] ?? null,
            ]);

            return;
        }

        $payload = [
            'type' => 'terminal:output',
            'payload' => $message['payload'] ?? [],
        ];

        \Log::info('[terminal] Sending output to browser', [
            'connection' => $terminal->id,
            'session_id' => $message['payload']['session_id'] ?? null,
            'data_length' => strlen(
                $message['payload']['data'] ?? ''
            ),
        ]);

        $result = $terminal->send($payload);

        \Log::info('[terminal] Browser send result', [
            'connection' => $terminal->id,
            'result' => $result,
        ]);
    }

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
            $frame .= chr(127);
            $frame .= pack('J', $length);
        }

        return $frame . $payload;
    }

    private function decodeFrame(string $data): string
    {
        if (strlen($data) < 6) {
            return '';
        }

        $firstByte = ord($data[0]);

        // Пока обрабатываем только text frames.
        if (($firstByte & 0x0F) !== 0x01) {
            return '';
        }

        $length = ord($data[1]) & 127;
        $offset = 2;

        if ($length === 126) {
            if (strlen($data) < 8) {
                return '';
            }

            $length = unpack(
                'n',
                substr($data, 2, 2)
            )[1];

            $offset = 4;
        } elseif ($length === 127) {
            if (strlen($data) < 14) {
                return '';
            }

            $length = unpack(
                'J',
                substr($data, 2, 8)
            )[1];

            $offset = 10;
        }

        if (strlen($data) < $offset + 4) {
            return '';
        }

        $mask = substr($data, $offset, 4);
        $offset += 4;

        if (strlen($data) < $offset + $length) {
            return '';
        }

        $payload = substr(
            $data,
            $offset,
            $length
        );

        $decoded = '';

        for ($i = 0; $i < $length; $i++) {
            $decoded .= $payload[$i]
                ^ $mask[$i % 4];
        }

        return $decoded;
    }
}