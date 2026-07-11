<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next, string $keyType = 'api-key'): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Missing API key'], 401);
        }

        $validKey = match ($keyType) {
            'webhook' => config('blog.webhook_secret'),
            default => config('blog.api_key'),
        };

        if (! $validKey || ! hash_equals($validKey, $token)) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        return $next($request);
    }
}
