<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {

        User::create([
            'name' => 'Owner',
            'email' => 'owner@demo.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Member',
            'email' => 'member@demo.com',
            'password' => Hash::make('password'),
        ]);
    }
}
