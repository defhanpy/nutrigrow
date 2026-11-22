<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('ortu')->index()->after('password');
            $table->foreignId('facility_id')->nullable()->after('role')->constrained('health_facilities')->nullOnDelete();
            $table->string('status', 20)->default('active')->after('facility_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_id');
            $table->dropColumn(['role', 'status']);
        });
    }
};
