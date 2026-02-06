<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $maxPos = Task::where('column_id', $column->id)->max('position') ?? 0;

        $task = Task::create([
            'board_id'    => $column->board_id,
            'column_id'   => $column->id,
            'created_by'  => Auth::id(),
            'title'       => $request->title,
            'description' => $request->input('description'), // เผื่อไว้ (nullable)
            'priority'    => 'normal',
            'position'    => $maxPos + 1,
        ]);

        if (function_exists('log_activity')) {
            log_activity(
                'CREATE_TASK',
                "สร้างงาน \"{$task->title}\"",
                null,
                $projectId,
                Auth::id()
            );
        }

        return back();
    }

    public function move(Request $request, Task $task)
    {
        $data = $request->validate([
            'column_id' => 'required|exists:board_columns,id',
            'ordered_task_ids' => 'required|array',
            'ordered_task_ids.*' => 'integer',
        ]);

        $projectId = $this->projectIdFromTask($task);
        $this->ensureMemberOfProject($projectId);

        $fromColumnId = (int) $task->column_id;
        $fromColumnName = (string) \App\Models\BoardColumn::where('id', $fromColumnId)->value('name');
        $toColumnId = (int) $data['column_id'];
        $toColumnName = (string) \App\Models\BoardColumn::where('id', $toColumnId)->value('name');

        // กันย้ายข้ามโปรเจกต์ (toColumn ต้องอยู่ board/project เดียวกัน)
        $toProjectId = (int) \App\Models\BoardColumn::join('boards', 'boards.id', '=', 'board_columns.board_id')
            ->where('board_columns.id', $toColumnId)
            ->value('boards.project_id');

        abort_unless($toProjectId === $projectId, 403);

        \DB::transaction(function () use ($task, $data, $toColumnId) {

            if ((int)$task->column_id !== (int)$toColumnId) {
                $task->update(['column_id' => $toColumnId]);
            }

            foreach ($data['ordered_task_ids'] as $index => $id) {
                Task::where('id', (int)$id)
                    ->where('column_id', $toColumnId)
                    ->update(['position' => $index + 1]);
            }
        });

        // ✅ log
        if (function_exists('log_activity')) {
            $workspaceId = (int) \App\Models\Project::where('id', $projectId)->value('workspace_id');

            if ($fromColumnId === $toColumnId) {
                log_activity(
                    'REORDER_TASK',
                    "จัดลำดับงานใหม่ในคอลัมน์ \"{$fromColumnName}\"",
                    $workspaceId,
                    $projectId,
                    Auth::id()
                );
            } else {
                log_activity(
                    'MOVE_TASK',
                    "ย้ายงาน \"{$task->title}\" จากคอลัมน์ \"{$fromColumnName}\" ไปยังคอลัมน์ \"{$toColumnName}\"",
                    $workspaceId,
                    $projectId,
                    Auth::id()
                );
            }
        }

        return response()->json(['ok' => true]);
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

        // ===== 🔔 SOFT NOTIFICATION (ข้อ 4 ใส่ตรงนี้) =====
        if ($assigneeId && $assigneeId !== Auth::id()) {
            notify_user(
                $assigneeId,
                'ASSIGN_TASK',
                'คุณถูกมอบหมายงานใหม่',
                "งาน: \"{$task->title}\"",
                route('projects.board', $projectId),
                [
                    'task_id' => $task->id,
                    'project_id' => $projectId,
                ]
            );
        }
        // ===== END NOTIFICATION =====

        if (function_exists('log_activity')) {
            $task->loadMissing('assignee', 'board.project');

            $assigneeText = $task->assignee
                ? ($task->assignee->email ?? $task->assignee->name)
                : 'unassigned';

            $workspaceId = optional(optional($task->board)->project)->workspace_id;

            log_activity(
                'ASSIGN_TASK',
                "มอบหมายงาน \"{$task->title}\" ให้ {$assigneeText}",
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
                "ตั้งค่า Priority เป็น {$task->priority} สำหรับงาน \"{$task->title}\"",
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

        /**
         * 🔔 RESET overdue_notified_at
         * - ถ้าแก้ due_date เป็น "วันนี้หรืออนาคต" → เคลียร์ flag (ไม่ overdue)
         * - ถ้าแก้ due_date ใหม่ (แม้ยังเป็นอดีต) → เคลียร์ เพื่อให้แจ้งใหม่รอบถัดไป
         */
        $resetOverdueNoti = false;

        if ($request->has('due_date')) {
            if ($request->due_date) {
                $newDue = \Carbon\Carbon::parse($request->due_date)->startOfDay();

                // ไม่ overdue แล้ว หรือ user ตั้งวันใหม่ → reset flag
                if ($newDue->gte(today())) {
                    $resetOverdueNoti = true;
                } else {
                    // ยัง overdue แต่เป็น due_date ใหม่ → อนุญาตให้แจ้งใหม่
                    $resetOverdueNoti = true;
                }
            } else {
                // ลบ due_date ออก → reset flag
                $resetOverdueNoti = true;
            }
        }

        $task->update([
            'title'       => $request->title,
            'description' => $request->description,
            'due_date'    => $request->due_date,
            'overdue_notified_at' => $resetOverdueNoti ? null : $task->overdue_notified_at,
        ]);

        if (function_exists('log_activity')) {
            // ✅ กัน workspaceId เป็น null
            $task->loadMissing('board.project');

            log_activity(
                'UPDATE_TASK',
                "แก้ไขงาน \"{$task->title}\"",
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
                "ลบงาน \"{$title}\"",
                null,
                $projectId,
                Auth::id()
            );
        }

        return back()->with('success', 'ลบ Task แล้ว');
    }
}
