<?php

namespace App\Http\Controllers;

use App\Services\AgentWebSocketService;
use App\Models\AgentMetric;
use Illuminate\Http\Request;
use App\Models\ServerAgent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    public function index()
    {
        $agent = ServerAgent::where('user_id', Auth::id())->first();

        if (!$agent) {
            return response()->json([
                'message' => 'No agents found'
            ], 404);
        }

        $metrics = AgentMetric::where('agent_id', $agent->agent_id)->first();
        $stats = $metrics?->metrics ?? [];

        return response()->json([
            ...$stats,
            'agent_id' => $agent->agent_id,
            'name' => $agent->name,
            'status' => $metrics ? 'online' : 'offline',
            'last_seen' => $metrics?->updated_at,
        ]);
    }
    
    public function heartbeat(Request $request) 
    {
        
    }
    

    public function getAll(Request $request)
    {
        $agents = ServerAgent::all();

        return response()->json(['allAgents' => $agents]);
    }

    public function saveAgent(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'required|string',
        ]);

        $agentId = (string) \Illuminate\Support\Str::uuid();

        ServerAgent::create([
            'user_id'  => Auth::id(),
            'agent_id' => $agentId,
            'name'     => $data['name'],
            'token'    => Hash::make($data['token']),
            'enabled'  => true,
        ]);

        return response()->json([
            'message' => 'Server Agent Created',
            'agent_id' => $agentId,
        ]);
    }

    public function deleteAgent(Request $request){
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        ServerAgent::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }

    public function handle(Request $request, Closure $next)
    {
        // Приоритет: заголовок, потом query, потом тело
        $token = $request->input('panel.token');

        $uuid = $request->input('agent.uuid');

        if (!$token || !$uuid) {
            return response()->json(['error' => 'Missing token or UUID'], 401);
        }

        $agent = \App\Models\ServerAgent::where('uuid', $uuid)
            ->where('token', $token)
            ->first();

        if (!$agent || !$agent->is_active) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $agent->update(['last_seen_at' => now()]);
        $request->merge(['agent' => $agent]);

        return $next($request);
    }


    public function dispatchCommand(Request $request, AgentWebSocketService $service)
    {
        $payload = $request->validate([
            'agent_id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'payload' => ['nullable', 'array'],
        ]);

        $service->sendCommand($payload['agent_id'], [
            'type' => $payload['type'],
            'payload' => $payload['payload'] ?? [],
        ]);

        return response()->json(['ok' => true]);
    }
}
