<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_advisories', function (Blueprint $table) {
            // التحقق من وجود المفتاح الأجنبي قبل محاولة حذفه
            $foreignKeys = $this->getForeignKeys('ai_advisories');

            if (in_array('ai_advisories_user_id_foreign', $foreignKeys)) {
                $table->dropForeign('ai_advisories_user_id_foreign');
            }

            // إضافة مفتاح أجنبي جديد مع cascade delete
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // ملاحظة:
            // تم تعطيل هذا السطر لأن unique على user_id + type موجود مسبقًا
            // من migration آخر، ولو تركناه سيظهر خطأ Duplicate key name.
            // $table->unique(['user_id', 'type'], 'ai_advisories_user_id_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ai_advisories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            // تم تعطيله لأنه لم يتم إنشاؤه داخل هذا الملف
            // $table->dropUnique('ai_advisories_user_id_type_unique');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    private function getForeignKeys($table)
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();
        $tableName = $table;

        $result = $conn->select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$dbName, $tableName]);

        return array_map(function ($item) {
            return $item->CONSTRAINT_NAME;
        }, $result);
    }
};