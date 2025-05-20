<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ReadingProgress;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        // Create a demo hierarchy
        $platoonLeader = User::create([
            'name' => 'John Platoon',
            'email' => 'platoon@example.com',
            'phone_number' => '1234567890',
            'password' => bcrypt('password'),
            'role' => 'platoon_leader'
        ]);

        $squadLeader = User::create([
            'name' => 'Mike Squad',
            'email' => 'squad@example.com',
            'phone_number' => '1234567891',
            'password' => bcrypt('password'),
            'role' => 'squad_leader'
        ]);

        // Create 5 demo members with random progress
        for ($i = 1; $i <= 10; $i++) {
            $member = User::create([
                'name' => "Member $i",
                'email' => "member$i@example.com",
                'phone_number' => "123456789$i",
                'password' => bcrypt('password'),
                'role' => 'member'
            ]);

            // Create reading progress for last 30 days
            for ($day = 1; $day <= 30; $day++) {
                ReadingProgress::create([
                    'user_id' => $member->id,
                    'day_number' => $day,
                    'is_completed' => rand(0, 1),
                    'completed_at' => now()->subDays(rand(0, 30))
                ]);
            }
        }
    }
}
