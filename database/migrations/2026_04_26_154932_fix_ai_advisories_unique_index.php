<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldIndexExists = collect(DB::select("
            SHOW INDEX FROM ai_advisories 
            WHERE Key_name = 'ai_advisories_user_id_unique'
        "))->isNotEmpty();

        if ($oldIndexExists) {
            DB::statement('ALTER TABLE ai_advisories DROP INDEX ai_advisories_user_id_unique');
        }

        $newIndexExists = collect(DB::select("
            SHOW INDEX FROM ai_advisories 
            WHERE Key_name = 'ai_advisories_user_id_type_unique'
        "))->isNotEmpty();

        if (!$newIndexExists) {
            DB::statement('ALTER TABLE ai_advisories ADD UNIQUE ai_advisories_user_id_type_unique (user_id, type)');
        }
    }

    public function down(): void
    {
        $newIndexExists = collect(DB::select("
            SHOW INDEX FROM ai_advisories 
            WHERE Key_name = 'ai_advisories_user_id_type_unique'
        "))->isNotEmpty();

        if ($newIndexExists) {
            DB::statement('ALTER TABLE ai_advisories DROP INDEX ai_advisories_user_id_type_unique');
        }
    }
};