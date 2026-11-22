<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 20)->default('wali');
            $table->timestamps();

            $table->unique(['user_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
