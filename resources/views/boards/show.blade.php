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

    <div class="ml-auto text-sm text-gray-500">
        Filter(DB):
        <span class="font-semibold text-gray-700">
            {{ ($priority ?? '') ?: 'priority=all' }}
            {{ ($creator ?? '') ? ' • creator='.$creator : ' • creator=all' }}
        </span>
    </div>
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

            <div class="ml-auto text-sm text-gray-500">
                Showing:
                <span class="font-semibold text-gray-700" x-text="filter"></span>
            </div>
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

                    {{-- Tasks list --}}
                    <div class="space-y-3" x-ref="tasksWrap"
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

                            {{-- ✅ Card --}}
                            <div data-task-card
                                 class="group border border-gray-200 rounded-xl bg-white shadow-sm hover:shadow-md transition overflow-hidden"
                                 x-show="
                                    filter === 'all'
                                    || (filter === 'my' && {{ (int)($task->assignee_id ?? 0) }} === me)
                                    || (filter === 'unassigned' && {{ $task->assignee_id ? 'false' : 'true' }})
                                 "
                                 x-transition.opacity
                                 x-data="{
                                    openTask: false
                                 }"
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

                                {{-- Card footer: Move status --}}
                                <div class="px-3 py-2 bg-gray-50 border-t border-gray-200">
                                    <form method="POST"
                                          action="{{ route('tasks.move', $task->id) }}"
                                          class="flex items-center justify-between gap-2">
                                        @csrf

                                        <div class="text-xs text-gray-500">
                                            Status
                                        </div>

                                        <select name="column_id"
                                                class="border rounded-lg p-2 text-sm bg-white"
                                                onchange="this.form.submit()">
                                            @foreach($project->board->columns as $c)
                                                <option value="{{ $c->id }}" @selected($c->id == $task->column_id)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>

                                {{-- ✅ Task Detail Modal (ทุกอย่างอยู่ “ข้างใน” modal เท่านั้น) --}}
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

                                            {{-- Add comment --}}
                                            <form method="POST"
                                                  action="{{ route('tasks.comments.store', $task->id) }}">
                                                @csrf

                                                <textarea name="comment" rows="3"
                                                          class="border rounded-lg p-2 w-full"
                                                          placeholder="พิมพ์คอมเมนต์..." required></textarea>

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

                                            {{-- DELETE TASK (แยก form ไม่ซ้อน) --}}
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
    </div>
</x-app-layout>
