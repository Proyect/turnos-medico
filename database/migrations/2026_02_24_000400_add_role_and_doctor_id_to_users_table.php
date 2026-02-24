<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('email');
            $table->foreignId('doctor_id')
                ->nullable()
                ->after('role')
                ->constrained('doctors')
                ->nullOnDelete()
                ->unique();
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropUnique('users_doctor_id_unique');
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'doctor_id']);
        });
    }
};
