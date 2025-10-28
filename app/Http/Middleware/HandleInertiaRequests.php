<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Course;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $sharedData = [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
        ];

        // Add pending courses count for admin users
        if ($request->user() && $request->user()->role === 'admin') {
            $sharedData['pendingCoursesCount'] = Course::where('status', 'Pending')->count();
        }

        return $sharedData;
    }
}
