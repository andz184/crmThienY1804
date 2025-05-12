<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogController extends Controller
{
    public function index(Request $request)
    {


        $query = Log::with('user')->latest();

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if ($authUser->hasRole('manager')) {
            $teamMemberIds = User::where('team_id', $authUser->manages_team_id)->pluck('id');
            $userIds = $teamMemberIds->push($authUser->id)->unique();
            $query->whereIn('user_id', $userIds);
        } elseif ($authUser->hasRole('staff')) {
            $query->where('user_id', $authUser->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhere('model_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('model_type')) {
            $query->where('model_type', 'App\\Models\\' . $request->model_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();
        $users = User::pluck('name', 'id');
        $actions = Log::distinct()->pluck('action');
        $models = Log::distinct()->pluck('model_type')->map(fn($m) => class_basename($m));

        return view('logs.index', compact('logs', 'users', 'actions', 'models'));
    }

    public function show(Log $log)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $canView = false;
        if ($authUser->hasRole('super-admin') || $authUser->hasRole('admin')) {
            $canView = true;
        } elseif ($authUser->hasRole('manager')) {
            $teamMemberIds = User::where('team_id', $authUser->manages_team_id)->pluck('id');
            $userIds = $teamMemberIds->push($authUser->id)->unique();
            if ($userIds->contains($log->user_id)) {
                $canView = true;
            }
        } elseif ($authUser->hasRole('staff')) {
            if ($log->user_id == $authUser->id) {
                $canView = true;
            }
        }

        if (!$canView) {
            abort(403, 'Unauthorized to view this log entry.');
        }

        $log->load('user');
        return view('logs.show', compact('log'));
    }

    public function modelLogs($modelType, $modelId)
    {
        $modelClass = "App\\Models\\" . ucfirst($modelType);
        if (!class_exists($modelClass)) {
            abort(404, 'Model type not found.');
        }

        $model = $modelClass::findOrFail($modelId);

        $query = Log::where('model_type', $modelClass)
                    ->where('model_id', $modelId)
                    ->with('user')
                    ->latest();

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if ($authUser->hasRole('manager')) {
            $teamMemberIds = User::where('team_id', $authUser->manages_team_id)->pluck('id');
            $userIds = $teamMemberIds->push($authUser->id)->unique();
            $query->whereIn('user_id', $userIds);
        } elseif ($authUser->hasRole('staff')) {
            $query->where('user_id', $authUser->id);
        }

        $logs = $query->paginate(10);

        return view('logs._model_log_list', compact('logs', 'model'));
    }
}
