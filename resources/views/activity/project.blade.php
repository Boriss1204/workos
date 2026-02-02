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

    <div class="p-6">
        <div class="bg-white border rounded-lg overflow-hidden">
            @if($logs->isEmpty())
                <div class="p-4 text-gray-500">ยังไม่มีกิจกรรม</div>
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
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3 text-gray-600">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="font-medium">
                                    {{ $log->user?->name ?? 'ไม่ทราบผู้ใช้' }}
                                </td>
                                <td class="font-semibold text-blue-700">
                                    {{ activity_label($log->action) }}
                                </td>
                                <td class="text-gray-700">
                                    {{ $log->details }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
