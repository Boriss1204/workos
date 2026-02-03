<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\ProjectMember;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * helper: หา project_id จาก task (กัน relation ไม่โหลด/ข้อมูลเก่า)
     */
    private function projectIdFromTask(Task $task): int
    {
        $task->loadMissing('board');

        if (!$task->board) {
            abort(404, 'Task ไม่มี board ผูกอยู่');
        }

        return (int) $task->board->project_id;
    }

    /**
     * helper: เช็คว่า user ปัจจุบันเป็นสมาชิกโปรเจกต์นั้นจริง
     */
    private function ensureMemberOfProject(int $projectId): void
    {
        $ok = ProjectMember::where('project_id', $projectId)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$ok) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงโปรเจกต์นี้');
        }
    }

    public function store(Request $request, BoardColumn $column)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // หา project_id จาก board ของ column นี้
        $projectId = (int) Board::where('id', $column->board_id)->value('project_id');
        $this->ensureMemberOfProject($projectId);

        $task = Task::create([
            'board_id'    => $column->board_id,
            'column_id'   => $column->id,
            'created_by'  => Auth::id(),
            'title'       => $request->title,
            'description' => $request->input('description'), // เผื่อไว้ (nullable)
            'priority'    => 'normal',
        ]);

        if (function_exists('log_activity')) {
            log_activity(
                'CREATE_TASK',
                "Created task: {$task->title}",
                null,
                $projectId,
                Auth::id()
            );
        }

        return back();
    }

    public function move(Request $request, Task $task)
    {
        $request->validate([
            'column_id' => 'required|exists:board_columns,id',
        ]);

        $projectId = $this->projectIdFromTask($task);
        $this->ensureMemberOfProject($projectId);

        $task->update([
            'column_id' => $request->column_id,
        ]);

        $task->load(['column', 'board']);

        if (function_exists('log_activity')) {
            log_activity(
                'MOVE_TASK',
                "Moved task '{$task->title}' to column {$task->column->name}",
                null,
                $task->board->project_id,
                Auth::id()
            );
        }

        return back();
    }

    public function assign(Request $request, Task $task)
    {
        $request->validate([
            'assignee_id' => 'nullable|integer',
        ]);

        $projectId = $this->projectIdFromTask($task);
        $this->ensureMemberOfProject($projectId);

        $assigneeId = $request->assignee_id ? (int) $request->assignee_id : null;

        // ถ้าเลือก assignee ต้องเป็น member ของ project นี้จริง
        if (!is_null($assigneeId)) {
            $isMember = ProjectMember::where('project_id', $projectId)
                ->where('user_id', $assigneeId)
                ->exists();

            if (!$isMember) {
                return back()->withErrors(['assignee_id' => 'ผู้ใช้นี้ไม่ได้เป็นสมาชิกของโปรเจกต์']);
            }
        }

        $task->update([
            'assignee_id' => $assigneeId,
        ]);

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
                Auth::id()
            );
        }

        return back()->with('success', 'อัปเดตผู้รับผิดชอบแล้ว');
    }

    public function priority(Request $request, Task $task)
    {
        $request->validate([
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        $projectId = $this->projectIdFromTask($task);
        $this->ensureMemberOfProject($projectId);

        $task->update([
            'priority' => $request->priority,
        ]);

        if (function_exists('log_activity')) {
            $task->loadMissing('board.project');

            $workspaceId = optional(optional($task->board)->project)->workspace_id;

            log_activity(
                'SET_PRIORITY',
                "Set priority '{$task->priority}' for task '{$task->title}'",
                $workspaceId,
                $projectId,
                Auth::id()
            );
        }

        return back()->with('success', 'อัปเดต Priority แล้ว');
    }

    /**
     * CRUD: update title/description/due_date
     */
    public function update(Request $request, Task $task)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
        ]);

        $projectId = $this->projectIdFromTask($task);
        $this->ensureMemberOfProject($projectId);

        $task->update([
            'title'       => $request->title,
            'description' => $request->description,
            'due_date'    => $request->due_date,
        ]);

        if (function_exists('log_activity')) {
            log_activity(
                'UPDATE_TASK',
                "Updated task '{$task->title}'",
                optional(optional($task->board)->project)->workspace_id,
                $projectId,
                Auth::id()
            );
        }

        return back()->with('success', 'แก้ไข Task แล้ว');
    }

    public function destroy(Task $task)
    {
        $projectId = $this->projectIdFromTask($task);
        $this->ensureMemberOfProject($projectId);

        $title = $task->title;
        $task->delete();

        if (function_exists('log_activity')) {
            log_activity(
                'DELETE_TASK',
                "Deleted task '{$title}'",
                null,
                $projectId,
                Auth::id()
            );
        }

        return back()->with('success', 'ลบ Task แล้ว');
    }
}
