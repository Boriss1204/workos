<?php

namespace App\Http\Controllers;

use App\Models\BoardColumn;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function store(Request $request, BoardColumn $column)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            // ถ้าคุณทำ Step 7 เลือก priority ตอนสร้าง ให้เปิดบรรทัดนี้
            // 'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $task = Task::create([
            'board_id'    => $column->board_id,
            'column_id'   => $column->id,
            'created_by'  => Auth::id(),
            'title'       => $request->title,
            'priority'    => 'normal', // หรือ $request->priority ?? 'normal'
        ]);

        $projectId = \App\Models\Board::find($task->board_id)->project_id;

        if (function_exists('log_activity')) {
            log_activity(
                'CREATE_TASK',
                "Created task: {$task->title}",
                null,
                $projectId,
                Auth::id()
            );
        }

        return redirect()->back();
    }

    public function move(Request $request, Task $task)
    {
        $request->validate([
            'column_id' => 'required|exists:board_columns,id',
        ]);

        $task->update([
            'column_id' => $request->column_id,
        ]);

        $task->load(['column', 'board']); // ให้แน่ใจว่า relation มีค่า

        if (function_exists('log_activity')) {
            log_activity(
                'MOVE_TASK',
                "Moved task '{$task->title}' to column {$task->column->name}",
                null,
                $task->board->project_id,
                Auth::id()
            );
        }

        return redirect()->back();
    }

    public function assign(Request $request, Task $task)
    {
        $request->validate([
            'assignee_id' => 'nullable|integer',
        ]);

        $assigneeId = $request->assignee_id ?: null;

        // โหลดความสัมพันธ์ที่ต้องใช้
        $task->loadMissing('board');

        // กันกรณี task ไม่มี board (ข้อมูลเก่า/ผิดพลาด)
        if (!$task->board) {
            return back()->withErrors(['assignee_id' => 'Task นี้ไม่มี board ผูกอยู่']);
        }

        // ใช้ project_id จาก board โดยตรง (กัน project relation ไม่ทำงาน/ไม่ถูกโหลด)
        $projectId = (int) $task->board->project_id;

        // ตรวจว่า assignee เป็น member ของ project นี้จริง
        if (!empty($assigneeId)) {
            $isMember = \App\Models\ProjectMember::where('project_id', $projectId)
                ->where('user_id', (int) $assigneeId)
                ->exists();

            if (!$isMember) {
                return back()->withErrors(['assignee_id' => 'ผู้ใช้นี้ไม่ได้เป็นสมาชิกของโปรเจกต์']);
            }
        }

        // update
        $task->update([
            'assignee_id' => $assigneeId,
        ]);

        // log activity
        if (function_exists('log_activity')) {
            $task->loadMissing('assignee', 'board.project');

            $assigneeText = $task->assignee
                ? ($task->assignee->email ?? $task->assignee->name)
                : 'unassigned';

            $workspaceId = optional(optional($task->board)->project)->workspace_id;

            log_activity(
                'ASSIGN_TASK',
                "Assigned task '{$task->title}' to {$assigneeText}",
                $workspaceId,
                $projectId,
                auth()->id()
            );
        }

        return back()->with('success', 'อัปเดตผู้รับผิดชอบแล้ว');
    }

    public function priority(Request $request, Task $task)
    {
        $request->validate([
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        $task->update([
            'priority' => $request->priority,
        ]);

        if (function_exists('log_activity')) {
            $task->loadMissing('board.project');

            $projectId = (int) $task->board->project_id;
            $workspaceId = optional(optional($task->board)->project)->workspace_id;

            log_activity(
                'SET_PRIORITY',
                "Set priority '{$request->priority}' for task '{$task->title}'",
                $workspaceId,
                $projectId,
                auth()->id()
            );
        }

        return back()->with('success', 'อัปเดต Priority แล้ว');
    }
}
