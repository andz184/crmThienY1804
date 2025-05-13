<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Kiểm tra quyền xem logs
        if (!Gate::any(['logs.view_all', 'logs.view_own'])) {
            abort(403, 'Unauthorized action.');
        }

        $query = ActivityLog::with('user')->latest();

        // Nếu là staff, chỉ xem được logs của mình
        if (Gate::allows('logs.view_own') && !Gate::allows('logs.view_all')) {
            $query->where('user_id', Auth::id());
        }

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by user (chỉ cho phép admin và manager lọc theo user)
        if ($request->filled('user_id') && Gate::allows('logs.view_all')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by module
        if ($request->filled('model_type')) {
            $query->where('module', $request->model_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get unique values for filters
        $users = Gate::allows('logs.view_all') ? User::pluck('name', 'id') : collect();
        $actions = ActivityLog::distinct()->pluck('action');
        $models = ActivityLog::distinct()->pluck('module');

        $logs = $query->paginate(20);

        return view('logs.index', compact('logs', 'users', 'actions', 'models'));
    }

    public function show(ActivityLog $log)
    {
        // Kiểm tra quyền xem chi tiết log
        if (!Gate::any(['logs.view_all', 'logs.view_own'])) {
            abort(403, 'Unauthorized action.');
        }

        // Staff chỉ xem được chi tiết log của mình
        if (Gate::allows('logs.view_own') && !Gate::allows('logs.view_all') && $log->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('admin.logs.show', compact('log'));
    }
}
