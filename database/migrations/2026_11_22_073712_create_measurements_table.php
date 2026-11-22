<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('measured_at')->index();
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('height_cm', 5, 2);
            $table->decimal('lila_cm', 4, 2)->nullable();
            $table->string('method', 20)->default('terlentang');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
