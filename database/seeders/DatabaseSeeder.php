<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            DemoWorkspaceSeeder::class,
            DemoProjectSeeder::class,
            DemoTaskSeeder::class,
            DemoCommentSeeder::class,
            DemoActivitySeeder::class,
            DemoNotificationSeeder::class,
        ]);
    }
}
