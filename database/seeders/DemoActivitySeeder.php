<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoActivitySeeder extends Seeder
{
    public function run(): void
    {
        

        $project = Project::first();
        $owner = User::where('email', 'owner@demo.com')->first();

        ActivityLog::create([
            'action' => 'DELETE_TASK',
            'details' => 'ลบงาน "Old Task"',
            'project_id' => $project->id,
            'user_id' => $owner->id,
        ]);
    }
}
