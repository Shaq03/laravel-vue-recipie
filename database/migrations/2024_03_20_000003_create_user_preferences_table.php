<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('preferred_cuisines')->nullable();
            $table->json('dietary_restrictions')->nullable();
            $table->string('cooking_skill_level')->default('beginner');
            $table->boolean('seasonal_preferences')->default(true);
            $table->json('favorite_ingredients')->nullable();
            $table->json('disliked_ingredients')->nullable();
            $table->json('cooking_history')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_preferences');
    }
}; 