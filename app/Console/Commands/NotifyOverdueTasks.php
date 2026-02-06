<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Task;

class NotifyOverdueTasks extends Command
{
    protected $signature = 'tasks:notify-overdue';
    protected $description = 'Send soft notifications for overdue tasks (once per overdue state)';

    public function handle(): int
    {
        // หา task ที่ overdue (due_date < วันนี้), ยังไม่เคยแจ้ง (overdue_notified_at is null)
        // และไม่ใช่คอลัมน์ Done (อิงชื่อคอลัมน์ = done)
        $tasks = Task::query()
            ->select('tasks.*')
            ->join('board_columns as bc', 'bc.id', '=', 'tasks.column_id')
            ->join('boards as b', 'b.id', '=', 'tasks.board_id')
            ->join('projects as p', 'p.id', '=', 'b.project_id')
            ->whereNotNull('tasks.due_date')
            ->whereDate('tasks.due_date', '<', today())
            ->whereNull('tasks.overdue_notified_at')
            ->whereRaw("LOWER(bc.name) <> 'done'")
            ->with(['assignee', 'creator', 'board.project'])
            ->limit(200) // กันหนักเกิน
            ->get();

        $sent = 0;

        DB::transaction(function () use ($tasks, &$sent) {
            foreach ($tasks as $task) {
                $projectId = (int) optional($task->board)->project_id;
                $projectName = optional(optional($task->board)->project)->name ?? 'Project';
                $due = optional($task->due_date)->format('d/m/Y') ?? '';

                // แจ้งผู้รับผิดชอบ (assignee) ก่อน
                if ($task->assignee_id) {
                    notify_user(
                        (int) $task->assignee_id,
                        'OVERDUE_TASK',
                        '⏰ งานเลยกำหนด (Overdue)',
                        "{$projectName}: \"{$task->title}\" • Due {$due}",
                        $projectId ? route('projects.board', $projectId) : null,
                        ['task_id' => $task->id, 'project_id' => $projectId]
                    );
                    $sent++;
                }

                // แจ้งผู้สร้างด้วย (ถ้าไม่ใช่คนเดียวกับ assignee)
                if ($task->created_by && (int)$task->created_by !== (int)($task->assignee_id ?? 0)) {
                    notify_user(
                        (int) $task->created_by,
                        'OVERDUE_TASK',
                        '⏰ งานที่คุณสร้างเลยกำหนด',
                        "{$projectName}: \"{$task->title}\" • Due {$due}",
                        $projectId ? route('projects.board', $projectId) : null,
                        ['task_id' => $task->id, 'project_id' => $projectId]
                    );
                    $sent++;
                }

                // mark ว่าแจ้งแล้ว กันสแปม
                $task->update(['overdue_notified_at' => now()]);
            }
        });

        $this->info("Overdue notifications sent: {$sent}");
        return self::SUCCESS;
    }
}
