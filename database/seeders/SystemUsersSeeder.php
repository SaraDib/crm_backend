<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SystemUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or Create Roles
        $adminRole = Role::where('slug', 'admin')->first();
        $supportRole = Role::where('slug', 'support')->first();
        $salesRole = Role::where('slug', 'sales')->first();

        $users = [
            [
                'first_name' => 'Admin',
                'last_name' => 'System',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'system',
                'status' => 'active',
                'role' => $adminRole
            ],
            [
                'first_name' => 'Support',
                'last_name' => 'Agent',
                'email' => 'support@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'system',
                'status' => 'active',
                'role' => $supportRole
            ],
            [
                'first_name' => 'Commercial',
                'last_name' => 'Sales',
                'email' => 'sales@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'system',
                'status' => 'active',
                'role' => $salesRole
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            // Create or get User
            $user = User::where('email', $userData['email'])->first();
            if (!$user) {
                $user = User::create($userData);
            }
            
            // Attach system role if not already attached
            if ($role) {
                $exists = DB::table('user_roles')
                    ->where('user_id', $user->id)
                    ->where('role_id', $role->id)
                    ->exists();
                
                if (!$exists) {
                    DB::table('user_roles')->insert([
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'created_at' => now(),
                    ]);
                }
            }
        }
    }
}
