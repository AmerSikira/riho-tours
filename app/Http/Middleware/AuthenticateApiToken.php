<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Validate API bearer token and authenticate user context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken() ?: (string) $request->header('X-Api-Token', '');
        if ($plainToken === '') {
            return response()->json([
                'message' => 'API token is required.',
            ], 401);
        }

        $hashedToken = hash('sha256', $plainToken);
        $user = User::query()
            ->where('api_token_hash', $hashedToken)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid API token.',
            ], 401);
        }

        $user->forceFill([
            'api_token_last_used_at' => now(),
        ])->save();

        $allowedDomains = collect($user->api_allowed_domains ?? [])
            ->map(static fn ($domain) => strtolower(trim((string) $domain)))
            ->filter(static fn ($domain) => $domain !== '')
            ->values();

        if ($allowedDomains->isEmpty()) {
            return response()->json([
                'message' => 'No allowed domains configured for this API key.',
            ], 403);
        }

        $candidate = strtolower(trim((string) $request->header('X-Plugin-Domain', '')));
        if ($candidate === '') {
            $origin = trim((string) $request->header('Origin', ''));
            if ($origin !== '') {
                $candidate = strtolower((string) parse_url($origin, PHP_URL_HOST));
            }
        }
        if ($candidate === '') {
            $referer = trim((string) $request->header('Referer', ''));
            if ($referer !== '') {
                $candidate = strtolower((string) parse_url($referer, PHP_URL_HOST));
            }
        }

        if ($candidate === '' || ! $allowedDomains->contains($candidate)) {
            return response()->json([
                'message' => 'Request domain is not allowed for this API key.',
            ], 403);
        }

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
