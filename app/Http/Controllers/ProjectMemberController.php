<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectMemberController extends Controller
{
    private function ensureOwner(Project $project): void
    {
        $isOwner = ProjectMember::where('project_id', $project->id)
            ->where('user_id', Auth::id())
            ->where('role', 'owner')
            ->exists();

        // fallback: ถ้าคุณใช้ created_by เป็นเจ้าของด้วย
        if (!$isOwner && (int)($project->created_by ?? 0) === (int)Auth::id()) {
            $isOwner = true;
        }

        if (!$isOwner) {
            abort(403);
        }
    }

    public function index(Project $project)
    {
        // สมาชิกในโปรเจกต์ดูได้ (ไม่ต้องเป็น owner ก็ได้)
        // แต่ถ้าคุณอยากล็อกให้เฉพาะ member ดู: เพิ่มเช็ก ProjectMember exists
        $members = ProjectMember::with('user')
            ->where('project_id', $project->id)
            ->orderByRaw("role = 'owner' DESC")
            ->get();

        return view('projects.members', compact('project', 'members'));
    }

    public function store(Request $request, Project $project)
    {
        $this->ensureOwner($project);

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:owner,member',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'ไม่พบผู้ใช้อีเมลนี้ในระบบ']);
        }

        // กันเพิ่มตัวเองซ้ำ
        $exists = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['email' => 'ผู้ใช้นี้เป็นสมาชิกโปรเจกต์อยู่แล้ว']);
        }

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => $request->role,
        ]);

        // ทำให้เป็นสมาชิก workspace ด้วย (ถ้ายังไม่เป็น)
        WorkspaceMember::firstOrCreate(
            ['workspace_id' => $project->workspace_id, 'user_id' => $user->id],
            ['role' => 'member']
        );

        if (function_exists('log_activity')) {
            log_activity(
                'INVITE_MEMBER',
                "ส่งคำเชิญเข้าร่วมโปรเจกต์ \"{$project->name}\" ให้ {$user->email}",
                $project->workspace_id,
                $project->id,
                Auth::id()
            );
        }

        return back();
    }

    public function update(Request $request, ProjectMember $member)
    {
        $project = Project::findOrFail($member->project_id);
        $this->ensureOwner($project);

        $request->validate([
            'role' => 'required|in:owner,member',
        ]);

        // กันไม่ให้ owner คนสุดท้ายถูกลดสิทธิ์ (สำคัญ)
        if ($member->role === 'owner' && $request->role === 'member') {
            $ownerCount = ProjectMember::where('project_id', $project->id)->where('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return back()->withErrors(['role' => 'ต้องมี Owner อย่างน้อย 1 คน']);
            }
        }

        $member->update(['role' => $request->role]);

        if (function_exists('log_activity')) {
            $member->loadMissing('user');
            $email = optional($member->user)->email ?? 'สมาชิก';

            log_activity(
                'CHANGE_MEMBER_ROLE',
                "เปลี่ยนสิทธิ์ของ {$email} เป็น {$request->role}",
                $project->workspace_id,
                $project->id,
                Auth::id()
            );
        }


        return back();
    }

    public function destroy(ProjectMember $member)
    {
        $project = Project::findOrFail($member->project_id);
        $this->ensureOwner($project);

        // กันไม่ให้ลบ owner คนสุดท้าย
        if ($member->role === 'owner') {
            $ownerCount = ProjectMember::where('project_id', $project->id)->where('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return back()->withErrors(['delete' => 'ต้องมี Owner อย่างน้อย 1 คน']);
            }
        }

        // ----- LOG (วางก่อน delete) -----
        if (function_exists('log_activity')) {
            $member->loadMissing('user', 'project');

            $email = optional($member->user)->email ?? 'สมาชิก';
            $projectName = optional($member->project)->name ?? ($project->name ?? 'โปรเจกต์');

            log_activity(
                'REMOVE_MEMBER',
                "ลบสมาชิก {$email} ออกจากโปรเจกต์ \"{$projectName}\"",
                $project->workspace_id,
                $project->id,
                Auth::id()
            );
        }
        // -------------------------------

        $member->delete();

        return back();
    }


    public function leave(Project $project)
{
    $member = \App\Models\ProjectMember::where('project_id', $project->id)
        ->where('user_id', \Illuminate\Support\Facades\Auth::id())
        ->first();

    if (!$member) abort(403);

    if ($member->role === 'owner') {
        $ownerCount = \App\Models\ProjectMember::where('project_id', $project->id)->where('role', 'owner')->count();
        if ($ownerCount <= 1) {
            return back()->withErrors(['leave' => 'คุณเป็น Owner คนสุดท้าย ต้องโอนสิทธิ์ก่อนออก']);
        }
    }

        // ----- LOG: leave project (วางก่อน $member->delete()) -----
        if (function_exists('log_activity')) {
            $user = Auth::user();

            log_activity(
                'LEAVE_PROJECT',
                "{$user->email} ออกจากโปรเจกต์",
                $project->workspace_id,
                $project->id,
                $user->id
            );
        }


    // ----------------------------------------------------------

    $member->delete();
    return redirect()->route('projects.index', $project->workspace_id);
}

}
