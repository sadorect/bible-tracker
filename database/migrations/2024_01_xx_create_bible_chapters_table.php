<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bible_chapters', function (Blueprint $table) {
          $table->id();
          $table->string('book_name');
          $table->integer('chapter_number');
          $table->integer('day_number');
          $table->enum('testament', ['old', 'new']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bible_chapters');
    }
};
