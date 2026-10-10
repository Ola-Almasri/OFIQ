<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // المستخدم التجريبي الافتراضي
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // إضافة المستخدم بيسان محمد
        User::create([
            'name' => 'بيسان محمد',
            'email' => 'besan@ofuq.com',
            'password' => Hash::make('12345678'),
        ]);
    }
}