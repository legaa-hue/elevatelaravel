<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    /**
     * Display the user management page.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'status' => $user->status ?? 'active',
                'profile_picture' => $user->profile_picture,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'students' => User::where('role', 'student')->count(),
            'active' => User::count(),
            'inactive' => 0,
            'pending' => 0,
        ];

        return Inertia::render('Admin/UserManagement', [
            'users' => $users,
            'stats' => $stats,
            'filters' => [
                'role' => $request->role ?? 'all',
                'status' => $request->status ?? 'all',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'username' => 'nullable|string|max:255|unique:users,username',
                'role' => 'required|in:admin,teacher,student',
                'password' => 'required|string|min:8',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $validated['name'] = $validated['first_name'] . ' ' . $validated['last_name'];
            $validated['status'] = 'active'; // Set default status

            $user = User::create($validated);

            // Log the action
            $this->logAction('created', $user, "Created new user: {$user->name} ({$user->role})");

            return redirect()->back()->with('success', 'User created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
                'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
                'role' => 'required|in:admin,teacher,student',
                'password' => 'nullable|string|min:8',
            ]);

            $oldData = $user->toArray();

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $validated['name'] = $validated['first_name'] . ' ' . $validated['last_name'];

            $user->update($validated);

            // Log the action
            $this->logAction('updated', $user, "Updated user: {$user->name}", [
                'old' => $oldData,
                'new' => $user->fresh()->toArray(),
            ]);

            return redirect()->back()->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $userName = $user->name;
        $userId = $user->id;

        $user->delete();

        // Log the action
        $this->logAction('deleted', null, "Deleted user: {$userName} (ID: {$userId})", null, $userId);

        return redirect()->back();
    }

    /**
     * Log an action to audit logs.
     */
    private function logAction(string $action, $model = null, string $description, array $changes = null, int $modelId = null)
    {
        // If changes contain sensitive data, filter them out
        if ($changes && isset($changes['new'])) {
            $sensitiveFields = ['password', 'remember_token', 'email_verified_at'];
            foreach ($sensitiveFields as $field) {
                if (isset($changes['old'][$field])) {
                    unset($changes['old'][$field]);
                }
                if (isset($changes['new'][$field])) {
                    unset($changes['new'][$field]);
                }
            }
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? class_basename(get_class($model)) : 'Users',
            'model_id' => $model ? $model->id : $modelId,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => null, // Privacy: Don't store IP addresses
        ]);
    }
}

