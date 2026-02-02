<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\ProjectMember;

class BoardController extends Controller
{
    private function ensureProjectMember(Project $project)
    {
        $isMember = \App\Models\ProjectMember::where('project_id', $project->id)
            ->where('user_id', auth()->id())
            ->exists();

        // เผื่อ owner ที่ผูกด้วย created_by
        if (!$isMember && (int)($project->created_by ?? 0) === (int)auth()->id()) {
            $isMember = true;
        }

        if (!$isMember) {
            return redirect()
                ->route('workspaces.index')
                ->with('error', 'คุณไม่มีสิทธิ์เข้าถึงโปรเจกต์นี้ หรือได้ออกจากโปรเจกต์แล้ว');
        }

        return null;
    }


    public function show(Project $project)
    {
        if ($redirect = $this->ensureProjectMember($project)) {
            return $redirect;
        }

        // ถ้ายังไม่มี board ให้สร้าง
        $board = $project->board;
        if (!$board) {
            $board = \App\Models\Board::create(['project_id' => $project->id]);

            foreach (['To Do', 'In Progress', 'Done'] as $i => $name) {
                \App\Models\BoardColumn::create([
                    'board_id' => $board->id,
                    'name' => $name,
                    'position' => $i + 1,
                ]);
            }
        }

        $project->load([
            'board.columns' => fn($q) => $q->orderBy('position')->with([
                'tasks' => fn($t) => $t->latest()->with([
                    'assignee',
                    'comments.user',
                    'attachments.user',
                ])
            ])
        ]);

        return view('boards.show', compact('project'));
    }

}
