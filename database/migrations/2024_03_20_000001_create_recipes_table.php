<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->json('ingredients');
            $table->json('instructions');
            $table->integer('cooking_time');
            $table->string('difficulty');
            $table->json('cuisines')->nullable();
            $table->json('tags')->nullable();
            $table->json('nutritional_info')->nullable();
            $table->float('popularity_score')->default(0);
            $table->float('average_rating')->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipes');
    }
}; 