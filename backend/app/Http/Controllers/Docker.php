<?php

namespace App\Http\Controllers;

use App\Services\AgentWebSocketService;
use Illuminate\Http\JsonResponse;

class Docker extends Controller
{
    public function __construct(
        protected AgentWebSocketService $webSocketService
    ) {}

    public function getContainers(string $agentUuid): JsonResponse
    {
        $requestId = bin2hex(random_bytes(16));

        $connection = $this->webSocketService
            ->findAgentConnection($agentUuid);

        if (!$connection) {
            return response()->json([
                'error' => 'Agent is not connected',
            ], 404);
        }

        $connection->send([
            'type' => 'docker:containers',
            'request_id' => $requestId,
            'payload' => [],
        ]);

        return response()->json([
            'request_id' => $requestId,
            'status' => 'sent',
        ]);
    }
}