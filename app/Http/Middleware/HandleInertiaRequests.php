<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Course;
use App\Models\AcademicYear;

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
        $user = $request->user();
        
        $sharedData = [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile_picture' => $user->profile_picture,
                    'google_id' => $user->google_id,
                    'is_active' => $user->is_active,
                    'email_verified_at' => $user->email_verified_at,
                ] : null,
            ],
            'csrf_token' => csrf_token(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];

        // Add pending courses count for admin users
        if ($user && $user->role === 'admin') {
            $sharedData['pendingCoursesCount'] = Course::where('status', 'Pending')->count();
        }

        // Add active academic year for all authenticated users
        if ($user) {
            $activeAcademicYear = AcademicYear::where('status', 'Active')->first();
            $sharedData['activeAcademicYear'] = $activeAcademicYear ? [
                'id' => $activeAcademicYear->id,
                'year_name' => $activeAcademicYear->year_name,
            ] : null;
        }

        return $sharedData;
    }
}
