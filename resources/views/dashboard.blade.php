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
</x-app-layout>
