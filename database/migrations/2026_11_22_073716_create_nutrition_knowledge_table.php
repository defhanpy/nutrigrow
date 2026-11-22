<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('age_group', 30)->index();
            $table->string('title');
            $table->text('content');
            $table->json('local_food_examples')->nullable();
            $table->json('key_nutrients')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_knowledge');
    }
};
