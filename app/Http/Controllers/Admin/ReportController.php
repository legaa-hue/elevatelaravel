<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportType = $request->get('type', 'users');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $data = $this->getReportData($reportType, $startDate, $endDate);

        return Inertia::render('Admin/Reports', [
            'reportType' => $reportType,
            'data' => $data,
            'filters' => [
                'type' => $reportType,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }

    public function fetch(Request $request)
    {
        $reportType = $request->get('type', 'users');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $data = $this->getReportData($reportType, $startDate, $endDate);

        return response()->json($data);
    }

    private function getReportData($type, $startDate = null, $endDate = null)
    {
        switch ($type) {
            case 'users':
                return $this->getUsersReport($startDate, $endDate);
            case 'courses':
                return $this->getCoursesReport($startDate, $endDate);
            case 'academic_year':
                return $this->getAcademicYearReport($startDate, $endDate);
            case 'audit_logs':
                return $this->getAuditLogsReport($startDate, $endDate);
            case 'performance':
                return $this->getPerformanceReport($startDate, $endDate);
            default:
                return $this->getUsersReport($startDate, $endDate);
        }
    }

    private function getUsersReport($startDate, $endDate)
    {
        $query = User::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $total = User::count();
        $admins = User::where('role', 'admin')->count();
        $teachers = User::where('role', 'teacher')->count();
        $students = User::where('role', 'student')->count();

        $users = $query->orderBy('created_at', 'desc')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'role' => ucfirst($user->role),
                'status' => ucfirst($user->status ?? 'active'),
                'created_at' => $user->created_at->format('M d, Y'),
            ];
        });

        // User registrations per month (SQLite compatible)
        $monthlyData = User::selectRaw("strftime('%Y-%m', created_at) as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        return [
            'summary' => [
                'total' => $total,
                'admins' => $admins,
                'teachers' => $teachers,
                'students' => $students,
            ],
            'records' => $users,
            'chartData' => [
                'labels' => $monthlyData->pluck('month')->map(fn($m) => Carbon::parse($m)->format('M Y')),
                'values' => $monthlyData->pluck('count'),
            ],
            'roleDistribution' => [
                ['role' => 'Admins', 'count' => $admins],
                ['role' => 'Teachers', 'count' => $teachers],
                ['role' => 'Students', 'count' => $students],
            ]
        ];
    }

    private function getCoursesReport($startDate, $endDate)
    {
        // Mock data for courses (implement when Course model exists)
        return [
            'summary' => [
                'total' => 0,
                'active' => 0,
                'pending' => 0,
                'archived' => 0,
            ],
            'records' => [],
            'chartData' => [
                'labels' => [],
                'values' => [],
            ],
            'statusDistribution' => [
                ['status' => 'Active', 'count' => 0],
                ['status' => 'Pending', 'count' => 0],
                ['status' => 'Archived', 'count' => 0],
            ]
        ];
    }

    private function getAcademicYearReport($startDate, $endDate)
    {
        $query = AcademicYear::with('uploader');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $total = AcademicYear::count();
        $active = AcademicYear::where('status', 'Active')->count();
        $inactive = AcademicYear::where('status', 'Inactive')->count();

        $years = $query->orderBy('created_at', 'desc')->get()->map(function ($year) {
            return [
                'id' => $year->id,
                'year_name' => $year->year_name,
                'version' => $year->version,
                'status' => $year->status,
                'uploaded_by' => $year->uploader ? $year->uploader->first_name . ' ' . $year->uploader->last_name : 'System',
                'created_at' => $year->created_at->format('M d, Y'),
            ];
        });

        return [
            'summary' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'versions' => AcademicYear::selectRaw('COUNT(DISTINCT year_name) as count')->first()->count ?? 0,
            ],
            'records' => $years,
            'chartData' => [
                'labels' => ['Active', 'Inactive'],
                'values' => [$active, $inactive],
            ],
            'statusDistribution' => [
                ['status' => 'Active', 'count' => $active],
                ['status' => 'Inactive', 'count' => $inactive],
            ]
        ];
    }

    private function getAuditLogsReport($startDate, $endDate)
    {
        $query = AuditLog::with('user');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $total = $query->count();
        $creates = AuditLog::where('action', 'create')->count();
        $updates = AuditLog::where('action', 'update')->count();
        $deletes = AuditLog::where('action', 'delete')->count();

        $logs = $query->orderBy('created_at', 'desc')->limit(100)->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'System',
                'action' => ucfirst($log->action),
                'module' => $log->model_type,
                'description' => $log->description,
                'created_at' => $log->created_at->format('M d, Y H:i'),
            ];
        });

        // Daily activity for last 7 days
        $dailyActivity = AuditLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return [
            'summary' => [
                'total' => $total,
                'creates' => $creates,
                'updates' => $updates,
                'deletes' => $deletes,
            ],
            'records' => $logs,
            'chartData' => [
                'labels' => $dailyActivity->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d')),
                'values' => $dailyActivity->pluck('count'),
            ],
            'actionDistribution' => [
                ['action' => 'Create', 'count' => $creates],
                ['action' => 'Update', 'count' => $updates],
                ['action' => 'Delete', 'count' => $deletes],
            ]
        ];
    }

    private function getPerformanceReport($startDate, $endDate)
    {
        // System performance metrics
        $totalUsers = User::count();
        $totalEvents = Event::count();
        $totalAcademicYears = AcademicYear::count();
        $totalAuditLogs = AuditLog::count();

        // Recent activity (last 30 days)
        $recentUsers = User::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $recentEvents = Event::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $recentLogs = AuditLog::where('created_at', '>=', Carbon::now()->subDays(30))->count();

        // Daily activity for last 14 days
        $dailyStats = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dailyStats[] = [
                'date' => Carbon::parse($date)->format('M d'),
                'users' => User::whereDate('created_at', $date)->count(),
                'events' => Event::whereDate('created_at', $date)->count(),
                'logs' => AuditLog::whereDate('created_at', $date)->count(),
            ];
        }

        return [
            'summary' => [
                'total_users' => $totalUsers,
                'total_events' => $totalEvents,
                'total_academic_years' => $totalAcademicYears,
                'total_audit_logs' => $totalAuditLogs,
            ],
            'records' => [
                ['metric' => 'New Users (30 days)', 'value' => $recentUsers, 'percentage' => $totalUsers > 0 ? round(($recentUsers / $totalUsers) * 100, 1) : 0],
                ['metric' => 'New Events (30 days)', 'value' => $recentEvents, 'percentage' => $totalEvents > 0 ? round(($recentEvents / $totalEvents) * 100, 1) : 0],
                ['metric' => 'Audit Logs (30 days)', 'value' => $recentLogs, 'percentage' => $totalAuditLogs > 0 ? round(($recentLogs / $totalAuditLogs) * 100, 1) : 0],
            ],
            'chartData' => [
                'labels' => collect($dailyStats)->pluck('date'),
                'datasets' => [
                    ['label' => 'Users', 'data' => collect($dailyStats)->pluck('users')],
                    ['label' => 'Events', 'data' => collect($dailyStats)->pluck('events')],
                    ['label' => 'Logs', 'data' => collect($dailyStats)->pluck('logs')],
                ]
            ],
            'dailyStats' => $dailyStats,
        ];
    }
}
