<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Menu::factory()->create([
            'name' => 'ナチュラルエクステ',
            'description' => '自然な仕上がりで普段使いにぴったり',
            'price' => 6800,
            'duration' => 90,
            'features' => json_encode(['Cカール', 'Jカール対応', '持続期間3-4週間']),
            'category' => 'まつげエクステンション',
            'is_popular' => true,
        ]);

        Menu::factory()->count(10)->create();
    }
}