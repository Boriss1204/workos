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
        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        $owner = User::firstOrCreate(
            ['email' => 'owner@workos.test'],
            [
                'name' => 'Owner User',
                'password' => Hash::make('password'),
            ]
        );

        $member1 = User::firstOrCreate(
            ['email' => 'member1@workos.test'],
            [
                'name' => 'Member One',
                'password' => Hash::make('password'),
            ]
        );

        $member2 = User::firstOrCreate(
            ['email' => 'member2@workos.test'],
            [
                'name' => 'Member Two',
                'password' => Hash::make('password'),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Workspace
        |--------------------------------------------------------------------------
        */
        $workspace = Workspace::firstOrCreate(
            ['name' => 'Demo Workspace'],
            ['owner_user_id' => $owner->id]
        );

        foreach ([
            [$owner, 'owner'],
            [$member1, 'member'],
            [$member2, 'member'],
        ] as [$user, $role]) {
            WorkspaceMember::firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'user_id' => $user->id,
                ],
                ['role' => $role]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Project
        |--------------------------------------------------------------------------
        */
        $project = Project::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'name' => 'Demo Project',
            ],
            [
                'created_by' => $owner->id,
            ]
        );

        foreach ([
            [$owner, 'owner'],
            [$member1, 'member'],
            [$member2, 'member'],
        ] as [$user, $role]) {
            ProjectMember::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                ],
                ['role' => $role]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Board
        |--------------------------------------------------------------------------
        */
        $board = Board::firstOrCreate(
            ['project_id' => $project->id]
        );

        /*
        |--------------------------------------------------------------------------
        | Board Columns (position is required)
        |--------------------------------------------------------------------------
        */
        $columns = [
            ['name' => 'To Do', 'position' => 1],
            ['name' => 'In Progress', 'position' => 2],
            ['name' => 'Done', 'position' => 3],
        ];

        $columnMap = [];

        foreach ($columns as $col) {
            $column = BoardColumn::firstOrCreate(
                [
                    'board_id' => $board->id,
                    'name' => $col['name'],
                ],
                ['position' => $col['position']]
            );

            if ((int)$column->position !== (int)$col['position']) {
                $column->position = $col['position'];
                $column->save();
            }

            $columnMap[$col['name']] = $column;
        }

        /*
        |--------------------------------------------------------------------------
        | Tasks
        |--------------------------------------------------------------------------
        */
        $tasks = [
            [
                'title' => 'ออกแบบหน้า Dashboard',
                'column' => 'To Do',
                'priority' => 'medium',
                'created_by' => $owner->id,
            ],
            [
                'title' => 'จัดการสมาชิกโปรเจกต์',
                'column' => 'In Progress',
                'priority' => 'urgent',
                'created_by' => $member1->id,
            ],
            [
                'title' => 'เพิ่มระบบ Activity Log',
                'column' => 'Done',
                'priority' => 'high',
                'created_by' => $member2->id,
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
                    'created_by' => $t['created_by'],
                    'description' => 'งานตัวอย่างจาก Seeder',
                    'due_date' => Carbon::now()->addDays(3)->toDateString(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Activity Logs
        |--------------------------------------------------------------------------
        */
        ActivityLog::firstOrCreate([
            'action' => 'DEMO_SEED',
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'user_id' => $owner->id,
        ], [
            'details' => 'สร้างข้อมูลเดโม (Owner + 2 Members)',
        ]);

        ActivityLog::firstOrCreate([
            'action' => 'CREATE_TASK',
            'project_id' => $project->id,
            'user_id' => $member1->id,
        ], [
            'details' => 'Member One สร้างงานใหม่',
        ]);

        ActivityLog::firstOrCreate([
            'action' => 'UPDATE_TASK',
            'project_id' => $project->id,
            'user_id' => $member2->id,
        ], [
            'details' => 'Member Two อัปเดตงานในบอร์ด',
        ]);
    }
}
