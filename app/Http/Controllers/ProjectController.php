<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Board;
use App\Models\BoardColumn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index($workspaceId)
    {
        $projects = Project::where('workspace_id', $workspaceId)->get();
        return view('projects.index', compact('projects', 'workspaceId'));
    }

    public function store(Request $request, $workspaceId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $project = Project::create([
            'workspace_id' => $workspaceId,
            'created_by' => Auth::id(),
            'name' => $request->name,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'role' => 'owner',
        ]);

        $board = Board::create([
            'project_id' => $project->id,
        ]);

        foreach (['To Do', 'In Progress', 'Done'] as $index => $name) {
            BoardColumn::create([
                'board_id' => $board->id,
                'name' => $name,
                'position' => $index + 1,
            ]);
        }

        log_activity(
            'CREATE_PROJECT',
            "สร้างโปรเจกต์ \"{$project->name}\"",
            $workspaceId,
            $project->id
        );

        return redirect()->back();
    }
}
