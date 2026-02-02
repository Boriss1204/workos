<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;

class ActivityLogController extends Controller
{
    public function project(Project $project)
    {
        $logs = ActivityLog::with('user')
            ->where('project_id', $project->id)
            ->latest()
            ->take(100)
            ->get();

        return view('activity.project', compact('project', 'logs'));
    }
}
