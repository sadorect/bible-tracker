<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * site-visits:prune and the admin dashboard both filter on created_at,
     * which was a full table scan without this index.
     */
    public function up(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->index('created_at', 'site_visits_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropIndex('site_visits_created_at_index');
        });
    }
};
