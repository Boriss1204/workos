<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
        public function project(Request $request, Project $project)
    {
        // (ถ้ามี auth check/member check ของคุณอยู่แล้ว ให้คงไว้)

        $action = $request->query('action');
        $userId = $request->query('user');
        $from   = $request->query('from');   // YYYY-MM-DD
        $to     = $request->query('to');     // YYYY-MM-DD
        $q      = $request->query('q');      // keyword

        $logs = ActivityLog::query()
            ->where('project_id', $project->id)
            ->when($action, fn ($qq) => $qq->where('action', $action))
            ->when($userId, fn ($qq) => $qq->where('user_id', $userId))
            ->when($from, fn ($qq) => $qq->whereDate('created_at', '>=', $from))
            ->when($to, fn ($qq) => $qq->whereDate('created_at', '<=', $to))
           ->when($q, function ($qq) use ($q) {
                $qq->where(function ($sub) use ($q) {
                    $sub->where('details', 'like', "%{$q}%")
                        ->orWhere('action', 'like', "%{$q}%");
                });
            })
            ->with('user')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // dropdown options
        $users = \App\Models\ProjectMember::with('user')
            ->where('project_id', $project->id)
            ->get()
            ->map(fn($pm) => $pm->user)
            ->filter()
            ->unique('id')
            ->values();

        $actions = ActivityLog::where('project_id', $project->id)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('activity.project', compact(
            'project',
            'logs',
            'users',
            'actions',
            'action',
            'userId',
            'from',
            'to',
            'q'
        ));
    }
}
