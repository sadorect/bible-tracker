<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('daily_readings')) {
            Schema::table('daily_readings', function (Blueprint $table) {
                if (!Schema::hasColumn('daily_readings', 'day_number')) {
                    $table->integer('day_number');
                }
                if (!Schema::hasColumn('daily_readings', 'book_start')) {
                    $table->string('book_start');
                }
                if (!Schema::hasColumn('daily_readings', 'chapter_start')) {
                    $table->integer('chapter_start');
                }
                if (!Schema::hasColumn('daily_readings', 'book_end')) {
                    $table->string('book_end');
                }
                if (!Schema::hasColumn('daily_readings', 'chapter_end')) {
                    $table->integer('chapter_end');
                }
                if (!Schema::hasColumn('daily_readings', 'is_break_day')) {
                    $table->boolean('is_break_day')->default(false);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: fields are part of the base table in fresh installs.
    }
};
