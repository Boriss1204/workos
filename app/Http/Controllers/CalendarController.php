<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * หน้า Calendar
     */
    public function index(Request $request)
    {
        return view('calendar.index');
    }

    /**
     * Events สำหรับ FullCalendar (AJAX)
     */
    public function events(Request $request)
    {
        // FullCalendar ส่ง start / end มาเป็น ISO string
        $start = $request->query('start');
        $end   = $request->query('end');

        $tasks = Task::query()
            ->with(['board.project', 'column'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            // เห็นเฉพาะงานที่เกี่ยวกับ user
            ->where(function ($q) {
                $q->where('assignee_id', Auth::id())
                  ->orWhere('created_by', Auth::id());
            })
            ->get();

        $events = $tasks->map(function (Task $t) {
            $project = optional($t->board)->project;
            $projectName = $project?->name ?? 'Project';

            $isDone = strtolower($t->column?->name ?? '') === 'done';
            $isOverdue = !$isDone && $t->due_date && Carbon::parse($t->due_date)->isPast();

            // 🎨 สีตามสถานะ / priority
            if ($isDone) {
                $color = '#6b7280'; // gray-500
            } elseif ($isOverdue) {
                $color = '#dc2626'; // red-600
            } else {
                $color = match ($t->priority) {
                    'urgent' => '#b91c1c', // red-700
                    'high'   => '#f97316', // orange-500
                    'normal' => '#2563eb', // blue-600
                    'low'    => '#6b7280', // gray-500
                    default  => '#2563eb',
                };
            }

            return [
                'id'    => $t->id,
                'title' => "{$t->title} ({$projectName})",
                'start' => Carbon::parse($t->due_date)->toDateString(),
                'allDay' => true,

                // 👉 คลิกแล้วไป Board
                'url' => $project ? route('projects.board', $project->id) : null,

                // 🎨 สี event
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'textColor'       => '#ffffff',

                // 👉 ใช้ต่อได้ใน JS (tooltip / modal)
                'extendedProps' => [
                    'priority' => $t->priority,
                    'overdue'  => $isOverdue,
                    'done'     => $isDone,
                    'project'  => $projectName,
                ],
            ];
        });

        return response()->json($events);
    }
}
