<?php

namespace App\Console\Commands;

use App\Services\AgentSocketConnection;
use App\Services\AgentWebSocketService;
use Illuminate\Console\Command;

class ServeAgentWebSocket extends Command
{
    protected $signature = 'agent:ws
        {--host=0.0.0.0}
        {--port=8000}';

    protected $description =
        'Start the Laravel WebSocket server for agent connections';

    public function handle(): int
    {
        $host = $this->option('host');
        $port = (int) $this->option('port');

        $serverSocket = @stream_socket_server(
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

        stream_set_blocking(
            $serverSocket,
            false
        );

        $service = new AgentWebSocketService();

        $this->info(
            "WebSocket server listening on ws://{$host}:{$port}"
        );

        /**
         * Структура:
         *
         * $connections[$id] = [
         *     'socket' => resource,
         *     'connection' => AgentSocketConnection,
         *     'handshake' => string,
         *     'ready' => bool,
         *     'path' => ?string,
         *     'buffer' => string,
         * ];
         */
        $connections = [];
        $lastPing = time();

        while (true) {
            // WebSocket heartbeat
            if (time() - $lastPing >= 30) {
                foreach ($connections as $client) {
                    if (
                        isset($client['connection'])
                        && $client['connection']->isOpen()
                    ) {
                        $client['connection']->sendPing();
                    }
                }

                $lastPing = time();
            }
            /*
             * =====================================================
             * 1. Новые подключения
             * =====================================================
             */

            while (
                $clientSocket = @stream_socket_accept(
                    $serverSocket,
                    0
                )
            ) {
                stream_set_blocking(
                    $clientSocket,
                    false
                );

                /*
                 * Не используем timestamp как ID.
                 *
                 * Два подключения могут появиться
                 * в одну миллисекунду.
                 */
                $id = bin2hex(
                    random_bytes(16)
                );

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

                    /*
                     * Очень важно:
                     *
                     * TCP chunk != WebSocket frame.
                     *
                     * Здесь храним недополученные данные.
                     */
                    'buffer' => '',
                ];

                $service->registerConnection(
                    $connection
                );

                $this->info(
                    "Client connected: {$id}"
                );
            }

            /*
             * =====================================================
             * 2. stream_select()
             * =====================================================
             *
             * Вместо постоянного fread() каждого сокета
             * используем нормальное ожидание данных.
             */

            $readSockets = [
                $serverSocket,
            ];

            foreach ($connections as $client) {
                if (
                    is_resource(
                        $client['socket']
                    )
                ) {
                    $readSockets[] =
                        $client['socket'];
                }
            }

            $writeSockets = null;
            $exceptSockets = null;

            /*
             * Timeout 100ms.
             *
             * Это позволяет:
             *
             * - моментально реагировать на данные;
             * - не грузить CPU;
             * - регулярно проверять соединения.
             */

            $selected = @stream_select(
                $readSockets,
                $writeSockets,
                $exceptSockets,
                0,
                100000
            );

            if ($selected === false) {
                usleep(10000);
                continue;
            }

            /*
             * =====================================================
             * 3. Новые подключения через stream_select
             * =====================================================
             */

            if (
                in_array(
                    $serverSocket,
                    $readSockets,
                    true
                )
            ) {
                /*
                 * accept уже выполняется выше в начале цикла,
                 * поэтому здесь ничего дополнительно делать не нужно.
                 */
            }

            /*
             * =====================================================
             * 4. Обработка клиентских соединений
             * =====================================================
             */

            foreach (
                $connections as $id => &$client
            ) {
                $socket = $client['socket'];
                $connection = $client['connection'];

                if (!is_resource($socket)) {
                    $service->disconnect(
                        $connection
                    );

                    unset(
                        $connections[$id]
                    );

                    continue;
                }

                /*
                 * stream_select вернул список readable sockets.
                 *
                 * Проверяем, есть ли наш socket.
                 */
                if (
                    !in_array(
                        $socket,
                        $readSockets,
                        true
                    )
                ) {
                    continue;
                }

                /*
                 * =================================================
                 * Соединение закрыто
                 * =================================================
                 */

                if (feof($socket)) {
                    $service->disconnect(
                        $connection
                    );

                    @fclose($socket);

                    unset(
                        $connections[$id]
                    );

                    continue;
                }

                /*
                 * =================================================
                 * Читаем данные
                 * =================================================
                 */

                $chunk = @fread(
                    $socket,
                    8192
                );

                if (
                    $chunk === false
                    || $chunk === ''
                ) {
                    /*
                     * Может означать:
                     *
                     * - временно нет данных;
                     * - соединение закрывается.
                     *
                     * Проверим на следующей итерации.
                     */
                    continue;
                }

                /*
                 * =================================================
                 * HTTP -> WebSocket handshake
                 * =================================================
                 */

                if (!$client['ready']) {

                    $client['handshake'] .= $chunk;

                    if (
                        !str_contains(
                            $client['handshake'],
                            "\r\n\r\n"
                        )
                    ) {
                        continue;
                    }

                    $path =
                        $this->getWebSocketPath(
                            $client['handshake']
                        );

                    if ($path === null) {
                        $this->error(
                            "Invalid WebSocket handshake: {$id}"
                        );

                        @fclose($socket);

                        unset(
                            $connections[$id]
                        );

                        continue;
                    }

                    /*
                     * Разрешаем только наши endpoints.
                     */

                    if (
                        !in_array(
                            $path,
                            [
                                '/ws/agent',
                                '/ws/terminal',
                            ],
                            true
                        )
                    ) {
                        $this->error(
                            "Rejected WebSocket path: {$path}"
                        );

                        $this->sendHttpError(
                            $socket,
                            404,
                            'Not Found'
                        );

                        @fclose($socket);

                        unset(
                            $connections[$id]
                        );

                        continue;
                    }

                    /*
                     * Определяем тип соединения.
                     */

                    $type = match ($path) {
                        '/ws/agent' =>
                            'agent',

                        '/ws/terminal' =>
                            'terminal',

                        default =>
                            'unknown',
                    };

                    $client['path'] = $path;

                    $connection->type =
                        $type;

                    /*
                     * Выполняем handshake.
                     */

                    if (
                        !$this->performHandshake(
                            $socket,
                            $client['handshake']
                        )
                    ) {
                        $this->error(
                            "WebSocket handshake failed: {$id}"
                        );

                        @fclose($socket);

                        unset(
                            $connections[$id]
                        );

                        continue;
                    }

                    $client['ready'] = true;

                    /*
                     * После handshake в $chunk теоретически
                     * могут остаться WebSocket данные.
                     *
                     * Поэтому они не должны потеряться.
                     */

                    $handshakeEnd =
                        strpos(
                            $client['handshake'],
                            "\r\n\r\n"
                        );

                    if (
                        $handshakeEnd !== false
                    ) {
                        $headerLength =
                            $handshakeEnd + 4;

                        $totalHandshakeData =
                            strlen(
                                $client['handshake']
                            );

                        if (
                            $totalHandshakeData >
                            $headerLength
                        ) {
                            $remaining =
                                substr(
                                    $client['handshake'],
                                    $headerLength
                                );

                            $client['buffer'] .=
                                $remaining;
                        }
                    }

                    $this->info(
                        "Handshake completed: {$id} " .
                        "path={$path} type={$type}"
                    );

                    /*
                     * Не continue:
                     *
                     * если после handshake уже пришёл frame,
                     * его нужно обработать прямо сейчас.
                     */
                } else {

                    /*
                     * =================================================
                     * WebSocket data
                     * =================================================
                     */

                    $client['buffer'] .= $chunk;
                }

                /*
                 * =====================================================
                 * 5. Разбираем ВСЕ полные WebSocket frames
                 * =====================================================
                 */

                if (!$client['ready']) {
                    continue;
                }

                while (
                    $client['buffer'] !== ''
                ) {
                    $frame =
                        $connection->receiveFrame(
                            $client['buffer']
                        );

                    /*
                     * Полного frame пока нет.
                     *
                     * Ждём следующий TCP chunk.
                     */
                    if ($frame === null) {
                        break;
                    }

                    $consumed =
                        $frame['consumed'] ?? 0;

                    if ($consumed <= 0) {
                        break;
                    }

                    /*
                     * Удаляем обработанный frame
                     * из TCP buffer.
                     */
                    $client['buffer'] =
                        substr(
                            $client['buffer'],
                            $consumed
                        );

                    $opcode =
                        $frame['opcode'] ?? 0x01;

                    /*
                     * =================================================
                     * Close
                     * =================================================
                     */

                    if ($opcode === 0x08) {
                        $this->info(
                            "WebSocket close received: {$id}"
                        );

                        $service->disconnect(
                            $connection
                        );

                        if (
                            is_resource($socket)
                        ) {
                            @fclose($socket);
                        }

                        unset(
                            $connections[$id]
                        );

                        break;
                    }

                    /*
                     * =================================================
                     * Ping
                     * =================================================
                     */

                    if ($opcode === 0x09) {
                        $connection->sendPong(
                            $frame['payload'] ?? ''
                        );

                        continue;
                    }

                    /*
                     * =================================================
                     * Pong
                     * =================================================
                     */

                    if ($opcode === 0x0A) {
                        continue;
                    }

                    /*
                     * =================================================
                     * Text
                     * =================================================
                     */

                    if ($opcode !== 0x01) {
                        continue;
                    }

                    $payload =
                        $frame['payload'] ?? '';

                    if ($payload === '') {
                        continue;
                    }

                    $this->line(
                        "[{$connection->type}] " .
                        "Payload: {$payload}"
                    );

                    try {
                        $service->handleMessage(
                            $connection,
                            $payload
                        );
                    } catch (\Throwable $e) {
                        $this->error(
                            $e->getMessage()
                        );

                        $this->error(
                            $e->getFile()
                        );

                        $this->error(
                            (string) $e->getLine()
                        );
                    }
                }
            }

            unset($client);
        }

        /*
         * Теоретически сюда код не должен попасть.
         */

        @fclose($serverSocket);

        return self::SUCCESS;
    }

