<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Clan Leader
        User::create([
            'name' => 'Clan Leader',
            'email' => 'clan@example.com',
            'phone_number' => '1234567890',
            'password' => Hash::make('password'),
            'role' => 'clan_leader',
        ]);

        // Platoon Leader
        User::create([
            'name' => 'Platoon Leader',
            'email' => 'platoon@example.com',
            'phone_number' => '1234567891',
            'password' => Hash::make('password'),
            'role' => 'platoon_leader',
            'hierarchy_id' => 1
        ]);

        // Squad Leader
        User::create([
            'name' => 'Squad Leader',
            'email' => 'squad@example.com',
            'phone_number' => '1234567892',
            'password' => Hash::make('password'),
            'role' => 'squad_leader',
            'hierarchy_id' => 2
        ]);

        // Batch Leader
        User::create([
            'name' => 'Batch Leader',
            'email' => 'batch@example.com',
            'phone_number' => '1234567893',
            'password' => Hash::make('password'),
            'role' => 'batch_leader',
            'hierarchy_id' => 3
        ]);

        // Team Leader
        User::create([
            'name' => 'Team Leader',
            'email' => 'team@example.com',
            'phone_number' => '1234567894',
            'password' => Hash::make('password'),
            'role' => 'team_leader',
            'hierarchy_id' => 4
        ]);

        // Create 5 regular members
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "Member $i",
                'email' => "member$i@example.com",
                'phone_number' => "123456789" . (4 + $i),
                'password' => Hash::make('password'),
                'role' => 'member',
                'hierarchy_id' => 4 // Assign to Team Victory
            ]);
        }
    }
}
