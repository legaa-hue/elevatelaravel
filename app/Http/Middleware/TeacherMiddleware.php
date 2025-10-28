<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === 'teacher') {
            // Share active academic year with all teacher pages
            $activeYear = \App\Models\AcademicYear::where('status', 'Active')->first();
            \Inertia\Inertia::share('activeAcademicYear', $activeYear);
            
            return $next($request);
        }

        abort(403, 'Unauthorized. Teacher access only.');
    }
}
