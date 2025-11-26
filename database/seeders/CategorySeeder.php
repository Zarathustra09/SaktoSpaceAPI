<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Furniture',
                'description' => 'All kinds of furniture items.',
                'type' => 'furniture',
            ],
            [
                'name' => 'Decor',
                'description' => 'Decorative items for your space.',
                'type' => 'decor',
            ],
            [
                'name' => 'Lighting',
                'description' => 'Lighting solutions and accessories.',
                'type' => 'lighting',
            ],
            [
                'name' => 'Outdoor',
                'description' => 'Outdoor furniture and decor.',
                'type' => 'outdoor',
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
