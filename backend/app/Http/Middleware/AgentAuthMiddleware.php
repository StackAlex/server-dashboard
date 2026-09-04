<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ServerAgent;

class AgentAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $payload = $message['payload'];
        $name = $payload["agent_name"];
        $token = $payload["token"] ?? null;

        if (!$token) {
            Log::info('Error', 'Missing Agent-Token');
            return response()->json(['error' => 'Missing Agent-Token'], 403);
        }
        if (!$name) {
            Log::info('Error', 'Missing Agent-Name');
            return response()->json(['error' => 'Missing Agent-Name'], 403);
        }

        $agent = ServerAgent::where('name', $name)->first();

        if (!Hash::check($payload['token'], $agent->token)) {
            Log::info('Error', 'No valide token');
            $connection->close();
            return null;
        }

        $request->attributes->add(['agent' => $agent]);

        return $next($request);
    }

}
