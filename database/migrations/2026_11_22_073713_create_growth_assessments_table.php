<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growth_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('age_in_days');
            $table->decimal('wfa_z', 6, 2)->nullable();
            $table->decimal('lhfa_z', 6, 2)->nullable();
            $table->decimal('wfh_z', 6, 2)->nullable();
            $table->decimal('bfa_z', 6, 2)->nullable();
            $table->string('wfa_status', 40)->nullable();
            $table->string('lhfa_status', 40)->nullable();
            $table->string('wfh_status', 40)->nullable();
            $table->string('bfa_status', 40)->nullable();
            $table->string('overall_status', 60)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_assessments');
    }
};
