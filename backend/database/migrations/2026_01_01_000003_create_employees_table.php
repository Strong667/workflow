<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 32)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('position')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
