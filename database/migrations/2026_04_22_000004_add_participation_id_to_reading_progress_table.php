<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reading_progress', 'reading_plan_participation_id')) {
            Schema::table('reading_progress', function (Blueprint $table) {
                $table->foreignId('reading_plan_participation_id')
                    ->nullable()
                    ->after('reading_plan_id')
                    ->constrained('reading_plan_participations')
                    ->nullOnDelete();
            });
        } elseif (! $this->hasForeignKey('reading_progress', 'reading_progress_reading_plan_participation_id_foreign')) {
            Schema::table('reading_progress', function (Blueprint $table) {
                $table->foreign('reading_plan_participation_id', 'reading_progress_reading_plan_participation_id_foreign')
                    ->references('id')
                    ->on('reading_plan_participations')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasIndex('reading_progress', 'rp_user_participation_idx')) {
            Schema::table('reading_progress', function (Blueprint $table) {
                $table->index(['user_id', 'reading_plan_participation_id'], 'rp_user_participation_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('reading_progress', 'reading_plan_participation_id')) {
            return;
        }

        Schema::table('reading_progress', function (Blueprint $table) {
            if ($this->hasForeignKey('reading_progress', 'reading_progress_reading_plan_participation_id_foreign')) {
                $table->dropForeign('reading_progress_reading_plan_participation_id_foreign');
            }

            if (Schema::hasIndex('reading_progress', 'rp_user_participation_idx')) {
                $table->dropIndex('rp_user_participation_idx');
            }

            $table->dropColumn('reading_plan_participation_id');
        });
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $foreignKey) => ($foreignKey['name'] ?? null) === $name);
    }
};
