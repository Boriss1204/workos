<?php

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

if (!function_exists('log_activity')) {
    function log_activity($action, $details = null, $workspaceId = null, $projectId = null, $userId = null)
    {
        $uid = $userId ?? Auth::id();
        if (!$uid) return; // กันกรณีไม่มีคน login (เช่น tinker)

        ActivityLog::create([
            'workspace_id' => $workspaceId,
            'project_id' => $projectId,
            'user_id' => $uid,
            'action' => $action,
            'details' => $details,
        ]);
    }
}

