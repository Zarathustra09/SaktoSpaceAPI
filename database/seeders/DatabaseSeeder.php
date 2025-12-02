<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the Admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Create the test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('Test@123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign the Admin role to the test user
        $user->assignRole($adminRole);

        $this->call([
            CategorySeeder::class,
        ]);
    }
}
