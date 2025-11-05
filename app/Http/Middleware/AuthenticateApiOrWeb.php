<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticateApiOrWeb
{
    /**
     * Handle an incoming request.
     * Authenticate using either JWT (api guard) or session (web guard)
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('AuthenticateApiOrWeb middleware executing', [
            'url' => $request->url(),
            'has_session' => $request->hasSession(),
            'web_check' => Auth::guard('web')->check(),
            'api_check' => Auth::guard('api')->check(),
            'cookies' => array_keys($request->cookies->all()),
            'has_bearer' => $request->bearerToken() !== null,
        ]);
        
        // Try web guard first (session-based)
        if (Auth::guard('web')->check()) {
            Log::info('Authenticated via web guard', ['user_id' => Auth::guard('web')->id()]);
            // Set the default guard to web so Auth::user() works
            Auth::shouldUse('web');
            return $next($request);
        }
        
        // Try api guard (JWT)
        if (Auth::guard('api')->check()) {
            Log::info('Authenticated via api guard', ['user_id' => Auth::guard('api')->id()]);
            // Set the default guard to api so Auth::user() works
            Auth::shouldUse('api');
            return $next($request);
        }
        
        Log::warning('Authentication failed - no valid guard');
        
        // Neither guard authenticated - return 401
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Please log in.',
        ], 401);
    }
}
