<?php

namespace Database\Seeders;

use App\Models\Clan;
use App\Models\Hierarchy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HierarchySeeder extends Seeder
{
    private const SCALE_SQUADS = 3;

    private const SCALE_PLATOONS_PER_SQUAD = 3;

    private const SCALE_BATCHES_PER_PLATOON = 3;

    private const SCALE_TEAMS_PER_BATCH = 4;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIdsByEmail = User::query()->pluck('id', 'email');

        $clanLeader = User::where('email', 'clan@example.com')->firstOrFail();
        $platoonLeader = User::where('email', 'platoon@example.com')->firstOrFail();
        $squadLeader = User::where('email', 'squad@example.com')->firstOrFail();
        $batchLeader = User::where('email', 'batch@example.com')->firstOrFail();
        $teamAlphaLeader = User::where('email', 'team.alpha.leader@example.com')->firstOrFail();
        $teamBravoLeader = User::where('email', 'team.bravo.leader@example.com')->firstOrFail();

        Clan::updateOrCreate(
            ['name' => 'Demo Clan'],
            ['leader_id' => $clanLeader->id]
        );

        $clanHierarchy = Hierarchy::updateOrCreate(
            ['name' => 'Demo Clan', 'type' => 'clan'],
            ['leader_id' => $clanLeader->id, 'parent_id' => null]
        );

        $squad = Hierarchy::updateOrCreate(
            ['name' => 'Squad One', 'type' => 'squad'],
            ['leader_id' => $squadLeader->id, 'parent_id' => $clanHierarchy->id]
        );

        $platoon = Hierarchy::updateOrCreate(
            ['name' => 'Platoon Alpha', 'type' => 'platoon'],
            ['leader_id' => $platoonLeader->id, 'parent_id' => $squad->id]
        );

        $batch = Hierarchy::updateOrCreate(
            ['name' => 'Batch 2026', 'type' => 'batch'],
            ['leader_id' => $batchLeader->id, 'parent_id' => $platoon->id]
        );

        $teamAlpha = Hierarchy::updateOrCreate(
            ['name' => 'Team Alpha', 'type' => 'team'],
            ['leader_id' => $teamAlphaLeader->id, 'parent_id' => $batch->id]
        );

        $teamBravo = Hierarchy::updateOrCreate(
            ['name' => 'Team Bravo', 'type' => 'team'],
            ['leader_id' => $teamBravoLeader->id, 'parent_id' => $batch->id]
        );

        $this->assignUsersToHierarchy($clanHierarchy, [
            'clan@example.com',
        ]);

        $this->assignUsersToHierarchy($squad, [
            'squad@example.com',
        ]);

        $this->assignUsersToHierarchy($platoon, [
            'platoon@example.com',
        ]);

        $this->assignUsersToHierarchy($batch, [
            'batch@example.com',
        ]);

        $this->assignUsersToHierarchy($teamAlpha, [
            'team.alpha.leader@example.com',
            'alpha.ontrack@example.com',
            'alpha.catchup@example.com',
            'alpha.ahead@example.com',
            'alpha.training@example.com',
            'alpha.awaiting@example.com',
            'alpha.idle@example.com',
        ]);

        $this->assignUsersToHierarchy($teamBravo, [
            'team.bravo.leader@example.com',
            'bravo.ontrack@example.com',
            'bravo.training@example.com',
            'bravo.ahead@example.com',
        ]);

        $scaleTeams = $this->seedScaleStructure($clanHierarchy, $userIdsByEmail->all());
        $this->assignMassMembers($scaleTeams);

        $this->command?->info('Hierarchy seeded with demo teams plus expanded scale structure.');
    }

    private function assignUsersToHierarchy(Hierarchy $hierarchy, array $emails): void
    {
        User::whereIn('email', $emails)->update([
            'hierarchy_id' => $hierarchy->id,
        ]);
    }

    private function seedScaleStructure(Hierarchy $clanHierarchy, array $userIdsByEmail): array
    {
        $teamHierarchies = [];

        for ($squad = 1; $squad <= self::SCALE_SQUADS; $squad++) {
            $squadHierarchy = Hierarchy::updateOrCreate(
                ['name' => "Scale Squad {$squad}", 'type' => 'squad'],
                [
                    'leader_id' => $userIdsByEmail["scale.squad.{$squad}.leader@example.com"] ?? null,
                    'parent_id' => $clanHierarchy->id,
                ]
            );

            $this->assignUsersToHierarchy($squadHierarchy, ["scale.squad.{$squad}.leader@example.com"]);

            for ($platoon = 1; $platoon <= self::SCALE_PLATOONS_PER_SQUAD; $platoon++) {
                $platoonHierarchy = Hierarchy::updateOrCreate(
                    ['name' => "Scale S{$squad} Platoon {$platoon}", 'type' => 'platoon'],
                    [
                        'leader_id' => $userIdsByEmail["scale.s{$squad}.p{$platoon}.leader@example.com"] ?? null,
                        'parent_id' => $squadHierarchy->id,
                    ]
                );

                $this->assignUsersToHierarchy($platoonHierarchy, ["scale.s{$squad}.p{$platoon}.leader@example.com"]);

                for ($batch = 1; $batch <= self::SCALE_BATCHES_PER_PLATOON; $batch++) {
                    $batchHierarchy = Hierarchy::updateOrCreate(
                        ['name' => "Scale S{$squad}P{$platoon} Batch {$batch}", 'type' => 'batch'],
                        [
                            'leader_id' => $userIdsByEmail["scale.s{$squad}.p{$platoon}.b{$batch}.leader@example.com"] ?? null,
                            'parent_id' => $platoonHierarchy->id,
                        ]
                    );

                    $this->assignUsersToHierarchy($batchHierarchy, ["scale.s{$squad}.p{$platoon}.b{$batch}.leader@example.com"]);

                    foreach (range('A', 'D') as $teamCode) {
                        $teamHierarchy = Hierarchy::updateOrCreate(
                            ['name' => "Scale S{$squad}P{$platoon}B{$batch} Team {$teamCode}", 'type' => 'team'],
                            [
                                'leader_id' => $userIdsByEmail["scale.s{$squad}.p{$platoon}.b{$batch}.t".strtolower($teamCode).'.leader@example.com'] ?? null,
                                'parent_id' => $batchHierarchy->id,
                            ]
                        );

                        $this->assignUsersToHierarchy($teamHierarchy, ["scale.s{$squad}.p{$platoon}.b{$batch}.t".strtolower($teamCode).'.leader@example.com']);
                        $teamHierarchies[] = $teamHierarchy;
                    }
                }
            }
        }

        return $teamHierarchies;
    }

    private function assignMassMembers(array $teamHierarchies): void
    {
        if ($teamHierarchies === []) {
            return;
        }

        $teamIds = collect($teamHierarchies)->pluck('id')->values()->all();
        $memberIds = User::where('email', 'like', 'member%@example.com')
            ->orderBy('email')
            ->pluck('id')
            ->values();

        $updates = [];
        foreach ($memberIds as $index => $memberId) {
            $updates[$memberId] = $teamIds[$index % count($teamIds)];
        }

        foreach (array_chunk($updates, 250, true) as $chunk) {
            $cases = [];
            $ids = [];

            foreach ($chunk as $memberId => $hierarchyId) {
                $cases[] = "WHEN {$memberId} THEN {$hierarchyId}";
                $ids[] = $memberId;
            }

            DB::statement(
                'UPDATE users SET hierarchy_id = CASE id '.implode(' ', $cases).' END WHERE id IN ('.implode(',', $ids).')'
            );
        }
    }
}
