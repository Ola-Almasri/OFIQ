<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_paths', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('assessment_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();
            $table->text('reason')->nullable();

            $table->integer('match_percentage')->default(0);

            $table->json('required_skills')->nullable();

            $table->integer('path_order')->default(1);

            $table->enum('status', [
                'active',
                'locked',
                'completed'
            ])->default('locked');

            $table->integer('progress')->default(0);

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'path_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_paths');
    }
};