<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_online')->nullable()->after('email_verified_at');
            $table->index('last_online');
        });

        // Seed a starting point from the visit log so the column is not empty
        // for everyone until they next sign in.
        if (Schema::hasTable('site_visits')) {
            DB::statement('
                update users
                set last_online = (
                    select max(created_at) from site_visits where site_visits.user_id = users.id
                )
            ');
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_online']);
            $table->dropColumn('last_online');
        });
    }
};
