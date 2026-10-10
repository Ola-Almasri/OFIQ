<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('assessments', 'selected_career')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->string('selected_career')->nullable()->after('work_environment');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessments', 'selected_career')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropColumn('selected_career');
            });
        }
    }
};