<?php

namespace Database\Seeders;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoWorkspaceSeeder extends Seeder
{
    public function run(): void
    {

        $owner = User::where('email', 'owner@demo.com')->first();
        $member = User::where('email', 'member@demo.com')->first();

        $workspace = Workspace::create([
            'name' => 'Demo Workspace',
            'owner_user_id' => $owner->id,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
    }
}
