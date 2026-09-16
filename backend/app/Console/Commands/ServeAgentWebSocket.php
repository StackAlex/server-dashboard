<?php

namespace App\Console\Commands;

use App\Services\AgentSocketConnection;
use App\Services\AgentWebSocketService;
use Illuminate\Console\Command;

class ServeAgentWebSocket extends Command
{
    protected $signature = 'agent:ws {--host=0.0.0.0} {--port=8000}';

    protected $description = 'Start the Laravel WebSocket server for agent connections';

    public function handle(): int
    {
        $host = $this->option('host');
        $port = (int) $this->option('port');

        $serverSocket = stream_socket_server(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr
        );

        if (!$serverSocket) {
            $this->error(
                "Unable to listen on {$host}:{$port}: {$errstr}"
            );

            return self::FAILURE;
        }

        stream_set_blocking($serverSocket, false);

        $service = new AgentWebSocketService();

        $this->info(
            "WebSocket server listening on ws://{$host}:{$port}"
        );

        $connections = [];

        while (true) {

            // -------------------------------------------------
            // Новые подключения
            // -------------------------------------------------

            while ($clientSocket = @stream_socket_accept($serverSocket, 0)) {

                stream_set_blocking($clientSocket, false);

                $id = (string) intval(microtime(true) * 1000);

                /*
                 * Тип пока неизвестен.
                 * Определим его после HTTP WebSocket handshake.
                 */
                $connection = new AgentSocketConnection(
                    $id,
                    $clientSocket,
                    'unknown'
                );

                $connections[$id] = [
                    'socket' => $clientSocket,
                    'connection' => $connection,
                    'handshake' => '',
                    'ready' => false,
                    'path' => null,
                ];

                $service->registerConnection($connection);

                $this->info("Client connected: {$id}");
            }

            // -------------------------------------------------
            // Обработка подключений
            // -------------------------------------------------

            foreach ($connections as $id => &$client) {

                $socket = $client['socket'];

                // Соединение закрыто
                if (!is_resource($socket) || feof($socket)) {

                    $service->disconnect(
                        $client['connection']
                    );

                    if (is_resource($socket)) {
                        @fclose($socket);
                    }

                    unset($connections[$id]);

                    continue;
                }

                $chunk = @fread($socket, 4096);

                if ($chunk === false || $chunk === '') {
                    continue;
                }

                // -------------------------------------------------
                // HTTP -> WebSocket handshake
                // -------------------------------------------------

                if (!$client['ready']) {

                    $client['handshake'] .= $chunk;

                    if (!str_contains(
                        $client['handshake'],
                        "\r\n\r\n"
                    )) {
                        continue;
                    }

                    $path = $this->getWebSocketPath(
                        $client['handshake']
                    );

                    if ($path === null) {

                        $this->error(
                            "Invalid WebSocket handshake: {$id}"
                        );

                        @fclose($socket);
                        unset($connections[$id]);

                        continue;
                    }

                    // Разрешаем только наши WebSocket endpoints
                    if (!in_array(
                        $path,
                        ['/ws/agent', '/ws/terminal'],
                        true
                    )) {

                        $this->error(
                            "Rejected WebSocket path: {$path}"
                        );

                        $this->sendHttpError(
                            $socket,
                            404,
                            'Not Found'
                        );

                        @fclose($socket);
                        unset($connections[$id]);

                        continue;
                    }

                    // Определяем тип подключения
                    $type = match ($path) {
                        '/ws/agent' => 'agent',
                        '/ws/terminal' => 'terminal',
                        default => 'unknown',
                    };

                    $client['path'] = $path;
                    $client['connection']->type = $type;

                    // Выполняем handshake
                    if (!$this->performHandshake(
                        $socket,
                        $client['handshake']
                    )) {

                        $this->error(
                            "WebSocket handshake failed: {$id}"
                        );

                        @fclose($socket);
                        unset($connections[$id]);

                        continue;
                    }

                    $client['ready'] = true;

                    $this->info(
                        "Handshake completed: {$id} " .
                        "path={$path} type={$type}"
                    );

                    continue;
                }

                // -------------------------------------------------
                // WebSocket frame
                // -------------------------------------------------

                $payload = $client['connection']->receive($chunk);

                if ($payload === '') {
                    continue;
                }

                $this->line(
                    "[{$client['connection']->type}] Payload: {$payload}"
                );

                try {

                    $service->handleMessage(
                        $client['connection'],
                        $payload
                    );

                } catch (\Throwable $e) {

                    $this->error($e->getMessage());
                    $this->error($e->getFile());
                    $this->error($e->getLine());
                }
            }

            unset($client);

            usleep(10000);
        }
    }

    /**
     * Получить путь из первой строки HTTP WebSocket handshake.
     */
    protected function getWebSocketPath(string $headers): ?string
    {
        $lines = explode("\r\n", $headers);

        if (empty($lines[0])) {
            return null;
        }

        if (!preg_match(
            '#^GET\s+(\S+)\s+HTTP/[\d.]+$#',
            $lines[0],
            $matches
        )) {
            return null;
        }

        $path = parse_url(
            $matches[1],
            PHP_URL_PATH
        );

        return is_string($path) ? $path : null;
    }

    /**
     * Выполнить WebSocket handshake.
     */
    protected function performHandshake(
        $clientSocket,
        string $headers
    ): bool {

        if (preg_match(
            '/Sec-WebSocket-Key:\s*(.*?)\r\n/i',
            $headers,
            $matches
        ) !== 1) {
            return false;
        }

        $key = trim($matches[1]);

        $accept = base64_encode(
            sha1(
                $key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
                true
            )
        );

        $response =
            "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: {$accept}\r\n" .
            "\r\n";

        return fwrite(
            $clientSocket,
            $response
        ) !== false;
    }

    /**
     * Отправить обычный HTTP error response.
     */
    protected function sendHttpError(
        $socket,
        int $status,
        string $message
    ): void {

        $body = $message . "\n";

        $response =
            "HTTP/1.1 {$status} {$message}\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Length: " . strlen($body) . "\r\n" .
            "Connection: close\r\n" .
            "\r\n" .
            $body;

        @fwrite($socket, $response);
    }
}