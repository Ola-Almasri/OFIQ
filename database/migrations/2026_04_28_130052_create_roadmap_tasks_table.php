<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadmap_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('career_path_id')
                ->constrained()
                ->onDelete('cascade');

            $table->integer('month_number')->default(1);
            $table->integer('week_number')->default(1);
            $table->integer('day_number')->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('task_type')->nullable(); 
            $table->string('duration')->nullable();

            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_tasks');
    }
};