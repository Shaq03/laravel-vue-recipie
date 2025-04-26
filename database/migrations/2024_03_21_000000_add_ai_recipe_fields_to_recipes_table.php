<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->integer('servings')->default(4)->after('cooking_time');
            $table->integer('preparation_time')->default(15)->after('servings');
            $table->integer('total_time')->default(45)->after('preparation_time');
            $table->integer('calories')->nullable()->after('total_time');
            $table->float('protein')->nullable()->after('calories');
            $table->float('carbs')->nullable()->after('protein');
            $table->float('fat')->nullable()->after('carbs');
            $table->float('fiber')->nullable()->after('fat');
            $table->float('sugar')->nullable()->after('fiber');
            $table->integer('sodium')->nullable()->after('sugar');
            $table->integer('cholesterol')->nullable()->after('sodium');
            $table->boolean('is_vegetarian')->default(false)->after('cholesterol');
            $table->boolean('is_vegan')->default(false)->after('is_vegetarian');
            $table->boolean('is_gluten_free')->default(false)->after('is_vegan');
            $table->boolean('is_dairy_free')->default(false)->after('is_gluten_free');
            $table->boolean('is_nut_free')->default(false)->after('is_dairy_free');
            $table->boolean('is_halal')->default(false)->after('is_nut_free');
            $table->boolean('is_kosher')->default(false)->after('is_halal');
            $table->boolean('seasonal')->default(false)->after('is_kosher');
            $table->integer('total_ratings')->default(0)->after('seasonal');
            $table->integer('total_favorites')->default(0)->after('total_ratings');
            $table->integer('total_views')->default(0)->after('total_favorites');
            $table->timestamp('last_cooked_at')->nullable()->after('total_views');
        });
    }

    public function down()
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn([
                'servings',
                'preparation_time',
                'total_time',
                'calories',
                'protein',
                'carbs',
                'fat',
                'fiber',
                'sugar',
                'sodium',
                'cholesterol',
                'is_vegetarian',
                'is_vegan',
                'is_gluten_free',
                'is_dairy_free',
                'is_nut_free',
                'is_halal',
                'is_kosher',
                'seasonal',
                'total_ratings',
                'total_favorites',
                'total_views',
                'last_cooked_at'
            ]);
        });
    }
}; 