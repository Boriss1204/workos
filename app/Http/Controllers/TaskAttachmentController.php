<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        $file = $request->file('file');

        // เก็บใน storage/app/public/task_attachments
        $path = $file->store('task_attachments', 'public');

        TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        if (function_exists('log_activity')) {
            log_activity(
                'ADD_ATTACHMENT',
                "Added attachment to task: {$task->title}",
                null,
                \App\Models\Board::find($task->board_id)->project_id,
                Auth::id()
            );
        }

        return redirect()->back();
    }

    public function destroy(TaskAttachment $attachment)
    {
        if ($attachment->user_id !== Auth::id()) {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->file_path);

        if (function_exists('log_activity')) {
            log_activity(
                'DELETE_ATTACHMENT',
                "Deleted attachment: {$attachment->original_name}",
                null,
                \App\Models\Board::find($attachment->task->board_id)->project_id,
                Auth::id()
            );
        }

        $attachment->delete();

        return redirect()->back();
    }
}
