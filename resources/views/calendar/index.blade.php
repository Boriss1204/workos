<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Calendar</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 underline">← Dashboard</a>
        </div>
    </x-slot>

    <div class="p-6">
        <div class="bg-white border rounded-xl shadow-sm p-4">
            <div id="calendar"></div>

            <div class="mt-4 text-xs text-gray-500 flex flex-wrap gap-3">
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded" style="background:#2563eb"></span> Normal</span>
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded" style="background:#e11d48"></span> Overdue</span>
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded" style="background:#6b7280"></span> Done</span>
                <span class="ml-auto">คลิก event เพื่อไปที่ Board ของโปรเจกต์</span>
            </div>
        </div>
    </div>

    {{-- FullCalendar (CDN) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        // ✅ ใช้ dayGrid (ไม่มีแกนเวลา)
        initialView: 'dayGridMonth',
        height: 'auto',
        displayEventTime: false,

        headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        // ✅ week/day ต้องเป็น dayGridWeek/dayGridDay (ไม่ใช่ timeGrid)
        right: 'dayGridMonth,dayGridWeek,dayGridDay,listWeek'
        },

        // ✅ ดึง event ของคุณเหมือนเดิม
        events: '{{ route('calendar.events') }}',

        eventClick(info) {
        if (info.event.url) {
            info.jsEvent.preventDefault();
            window.location.href = info.event.url;
        }
        },
    });

    calendar.render();
    });
    </script>

</x-app-layout>
