<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザーをデータベースに登録
        User::create([
            'name' => 'REFINE',
            'email' => 'takamee666@gmail.com',
            'password' => Hash::make('refine0403'),
            'role' => 'admin',
        ]);
    }
}
