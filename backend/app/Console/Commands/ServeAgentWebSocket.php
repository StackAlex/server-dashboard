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
            $this->error("Unable to listen on {$host}:{$port}: {$errstr}");
            return self::FAILURE;
        }

        stream_set_blocking($serverSocket, false);

        $service = new AgentWebSocketService();

        $this->info("Agent WebSocket server listening on ws://{$host}:{$port}/ws/agent");

        $connections = [];

        while (true) {

            // Принимаем новые подключения
            while ($clientSocket = @stream_socket_accept($serverSocket, 0)) {

                stream_set_blocking($clientSocket, false);

                $id = (string) intval(microtime(true) * 1000);

                $connection = new AgentSocketConnection(
                    $id,
                    $clientSocket,
                    'agent'
                );

                $connections[$id] = [
                    'socket' => $clientSocket,
                    'connection' => $connection,
                    'handshake' => '',
                    'ready' => false,
                ];

                $service->registerConnection($connection);

                $this->info("Client connected: {$id}");
            }

            foreach ($connections as $id => &$client) {

                $socket = $client['socket'];

                if (!is_resource($socket) || feof($socket)) {
                    $service->disconnect($client['connection']);
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

                // ---------- Handshake ----------
                if (!$client['ready']) {

                    $client['handshake'] .= $chunk;

                    if (!str_contains($client['handshake'], "\r\n\r\n")) {
                        continue;
                    }

                    if (
                        preg_match(
                            '/GET \/ws\/agent HTTP\//',
                            $client['handshake']
                        ) === 1
                    ) {
                        $this->performHandshake(
                            $socket,
                            $client['handshake']
                        );

                        $client['ready'] = true;

                        $this->info("Handshake completed: {$id}");
                    }

                    continue;
                }

                // ---------- WebSocket ----------
                $payload = $client['connection']->receive($chunk);

                if ($payload === '') {
                    continue;
                }

                $this->line("Payload: {$payload}");

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

    protected function performHandshake($clientSocket, string $headers): void
    {
        if (preg_match('/Sec-WebSocket-Key: (.*?)\r\n/i', $headers, $matches) !== 1) {
            return;
        }

        $key = trim($matches[1]);
        $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: {$accept}\r\n\r\n";

        fwrite($clientSocket, $response);
    }
}