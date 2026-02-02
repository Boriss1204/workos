<x-app-layout>
    @php
        $isOwner =
            $members->where('user_id', auth()->id())->where('role', 'owner')->count() > 0
            || (int)($project->created_by ?? 0) === (int)auth()->id();
    @endphp

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">
                Members: {{ $project->name }}
            </h2>

            <div class="flex gap-4 text-sm">
                <a class="underline text-gray-600" href="{{ url('/projects/'.$project->id.'/board') }}">
                    Back to Board
                </a>

                <a class="underline text-gray-600" href="{{ route('projects.index', $project->workspace_id) }}">
                    Back to Projects
                </a>
            </div>
        </div>
    </x-slot>

    <div class="p-6 max-w-3xl mx-auto">
        {{-- Errors --}}
        @if($errors->any())
            <div class="mb-4 border rounded p-3 bg-red-50 text-red-700 text-sm">
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Invite form (Owner only) --}}
        @if($isOwner)
            <div class="border rounded bg-white p-4 mb-6">
                <div class="font-semibold mb-3">Invite Member</div>

                <form method="POST"
                      action="{{ route('projects.invites.store', $project->id) }}"
                      class="flex gap-2 items-end">
                    @csrf

                    <div class="flex-1">
                        <label class="text-sm text-gray-700">Email</label>
                        <input name="email"
                               type="email"
                               class="border rounded p-2 w-full"
                               placeholder="user@email.com"
                               required>
                    </div>

                    <div>
                        <label class="text-sm text-gray-700">Role</label>
                        <select name="role" class="border rounded p-2">
                            <option value="member">member</option>
                            <option value="owner">owner</option>
                        </select>
                    </div>

                    <button class="!bg-blue-600 hover:!bg-blue-700 !text-white font-semibold px-4 py-2 rounded shadow">
                        Invite
                    </button>
                </form>
            </div>
        @endif
        @if($isOwner)
            <div class="border rounded bg-white p-4 mb-6">
                <div class="font-semibold mb-3">Pending Invites</div>

                @php
                    $pendingInvites = \App\Models\ProjectInvite::where('project_id', $project->id)
                        ->where('status', 'pending')
                        ->latest()
                        ->get();
                @endphp

                @if($pendingInvites->isEmpty())
                    <div class="text-sm text-gray-500">ไม่มีคำเชิญค้างอยู่</div>
                @else
                    <div class="space-y-2">
                        @foreach($pendingInvites as $inv)
                            <div class="border rounded p-3 flex justify-between items-center">
                                <div>
                                    <div class="font-medium">{{ $inv->email }}</div>
                                    <div class="text-sm text-gray-600">
                                        role: {{ $inv->role }}
                                        @if($inv->expires_at)
                                            • expires: {{ \Carbon\Carbon::parse($inv->expires_at)->format('d/m/Y H:i') }}
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('invites.cancel', $inv->id) }}"
                                    onsubmit="return confirm('ยกเลิกคำเชิญนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline">Cancel</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
        
        {{-- Leave project --}}
        @if(!$isOwner || $members->where('role','owner')->count() > 1)
            <div class="mb-4">
                <form method="POST"
                    action="{{ route('projects.leave', $project->id) }}"
                    onsubmit="return confirm('ออกจากโปรเจกต์นี้?')">
                    @csrf
                    <button class="border px-3 py-2 rounded text-sm">
                        Leave Project
                    </button>
                </form>
            </div>
        @endif

        {{-- Members list --}}
        <div class="border rounded bg-white p-4">
            <div class="font-semibold mb-3">Project Members</div>

            <div class="space-y-2">
                @foreach($members as $m)
                    <div class="border rounded p-3 flex justify-between items-center">
                        <div>
                            <div class="font-medium">{{ $m->user?->name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-600">{{ $m->user?->email }}</div>
                            <div class="text-xs text-gray-500 mt-1">role: {{ $m->role }}</div>
                        </div>

                        {{-- Actions (Owner only) --}}
                        @if($isOwner && $m->user_id !== auth()->id())
                            <div class="flex gap-2 items-center">
                                {{-- Change role --}}
                                <form method="POST"
                                      action="{{ route('project_members.update', $m->id) }}"
                                      class="flex gap-2 items-center">
                                    @csrf
                                    @method('PATCH')

                                    <select name="role" class="border rounded p-2">
                                        <option value="owner" @selected($m->role === 'owner')>owner</option>
                                        <option value="member" @selected($m->role === 'member')>member</option>
                                    </select>

                                    <button class="border px-3 py-2 rounded">
                                        Save
                                    </button>
                                </form>

                                {{-- Remove --}}
                                <form method="POST"
                                      action="{{ route('project_members.destroy', $m->id) }}"
                                      onsubmit="return confirm('ลบสมาชิกคนนี้ออกจากโปรเจกต์?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline px-2">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
