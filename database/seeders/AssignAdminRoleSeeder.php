<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if ($user) {
            $role = Role::firstOrCreate(['name' => 'Admin']);
            $user->assignRole($role);
        }
    }
}
