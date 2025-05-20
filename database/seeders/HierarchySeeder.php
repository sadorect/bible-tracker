<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hierarchy;
use App\Models\Clan;

class HierarchySeeder extends Seeder
{
    public function run()
    {
        // Create demo clan
        $clan = Clan::create([
            'name' => 'Demo Clan',
            'leader_id' => 1 // Will be created in UserSeeder
        ]);

        // Create hierarchies
        $platoon = Hierarchy::create([
            'name' => 'Platoon Alpha',
            'type' => 'platoon',
            'leader_id' => 2,
            'parent_id' => null
        ]);

        $squad = Hierarchy::create([
            'name' => 'Squad One',
            'type' => 'squad',
            'leader_id' => 3,
            'parent_id' => $platoon->id
        ]);

        $batch = Hierarchy::create([
            'name' => 'Batch 2024',
            'type' => 'batch',
            'leader_id' => 4,
            'parent_id' => $squad->id
        ]);

        $team = Hierarchy::create([
            'name' => 'Team Victory',
            'type' => 'team',
            'leader_id' => 5,
            'parent_id' => $batch->id
        ]);
    }
}
