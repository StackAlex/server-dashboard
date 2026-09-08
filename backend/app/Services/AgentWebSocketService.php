<?php

namespace App\Services;

use App\Models\AgentMetric;
use Illuminate\Support\Facades\Log;
use App\Models\ServerAgent;
use Illuminate\Support\Facades\Hash;

class AgentWebSocketService
{
    /**
     * @var AgentSocketConnection[]
     */
    protected array $connections = [];

    public function registerConnection(AgentSocketConnection $connection): void
    {
        $this->connections[$connection->id] = $connection;

        Log::info("Agent connected", [
            'connection' => $connection->id,
        ]);
    }

    public function disconnect(AgentSocketConnection $connection): void
    {
        unset($this->connections[$connection->id]);

        Log::info("Agent disconnected", [
            'connection' => $connection->id,
        ]);

        $connection->close();
    }

    public function handleMessage(
        AgentSocketConnection $connection,
        string $payload
    ): void {
        $message = json_decode($payload, true);
        Log::info('Incoming message', $message);

        if (!is_array($message)) {
            return;
        }

        switch ($message['type'] ?? '') {

            case 'auth':
                $this->handleAuth($connection, $message);
                break;

            case 'stats':
                $this->handleStats($connection, $message);
                break;

            case 'heartbeat':
                break;

            default:
                Log::warning("Unknown message", $message);
        }
    }

    protected function handleAuth(AgentSocketConnection $connection, array $message): void
    {
        $payload = $message['payload'] ?? $message;
        $name = $payload['agent_name'] ?? null;
        $token = $payload['token'] ?? null;

        if (!$token) {
            Log::warning('Agent auth failed: missing token');
            $connection->close();
            return;
        }
        if (!$name) {
            Log::warning('Agent auth failed: missing agent name');
            $connection->close();
            return;
        }

        $agent = ServerAgent::where('name', $name)->first();

        if (!$agent || !$agent->enabled || !Hash::check($token, $agent->token)) {
            Log::warning('Agent auth failed', ['name' => $name]);
            $connection->close();
            return;
        }

        $connection->authenticated = true;
        $connection->agentUuid = $payload['agent_id'] ?? $agent->agent_id;

        if ($agent->agent_id !== $connection->agentUuid) {
            $agent->update(['agent_id' => $connection->agentUuid]);
        }

        $connection->send([
            'type' => 'auth_ok',
            'agent_id' => $connection->agentUuid,
        ]);
    }

    protected function handleStats(AgentSocketConnection $connection, array $message): void
    {
        if (!$connection->authenticated) {
            Log::warning('Ignoring stats from unauthenticated agent');
            return;
        }

        $payload = $message['payload'] ?? [];
        $stats = $payload['stats'] ?? null;
        $agentId = $payload['agent_id'] ?? $connection->agentUuid;

        if (!is_array($stats) || !$agentId) {
            return;
        }

        AgentMetric::updateOrCreate(
            ['agent_id' => $agentId],
            ['metrics' => $stats]
        );
    }
}