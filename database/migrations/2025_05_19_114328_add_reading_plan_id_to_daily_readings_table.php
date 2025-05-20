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
            $table->foreignId('reading_plan_id')->after('id')->constrained()->onDelete('cascade');
        
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
