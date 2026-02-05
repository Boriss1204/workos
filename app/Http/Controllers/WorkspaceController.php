<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    public function index()
    {
        $workspaces = Workspace::whereHas('members', function ($q) {
            $q->where('user_id', Auth::id());
        })->get();

        return view('workspaces.index', compact('workspaces'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = Workspace::create([
            'name' => $request->name,
            'owner_user_id' => Auth::id(),
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => Auth::id(),
            'role' => 'owner',
        ]);

        log_activity(
            'CREATE_WORKSPACE',
            "สร้างเวิร์กสเปซ \"{$workspace->name}\"",
            $workspace->id,
            null
        );
        
        return redirect()->back();
    }
}
