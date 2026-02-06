<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectInviteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CalendarController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Workspaces
    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');

    // Projects (by workspace)
    Route::get('/workspaces/{workspace}/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/workspaces/{workspace}/projects', [ProjectController::class, 'store'])->name('projects.store');

    // Board + Activity
    Route::get('/projects/{project}/board', [BoardController::class, 'show'])->name('projects.board');
    Route::get('/projects/{project}/activity', [ActivityLogController::class, 'project'])->name('projects.activity');

    // Tasks
    Route::post('/columns/{column}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
    Route::post('/tasks/{task}/priority', [TaskController::class, 'priority'])->name('tasks.priority');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Comments
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
    Route::patch('/comments/{comment}', [TaskCommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');

    // Attachments
    Route::post('/tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('tasks.attachments.store');
    Route::delete('/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Members
    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index'])->name('projects.members');
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::patch('/project-members/{member}', [ProjectMemberController::class, 'update'])->name('project_members.update');
    Route::delete('/project-members/{member}', [ProjectMemberController::class, 'destroy'])->name('project_members.destroy');
    Route::post('/projects/{project}/leave', [ProjectMemberController::class, 'leave'])->name('projects.leave');

    // Invites (inbox)
    Route::get('/invites', [ProjectInviteController::class, 'index'])->name('invites.index');
    Route::post('/projects/{project}/invites', [ProjectInviteController::class, 'store'])->name('projects.invites.store');
    Route::post('/invites/{invite}/accept', [ProjectInviteController::class, 'accept'])->name('invites.accept');
    Route::post('/invites/{invite}/decline', [ProjectInviteController::class, 'decline'])->name('invites.decline');
    Route::delete('/invites/{invite}', [ProjectInviteController::class, 'cancel'])->name('invites.cancel');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.readAll');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
});

require __DIR__.'/auth.php';
