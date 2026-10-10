<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roadmap_overviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('career_path_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->text('summary')->nullable();

            $table->json('stages')->nullable();
            $table->json('skills')->nullable();
            $table->json('outcomes')->nullable();

            $table->string('estimated_duration')->nullable();
            $table->string('level')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_overviews');
    }
};
