<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('career_path_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->json('answers')->nullable();

            $table->integer('total_score')->default(0);
            $table->integer('max_score')->default(0);
            $table->unsignedTinyInteger('percentage')->default(0);

            $table->string('level')->nullable(); 
            // مبتدئ، متوسط، متقدم

            $table->integer('unknown_answers_count')->default(0);

            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();

            $table->text('analysis')->nullable();
            $table->text('recommendation')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_results');
    }
};