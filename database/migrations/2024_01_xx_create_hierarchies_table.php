<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hierarchies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['clan', 'platoon', 'squad', 'batch', 'team']);
            $table->foreignId('leader_id')->constrained('users');
            $table->foreignId('parent_id')->nullable()->constrained('hierarchies');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hierarchies');
    }
};
