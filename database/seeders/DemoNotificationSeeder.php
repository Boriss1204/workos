<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'member@demo.com')->first();

        Notification::create([
            'user_id' => $user->id,
            'type'    => 'TASK_ASSIGNED',
            'title'   => 'คุณถูกมอบหมายงานใหม่',
            'body'    => '🔥 Overdue Task',
            'read_at' => null, // ✅ ยังไม่อ่าน
        ]);
    }
}
