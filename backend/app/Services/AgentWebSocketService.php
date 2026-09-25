<?php

namespace App\Services;

use App\Models\AgentMetric;
use App\Models\ServerAgent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AgentWebSocketService
{
    /**
     * @var AgentSocketConnection[]
     */
    protected array $connections = [];

    /**
     * session_id => terminal connection id
     *
     * @var array<string, string>
     */
    protected array $terminalSessions = [];

    public function registerConnection(AgentSocketConnection $connection): void
    {
        $this->connections[$connection->id] = $connection;

        Log::info('Agent connected', [
            'connection' => $connection->id,
            'type' => $connection->type,
        ]);
    }

    public function disconnect(AgentSocketConnection $connection): void
    {
        if ($connection->terminalSessionId) {
            unset(
                $this->terminalSessions[
                    $connection->terminalSessionId
                ]
            );
        }

        foreach ($this->terminalSessions as $sessionId => $connectionId) {
            if ($connectionId === $connection->id) {
                unset($this->terminalSessions[$sessionId]);
            }
        }

        unset($this->connections[$connection->id]);

        Log::info('Agent disconnected', [
            'connection' => $connection->id,
            'type' => $connection->type,
        ]);

        $connection->close();
    }

    public function handleMessage(
        AgentSocketConnection $connection,
        string $payload
    ): void {
        $message = json_decode($payload, true);

        Log::info(
            "[{$connection->type}] Payload: " . $payload
        );

        if (!is_array($message)) {
            return;
        }

        switch ($message['type'] ?? '') {

            case 'auth':
                $this->handleAuth(
                    $connection,
                    $message['payload'] ?? []
                );
                break;

            case 'stats':
                $this->handleStats($connection, $message);
                break;

            case 'heartbeat':
                break;

            /*
             * Browser -> Agent
             */
            case 'terminal:open':
                $this->handleTerminalOpen($connection, $message);
                break;

            case 'terminal:input':
                $this->handleTerminalInput($connection, $message);
                break;

            case 'terminal:resize':
                $this->handleTerminalResize($connection, $message);
                break;

            case 'terminal:close':
                $this->handleTerminalClose($connection, $message);
                break;

            /*
             * Agent -> Browser
             */
            case 'terminal:opened':
                $this->handleTerminalOpened($connection, $message);
                break;

            case 'terminal:output':
                $this->handleTerminalOutput($connection, $message);
                break;

            case 'terminal:exit':
                $this->handleTerminalExit($connection, $message);
                break;

            default:
                Log::warning(
                    'Unknown message',
                    $message
                );
        }
    }

    protected function handleAuth(
        AgentSocketConnection $connection,
        array $payload
    ): void {
        $agentUuid = $payload['agent_id'] ?? null;
        $token = $payload['token'] ?? null;

        if (!$agentUuid || !$token) {
            $connection->send([
                'type' => 'auth:error',
                'payload' => [
                    'message' => 'Missing agent credentials',
                ],
            ]);

            $connection->close();

            return;
        }

        $agent = ServerAgent::where('agent_id', $agentUuid)->first();

        if (!$agent) {
            $connection->send([
                'type' => 'auth:error',
                'payload' => [
                    'message' => 'Agent not found',
                ],
            ]);

            $connection->close();

            return;
        }

        if (!Hash::check($token, $agent->token)) {
            $connection->send([
                'type' => 'auth:error',
                'payload' => [
                    'message' => 'Invalid token',
                ],
            ]);

            $connection->close();

            return;
        }

        $connection->authenticated = true;
        $connection->agentUuid = $agentUuid;

        $this->replaceAgentConnection(
            $agentUuid,
            $connection
        );

        $connection->send([
            'type' => 'auth:success',
            'payload' => [
                'agent_id' => $agentUuid,
            ],
        ]);

        Log::info('[Agent] Authenticated', [
            'agent_id' => $agentUuid,
            'connection' => $connection->id,
        ]);
    }
    public function replaceAgentConnection(
        string $agentUuid,
        AgentSocketConnection $newConnection
    ): void {
        foreach ($this->connections as $connection) {
            if (
                $connection->agentUuid === $agentUuid
                && $connection !== $newConnection
            ) {
                Log::info('[Agent] Replacing old connection', [
                    'agent_uuid' => $agentUuid,
                    'old_connection' => $connection->id,
                    'new_connection' => $newConnection->id,
                ]);

                $connection->send([
                    'type' => 'connection:replaced',
                    'payload' => [
                        'message' => 'Connection replaced by a new connection',
                    ],
                ]);

                $connection->close();

                unset($this->connections[$connection->id]);
            }
        }

        $this->registerConnection($newConnection);
    }
    protected function handleStats(
        AgentSocketConnection $connection,
        array $message
    ): void {
        if (!$connection->authenticated) {
            Log::warning(
                'Ignoring stats from unauthenticated agent'
            );

            return;
        }

        $payload = $message['payload'] ?? [];

        $stats = $payload['stats'] ?? null;

        $agentId =
            $payload['agent_id'] ??
            $connection->agentUuid;

        if (
            !is_array($stats) ||
            !$agentId
        ) {
            return;
        }

        AgentMetric::updateOrCreate(
            ['agent_id' => $agentId],
            ['metrics' => $stats]
        );
    }

    /*
     * ============================================================
     * Browser -> Agent
     * ============================================================
     */

    protected function handleTerminalOpen(
        AgentSocketConnection $terminal,
        array $message
    ): void {
        if ($terminal->type !== 'terminal') {
            Log::warning(
                'terminal:open received from non-terminal connection'
            );

            return;
        }

        $payload = $message['payload'] ?? [];

        $agentId = $payload['agent_id'] ?? null;

        if (!$agentId) {
            Log::warning(
                'terminal:open missing agent_id'
            );

            return;
        }

        $agent = $this->findAgentConnection(
            $agentId
        );
        Log::info('[Terminal] Agent lookup', [
            'agent_id' => $agentId,
            'found' => $agent !== null,
            'connection_id' => $agent?->id,
            'authenticated' => $agent?->authenticated,
            'agent_uuid' => $agent?->agentUuid,
        ]);
        if (!$agent) {
            Log::warning(
                'Terminal target agent not connected',
                [
                    'agent_id' => $agentId,
                ]
            );

            $terminal->send([
                'type' => 'terminal:error',
                'payload' => [
                    'message' =>
                        'Agent is not connected',
                ],
            ]);

            return;
        }

        $terminal->targetAgentUuid = $agentId;

        $agent->send([
            'type' => 'terminal:open',
            'request_id' =>
                $message['request_id'] ?? null,
            'payload' => [
                'cols' =>
                    (int) ($payload['cols'] ?? 80),

                'rows' =>
                    (int) ($payload['rows'] ?? 24),
            ],
        ]);

        Log::info(
            'Terminal open forwarded to agent',
            [
                'agent_id' => $agentId,
                'terminal_connection' =>
                    $terminal->id,
                'agent_connection' =>
                    $agent->id,
            ]
        );
    }

    protected function handleTerminalInput(
        AgentSocketConnection $terminal,
        array $message
    ): void {
        $agent = $this->getAgentForTerminal(
            $terminal,
            $message
        );

        if (!$agent) {
            return;
        }

        $payload = $message['payload'] ?? [];

        $agent->send([
            'type' => 'terminal:input',
            'request_id' =>
                $message['request_id'] ?? null,
            'payload' => [
                'session_id' =>
                    $payload['session_id'] ?? null,

                'data' =>
                    $payload['data'] ?? '',
            ],
        ]);
    }

    protected function handleTerminalResize(
        AgentSocketConnection $terminal,
        array $message
    ): void {
        $agent = $this->getAgentForTerminal(
            $terminal,
            $message
        );

        if (!$agent) {
            return;
        }

        $payload = $message['payload'] ?? [];

        $agent->send([
            'type' => 'terminal:resize',
            'request_id' =>
                $message['request_id'] ?? null,
            'payload' => [
                'session_id' =>
                    $payload['session_id'] ?? null,

                'cols' =>
                    (int) ($payload['cols'] ?? 80),

                'rows' =>
                    (int) ($payload['rows'] ?? 24),
            ],
        ]);
    }

    protected function handleTerminalClose(
        AgentSocketConnection $terminal,
        array $message
    ): void {
        $agent = $this->getAgentForTerminal(
            $terminal,
            $message
        );

        if (!$agent) {
            return;
        }

        $payload = $message['payload'] ?? [];

        $agent->send([
            'type' => 'terminal:close',
            'request_id' =>
                $message['request_id'] ?? null,
            'payload' => [
                'session_id' =>
                    $payload['session_id'] ?? null,

                'reason' =>
                    $payload['reason'] ?? '',
            ],
        ]);
    }

    /*
     * ============================================================
     * Agent -> Browser
     * ============================================================
     */

    protected function handleTerminalOpened(
        AgentSocketConnection $agent,
        array $message
    ): void {
        if ($agent->type !== 'agent') {
            return;
        }

        if (!$agent->authenticated) {
            return;
        }

        $payload = $message['payload'] ?? [];

        $sessionId =
            $payload['session_id'] ?? null;

        if (!$sessionId) {
            Log::warning(
                'terminal:opened missing session_id'
            );

            return;
        }

        $terminal =
            $this->findTerminalForAgent(
                $agent
            );

        if (!$terminal) {
            Log::warning(
                'No terminal connection for agent',
                [
                    'agent_id' =>
                        $agent->agentUuid,
                    'session_id' =>
                        $sessionId,
                ]
            );

            return;
        }

        $terminal->terminalSessionId =
            $sessionId;

        $this->terminalSessions[$sessionId] =
            $terminal->id;

        $terminal->send([
            'type' => 'terminal:opened',
            'request_id' =>
                $message['request_id'] ?? null,
            'payload' => [
                'session_id' => $sessionId,
            ],
        ]);

        Log::info(
            'Terminal session opened',
            [
                'session_id' => $sessionId,
                'agent_id' =>
                    $agent->agentUuid,
                'terminal_connection' =>
                    $terminal->id,
            ]
        );
    }

    protected function handleTerminalOutput(
        AgentSocketConnection $agent,
        array $message
    ): void {
        $payload = $message['payload'] ?? [];

        $sessionId = $payload['session_id'] ?? null;
        $data = $payload['data'] ?? '';

        $terminal = $this->findTerminalBySession($message);

        if (!$terminal) {
            Log::warning(
                '[terminal] Browser connection not found',
                [
                    'session_id' => $sessionId,
                ]
            );

            return;
        }

        Log::info(
            '[terminal] Sending output to browser',
            [
                'connection' => $terminal->id,
                'session_id' => $sessionId,
                'data_length' => strlen($data),
                'data' => $data,
            ]
        );

        $result = $terminal->send([
            'type' => 'terminal:output',
            'payload' => $payload,
        ]);

        Log::info(
            '[terminal] Browser send result',
            [
                'connection' => $terminal->id,
                'result' => $result,
            ]
        );
    }

    protected function handleTerminalExit(
        AgentSocketConnection $agent,
        array $message
    ): void {
        $payload = $message['payload'] ?? [];

        $sessionId =
            $payload['session_id'] ?? null;

        $terminal =
            $this->findTerminalBySession(
                $message
            );

        if (!$terminal) {
            return;
        }

        $terminal->send([
            'type' => 'terminal:exit',
            'payload' => $payload,
        ]);

        if ($sessionId) {
            unset(
                $this->terminalSessions[$sessionId]
            );

            if (
                $terminal->terminalSessionId ===
                $sessionId
            ) {
                $terminal->terminalSessionId = null;
            }
        }
    }

    /*
     * ============================================================
     * Helpers
     * ============================================================
     */

    protected function findAgentConnection(
        string $agentId
    ): ?AgentSocketConnection {
        foreach ($this->connections as $connection) {
            if (
                $connection->type === 'agent' &&
                $connection->authenticated &&
                $connection->agentUuid === $agentId &&
                $connection->isOpen()
            ) {
                return $connection;
            }
        }

        return null;
    }

    protected function getAgentForTerminal(
        AgentSocketConnection $terminal,
        array $message
    ): ?AgentSocketConnection {
        $payload = $message['payload'] ?? [];

        $sessionId =
            $payload['session_id'] ?? null;

        if (!$sessionId) {
            Log::warning(
                'Terminal message missing session_id'
            );

            return null;
        }

        $connectionId =
            $this->terminalSessions[$sessionId]
            ?? null;

        if (!$connectionId) {
            Log::warning(
                'Unknown terminal session',
                [
                    'session_id' =>
                        $sessionId,
                ]
            );

            return null;
        }

        if (
            $connectionId !==
            $terminal->id
        ) {
            Log::warning(
                'Terminal session ownership mismatch'
            );

            return null;
        }

        $agentId =
            $terminal->targetAgentUuid;

        if (!$agentId) {
            return null;
        }

        return $this->findAgentConnection(
            $agentId
        );
    }

    protected function findTerminalForAgent(
        AgentSocketConnection $agent
    ): ?AgentSocketConnection {
        foreach ($this->connections as $connection) {
            if (
                $connection->type === 'terminal' &&
                $connection->targetAgentUuid ===
                    $agent->agentUuid &&
                $connection->isOpen()
            ) {
                return $connection;
            }
        }

        return null;
    }

    protected function findTerminalBySession(
        array $message
    ): ?AgentSocketConnection {
        $payload = $message['payload'] ?? [];

        $sessionId =
            $payload['session_id'] ?? null;

        if (!$sessionId) {
            return null;
        }

        $connectionId =
            $this->terminalSessions[$sessionId]
            ?? null;

        if (!$connectionId) {
            Log::warning(
                'Unknown terminal session',
                [
                    'session_id' =>
                        $sessionId,
                ]
            );

            return null;
        }

        $terminal =
            $this->connections[$connectionId]
            ?? null;

        if (
            !$terminal ||
            $terminal->type !== 'terminal'
        ) {
            return null;
        }

        return $terminal;
    }
}