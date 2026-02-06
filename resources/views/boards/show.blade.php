<x-app-layout>
    @php
        // ดึงสมาชิกของโปรเจกต์ (ไว้ใช้ใน dropdown assign)
        $projectMembers = \App\Models\ProjectMember::with('user')
            ->where('project_id', $project->id)
            ->get();

        // นับจำนวน task สำหรับปุ่ม filter (รวมทุกคอลัมน์)
        $allCount = 0;
        $myCount = 0;
        $unassignedCount = 0;

        foreach ($project->board->columns as $cc) {
            foreach ($cc->tasks as $tt) {
                $allCount++;
                if ((int)($tt->assignee_id ?? 0) === (int)auth()->id()) $myCount++;
                if (empty($tt->assignee_id)) $unassignedCount++;
            }
        }
    @endphp

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">
                Board: {{ $project->name }}
            </h2>

            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('projects.members', $project->id) }}" class="text-gray-600 underline">
                    Members
                </a>

                <a href="{{ route('projects.activity', $project->id) }}" class="text-gray-600 underline">
                    Activity
                </a>

                <a href="{{ route('projects.index', $project->workspace_id) }}" class="text-gray-600 underline">
                    Back to Projects
                </a>
            </div>
        </div>
    </x-slot>

    {{-- ✅ ROOT state: filter + me อยู่ที่นี่ที่เดียว --}}
    <div class="p-6" x-data="{ filter: 'all', me: {{ (int)auth()->id() }} }">

        {{-- ✅ Server-side filter: Priority / Creator --}}
        <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="text-sm font-semibold text-gray-700">Priority</label>
                <select name="priority" class="mt-1 border rounded-lg p-2 bg-white text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="urgent" @selected(($priority ?? '') === 'urgent')>Urgent</option>
                    <option value="high"   @selected(($priority ?? '') === 'high')>High</option>
                    <option value="normal" @selected(($priority ?? '') === 'normal')>Normal</option>
                    <option value="low"    @selected(($priority ?? '') === 'low')>Low</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">ผู้สร้าง</label>
                <select name="creator" class="mt-1 border rounded-lg p-2 bg-white text-sm min-w-[220px]">
                    <option value="">ทั้งหมด</option>
                    @foreach(($creators ?? collect()) as $u)
                        <option value="{{ $u->id }}" @selected((string)($creator ?? '') === (string)$u->id)>
                            {{ $u->name ?? $u->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="!bg-blue-600 hover:!bg-blue-700 !text-white px-4 py-2 rounded-lg text-sm font-semibold">
                Apply
            </button>

            <a href="{{ url()->current() }}"
               class="border px-4 py-2 rounded-lg text-sm text-gray-700">
                Reset
            </a>
        </form>

        {{-- Filter bar --}}
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <button type="button"
                    class="px-3 py-2 rounded-lg border text-sm"
                    :class="filter==='all' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200'"
                    @click="filter='all'">
                All <span class="opacity-80">({{ $allCount }})</span>
            </button>

            <button type="button"
                    class="px-3 py-2 rounded-lg border text-sm"
                    :class="filter==='my' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200'"
                    @click="filter='my'">
                My Tasks <span class="opacity-80">({{ $myCount }})</span>
            </button>

            <button type="button"
                    class="px-3 py-2 rounded-lg border text-sm"
                    :class="filter==='unassigned' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200'"
                    @click="filter='unassigned'">
                Unassigned <span class="opacity-80">({{ $unassignedCount }})</span>
            </button>

        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($project->board->columns as $col)
                {{-- ✅ Column scope: open + visibleCount เท่านั้น --}}
                <div class="border border-gray-200 rounded-2xl bg-white shadow-sm p-3"
                     x-data="{ open:false, visibleCount: 0 }">

                    {{-- Column header --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="font-semibold text-gray-900">
                            {{ $col->name }}
                        </div>

                        <button type="button"
                                class="!bg-blue-600 hover:!bg-blue-700 !text-white text-sm font-semibold px-3 py-2 rounded-lg shadow"
                                @click="open = true">
                            + Add Task
                        </button>
                    </div>

                    {{-- ✅ Tasks list (ทำให้ SortableJS จับได้) --}}
                    <div class="space-y-3 task-list"
                         data-column-id="{{ $col->id }}"
                         data-column-name="{{ strtolower($col->name) }}"
                         x-ref="tasksWrap"
                         x-effect="
                            filter; // ✅ ทำให้ reactive ตาม filter
                            $nextTick(() => {
                                visibleCount = $refs.tasksWrap
                                    ? Array.from($refs.tasksWrap.querySelectorAll('[data-task-card]'))
                                        .filter(el => el.offsetParent !== null).length
                                    : 0;
                            });
                         ">

                        @foreach($col->tasks as $task)
                            @php
                                $priorityMeta = [
                                    'low' => [
                                        'label' => 'Low',
                                        'class' => 'bg-slate-200 text-slate-800 border border-slate-300',
                                    ],
                                    'normal' => [
                                        'label' => 'Normal',
                                        'class' => 'bg-blue-200 text-blue-800 border border-blue-300',
                                    ],
                                    'high' => [
                                        'label' => 'High',
                                        'class' => 'bg-amber-200 text-amber-900 border border-amber-300',
                                    ],
                                    'urgent' => [
                                        'label' => 'Urgent',
                                        'class' => 'bg-rose-200 text-rose-900 border border-rose-300',
                                    ],
                                ];
                                $p = $task->priority ?? 'normal';
                                $pm = $priorityMeta[$p] ?? $priorityMeta['normal'];
                            @endphp

                            @php
                                $isDoneColumn = strtolower($col->name) === 'done';
                                $isOverdue = !$isDoneColumn && $task->due_date && \Carbon\Carbon::parse($task->due_date)->lt(today());
                                $isDueToday = !$isDoneColumn && $task->due_date && \Carbon\Carbon::parse($task->due_date)->isToday();
                            @endphp


                            {{-- ✅ Card (เพิ่ม task-card + data-task-id ให้ลากได้) --}}
                            <div data-task-card
                                class="task-card group border rounded-xl bg-white shadow-sm hover:shadow-md transition overflow-hidden
                                        {{ (int)($task->created_by ?? 0) === (int)auth()->id()
                                            ? 'border-blue-300 ring-1 ring-blue-200'
                                            : 'border-gray-200' }}
                                        {{ $isOverdue ? '!border-rose-500 ring-2 ring-rose-200 bg-rose-50' : '' }}
                                        {{ $isDueToday ? '!border-amber-500 ring-2 ring-amber-200 bg-amber-50' : '' }}"
                                data-task-id="{{ $task->id }}"
                                data-current-column-id="{{ $task->column_id }}"
                                data-due-date="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
                                 x-show="
                                    filter === 'all'
                                    || (filter === 'my' && {{ (int)($task->assignee_id ?? 0) }} === me)
                                    || (filter === 'unassigned' && {{ $task->assignee_id ? 'false' : 'true' }})
                                 "
                                 x-transition.opacity
                                 x-data="{ openTask: false }"
                                 @keydown.escape.window="openTask = false">

                                {{-- Card body --}}
                                <div class="p-3">
                                    <div class="flex items-start justify-between gap-3">

                                        {{-- Title --}}
                                        <button type="button"
                                                class="min-w-0 flex-1 text-left"
                                                @click="openTask = true">
                                            <div class="font-semibold text-gray-900 truncate group-hover:underline">
                                                {{ $task->title }}
                                            </div>

                                            <div class="mt-1 text-xs text-gray-500">
                                                #{{ $task->id }}
                                                @if($task->created_at)
                                                    • {{ $task->created_at->format('d/m/Y H:i') }}
                                                @endif
                                            </div>
                                            @if(!$isDoneColumn && $task->due_date)
                                                @if($isOverdue)
                                                    <div class="mt-2 inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 border border-rose-200">
                                                        ⏰ Overdue
                                                    </div>
                                                    <div class="mt-1 text-xs text-rose-700 font-semibold">
                                                        Due: {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                                                    </div>
                                                @elseif($isDueToday)
                                                    <div class="mt-2 inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                                        📅 Due today
                                                    </div>
                                                    <div class="mt-1 text-xs text-amber-800 font-semibold">
                                                        Due: {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                                                    </div>
                                                @else
                                                    <div class="mt-1 text-xs text-gray-500">
                                                        Due: {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                                                    </div>
                                                @endif
                                            @endif

                                            {{-- ✅ Creator badge --}}
                                            <div class="mt-2">
                                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full
                                                    {{ (int)($task->created_by ?? 0) === (int)auth()->id()
                                                        ? 'bg-blue-50 text-blue-700 border border-blue-100'
                                                        : 'bg-gray-50 text-gray-700 border border-gray-200' }}">
                                                    ✍️ {{ $task->creator?->name ?? 'Unknown' }}
                                                </span>
                                            </div>
                                        </button>

                                        {{-- Priority + Assignee badge (โชว์บนการ์ดอย่างเดียว) --}}
                                        <div class="shrink-0 flex flex-col items-end gap-2">
                                            <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-full {{ $pm['class'] }} ring-1 ring-black/5">
                                                <span class="font-semibold">{{ $pm['label'] }}</span>
                                            </span>

                                            @if($task->assignee)
                                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                    <span>👤</span>
                                                    <span class="font-semibold">{{ $task->assignee->name }}</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-gray-50 text-gray-600 border border-gray-200">
                                                    <span>—</span>
                                                    <span class="font-semibold">Unassigned</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- ✅ Card footer: Status dropdown (ใช้ JS ไม่ submit form แล้ว) --}}
                                <div class="px-3 py-2 bg-gray-50 border-t border-gray-200">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="text-xs text-gray-500">
                                            Status
                                        </div>

                                        <select
                                            class="border rounded-lg p-2 text-sm bg-white task-status-select"
                                            data-task-id="{{ $task->id }}"
                                            data-from-column-id="{{ $task->column_id }}"
                                        >
                                            @foreach($project->board->columns as $c)
                                                <option value="{{ $c->id }}" @selected($c->id == $task->column_id)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- ✅ Task Detail Modal --}}
                                <div x-show="openTask" x-cloak
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                                     @click.self="openTask = false">

                                    <div class="bg-white rounded-xl shadow w-full max-w-2xl max-h-[85vh] overflow-auto">
                                        {{-- Header --}}
                                        <div class="p-6 border-b flex justify-between items-center">
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900">{{ $task->title }}</h3>
                                                <div class="text-xs text-gray-500 mt-1">#{{ $task->id }}</div>
                                            </div>
                                            <button type="button"
                                                    class="text-gray-500 hover:text-gray-700"
                                                    @click="openTask = false">
                                                ✕
                                            </button>
                                        </div>

                                        <div class="p-6 space-y-5">

                                            {{-- UPDATE TASK --}}
                                            <form method="POST" action="{{ route('tasks.update', $task->id) }}">
                                                @csrf
                                                @method('PATCH')

                                                <div class="grid grid-cols-1 gap-4">
                                                    <div>
                                                        <label class="text-sm font-semibold text-gray-700">Title</label>
                                                        <input name="title"
                                                               class="border rounded-lg p-2 w-full"
                                                               value="{{ $task->title }}"
                                                               required>
                                                    </div>

                                                    <div>
                                                        <label class="text-sm font-semibold text-gray-700">Description</label>
                                                        <textarea name="description"
                                                                  rows="4"
                                                                  class="border rounded-lg p-2 w-full"
                                                                  placeholder="รายละเอียดงาน...">{{ $task->description }}</textarea>
                                                    </div>

                                                    <div>
                                                        <label class="text-sm font-semibold text-gray-700">Due date</label>
                                                        <input type="date"
                                                            name="due_date"
                                                            class="border rounded-lg p-2 w-full"
                                                            value="{{ optional($task->due_date)->format('Y-m-d') }}">
                                                    </div>

                                                    <div class="flex justify-end">
                                                        <button type="submit"
                                                                class="!bg-blue-600 hover:!bg-blue-700 !text-white px-4 py-2 rounded-lg font-semibold">
                                                            Save
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            {{-- Assignee --}}
                                            <div>
                                                <label class="text-sm font-semibold text-gray-700">Assignee</label>
                                                <form method="POST"
                                                      action="{{ route('tasks.assign', $task->id) }}"
                                                      class="mt-2 flex gap-2">
                                                    @csrf
                                                    <select name="assignee_id" class="border rounded-lg p-2 flex-1">
                                                        <option value="">— Unassigned —</option>
                                                        @foreach($projectMembers as $pm2)
                                                            <option value="{{ $pm2->user->id }}"
                                                                @selected($task->assignee_id === $pm2->user->id)>
                                                                {{ $pm2->user->name }} ({{ $pm2->role }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button class="!bg-blue-600 hover:!bg-blue-700 !text-white px-4 py-2 rounded-lg">
                                                        Save
                                                    </button>
                                                </form>
                                            </div>

                                            {{-- Priority --}}
                                            <div>
                                                <label class="text-sm font-semibold text-gray-700">Priority</label>
                                                <form method="POST"
                                                      action="{{ route('tasks.priority', $task->id) }}"
                                                      class="mt-2 flex gap-2">
                                                    @csrf
                                                    <select name="priority" class="border rounded-lg p-2 flex-1">
                                                        <option value="low" @selected(($task->priority ?? 'normal') === 'low')>Low</option>
                                                        <option value="normal" @selected(($task->priority ?? 'normal') === 'normal')>Normal</option>
                                                        <option value="high" @selected(($task->priority ?? 'normal') === 'high')>High</option>
                                                        <option value="urgent" @selected(($task->priority ?? 'normal') === 'urgent')>Urgent</option>
                                                    </select>
                                                    <button class="!bg-blue-600 hover:!bg-blue-700 !text-white px-4 py-2 rounded-lg">
                                                        Save
                                                    </button>
                                                </form>
                                            </div>

                                            {{-- Comments list --}}
                                            <div class="border rounded-lg p-3 bg-gray-50 max-h-60 overflow-auto">
                                                @if($task->comments->isEmpty())
                                                    <div class="text-gray-500 text-sm">ยังไม่มีคอมเมนต์</div>
                                                @else
                                                    <div class="space-y-2">
                                                        @foreach($task->comments as $cmt)
                                                            <div class="bg-white border rounded-lg p-2" x-data="{ edit:false }">
                                                                <div class="flex justify-between items-start">
                                                                    <div class="text-sm font-semibold">
                                                                        {{ $cmt->user?->name ?? 'Unknown' }}
                                                                        <span class="text-gray-500 font-normal">
                                                                            • {{ $cmt->created_at->format('d/m/Y H:i') }}
                                                                        </span>
                                                                    </div>

                                                                    @if($cmt->user_id === auth()->id())
                                                                        <div class="flex gap-2 text-sm">
                                                                            <button type="button"
                                                                                    class="text-blue-600 hover:underline"
                                                                                    @click="edit = true">
                                                                                แก้ไข
                                                                            </button>

                                                                            <form method="POST"
                                                                                  action="{{ route('comments.destroy', $cmt->id) }}"
                                                                                  onsubmit="return confirm('ลบคอมเมนต์นี้?')">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="text-red-600 hover:underline">
                                                                                    ลบ
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div x-show="!edit" class="text-sm text-gray-700 mt-1">
                                                                    {{ $cmt->comment }}
                                                                </div>

                                                                @if($cmt->user_id === auth()->id())
                                                                    <form x-show="edit"
                                                                          method="POST"
                                                                          action="{{ route('comments.update', $cmt->id) }}"
                                                                          class="mt-2">
                                                                        @csrf
                                                                        @method('PATCH')

                                                                        <textarea name="comment"
                                                                                  class="border rounded-lg p-2 w-full text-sm"
                                                                                  rows="2"
                                                                                  required>{{ $cmt->comment }}</textarea>

                                                                        <div class="mt-2 flex gap-2 justify-end">
                                                                            <button type="button"
                                                                                    class="border px-3 py-1 rounded-lg text-sm"
                                                                                    @click="edit = false">
                                                                                Cancel
                                                                            </button>
                                                                            <button type="submit"
                                                                                    class="!bg-blue-600 hover:!bg-blue-700 !text-white px-3 py-1 rounded-lg text-sm">
                                                                                Save
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Attachments --}}
                                            <div>
                                                <div class="font-semibold mb-2">Attachments</div>

                                                <form method="POST"
                                                      action="{{ route('tasks.attachments.store', $task->id) }}"
                                                      enctype="multipart/form-data"
                                                      class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="file" name="file" class="border rounded-lg p-2 w-full" required>
                                                    <button type="submit"
                                                            class="!bg-purple-600 hover:!bg-purple-700 !text-white font-semibold px-4 py-2 rounded-lg shadow">
                                                        Upload
                                                    </button>
                                                </form>

                                                <div class="mt-3 space-y-2">
                                                    @if($task->attachments->isEmpty())
                                                        <div class="text-gray-500 text-sm">ยังไม่มีไฟล์แนบ</div>
                                                    @else
                                                        @foreach($task->attachments as $att)
                                                            <div class="bg-white border rounded-lg p-2 flex justify-between items-center">
                                                                <div class="text-sm">
                                                                    <a class="text-blue-600 hover:underline"
                                                                       href="{{ asset('storage/'.$att->file_path) }}"
                                                                       target="_blank">
                                                                        {{ $att->original_name }}
                                                                    </a>
                                                                    <div class="text-xs text-gray-500">
                                                                        by {{ $att->user?->name ?? 'Unknown' }}
                                                                        • {{ $att->created_at->format('d/m/Y H:i') }}
                                                                    </div>
                                                                </div>

                                                                @if($att->user_id === auth()->id())
                                                                    <form method="POST"
                                                                          action="{{ route('attachments.destroy', $att->id) }}"
                                                                          onsubmit="return confirm('ลบไฟล์นี้?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button class="text-red-600 hover:underline text-sm" type="submit">
                                                                            ลบ
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                                {{-- Add comment (with @mention autocomplete) --}}
                                                @php
                                                    $mentionUsers = $projectMembers
                                                        ->map(fn($pm) => [
                                                            'id' => $pm->user->id,
                                                            'name' => $pm->user->name ?? $pm->user->email,
                                                            'email' => $pm->user->email,
                                                        ])
                                                        ->unique('id')
                                                        ->values();
                                                @endphp

                                                <form method="POST"
                                                    action="{{ route('tasks.comments.store', $task->id) }}">
                                                    @csrf

                                                    <div
                                                        x-data='mentionBox(@json($mentionUsers))' class="relative">

                                                        class="relative"
                                                    >
                                                        <textarea
                                                            name="comment"
                                                            rows="3"
                                                            class="border rounded-lg p-2 w-full"
                                                            placeholder="พิมพ์คอมเมนต์... พิมพ์ @ เพื่อ mention"
                                                            required
                                                            x-ref="ta"
                                                            x-model="text"
                                                            @input="onInput"
                                                            @keydown="onKeydown"
                                                            @click="onInput"
                                                        ></textarea>

                                                        {{-- Dropdown --}}
                                                        <div
                                                            x-show="open && filtered.length > 0"
                                                            x-cloak
                                                            class="absolute z-50 mt-1 w-full max-h-56 overflow-auto bg-white border rounded-lg shadow"
                                                            @mousedown.prevent
                                                        >
                                                            <template x-for="(u, idx) in filtered" :key="u.id">
                                                                <button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between"
                                                                    :class="idx === activeIndex ? 'bg-gray-100' : ''"
                                                                    @click="choose(idx)"
                                                                >
                                                                    <span class="font-semibold text-gray-900" x-text="u.name"></span>
                                                                    <span class="text-xs text-gray-500" x-text="u.email"></span>
                                                                </button>
                                                            </template>
                                                        </div>

                                                        <div x-show="open && filtered.length === 0" x-cloak
                                                            class="absolute z-50 mt-1 w-full bg-white border rounded-lg shadow px-3 py-2 text-sm text-gray-500">
                                                            ไม่พบผู้ใช้
                                                        </div>
                                                    </div>

                                                    <div class="mt-3 flex justify-end gap-2">
                                                        <button type="button"
                                                                class="border px-4 py-2 rounded-lg"
                                                                @click="openTask = false">
                                                            Close
                                                        </button>
                                                        <button type="submit"
                                                                class="!bg-blue-600 hover:!bg-blue-700 !text-white font-semibold px-4 py-2 rounded-lg shadow">
                                                            Comment
                                                        </button>
                                                    </div>
                                                </form>

                                            {{-- DELETE TASK --}}
                                            <form method="POST"
                                                  action="{{ route('tasks.destroy', $task->id) }}"
                                                  class="pt-4 border-t"
                                                  onsubmit="return confirm('ลบ Task นี้?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="text-red-600 hover:underline text-sm">
                                                    🗑 Delete Task
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Empty state when filter hides everything --}}
                        <div x-show="visibleCount === 0" x-cloak
                             class="text-sm text-gray-500 border border-dashed rounded-xl p-3">
                            ไม่มีงานตามตัวกรองนี้
                        </div>

                        {{-- Empty state when column has no tasks at all (server-side) --}}
                        @if($col->tasks->isEmpty())
                            <div class="text-sm text-gray-500">
                                ยังไม่มีงานในคอลัมน์นี้
                            </div>
                        @endif
                    </div>

                    {{-- Create Task Modal --}}
                    <div x-show="open" x-cloak x-transition.opacity
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                         @click.self="open = false"
                         @keydown.escape.window="open = false">

                        <div class="bg-white rounded-xl shadow p-6 w-full max-w-md">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">Create Task ({{ $col->name }})</h3>

                                <button type="button"
                                        class="text-gray-500 hover:text-gray-700"
                                        @click="open = false">
                                    ✕
                                </button>
                            </div>

                            <form method="POST" action="{{ route('tasks.store', $col->id) }}">
                                @csrf

                                <label class="block text-sm text-gray-700 mb-1">Task title</label>
                                <input name="title"
                                       class="border rounded-lg p-2 w-full"
                                       placeholder="Enter task title..."
                                       required
                                       autofocus>

                                <div class="mt-4 flex justify-end gap-2">
                                    <button type="button"
                                            class="border px-4 py-2 rounded-lg"
                                            @click="open = false">
                                        Cancel
                                    </button>

                                    <button type="submit"
                                            class="!bg-blue-600 hover:!bg-blue-700 !text-white font-semibold px-4 py-2 rounded-lg shadow">
                                        Create
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        {{-- SortableJS --}}
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

        <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });

            function postMove(taskId, columnId, orderedTaskIds) {
                return fetch(`/tasks/${taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        column_id: columnId,
                        ordered_task_ids: orderedTaskIds
                    })
                }).then(res => {
                    if (!res.ok) throw new Error('Move failed');
                    return res.json().catch(() => ({}));
                });
            }

            // ===== STEP 2: helper สำหรับ sort DOM แบบไม่ต้อง refresh =====
            function isOverdue(dueStr) {
                if (!dueStr) return false;
                const due = new Date(dueStr + 'T00:00:00');
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return due < today;
            }

            function isDueToday(dueStr) {
                if (!dueStr) return false;
                const due = new Date(dueStr + 'T00:00:00');
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return due.getTime() === today.getTime();
            }

            function sortColumnDOM(listEl) {
                if (!listEl) return;

                const colName = (listEl.dataset.columnName || '').toLowerCase();
                const isDone = colName === 'done';

                const cards = Array.from(listEl.querySelectorAll('.task-card'));
                const originalIndex = new Map(cards.map((c, i) => [c, i])); // stable sort

                const key = (card) => {
                    const due = card.dataset.dueDate || '';

                    if (isDone) return [9, 0, originalIndex.get(card)];
                    if (isOverdue(due)) return [0, 0, originalIndex.get(card)];
                    if (isDueToday(due)) return [1, 0, originalIndex.get(card)];
                    if (due) return [2, new Date(due + 'T00:00:00').getTime(), originalIndex.get(card)];

                    return [3, 0, originalIndex.get(card)];
                };

                cards.sort((a, b) => {
                    const ka = key(a), kb = key(b);
                    for (let i = 0; i < ka.length; i++) {
                        if (ka[i] < kb[i]) return -1;
                        if (ka[i] > kb[i]) return 1;
                    }
                    return 0;
                });

                // append กลับเข้าไปตามลำดับใหม่
                cards.forEach(c => listEl.appendChild(c));

                // ถ้าย้ายเข้า Done -> ถอด highlight ทันที (ไม่ต้อง refresh)
                if (isDone) {
                    cards.forEach(card => {
                        card.classList.remove('!border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50');
                        card.classList.remove('!border-amber-500', 'ring-amber-200', 'bg-amber-50');
                    });
                }
            }

            function initSortable() {
                document.querySelectorAll('.task-list').forEach(list => {
                    new Sortable(list, {
                        group: 'tasks',
                        animation: 150,
                        onEnd: async function (evt) {
                            const toList = evt.to;
                            const fromList = evt.from;

                            const toColumnId = toList.dataset.columnId;
                            const movedTaskId = evt.item.dataset.taskId;

                            // อัปเดต dropdown ให้ตรงกับ column ใหม่
                            const select = evt.item.querySelector('.task-status-select');
                            if (select) select.value = toColumnId;

                            const orderedTaskIds = Array.from(toList.querySelectorAll('.task-card'))
                                .map(el => el.dataset.taskId);

                            try {
                                await postMove(movedTaskId, toColumnId, orderedTaskIds);

                                // ✅ sort DOM ทันที (ปลายทาง + ต้นทาง)
                                sortColumnDOM(toList);
                                if (fromList && fromList !== toList) sortColumnDOM(fromList);

                            } catch (e) {
                                alert('ย้ายงานไม่สำเร็จ');
                                location.reload();
                            }
                        }
                    });
                });
            }

            function initStatusSelect() {
                document.querySelectorAll('.task-status-select').forEach(select => {
                    select.addEventListener('change', async function () {
                        const taskId = this.dataset.taskId;
                        const toColumnId = this.value;

                        const card = document.querySelector(`.task-card[data-task-id="${taskId}"]`);
                        const toList = document.querySelector(`.task-list[data-column-id="${toColumnId}"]`);

                        if (!card || !toList) return;

                        // ย้าย DOM ไปท้ายคอลัมน์ปลายทาง
                        toList.appendChild(card);

                        // สร้าง ordered list ใหม่
                        const orderedTaskIds = Array.from(toList.querySelectorAll('.task-card'))
                            .map(el => el.dataset.taskId);

                        try {
                            await postMove(taskId, toColumnId, orderedTaskIds);

                            // ✅ sort DOM ทันที (ไม่ต้อง refresh)
                            sortColumnDOM(toList);

                        } catch (e) {
                            alert('ย้ายงานไม่สำเร็จ');
                            location.reload();
                        }
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                initSortable();
                initStatusSelect();

                // ✅ sort ตอนโหลดหน้า 1 ครั้ง (ให้ตรงกับกติกาเสมอ)
                document.querySelectorAll('.task-list').forEach(list => sortColumnDOM(list));
            });
        })();
        mentionBox();
        </script>

    </div>
    <script>
window.mentionBox = function(users) {
    return {
        users: users || [],
        text: '',
        open: false,
        filtered: [],
        activeIndex: 0,
        atIndex: -1,
        keyword: '',

        onInput() {
            const ta = this.$refs.ta;
            if (!ta) return;

            const pos = ta.selectionStart ?? 0;
            const before = this.text.slice(0, pos);

            const lastAt = before.lastIndexOf('@');
            if (lastAt === -1) { this.close(); return; }

            const charBefore = lastAt > 0 ? before[lastAt - 1] : ' ';
            if (/[A-Za-z0-9_]/.test(charBefore)) { this.close(); return; }

            const afterAt = before.slice(lastAt + 1);
            if (/\s/.test(afterAt)) { this.close(); return; }

            this.atIndex = lastAt;
            this.keyword = afterAt;

            const kw = this.keyword.toLowerCase();
            this.filtered = this.users
                .filter(u =>
                    (u.name || '').toLowerCase().includes(kw) ||
                    (u.email || '').toLowerCase().includes(kw)
                )
                .slice(0, 8);

            this.open = true;
            this.activeIndex = 0;
        },

        onKeydown(e) {
            if (!this.open) return;

            if (e.key === 'Escape') { e.preventDefault(); this.close(); return; }
            if (e.key === 'ArrowDown') { e.preventDefault(); this.activeIndex = Math.min(this.activeIndex + 1, this.filtered.length - 1); return; }
            if (e.key === 'ArrowUp') { e.preventDefault(); this.activeIndex = Math.max(this.activeIndex - 1, 0); return; }

            if (e.key === 'Enter') {
                e.preventDefault();
                this.choose(this.activeIndex);
            }
        },

        choose(idx) {
            const u = this.filtered[idx];
            if (!u) return;

            const ta = this.$refs.ta;
            const pos = ta.selectionStart ?? this.text.length;

            const before = this.text.slice(0, this.atIndex);
            const after = this.text.slice(pos);

            const mentionToken = '@' + (u.email || u.name); // ✅ แนะนำใช้ email ชัวร์สุด
            this.text = before + mentionToken + ' ' + after;

            this.$nextTick(() => {
                const newPos = (before + mentionToken + ' ').length;
                ta.focus();
                ta.setSelectionRange(newPos, newPos);
            });

            this.close();
        },

        close() {
            this.open = false;
            this.filtered = [];
            this.activeIndex = 0;
            this.atIndex = -1;
            this.keyword = '';
        }
    }
}
</script>

</x-app-layout>
