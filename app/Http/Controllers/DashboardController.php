<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;  
use App\Models\Workspace;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectInvite;
use App\Models\ActivityLog;
use App\Models\Task;
use Carbon\Carbon;

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

        $today = Carbon::today();

        $myFocusTasks = Task::query()
            ->with(['board.project', 'column'])
            ->where('assignee_id', Auth::id())
            ->whereHas('column', fn ($q) =>
                $q->whereRaw("LOWER(name) <> 'done'")
            )
            ->where(function ($q) use ($today) {
                $q->whereNull('due_date')
                ->orWhereDate('due_date', '<=', $today->copy()->addDays(3));
            })
            ->orderByRaw("
                CASE
                    WHEN due_date < ? THEN 1
                    WHEN due_date = ? THEN 2
                    WHEN due_date <= ? THEN 3
                    ELSE 4
                END
            ", [
                $today->toDateString(),
                $today->toDateString(),
                $today->copy()->addDays(3)->toDateString(),
            ])
            ->limit(5)
            ->get();    

        // ===== Analytics =====
        $today = Carbon::today();

        // งานทั้งหมดที่ user เกี่ยวข้อง
        $totalTasks = Task::where(function ($q) {
                $q->where('assignee_id', Auth::id())
                ->orWhere('created_by', Auth::id());
            })
            ->count();

        // งานที่เสร็จแล้ว
        $doneTasks = Task::where(function ($q) {
                $q->where('assignee_id', Auth::id())
                ->orWhere('created_by', Auth::id());
            })
            ->whereHas('column', fn ($q) =>
                $q->whereRaw("LOWER(name) = 'done'")
            )
            ->count();

        // งาน overdue
        $overdueTasks = Task::where('assignee_id', Auth::id())
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->whereHas('column', fn ($q) =>
                $q->whereRaw("LOWER(name) <> 'done'")
            )
            ->count();

        // งาน due today
        $dueTodayTasks = Task::where('assignee_id', Auth::id())
            ->whereDate('due_date', $today)
            ->whereHas('column', fn ($q) =>
                $q->whereRaw("LOWER(name) <> 'done'")
            )
            ->count();

        // progress %
        $progressPercent = $totalTasks > 0
            ? round(($doneTasks / $totalTasks) * 100)
            : 0;    

        return view('dashboard', compact(
            'myFocusTasks',
            'totalTasks',
            'doneTasks',
            'overdueTasks',
            'dueTodayTasks',
            'progressPercent',
            'workspacesCount',
            'pendingInvitesCount',
            'recentActivities',
            'recentProjects',
            'projectNameMap',
            'pendingInvites'
        ));
    }
}
