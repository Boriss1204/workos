<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">
                Notifications
            </h2>

            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button class="text-sm text-gray-600 underline">
                    Mark all as read
                </button>
            </form>
        </div>
    </x-slot>

    <div class="p-6">
        <div class="bg-white border rounded-lg overflow-hidden">
            @if($notifications->isEmpty())
                <div class="p-4 text-gray-500">
                    ไม่มีการแจ้งเตือน
                </div>
            @else
                <ul class="divide-y">
                    @foreach($notifications as $noti)
                        <li class="p-4 flex justify-between items-start
                            {{ $noti->read_at ? 'bg-white' : 'bg-blue-50' }}">
                            
                            <div>
                                <div class="font-semibold text-gray-900">
                                    {{ $noti->title }}
                                </div>

                                @if($noti->body)
                                    <div class="text-sm text-gray-600 mt-1">
                                        {{ $noti->body }}
                                    </div>
                                @endif

                                <div class="text-xs text-gray-500 mt-2">
                                    {{ $noti->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                @if($noti->url)
                                    <a href="{{ $noti->url }}"
                                       class="text-blue-600 text-sm underline">
                                        เปิด
                                    </a>
                                @endif

                                @if(!$noti->read_at)
                                    <form method="POST"
                                          action="{{ route('notifications.read', $noti->id) }}">
                                        @csrf
                                        <button class="text-sm text-gray-600 underline">
                                            Mark read
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="p-3 border-t">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
