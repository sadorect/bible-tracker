<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\HierarchySeeder;
use Database\Seeders\BibleChapterSeeder;
use Database\Seeders\BibleReadingSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,         // First create users
            HierarchySeeder::class,    // Then create hierarchies
            BibleReadingSeeder::class, // Finally seed bible chapters
            AdminUserSeeder::class,
        ]);
    }
}
