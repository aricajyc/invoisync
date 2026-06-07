<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@invoisync.com'],
            [
                'full_name' => 'System Administrator',
                'password' => Hash::make('password'),
                'phone_number' => '0000000000',
                'user_type' => 'Admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
