<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,email,role')->latest();

        // 1. Text Search (on activity or description)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('activity', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. User Filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // 3. Activity Type Filter
        if ($request->filled('activity_type')) {
            $type = $request->input('activity_type');
            if ($type === 'auth') {
                $query->whereIn('activity', ['Login', 'Logout', 'Register', 'Change Password']);
            } elseif ($type === 'created') {
                $query->where('activity', 'like', 'Created%');
            } elseif ($type === 'updated') {
                $query->where('activity', 'like', 'Updated%');
            } elseif ($type === 'deleted') {
                $query->where('activity', 'like', 'Deleted%');
            }
        }

        // 4. Date Range Filter
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }
        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        $logs = $query->paginate($request->input('per_page', 15));

        return response()->json($logs);
    }

    /**
     * Get summary statistics for the audit logs dashboard.
     */
    public function summary(): JsonResponse
    {
        $today = Carbon::today();
        
        $totalLogsToday = AuditLog::where('created_at', '>=', $today)->count();
        
        $activeUsersToday = AuditLog::where('created_at', '>=', $today)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
            
        $recentCrud = AuditLog::where('activity', 'not like', 'Login')
            ->where('activity', 'not like', 'Logout')
            ->latest()
            ->limit(5)
            ->get();
            
        return response()->json([
            'today_count' => $totalLogsToday,
            'active_users_today' => $activeUsersToday,
            'recent_crud' => $recentCrud,
        ]);
    }
}
