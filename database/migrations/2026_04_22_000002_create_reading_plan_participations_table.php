<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reading_plan_participations')) {
            Schema::create('reading_plan_participations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reading_plan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reading_plan_invite_id')->nullable()->constrained('reading_plan_invites')->nullOnDelete();
                $table->unsignedInteger('participation_number')->default(1);
                $table->string('join_source', 20)->default('direct');
                $table->date('started_on');
                $table->date('ended_on')->nullable();
                $table->string('status', 20)->default('active');
                $table->json('summary')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('reading_plan_participations', function (Blueprint $table) {
            if (! Schema::hasIndex('reading_plan_participations', 'rpp_user_plan_cycle_idx')) {
                $table->index(['user_id', 'reading_plan_id', 'participation_number'], 'rpp_user_plan_cycle_idx');
            }

            if (! Schema::hasIndex('reading_plan_participations', 'rpp_user_status_idx')) {
                $table->index(['user_id', 'status'], 'rpp_user_status_idx');
            }

            if (! Schema::hasIndex('reading_plan_participations', 'rpp_plan_status_idx')) {
                $table->index(['reading_plan_id', 'status'], 'rpp_plan_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_plan_participations');
    }
};
