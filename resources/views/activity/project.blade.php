<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">
                ประวัติกิจกรรม: {{ $project->name }}
            </h2>

            <a href="{{ route('projects.board', $project->id) }}"
               class="text-sm text-gray-600 underline">
                ← กลับไปที่ Board
            </a>
        </div>
    </x-slot>

    <div class="p-6 space-y-4">

        {{-- ================= FILTER BAR ================= --}}
        <form method="GET" class="flex flex-wrap items-end gap-3">
            {{-- Action --}}
            <div>
                <label class="text-sm font-semibold text-gray-700">กิจกรรม</label>
                <select name="action"
                        class="mt-1 border rounded-lg p-2 bg-white text-sm min-w-[200px]">
                    <option value="">ทั้งหมด</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}"
                            @selected(($action ?? '') === $a)>
                            {{ activity_label($a) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- User --}}
            <div>
                <label class="text-sm font-semibold text-gray-700">ผู้ใช้งาน</label>
                <select name="user"
                        class="mt-1 border rounded-lg p-2 bg-white text-sm min-w-[220px]">
                    <option value="">ทั้งหมด</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}"
                            @selected((string)($userId ?? '') === (string)$u->id)>
                            {{ $u->name ?? $u->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date from --}}
            <div>
                <label class="text-sm font-semibold text-gray-700">จากวันที่</label>
                <input type="date"
                       name="from"
                       value="{{ $from ?? '' }}"
                       class="mt-1 border rounded-lg p-2 bg-white text-sm">
            </div>

            {{-- Date to --}}
            <div>
                <label class="text-sm font-semibold text-gray-700">ถึงวันที่</label>
                <input type="date"
                       name="to"
                       value="{{ $to ?? '' }}"
                       class="mt-1 border rounded-lg p-2 bg-white text-sm">
            </div>

            {{-- Search --}}
            <div class="flex-1 min-w-[240px]">
                <label class="text-sm font-semibold text-gray-700">ค้นหา</label>
                <input type="text"
                       name="q"
                       value="{{ $q ?? '' }}"
                       placeholder="ค้นหาในรายละเอียด..."
                       class="mt-1 border rounded-lg p-2 bg-white text-sm w-full">
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
                Filter:
                <span class="font-semibold text-gray-700">
                    {{ ($action ?? '') ?: 'action=all' }}
                    {{ ($userId ?? '') ? ' • user='.$userId : ' • user=all' }}
                    {{ ($from ?? '') ? ' • from='.$from : '' }}
                    {{ ($to ?? '') ? ' • to='.$to : '' }}
                </span>
            </div>
        </form>

        {{-- ================= TABLE ================= --}}
        <div class="bg-white border rounded-lg overflow-hidden">
            @if($logs->isEmpty())
                <div class="p-4 text-gray-500">ไม่พบกิจกรรมตามตัวกรองนี้</div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr class="text-left">
                            <th class="p-3">เวลา</th>
                            <th>ผู้ใช้งาน</th>
                            <th>กิจกรรม</th>
                            <th>รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            @php
                                // map สีตามความสำคัญของ action
                                $rowClass = match($log->action) {
                                    'DELETE_TASK', 'REMOVE_MEMBER', 'INVITE_CANCEL' => 'bg-rose-50 hover:bg-rose-100',
                                    'SET_OVERDUE', 'OVERDUE', 'TASK_OVERDUE' => 'bg-amber-50 hover:bg-amber-100',
                                    default => 'hover:bg-gray-50',
                                };

                                $badgeClass = match($log->action) {
                                    'DELETE_TASK', 'REMOVE_MEMBER', 'INVITE_CANCEL' => 'bg-rose-100 text-rose-800 border border-rose-200',
                                    'SET_OVERDUE', 'OVERDUE', 'TASK_OVERDUE' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                    default => 'bg-blue-50 text-blue-800 border border-blue-100',
                                };

                                $icon = match($log->action) {
                                    'DELETE_TASK', 'REMOVE_MEMBER', 'INVITE_CANCEL' => '🗑️',
                                    'SET_OVERDUE', 'OVERDUE', 'TASK_OVERDUE' => '⏰',
                                    default => '📝',
                                };
                            @endphp

                            <tr class="border-t {{ $rowClass }}">
                                <td class="p-3 text-gray-600 whitespace-nowrap">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>

                                <td class="font-medium">
                                    {{ $log->user?->name ?? 'ไม่ทราบผู้ใช้' }}
                                </td>

                                <td class="whitespace-nowrap">
                                    <span class="inline-flex items-center gap-2 text-xs font-semibold px-2.5 py-1 rounded-full {{ $badgeClass }}">
                                        <span>{{ $icon }}</span>
                                        <span>{{ activity_label($log->action) }}</span>
                                    </span>
                                </td>

                                <td class="text-gray-700">
                                    {{ $log->details }}
                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>

                <div class="p-3 border-t">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
