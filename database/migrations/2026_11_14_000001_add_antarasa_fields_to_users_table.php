<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->string('phone', 30)->nullable()->unique();
            $table->string('role', 20)->default('konsumen')->index();
            $table->string('account_status', 20)->default('active')->index();
            $table->boolean('must_change_password')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['phone']);
            $table->dropIndex(['role']);
            $table->dropIndex(['account_status']);
            $table->dropColumn(['username', 'phone', 'role', 'account_status', 'must_change_password']);
        });
    }
};
