<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 20)->default('todo');
            $table->string('priority', 10)->default('medium');
            $table->date('deadline')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['status', 'position']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
