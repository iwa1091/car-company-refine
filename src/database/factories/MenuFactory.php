<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Menu::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $categories = ['まつげエクステンション', '眉メニュー', 'お得なセットメニュー'];
        $category = $this->faker->randomElement($categories);

        $features = [];
        if ($category === 'まつげエクステンション') {
            $features = ['Cカール', 'Jカール対応', '持続期間3-4週間'];
        }

        return [
            'name' => $this->faker->words(2, true) . 'メニュー',
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(5000, 15000),
            'duration' => $this->faker->numberBetween(60, 120),
            'features' => json_encode($features),
            'category' => $category,
            'is_popular' => $this->faker->boolean(20),
        ];
    }
}