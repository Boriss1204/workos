<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCommentSeeder extends Seeder
{
    public function run(): void
    {
        

        $task = Task::first();
        $owner = User::where('email', 'owner@demo.com')->first();
        $member = User::where('email', 'member@demo.com')->first();

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'comment' => "ช่วยดูงานนี้หน่อยครับ @{$owner->name}",
        ]);
    }
}
