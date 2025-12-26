<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // ダミーの画像URL
        $imageUrls = [
            'https://placehold.co/600x400/EAD7CC/5A4B4B?text=商品画像1',
            'https://placehold.co/600x400/EAD7CC/5A4B4B?text=商品画像2',
            'https://placehold.co/600x400/EAD7CC/5A4B4B?text=商品画像3',
            'https://placehold.co/600x400/EAD7CC/5A4B4B?text=商品画像4',
            'https://placehold.co/600x400/EAD7CC/5A4B4B?text=商品画像5',
        ];

        return [
            'name' => $this->faker->unique()->word . ' ' . $this->faker->randomElement(['美容液', 'クレンジング', 'パック', 'ブラシ']),
            'description' => $this->faker->realText(100),
            'price' => $this->faker->numberBetween(1000, 10000),
            'image_path' => $this->faker->randomElement($imageUrls),
        ];
    }
}
