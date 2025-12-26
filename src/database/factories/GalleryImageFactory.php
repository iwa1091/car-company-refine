<?php

namespace Database\Factories;

use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;
    
    public function definition(): array
    {
        $titles = ['ナチュラルエクステ', 'ボリュームエクステ', 'カラーエクステ', '眉毛ワックス', '眉エクステ', '眉ティント'];
        $categories = ['まつげ', '眉'];
        
        return [
            'url' => 'https://images.unsplash.com/photo-' . $this->faker->randomNumber(9) . '?w=400&h=300&fit=crop',
            'title' => $this->faker->randomElement($titles),
            'category' => $this->faker->randomElement($categories),
        ];
    }
}