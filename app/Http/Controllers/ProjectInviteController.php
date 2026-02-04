<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInvite;
use App\Models\ProjectMember;
use App\Models\WorkspaceMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProjectInviteController extends Controller
{
    /* -------------------------------------------------
     |  Helper: เช็กว่าเป็น Owner ของ Project ไหม
     ------------------------------------------------- */
    private function ensureOwner(Project $project): void
    {
        $isOwner = ProjectMember::where('project_id', $project->id)
            ->where('user_id', Auth::id())
            ->where('role', 'owner')
            ->exists();

        if (!$isOwner) {
            abort(403, 'Only project owner can do this action.');
        }
    }

    /* -------------------------------------------------
     |  Invite list (Invite Inbox)
     |  GET /invites
     ------------------------------------------------- */
    public function index()
    {
        $email = Auth::user()->email;

        $invites = ProjectInvite::with('project.workspace')
            ->where('email', $email)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();

        return view('invites.index', compact('invites'));
    }

    /* -------------------------------------------------
     |  Send invite (Owner only)
     |  POST /projects/{project}/invites
     ------------------------------------------------- */
    public function store(Request $request, Project $project)
    {
        $this->ensureOwner($project);

        $request->validate([
            'email' => 'required|email',
            'role'  => 'required|in:owner,member',
        ]);
        
        // กันเชิญตัวเอง
        $email = strtolower(trim($request->email));

                if ($email === auth()->user()->email) {
            return back()->withErrors([
                'email' => 'ไม่สามารถเชิญอีเมลของตัวเองได้'
            ]);
        }

        //  บังคับ: ต้องมี user ในระบบเท่านั้นถึงเชิญได้
        $existingUser = User::where('email', $email)->first();
        if (!$existingUser) {
            return back()->withErrors([
                'email' => 'ไม่พบผู้ใช้อีเมลนี้ในระบบ กรุณาให้ผู้ใช้นี้สมัครสมาชิกก่อน'
            ]);
        }

        // กันเชิญคนที่เป็นสมาชิกอยู่แล้ว
        $alreadyMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $existingUser->id)
            ->exists();

        if ($alreadyMember) {
            return back()->withErrors([
                'email' => 'ผู้ใช้นี้เป็นสมาชิกโปรเจกต์อยู่แล้ว'
            ]);
        }

        // กัน invite ซ้ำ (pending)
        $pending = ProjectInvite::where('project_id', $project->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            return back()->withErrors([
                'email' => 'อีเมลนี้ถูกเชิญแล้ว (pending)'
            ]);
        }

        ProjectInvite::create([
            'project_id' => $project->id,
            'email'      => $email,
            'role'       => $request->role,
            'token'      => Str::random(40),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
            'invited_by' => Auth::id(),
        ]);

        if (function_exists('log_activity')) {
            log_activity(
                'INVITE_MEMBER',
                "ส่งคำเชิญเข้าร่วมโปรเจกต์ให้ {$email}",
                $project->workspace_id,
                $project->id,
                Auth::id()
            );
        }

        return back()->with('success', 'ส่งคำเชิญเรียบร้อยแล้ว');
    }


    /* -------------------------------------------------
     |  Accept invite
     |  POST /invites/{invite}/accept
     ------------------------------------------------- */
    public function accept(ProjectInvite $invite)
    {
        $user = Auth::user();

        // กันคนอื่นกดแทน
        if ($invite->email !== $user->email) {
            abort(403);
        }

        // หมดอายุ
        if ($invite->expires_at && Carbon::parse($invite->expires_at)->isPast()) {
            return back()->withErrors(['invite' => 'คำเชิญหมดอายุแล้ว']);
        }

        if ($invite->status !== 'pending') {
            return back();
        }

        $project = $invite->project;

        /* --- เพิ่มเป็นสมาชิก Project --- */
        ProjectMember::firstOrCreate(
            [
                'project_id' => $project->id,
                'user_id'    => $user->id,
            ],
            [
                'role' => $invite->role ?? 'member',
            ]
        );

        /* --- เพิ่มเป็นสมาชิก Workspace (สำคัญมาก) --- */
        WorkspaceMember::firstOrCreate(
            [
                'workspace_id' => $project->workspace_id,
                'user_id'      => $user->id,
            ],
            [
                'role' => 'member',
            ]
        );

        $invite->delete();

        if (function_exists('log_activity')) {
            log_activity(
                'ACCEPT_INVITE',
                "ยอมรับคำเชิญเข้าร่วมโปรเจกต์ \"{$project->name}\"",
                $project->workspace_id,
                $project->id,
                $user->id
            );
        }

        return redirect()
            ->route('projects.index', $project->workspace_id)
            ->with('success', 'เข้าร่วมโปรเจกต์เรียบร้อยแล้ว');
    }

    public function decline(ProjectInvite $invite)
{
    $user = Auth::user();

    if ($invite->email !== $user->email) {
        abort(403);
    }

    $project = $invite->project;

    // ลบเพื่อให้เชิญใหม่ได้ (ไม่ติด unique)
    $invite->delete();

    if (function_exists('log_activity')) {
        log_activity(
            'DECLINE_INVITE',
            "ปฏิเสธคำเชิญเข้าร่วมโปรเจกต์ \"{$project->name}\"",
            $project->workspace_id,
            $project->id,
            $user->id
        );
    }

    return back()->with('success', 'ปฏิเสธคำเชิญแล้ว');
}


    /* -------------------------------------------------
     |  Cancel invite (Owner only)
     |  DELETE /invites/{invite}
     ------------------------------------------------- */
    public function cancel(ProjectInvite $invite)
    {
        $project = $invite->project;
        $this->ensureOwner($project);

        if ($invite->status === 'pending') {
            $invite->delete();
        }

        return back()->with('success', 'ยกเลิกคำเชิญแล้ว');
    }
}
