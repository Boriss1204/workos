<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\ActivityLog;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ===== User เดโม =====
        $user = User::firstOrCreate(
            ['email' => 'demo@workos.test'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
            ]
        );

        // ===== Workspace =====
        $workspace = Workspace::firstOrCreate(
            ['name' => 'Demo Workspace'],
            ['owner_user_id' => $user->id]
        );

        WorkspaceMember::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
            ],
            ['role' => 'owner']
        );

        // ===== Project =====
        $project = Project::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'name' => 'Demo Project',
            ],
            [
                'created_by' => $user->id,
            ]
        );

        ProjectMember::firstOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ],
            ['role' => 'owner']
        );

        // ===== Board =====
        // ตาราง boards ของคุณไม่มีคอลัมน์ name -> ใช้ project_id อย่างเดียว
        $board = Board::firstOrCreate(
            ['project_id' => $project->id]
        );

        // ===== Columns =====
        // ตาราง board_columns มี position (NOT NULL) -> ต้องใส่ position ทุกครั้ง
        $columns = [
            ['name' => 'To Do', 'position' => 1],
            ['name' => 'In Progress', 'position' => 2],
            ['name' => 'Done', 'position' => 3],
        ];

        $columnMap = [];

        foreach ($columns as $colData) {
            $col = BoardColumn::firstOrCreate(
                [
                    'board_id' => $board->id,
                    'name' => $colData['name'],
                ],
                [
                    'position' => $colData['position'],
                ]
            );

            // กันกรณี seed ซ้ำแล้วตำแหน่งเพี้ยน
            if ((int)($col->position ?? 0) !== (int)$colData['position']) {
                $col->position = $colData['position'];
                $col->save();
            }

            $columnMap[$colData['name']] = $col;
        }

        // ===== Tasks =====
        $tasks = [
            [
                'title' => 'ออกแบบหน้า Dashboard',
                'column' => 'To Do',
                'priority' => 'medium',
                'description' => 'จัดหน้าให้สวย อ่านง่าย และพร้อมเดโม',
                'due_days' => 7,
            ],
            [
                'title' => 'สร้างระบบ Workspace',
                'column' => 'Done',
                'priority' => 'high',
                'description' => 'สร้าง/จัดการเวิร์กสเปซและสมาชิก',
                'due_days' => 2,
            ],
            [
                'title' => 'จัดการสมาชิกโปรเจกต์',
                'column' => 'In Progress',
                'priority' => 'urgent',
                'description' => 'เชิญสมาชิก, เปลี่ยนสิทธิ์, ลบ/ออกจากโปรเจกต์',
                'due_days' => 1,
            ],
            [
                'title' => 'เพิ่มระบบ Activity Log',
                'column' => 'Done',
                'priority' => 'medium',
                'description' => 'บันทึกกิจกรรมหลักเพื่อใช้ตรวจสอบย้อนหลัง',
                'due_days' => 3,
            ],
            [
                'title' => 'เตรียมระบบ Seeder',
                'column' => 'In Progress',
                'priority' => 'high',
                'description' => 'สร้างข้อมูลตัวอย่างสำหรับทดสอบ/สาธิตระบบ',
                'due_days' => 1,
            ],
        ];

        foreach ($tasks as $t) {
            Task::firstOrCreate(
                [
                    'board_id' => $board->id,
                    'title' => $t['title'],
                ],
                [
                    'column_id' => $columnMap[$t['column']]->id,
                    'priority' => $t['priority'],
                    'description' => $t['description'] ?? null,
                    'due_date' => isset($t['due_days'])
                        ? Carbon::now()->addDays((int)$t['due_days'])->toDateString()
                        : null,
                    'created_by' => $user->id,
                ]
            );
        }

        // ===== Activity Log =====
        ActivityLog::firstOrCreate(
            [
                'action' => 'DEMO_SEED',
                'workspace_id' => $workspace->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
            ],
            [
                'details' => 'สร้างข้อมูลเดโมสำหรับการสาธิตระบบ (Seeder)',
            ]
        );
    }
}