    /**
     * Получить путь из первой строки
     * HTTP WebSocket handshake.
     */
    protected function getWebSocketPath(
        string $headers
    ): ?string {
        $lines = explode(
            "\r\n",
            $headers
        );

        if (empty($lines[0])) {
            return null;
        }

        if (
            !preg_match(
                '#^GET\s+(\S+)\s+HTTP/[\d.]+$#',
                $lines[0],
                $matches
            )
        ) {
            return null;
        }

        $path = parse_url(
            $matches[1],
            PHP_URL_PATH
        );

        return is_string($path)
            ? $path
            : null;
    }

    /**
     * Выполнить WebSocket handshake.
     */
    protected function performHandshake(
        $clientSocket,
        string $headers
    ): bool {
        if (
            preg_match(
                '/Sec-WebSocket-Key:\s*(.*?)\r\n/i',
                $headers,
                $matches
            ) !== 1
        ) {
            return false;
        }

        $key = trim(
            $matches[1]
        );

        $accept = base64_encode(
            sha1(
                $key .
                '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
                true
            )
        );

        $response =
            "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: {$accept}\r\n" .
            "\r\n";

        $written = @fwrite(
            $clientSocket,
            $response
        );

        @fflush(
            $clientSocket
        );

        return $written !== false;
    }

    /**
     * Отправить обычный HTTP error response.
     */
    protected function sendHttpError(
        $socket,
        int $status,
        string $message
    ): void {
        $body =
            $message .
            "\n";

        $response =
            "HTTP/1.1 {$status} {$message}\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Length: " .
            strlen($body) .
            "\r\n" .
            "Connection: close\r\n" .
            "\r\n" .
            $body;

        @fwrite(
            $socket,
            $response
        );

        @fflush(
            $socket
        );
    }
}