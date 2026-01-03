<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $data = [
            'name' => '管理者',
            'email' => 'takamee666@gmail.com',
            'password' => Hash::make('refine0403'),
            'role' => 'admin',
        ];

        if ($admin) {
            $admin->update($data);  // ← 既存を更新
        } else {
            User::create($data);    // ← なければ作成
        }
    }
}
