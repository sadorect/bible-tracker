<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_reading_plans', 'current_participation_id')) {
            Schema::table('user_reading_plans', function (Blueprint $table) {
                $table->foreignId('current_participation_id')
                    ->nullable()
                    ->after('reading_plan_id')
                    ->constrained('reading_plan_participations')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_reading_plans', 'current_participation_id')) {
            Schema::table('user_reading_plans', function (Blueprint $table) {
                $table->dropConstrainedForeignId('current_participation_id');
            });
        }
    }
};
