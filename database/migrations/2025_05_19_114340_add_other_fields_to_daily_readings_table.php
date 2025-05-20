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
        Schema::table('daily_readings', function (Blueprint $table) {
            $table->integer('day_number');
    $table->string('book_start');
    $table->integer('chapter_start');
    $table->string('book_end');
    $table->integer('chapter_end');
    $table->boolean('is_break_day')->default(false);
        
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_readings', function (Blueprint $table) {
            $table->dropForeign(['reading_plan_id']);
            $table->dropColumn('reading_plan_id');
        });
    }
};
