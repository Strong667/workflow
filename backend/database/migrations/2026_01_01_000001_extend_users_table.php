<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('employee')->after('password');
            $table->string('avatar')->nullable()->after('role');
            $table->string('language', 5)->default('ru')->after('avatar');
            $table->string('theme', 10)->default('light')->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'avatar', 'language', 'theme']);
        });
    }
};
