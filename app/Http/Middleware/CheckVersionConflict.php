<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class CheckVersionConflict
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for PUT, PATCH, DELETE requests (updates)
        if (!in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Check if request has version number and resource info
        $clientVersion = $request->input('version') ?? $request->header('X-Resource-Version');
        $table = $request->input('resource_table') ?? $request->header('X-Resource-Table');
        $resourceId = $request->input('resource_id') ?? $request->route('id') ?? $request->route('course') ?? $request->route('classwork');

        // If no version info provided, skip conflict check
        if (!$clientVersion || !$table || !$resourceId) {
            return $next($request);
        }

        // Validate table name to prevent SQL injection
        $allowedTables = ['courses', 'classwork', 'classwork_submissions', 'events', 'programs'];
        if (!in_array($table, $allowedTables)) {
            return $next($request);
        }

        // Get current version from database
        try {
            $currentVersion = DB::table($table)
                ->where('id', $resourceId)
                ->value('version');

            // If resource doesn't exist, let controller handle it
            if ($currentVersion === null) {
                return $next($request);
            }

            // Check for version conflict
            if ((int)$clientVersion !== (int)$currentVersion) {
                // Get the current resource data to return
                $currentData = DB::table($table)
                    ->where('id', $resourceId)
                    ->first();

                return response()->json([
                    'error' => 'Conflict',
                    'message' => 'The resource has been modified by another user. Please refresh and try again.',
                    'conflict' => true,
                    'client_version' => (int)$clientVersion,
                    'server_version' => (int)$currentVersion,
                    'current_data' => $currentData
                ], 409);
            }
        } catch (\Exception $e) {
            // Log error but don't block the request
            \Log::warning('Version conflict check failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}
