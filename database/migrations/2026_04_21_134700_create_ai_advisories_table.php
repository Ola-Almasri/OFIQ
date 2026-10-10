<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_advisories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->text('prompt_asked');
            $table->text('ai_response');
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('ai_advisories');
    }
};