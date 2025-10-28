<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Action filter
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Role filter
        if ($request->filled('role')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        // Module filter
        if ($request->filled('module') && $request->module !== 'All') {
            $module = $request->module;
            
            // Handle different module name formats
            $query->where(function ($q) use ($module) {
                // Direct match (e.g., "Calendar", "Users")
                $q->where('model_type', $module);
                
                // Match class names ending with module name (e.g., "App\Models\User" for "Users")
                // Remove trailing 's' for singular class names
                $singular = rtrim($module, 's');
                $q->orWhere('model_type', 'like', "%\\{$singular}");
                
                // Also match if it's the exact class basename
                $q->orWhere('model_type', $singular);
            });
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Get paginated logs
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($log) {
                // Normalize model type to display name and map to module
                $modelType = $log->model_type;
                if (str_contains($modelType, '\\')) {
                    $modelType = class_basename($modelType);
                }
                
                // Map model types to module categories
                $module = $this->mapModelTypeToModule($modelType);
                
                return [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'System',
                    'user_role' => $log->user ? ucfirst($log->user->role) : 'System',
                    'action' => $log->action,
                    'model_type' => $modelType,
                    'module' => $module,
                    'model_id' => $log->model_id,
                    'description' => $log->description,
                    'changes' => $log->changes,
                    'created_at' => $log->created_at->toISOString(),
                ];
            });

        // Calculate stats
        $today = Carbon::today();
        
        $stats = [
            'today_actions' => AuditLog::whereDate('created_at', $today)->count(),
            'today_logins' => AuditLog::whereDate('created_at', $today)
                ->where('action', 'login')
                ->count(),
            'today_deletions' => AuditLog::whereDate('created_at', $today)
                ->where('action', 'delete')
                ->count(),
            'most_active_user' => $this->getMostActiveUser($today),
        ];

        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs,
            'stats' => $stats,
            'currentFilters' => [
                'search' => $request->search,
                'action' => $request->action,
                'role' => $request->role,
                'module' => $request->module,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);
    }

    private function getMostActiveUser($date)
    {
        $mostActive = AuditLog::with('user')
            ->whereDate('created_at', $date)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as action_count')
            ->groupBy('user_id')
            ->orderBy('action_count', 'desc')
            ->first();

        if ($mostActive && $mostActive->user) {
            return $mostActive->user->first_name . ' ' . $mostActive->user->last_name;
        }

        return null;
    }

    private function mapModelTypeToModule($modelType)
    {
        // Map model types to module categories
        $mapping = [
            'User' => 'Users',
            'Calendar' => 'Calendar',
            'Event' => 'Calendar',
            'Course' => 'Courses',
            'AcademicYear' => 'Academic Year',
        ];

        return $mapping[$modelType] ?? 'Users';
    }
}
