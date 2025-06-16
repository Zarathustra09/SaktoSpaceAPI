<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $types = Category::getTypes();

        $data = [
            [
                'name' => 'Furniture',
                'description' => 'All kinds of furniture items.',
                'type' => Category::TYPE_FURNITURE,
            ],
            [
                'name' => 'Decor',
                'description' => 'Decorative items for your space.',
                'type' => Category::TYPE_DECOR,
            ],
            [
                'name' => 'Lighting',
                'description' => 'Lighting solutions and accessories.',
                'type' => Category::TYPE_LIGHTING,
            ],
            [
                'name' => 'Outdoor',
                'description' => 'Outdoor furniture and decor.',
                'type' => Category::TYPE_OUTDOOR,
            ],
        ];

        foreach ($data as $category) {
            Category::firstOrCreate(
                ['type' => $category['type']],
                $category
            );
        }
    }
}
