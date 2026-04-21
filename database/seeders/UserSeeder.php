<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();
        $password = Hash::make(self::DEFAULT_PASSWORD);

        $rows = collect($this->users())
            ->map(function (array $userData) use ($timestamp, $password) {
                return [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'phone_number' => $userData['phone_number'],
                    'password' => $password,
                    'role' => $userData['role'],
                    'email_verified_at' => $timestamp,
                    'remember_token' => Str::random(10),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->values()
            ->all();

        DB::table('users')->upsert(
            $rows,
            ['email'],
            ['name', 'phone_number', 'password', 'role', 'email_verified_at', 'remember_token', 'updated_at']
        );

        $memberCount = collect($rows)->where('role', User::ROLE_MEMBER)->count();

        $this->command?->info(
            'Users seeded for testing. Default password: '.self::DEFAULT_PASSWORD.' ('.$memberCount.' members ready for hierarchy assignment)'
        );
    }

    private function users(): array
    {
        return array_merge(
            $this->coreUsers(),
            $this->scaleLeaders(),
            $this->scaleMembers(),
        );
    }

    private function coreUsers(): array
    {
        return [
            ['name' => 'Clan Leader', 'email' => 'clan@example.com', 'phone_number' => '08000000001', 'role' => User::ROLE_CLAN_LEADER],
            ['name' => 'Platoon Leader', 'email' => 'platoon@example.com', 'phone_number' => '08000000002', 'role' => User::ROLE_PLATOON_LEADER],
            ['name' => 'Squad Leader', 'email' => 'squad@example.com', 'phone_number' => '08000000003', 'role' => User::ROLE_SQUAD_LEADER],
            ['name' => 'Batch Leader', 'email' => 'batch@example.com', 'phone_number' => '08000000004', 'role' => User::ROLE_BATCH_LEADER],
            ['name' => 'Team Alpha Leader', 'email' => 'team.alpha.leader@example.com', 'phone_number' => '08000000005', 'role' => User::ROLE_TEAM_LEADER],
            ['name' => 'Team Bravo Leader', 'email' => 'team.bravo.leader@example.com', 'phone_number' => '08000000006', 'role' => User::ROLE_TEAM_LEADER],

            ['name' => 'Alpha On Track', 'email' => 'alpha.ontrack@example.com', 'phone_number' => '08000000011', 'role' => User::ROLE_MEMBER],
            ['name' => 'Alpha Catch Up', 'email' => 'alpha.catchup@example.com', 'phone_number' => '08000000012', 'role' => User::ROLE_MEMBER],
            ['name' => 'Alpha Ahead', 'email' => 'alpha.ahead@example.com', 'phone_number' => '08000000013', 'role' => User::ROLE_MEMBER],
            ['name' => 'Alpha Training', 'email' => 'alpha.training@example.com', 'phone_number' => '08000000014', 'role' => User::ROLE_MEMBER],
            ['name' => 'Alpha Awaiting Start', 'email' => 'alpha.awaiting@example.com', 'phone_number' => '08000000015', 'role' => User::ROLE_MEMBER],
            ['name' => 'Alpha Idle', 'email' => 'alpha.idle@example.com', 'phone_number' => '08000000016', 'role' => User::ROLE_MEMBER],

            ['name' => 'Bravo On Track', 'email' => 'bravo.ontrack@example.com', 'phone_number' => '08000000021', 'role' => User::ROLE_MEMBER],
            ['name' => 'Bravo Training', 'email' => 'bravo.training@example.com', 'phone_number' => '08000000022', 'role' => User::ROLE_MEMBER],
            ['name' => 'Bravo Ahead', 'email' => 'bravo.ahead@example.com', 'phone_number' => '08000000023', 'role' => User::ROLE_MEMBER],
        ];
    }

    private function scaleLeaders(): array
    {
        $leaders = [];

        for ($squad = 1; $squad <= 3; $squad++) {
            $leaders[] = $this->makeUserDefinition(
                "Scale Squad {$squad} Leader",
                "scale.squad.{$squad}.leader@example.com",
                User::ROLE_SQUAD_LEADER,
                1000 + $squad
            );

            for ($platoon = 1; $platoon <= 3; $platoon++) {
                $leaders[] = $this->makeUserDefinition(
                    "Scale S{$squad} Platoon {$platoon} Leader",
                    "scale.s{$squad}.p{$platoon}.leader@example.com",
                    User::ROLE_PLATOON_LEADER,
                    1100 + ($squad * 10) + $platoon
                );

                for ($batch = 1; $batch <= 3; $batch++) {
                    $leaders[] = $this->makeUserDefinition(
                        "Scale S{$squad}P{$platoon} Batch {$batch} Leader",
                        "scale.s{$squad}.p{$platoon}.b{$batch}.leader@example.com",
                        User::ROLE_BATCH_LEADER,
                        2000 + ($squad * 100) + ($platoon * 10) + $batch
                    );

                    foreach (range('A', 'D') as $teamIndex => $teamCode) {
                        $leaders[] = $this->makeUserDefinition(
                            "Scale S{$squad}P{$platoon}B{$batch} Team {$teamCode} Leader",
                            "scale.s{$squad}.p{$platoon}.b{$batch}.t".strtolower($teamCode).'.leader@example.com',
                            User::ROLE_TEAM_LEADER,
                            3000 + ($squad * 1000) + ($platoon * 100) + ($batch * 10) + $teamIndex
                        );
                    }
                }
            }
        }

        return $leaders;
    }

    private function scaleMembers(): array
    {
        $members = [];

        for ($index = 1; $index <= 1000; $index++) {
            $members[] = $this->makeUserDefinition(
                'Member '.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'member'.str_pad((string) $index, 4, '0', STR_PAD_LEFT).'@example.com',
                User::ROLE_MEMBER,
                4000 + $index
            );
        }

        return $members;
    }

    private function makeUserDefinition(string $name, string $email, string $role, int $phoneSuffix): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'phone_number' => sprintf('081%08d', $phoneSuffix),
            'role' => $role,
        ];
    }
}
