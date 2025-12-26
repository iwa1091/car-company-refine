<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既に同じメールのユーザーが存在していたら作成しない
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => '管理者',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'), // 必ず後で変更してください！
                'role' => 'admin',
            ]);
        }
    }
}
