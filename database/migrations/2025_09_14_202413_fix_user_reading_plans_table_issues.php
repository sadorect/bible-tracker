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
        Schema::table('user_reading_plans', function (Blueprint $table) {
            // Add joined_date column with default value to prevent NOT NULL constraint errors
            if (!Schema::hasColumn('user_reading_plans', 'joined_date')) {
                $table->date('joined_date')->default(now()->toDateString())->after('reading_plan_id');
            }
            
            // Ensure current_day has a proper default
            if (Schema::hasColumn('user_reading_plans', 'current_day')) {
                $table->integer('current_day')->default(1)->change();
            } else {
                $table->integer('current_day')->default(1)->after('joined_date');
            }
            
            // Ensure current_streak has a proper default
            if (!Schema::hasColumn('user_reading_plans', 'current_streak')) {
                $table->integer('current_streak')->default(0)->after('current_day');
            }
            
            // Ensure completion_rate has a proper default
            if (!Schema::hasColumn('user_reading_plans', 'completion_rate')) {
                $table->decimal('completion_rate', 5, 2)->default(0.00)->after('current_streak');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_reading_plans', function (Blueprint $table) {
            $table->dropColumn(['joined_date', 'current_streak', 'completion_rate']);
        });
    }
};
