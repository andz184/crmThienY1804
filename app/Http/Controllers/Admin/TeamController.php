<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TeamController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the team structure.
     */
    public function structure()
    {
        $this->authorize('teams.view');
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Khởi tạo các biến
        $superAdmins = collect();
        $admins = collect();
        $leaders = collect();
        $staffByTeam = collect();
        $unassignedStaff = collect();

        // Quyết định dữ liệu cần lấy dựa trên role
        if ($currentUser->hasRole(['super-admin', 'admin'])) {
            // Admin/Super Admin: Lấy tất cả
            $superAdmins = User::whereHas('roles', fn($q) => $q->where('name', 'super-admin'))->get();
            $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
            $leaders = User::whereNotNull('manages_team_id')
                           ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
                           ->get();
            $staffByTeam = User::whereNotNull('team_id')
                              ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                              ->get()
                              ->groupBy('team_id');
            $unassignedStaff = User::whereNull('team_id')
                                  ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                                  ->get();
        } elseif ($currentUser->hasRole('manager') && $currentUser->manages_team_id) {
            // Manager: Chỉ lấy team của họ
            $leaders = collect([$currentUser]); // Chỉ hiển thị manager hiện tại
            $staffByTeam = User::where('team_id', $currentUser->manages_team_id)
                              ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                              ->get()
                              ->groupBy('team_id');
            // Manager không xem được admins, super admins, hay staff chưa gán (theo logic này)
        }
        // Các role khác sẽ thấy trang trắng vì các collection đều rỗng (hoặc bị chặn bởi authorize)

        return view('admin.teams.structure', compact('superAdmins', 'admins', 'leaders', 'staffByTeam', 'unassignedStaff'));
    }
}
