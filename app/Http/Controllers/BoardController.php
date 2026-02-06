<?php

namespace App\Http\Controllers;

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

        // ===== รับค่าตัวกรองจาก query string =====
        $priority = request('priority'); // urgent/high/medium/low หรือค่าในระบบคุณ
        $creator  = request('creator');  // user_id ของผู้สร้างงาน (created_by)

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

        // ===== รายชื่อผู้สร้าง (dropdown) =====
        // ใช้สมาชิกในโปรเจกต์เป็นตัวเลือก (owner + member)
        $creators = ProjectMember::with('user')
            ->where('project_id', $project->id)
            ->get()
            ->map(fn ($m) => $m->user)
            ->filter()
            ->unique('id')
            ->values();

        // ===== โหลด board/columns/tasks พร้อม filter =====
        $project->load([
            'board.columns' => fn ($q) => $q->orderBy('position')->with([
               'tasks' => fn ($t) => $t->orderBy('position')
                ->when($priority, fn ($qq) => $qq->where('priority', $priority))
                ->when($creator, fn ($qq) => $qq->where('created_by', $creator))
                ->with([
                    'creator',
                    'assignee',
                    'comments.user',
                    'attachments.user',
                ])

            ])
        ]);

        return view('boards.show', compact(
            'project',
            'creators',
            'priority',
            'creator'
        ));
    }
}
    