<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InstallOfuq extends Command
{
    protected $signature = 'ofuq:install';
    protected $description = 'تثبيت منصة أفق بالكامل (قاعدة بيانات، جداول، بيانات تجريبية)';

    public function handle()
    {
        $this->info('🚀 بدء تثبيت منصة أفق...');
        $this->newLine();

        // ==============================================
        // 1. إنشاء ملف قاعدة البيانات
        // ==============================================
        $this->info('📁 1. إنشاء قاعدة البيانات...');
        
        $dbPath = database_path('database.sqlite');
        
        if (!File::exists($dbPath)) {
            File::put($dbPath, '');
            $this->info('   ✅ تم إنشاء ملف database.sqlite');
        } else {
            $this->info('   ⚠️ ملف قاعدة البيانات موجود مسبقاً');
        }
        
        $this->newLine();

        // ==============================================
        // 2. تشغيل المايجريشن (إنشاء الجداول)
        // ==============================================
        $this->info('📊 2. إنشاء جداول قاعدة البيانات...');
        
        Artisan::call('migrate:fresh', [
            '--force' => true,
        ]);
        
        $this->info('   ' . Artisan::output());
        $this->info('   ✅ تم إنشاء جميع الجداول');
        
        $this->newLine();

        // ==============================================
        // 3. تشغيل Seeders (بيانات تجريبية)
        // ==============================================
        $this->info('🌱 3. إضافة بيانات تجريبية...');
        
        Artisan::call('db:seed', [
            '--force' => true,
        ]);
        
        $this->info('   ' . Artisan::output());
        $this->info('   ✅ تم إضافة البيانات التجريبية');
        
        $this->newLine();

        // ==============================================
        // 4. إنشاء رابط التخزين
        // ==============================================
        $this->info('🔗 4. إنشاء رابط التخزين...');
        
        Artisan::call('storage:link');
        $this->info('   ' . Artisan::output());
        
        $this->newLine();

        // ==============================================
        // النتيجة النهائية
        // ==============================================
        $this->newLine();
        $this->info('═══════════════════════════════════════════════');
        $this->info('✅ تم تثبيت منصة أفق بنجاح!');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();
        
        $this->info('🔧 لتشغيل المشروع:');
        $this->info('   php artisan serve');
        $this->newLine();
        
        $this->info('🎉 أتمنى لك تجربة ممتعة مع منصة أفق!');
    }
}