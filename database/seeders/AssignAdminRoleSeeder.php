<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the Admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Admin accounts to ensure exist
        $accounts = [
            ['name' => 'SaktoSpace Admin', 'email' => 'admin@saktospace.com', 'password' => 'ChangeMeNow!123'],
            ['name' => 'Carl Denver', 'email' => 'carldenver0@gmail.com', 'password' => 'ChangeMeNow!123'],
        ];

        foreach ($accounts as $data) {
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                // Create new user (password will be hashed by model cast)
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                ]);
            } else {
                // Keep existing password; only update name if changed
                if ($user->name !== $data['name']) {
                    $user->name = $data['name'];
                    $user->save();
                }
            }

            // Verify email if not already verified
            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
                $user->save();
            }

            // Assign Admin role if missing
            if (!$user->hasRole('Admin')) {
                $user->assignRole($adminRole);
            }
        }
    }
}
