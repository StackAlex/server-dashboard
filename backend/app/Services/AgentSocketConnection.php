<?php

namespace App\Services;

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

        $length = strlen($frame);
        $written = 0;

        while ($written < $length) {
            $result = @fwrite(
                $this->socket,
                substr($frame, $written)
            );

            if ($result === false || $result === 0) {
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