<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // معلومات شخصية
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['ذكر', 'أنثى'])->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            
            // معلومات أكاديمية
            $table->string('education_level')->nullable();
            $table->string('major')->nullable();
            $table->string('university')->nullable();
            $table->decimal('gpa', 5, 2)->nullable();  
            $table->string('grading_system')->nullable();
            $table->string('graduation_year')->nullable();
            
            // مهارات واهتمامات
            $table->text('skills')->nullable();
            $table->string('english_level')->nullable();
            $table->text('experience')->nullable();
            $table->text('interests')->nullable();
            $table->text('goal')->nullable();
            $table->string('work_environment')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assessments');
    }
};