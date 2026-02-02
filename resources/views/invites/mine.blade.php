<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">My Invites</h2>
    </x-slot>

    <div class="p-6 max-w-2xl mx-auto">
        @if($errors->any())
            <div class="mb-4 border rounded p-3 bg-red-50 text-red-700 text-sm">
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="border rounded bg-white p-4">
            @if($invites->isEmpty())
                <div class="text-sm text-gray-500">ไม่มีคำเชิญ</div>
            @else
                <div class="space-y-2">
                    @foreach($invites as $inv)
                        <div class="border rounded p-3 flex justify-between items-center">
                            <div>
                                <div class="font-medium">{{ $inv->project?->name ?? 'Project' }}</div>
                                <div class="text-sm text-gray-600">
                                    role: {{ $inv->role }}
                                    @if($inv->expires_at) • expires: {{ $inv->expires_at->format('d/m/Y H:i') }} @endif
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('invites.accept', $inv->id) }}">
                                    @csrf
                                    <button class="!bg-green-600 hover:!bg-green-700 !text-white px-4 py-2 rounded">
                                        Accept
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('invites.decline', $inv->id) }}">
                                    @csrf
                                    <button class="border px-4 py-2 rounded">
                                        Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
