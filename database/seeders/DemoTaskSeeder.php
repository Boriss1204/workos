<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\BoardColumn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoTaskSeeder extends Seeder
{
    public function run(): void
    {
        

        $todo = BoardColumn::where('name', 'To Do')->first();
        $progress = BoardColumn::where('name', 'In Progress')->first();
        $done = BoardColumn::where('name', 'Done')->first();

        $owner = User::where('email', 'owner@demo.com')->first();
        $member = User::where('email', 'member@demo.com')->first();

        Task::create([
            'board_id' => $todo->board_id,
            'column_id' => $todo->id,
            'title' => '🔥 Overdue Task',
            'priority' => 'urgent',
            'due_date' => Carbon::now()->subDays(2),
            'created_by' => $owner->id,
            'assignee_id' => $member->id,
            'position' => 1,
        ]);

        Task::create([
            'board_id' => $todo->board_id,
            'column_id' => $todo->id,
            'title' => '📅 Due Today Task',
            'priority' => 'high',
            'due_date' => Carbon::today(),
            'created_by' => $owner->id,
            'assignee_id' => null,
            'position' => 2,
        ]);

        Task::create([
            'board_id' => $progress->board_id,
            'column_id' => $progress->id,
            'title' => 'Normal In Progress',
            'priority' => 'normal',
            'due_date' => Carbon::now()->addDays(3),
            'created_by' => $member->id,
            'assignee_id' => $member->id,
            'position' => 1,
        ]);

        Task::create([
            'board_id' => $done->board_id,
            'column_id' => $done->id,
            'title' => '✅ Completed Task',
            'priority' => 'low',
            'due_date' => Carbon::now()->subDays(5),
            'created_by' => $owner->id,
            'assignee_id' => $owner->id,
            'position' => 1,
        ]);
    }
}
