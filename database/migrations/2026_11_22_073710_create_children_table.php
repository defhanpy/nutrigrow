<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('registration_no', 30)->unique();
            $table->string('nik', 20)->nullable()->unique();
            $table->string('full_name');
            $table->date('date_of_birth');
            $table->string('sex', 1)->index();
            $table->foreignId('facility_id')->constrained('health_facilities')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('birth_weight', 5, 2)->nullable();
            $table->decimal('birth_height', 5, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['facility_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
