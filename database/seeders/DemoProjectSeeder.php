<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {


        $workspace = Workspace::first();
        $owner = User::where('email', 'owner@demo.com')->first();
        $member = User::where('email', 'member@demo.com')->first();

        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Demo Project',
            'created_by' => $owner->id,
        ]);

        ProjectMember::insert([
            ['project_id' => $project->id, 'user_id' => $owner->id, 'role' => 'owner'],
            ['project_id' => $project->id, 'user_id' => $member->id, 'role' => 'member'],
        ]);

        $board = Board::create(['project_id' => $project->id]);

        foreach (['To Do', 'In Progress', 'Done'] as $i => $name) {
            BoardColumn::create([
                'board_id' => $board->id,
                'name' => $name,
                'position' => $i + 1,
            ]);
        }
    }
}
