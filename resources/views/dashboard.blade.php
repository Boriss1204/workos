<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                แดชบอร์ด
            </h2>
            <div class="text-sm text-gray-400">
                ภาพรวมการทำงานของคุณ
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- 🔥 My Focus Today --}}
        <div class="bg-white rounded-xl border shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg text-gray-900">
                    🎯 My Focus Today
                </h3>

                <span class="text-sm text-gray-500">
                    {{ $myFocusTasks->count() }} tasks
                </span>
            </div>

            @if($myFocusTasks->isEmpty())
                <div class="text-gray-500 text-sm">
                    🎉 วันนี้คุณไม่มีงานเร่งด่วน
                </div>
            @else
                <div class="space-y-3">
                    @foreach($myFocusTasks as $task)
                        @php
                            $due = $task->due_date
                                ? \Carbon\Carbon::parse($task->due_date)
                                : null;

                            $badge = null;
                            if ($due && $due->lt(today())) {
                                $badge = ['text' => 'Overdue', 'class' => 'bg-rose-100 text-rose-800'];
                            } elseif ($due && $due->isToday()) {
                                $badge = ['text' => 'Today', 'class' => 'bg-amber-100 text-amber-800'];
                            } elseif ($due && $due->lte(today()->addDays(3))) {
                                $badge = ['text' => 'Soon', 'class' => 'bg-yellow-100 text-yellow-800'];
                            }
                        @endphp

                        <a href="{{ route('projects.board', $task->board->project_id) }}"
                        class="block border rounded-lg p-3 hover:bg-gray-50 transition">

                            <div class="flex justify-between items-start gap-3">
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ $task->title }}
                                    </div>

                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $task->board->project->name }}
                                        • {{ $task->column->name }}
                                    </div>

                                    @if($due)
                                        <div class="text-xs mt-1
                                            {{ $due->lt(today()) ? 'text-rose-600' : 'text-gray-600' }}">
                                            ⏰ Due {{ $due->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </div>

                                @if($badge)
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $badge['class'] }}">
                                        {{ $badge['text'] }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

            {{-- 📊 Dashboard Analytics --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

                {{-- Total --}}
                <div class="bg-white border rounded-xl p-4 shadow-sm">
                    <div class="text-sm text-gray-500">Tasks</div>
                    <div class="text-2xl font-bold text-gray-900">
                        {{ $totalTasks }}
                    </div>
                </div>

                {{-- Done --}}
                <div class="bg-white border rounded-xl p-4 shadow-sm">
                    <div class="text-sm text-gray-500">Completed</div>
                    <div class="text-2xl font-bold text-emerald-600">
                        {{ $doneTasks }}
                    </div>
                </div>

                {{-- Overdue --}}
                <div class="bg-white border rounded-xl p-4 shadow-sm">
                    <div class="text-sm text-gray-500">Overdue</div>
                    <div class="text-2xl font-bold text-rose-600">
                        {{ $overdueTasks }}
                    </div>
                </div>

                {{-- Due today --}}
                <div class="bg-white border rounded-xl p-4 shadow-sm">
                    <div class="text-sm text-gray-500">Due Today</div>
                    <div class="text-2xl font-bold text-amber-600">
                        {{ $dueTodayTasks }}
                    </div>
                </div>

                {{-- Progress --}}
                <div class="bg-white border rounded-xl p-4 shadow-sm">
                    <div class="text-sm text-gray-500 mb-1">Progress</div>

                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600"
                                style="width: {{ $progressPercent }}%">
                            </div>
                        </div>

                        <div class="text-sm font-semibold text-gray-700">
                            {{ $progressPercent }}%
                        </div>
                    </div>
                </div>
            </div>

            {{-- 📅 Calendar Overview --}}
            <div class="bg-white border rounded-xl shadow-sm p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg text-gray-900">
                        📅 Calendar
                    </h3>

                    <a href="{{ route('calendar.index') }}"
                    class="text-sm text-blue-600 hover:underline">
                        View full calendar →
                    </a>
                </div>

                <div id="dashboard-calendar"></div>

                <div class="mt-3 text-xs text-gray-500 flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-2">
                        <span class="w-3 h-3 rounded" style="background:#2563eb"></span> Normal
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="w-3 h-3 rounded" style="background:#e11d48"></span> Overdue
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="w-3 h-3 rounded" style="background:#6b7280"></span> Done
                    </span>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('dashboard-calendar');
                if (!el) return;

                const calendar = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    height: 'auto',
                    displayEventTime: false,

                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek,dayGridDay,listWeek'
                    },

                    events: '{{ route('calendar.events') }}',

                    eventClick(info) {
                        if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.location.href = info.event.url;
                        }
                    }
                    });
                calendar.render();
            });
            </script>

            {{-- สรุปภาพรวม --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- เวิร์กสเปซ --}}
                <div class="bg-gray-800/60 border border-gray-700 rounded-2xl p-5">
                    <div class="text-gray-300 text-sm">เวิร์กสเปซของฉัน</div>
                    <div class="text-3xl font-bold text-white mt-2">
                        {{ $workspacesCount ?? 0 }}
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('workspaces.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-700 hover:bg-gray-600 text-white text-sm">
                            ไปหน้าเวิร์กสเปซ <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>

                {{-- คำเชิญ --}}
                <div class="bg-gray-800/60 border border-gray-700 rounded-2xl p-5">
                    <div class="text-gray-300 text-sm">คำเชิญที่รอดำเนินการ</div>
                    <div class="text-3xl font-bold text-white mt-2">
                        {{ $pendingInvitesCount ?? 0 }}
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('invites.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-700 hover:bg-gray-600 text-white text-sm">
                            ดูคำเชิญทั้งหมด <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    <div class="text-gray-400 text-xs mt-3">
                        คำเชิญจะหมดอายุใน 7 วัน
                    </div>
                </div>
            </div>

            {{-- โปรเจกต์ล่าสุด --}}
            <div class="grid grid-cols-1 gap-4">
                <div class="bg-gray-800/60 border border-gray-700 rounded-2xl overflow-hidden">
                    <div class="p-5 border-b border-gray-700">
                        <div class="text-white font-semibold">โปรเจกต์ล่าสุด</div>
                        <div class="text-gray-400 text-sm">เข้าบอร์ดเพื่อจัดการงาน</div>
                    </div>

                    <div class="p-5">
                        @forelse(($recentProjects ?? collect()) as $project)
                            <div class="p-4 rounded-xl bg-gray-900/40 border border-gray-700 flex items-center justify-between gap-4 mb-3">
                                <div>
                                    <div class="text-white font-medium">
                                        {{ $project->name }}
                                    </div>
                                    <div class="text-gray-400 text-sm mt-1">
                                        เวิร์กสเปซ: {{ $project->workspace->name ?? '-' }}
                                    </div>
                                </div>

                                <a href="{{ route('projects.board', $project) }}"
                                   class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm whitespace-nowrap">
                                    ไปบอร์ด
                                </a>
                            </div>
                        @empty
                            <div class="text-gray-300">ยังไม่มีโปรเจกต์</div>
                            <div class="text-gray-400 text-sm mt-2">
                                ไปที่หน้าเวิร์กสเปซเพื่อสร้างโปรเจกต์สำหรับเริ่มใช้งาน
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('workspaces.index') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-700 hover:bg-gray-600 text-white text-sm">
                                    ไปสร้างโปรเจกต์ <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- กิจกรรมล่าสุด --}}
            <div class="bg-gray-800/60 border border-gray-700 rounded-2xl overflow-hidden">
                <div class="p-5 border-b border-gray-700">
                    <div class="text-white font-semibold">กิจกรรมล่าสุดของฉัน</div>
                    <div class="text-gray-400 text-sm">อัปเดตล่าสุด 10 รายการ</div>
                </div>

                <div class="p-5">
                    @if(($recentActivities ?? collect())->count() === 0)
                        <div class="text-gray-300">
                            ยังไม่มีกิจกรรมล่าสุด
                        </div>
                        <div class="text-gray-400 text-sm mt-2">
                            ลองสร้างเวิร์กสเปซ/โปรเจกต์ แล้วระบบจะเริ่มบันทึกกิจกรรมให้อัตโนมัติ
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($recentActivities as $activity)
                                @php
                                    $pname = !empty($activity->project_id)
                                        ? ($projectNameMap[$activity->project_id] ?? null)
                                        : null;
                                @endphp

                                <div class="p-4 rounded-xl bg-gray-900/40 border border-gray-700">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="text-white font-medium break-words">
                                                {{ $activity->details ?? 'กิจกรรม' }}
                                            </div>

                                            <div class="text-gray-400 text-sm mt-1">
                                                กิจกรรม: {{ activity_label($activity->action ?? '-') }}
                                                @if(!empty($activity->project_id))
                                                    • โปรเจกต์: {{ $pname ?? ('#'.$activity->project_id) }}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-gray-400 text-sm whitespace-nowrap">
                                            {{ optional($activity->created_at)->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
    <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
</x-app-layout>
