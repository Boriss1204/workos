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

        // ✅ สร้าง record แล้วเก็บลงตัวแปร $attachment (สำคัญมาก)
        $attachment = \App\Models\TaskAttachment::create([
            'task_id'        => $task->id,
            'user_id'        => auth()->id(),
            'file_path'      => $path,
            'original_name'  => $file->getClientOriginalName(),
            'mime_type'      => $file->getClientMimeType(),
            'file_size'      => $file->getSize(),
        ]);

        // ✅ LOG
        if (function_exists('log_activity')) {
            $attachment->loadMissing('task');

            $taskTitle = optional($attachment->task)->title ?? 'งาน';
            $board = \App\Models\Board::find(optional($attachment->task)->board_id);
            $projectId = optional($board)->project_id;

            log_activity(
                'ADD_ATTACHMENT',
                "แนบไฟล์ให้กับงาน \"{$taskTitle}\"",
                null,
                $projectId,
                auth()->id()
            );
        }

        return back()->with('success', 'อัปโหลดไฟล์เรียบร้อยแล้ว');
    }


    public function destroy(TaskAttachment $attachment)
    {
        if ($attachment->user_id !== Auth::id()) {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->file_path);

        if (function_exists('log_activity')) {
            $attachment->loadMissing('task');

            $taskTitle = optional($attachment->task)->title ?? 'งาน';
            $board = \App\Models\Board::find(optional($attachment->task)->board_id);

            log_activity(
                'DELETE_ATTACHMENT',
                "ลบไฟล์ \"{$attachment->original_name}\" ออกจากงาน \"{$taskTitle}\"",
                null,
                optional($board)->project_id,
                Auth::id()
            );
        }


        $attachment->delete();

        return redirect()->back();
    }
}
