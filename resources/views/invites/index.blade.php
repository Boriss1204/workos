<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Invites</h2>
            <a href="{{ route('workspaces.index') }}" class="text-sm underline text-gray-600">Back</a>
        </div>
    </x-slot>

    <div class="p-6 max-w-3xl mx-auto">
        @if(session('success'))
            <div class="mb-4 border rounded p-3 bg-green-50 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

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
            <div class="font-semibold mb-3">Your invitations</div>

            @if($invites->isEmpty())
                <div class="text-sm text-gray-600">ยังไม่มีคำเชิญ</div>
            @else
                <div class="space-y-3">
                    @foreach($invites as $inv)
                        <div class="border rounded p-3 flex justify-between items-center">
                            <div>
                                <div class="font-medium">
                                    {{ $inv->project?->name ?? 'Unknown Project' }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    role: <span class="font-semibold">{{ $inv->role }}</span>
                                    @if($inv->expires_at)
                                        • expires: {{ $inv->expires_at->format('d/m/Y H:i') }}
                                    @endif

                                </div>
                            </div>

                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('invites.accept', $inv->id) }}">
                                    @csrf
                                    <button class="!bg-blue-600 hover:!bg-blue-700 !text-white px-3 py-2 rounded text-sm">
                                        Accept
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('invites.decline', $inv->id) }}"
                                      onsubmit="return confirm('ปฏิเสธคำเชิญนี้?')">
                                    @csrf
                                    <button class="border px-3 py-2 rounded text-sm">
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
