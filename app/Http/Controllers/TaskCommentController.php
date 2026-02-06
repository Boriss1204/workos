<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskCommentController extends Controller
{
public function store(Request $request, Task $task)
{
    $request->validate([
        'comment' => 'required|string|max:2000',
    ]);

    $projectId = $task->board->project_id;

    // 1) สร้าง comment
    $comment = $task->comments()->create([
        'user_id' => auth()->id(),
        'comment' => $request->comment,
    ]);

    $commenterId = auth()->id();
    $commenterName = auth()->user()->name ?? 'Someone';
    $projectName = optional($task->board->project)->name ?? 'Project';

    // เก็บ user_id ที่แจ้งไปแล้ว (กันซ้ำ)
    $notifiedUserIds = collect();

    // ===== 🔔 NOTI: ASSIGNEE =====
    if ($task->assignee_id && $task->assignee_id !== $commenterId) {
        notify_user(
            $task->assignee_id,
            'COMMENT_TASK',
            '💬 มีคนคอมเมนต์งานของคุณ',
            "{$commenterName}: \"{$task->title}\" ({$projectName})",
            route('projects.board', $projectId),
            [
                'task_id' => $task->id,
                'comment_id' => $comment->id,
            ]
        );
        $notifiedUserIds->push($task->assignee_id);
    }

    // ===== 🔔 NOTI: CREATOR =====
    if (
        $task->created_by &&
        $task->created_by !== $commenterId &&
        !$notifiedUserIds->contains($task->created_by)
    ) {
        notify_user(
            $task->created_by,
            'COMMENT_TASK',
            '💬 มีคนคอมเมนต์งานที่คุณสร้าง',
            "{$commenterName}: \"{$task->title}\" ({$projectName})",
            route('projects.board', $projectId),
            [
                'task_id' => $task->id,
                'comment_id' => $comment->id,
            ]
        );
        $notifiedUserIds->push($task->created_by);
    }

    // ===== 🔔 NOTI: @MENTION =====
    preg_match_all('/@([\w\.\-@]+)/', $request->comment, $matches);

    if (!empty($matches[1])) {
        $mentions = collect($matches[1])->unique();

        foreach ($mentions as $mention) {

            // หา user จาก name หรือ email
            $user = \App\Models\User::query()
                ->where('name', $mention)
                ->orWhere('email', $mention)
                ->first();

            if (!$user) continue;

            // ไม่แจ้งตัวเอง
            if ($user->id === $commenterId) continue;

            // ไม่แจ้งซ้ำ
            if ($notifiedUserIds->contains($user->id)) continue;

            notify_user(
                $user->id,
                'MENTION_TASK',
                '👋 คุณถูก mention ในคอมเมนต์',
                "{$commenterName} mention คุณในงาน \"{$task->title}\" ({$projectName})",
                route('projects.board', $projectId),
                [
                    'task_id' => $task->id,
                    'comment_id' => $comment->id,
                ]
            );

            $notifiedUserIds->push($user->id);
        }
    }

    // ===== LOG =====
    if (function_exists('log_activity')) {
        log_activity(
            'COMMENT_TASK',
            "คอมเมนต์งาน \"{$task->title}\"",
            optional(optional($task->board)->project)->workspace_id,
            $projectId,
            $commenterId
        );
    }

    return back()->with('success', 'เพิ่มคอมเมนต์แล้ว');
}



    public function update(Request $request, TaskComment $comment)
{
    // อนุญาตเฉพาะเจ้าของคอมเมนต์
    if ($comment->user_id !== Auth::id()) {
        abort(403);
    }

    $request->validate([
        'comment' => 'required|string',
    ]);

    $comment->update([
        'comment' => $request->comment,
    ]);

    if (function_exists('log_activity')) {
        $comment->loadMissing('task'); // กัน task ยังไม่ถูกโหลด

        $taskTitle = optional($comment->task)->title ?? 'งาน';
        $board = \App\Models\Board::find(optional($comment->task)->board_id);

        log_activity(
            'UPDATE_COMMENT',
            "แก้ไขคอมเมนต์ในงาน \"{$taskTitle}\"",
            null,
            optional($board)->project_id,
            Auth::id()
        );
    }

    return redirect()->back();
}

    public function destroy(TaskComment $comment)
    {
        if (function_exists('log_activity')) {
            $comment->loadMissing('task');

            $taskTitle = optional($comment->task)->title ?? 'งาน';
            $board = \App\Models\Board::find(optional($comment->task)->board_id);

            log_activity(
                'DELETE_COMMENT',
                "ลบคอมเมนต์ในงาน \"{$taskTitle}\"",
                null,
                optional($board)->project_id,
                Auth::id()
            );
        }


        $comment->delete();

        return redirect()->back();
    }
}
