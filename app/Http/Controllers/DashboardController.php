<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectInvite;
use App\Models\ActivityLog;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        /**
         * -----------------------------
         * สรุปจำนวนเวิร์กสเปซ
         * -----------------------------
         */
        $workspacesCount = Workspace::query()
            ->where('owner_user_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        /**
         * -----------------------------
         * คำเชิญที่รอดำเนินการ (จำนวน)
         * -----------------------------
         */
        $pendingInvitesCount = ProjectInvite::query()
            ->where('email', $user->email)
            ->where('status', 'pending')
            ->count();

        /**
         * -----------------------------
         * กิจกรรมล่าสุดของผู้ใช้
         * -----------------------------
         */
        $recentActivities = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        /**
         * -----------------------------
         * โปรเจกต์ล่าสุด (ที่ผู้ใช้เป็นสมาชิก)
         * -----------------------------
         */
        $projectIds = ProjectMember::where('user_id', $user->id)
            ->pluck('project_id');

        $recentProjects = Project::with('workspace')
            ->whereIn('id', $projectIds)
            ->latest()
            ->take(5)
            ->get();

        /**
         * -----------------------------
         * map project_id -> project_name
         * (ใช้แสดงใน activity log)
         * -----------------------------
         */
        $projectNameMap = Project::whereIn(
                'id',
                $recentActivities->pluck('project_id')->filter()->unique()
            )
            ->pluck('name', 'id');

        /**
         * -----------------------------
         * คำเชิญล่าสุด (แสดงรายการ)
         * -----------------------------
         */
        $pendingInvites = ProjectInvite::with('project')
            ->where('email', $user->email)
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'workspacesCount',
            'pendingInvitesCount',
            'recentActivities',
            'recentProjects',
            'projectNameMap',
            'pendingInvites'
        ));
    }
}
