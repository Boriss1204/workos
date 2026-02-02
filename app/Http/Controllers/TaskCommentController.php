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
            'comment' => 'required|string',
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        // Activity log (ถ้าคุณทำ log แล้ว)
        if (function_exists('log_activity')) {
            log_activity(
                'ADD_COMMENT',
                "Added comment on task: {$task->title}",
                null,
                \App\Models\Board::find($task->board_id)->project_id,
                Auth::id()
            );
        }

        return redirect()->back();
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
        log_activity(
            'UPDATE_COMMENT',
            'Updated a comment',
            null,
            \App\Models\Board::find($comment->task->board_id)->project_id,
            Auth::id()
        );
    }

    return redirect()->back();
}

    public function destroy(TaskComment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            abort(403);
        }

        if (function_exists('log_activity')) {
            log_activity(
                'DELETE_COMMENT',
                'Deleted a comment',
                null,
                \App\Models\Board::find($comment->task->board_id)->project_id,
                Auth::id()
            );
        }

        $comment->delete();

        return redirect()->back();
    }
}
