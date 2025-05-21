<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'phone_number' => '07034531814',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('password'), // Change this to a secure password
        ]);

        $this->command->info('Admin user created with email: admin@example.com');
    }
}
