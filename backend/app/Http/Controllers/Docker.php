<?php

namespace App\Http\Controllers;

use App\Services\AgentWebSocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ServerSettings;
use App\Models\ServerAgent;
use Illuminate\Support\Facades\Cache;

class Docker extends Controller
{
    public function __construct(
        protected AgentWebSocketService $webSocketService
    ) {}

    public function getContainers(): JsonResponse
    {
        $agentSetting = ServerSettings::where(
            'settings',
            'Main_agent'
        )->first();

        $agentId = $agentSetting?->value[0] ?? null;

        if (!$agentId) {
            return response()->json([
                'error' => 'Main agent is not configured',
            ], 422);
        }

        $agent = ServerAgent::find($agentId);

        if (!$agent) {
            return response()->json([
                'error' => 'Main agent not found',
            ], 404);
        }

        $agentUuid = $agent->agent_id;

        $connection = $this->webSocketService
            ->findAgentConnection($agentUuid);

        if (!$connection) {
            return response()->json([
                'error' => 'Agent is not connected',
            ], 404);
        }

        $requestId = bin2hex(
            random_bytes(16)
        );

        $connection->send([
            'type' => 'docker:containers',
            'request_id' => $requestId,
            'payload' => [],
        ]);

        /*
        * Ждём ответ агента.
        */
        $cacheKey = "docker:containers:{$requestId}";

        $timeout = 10;

        $startedAt = microtime(true);

        while (
            microtime(true) - $startedAt < $timeout
        ) {
            $result = Cache::get($cacheKey);

            if ($result !== null) {
                Cache::forget($cacheKey);

                if (
                    ($result['status'] ?? null)
                    === 'error'
                ) {
                    return response()->json([
                        'error' =>
                            $result['payload']['error']
                            ?? 'Agent error',
                    ], 500);
                }

                return response()->json(
                    $result['payload']
                );
            }

            usleep(100000);
        }

        return response()->json([
            'error' => 'Agent response timeout',
            'request_id' => $requestId,
        ], 504);
    }
}