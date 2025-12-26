<?php

namespace Database\Factories;

use App\Models\BeforeAfterCase;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeforeAfterCaseFactory extends Factory
{
    protected $model = BeforeAfterCase::class;
    
    public function definition(): array
    {
        $titles = ['ナチュラルエクステ', 'ボリュームエクステ', '眉毛ワックス脱毛'];
        $descriptions = ['自然な仕上がりで目力アップ', '華やかで印象的な目元に', '理想の眉の形に整形'];
        
        return [
            'before_url' => 'https://images.unsplash.com/photo-' . $this->faker->randomNumber(9) . '?w=300&h=300&fit=crop',
            'after_url' => 'https://images.unsplash.com/photo-' . $this->faker->randomNumber(9) . '?w=300&h=300&fit=crop',
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->randomElement($descriptions),
        ];
    }
}