<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubscriptionPlanSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SystemSettingSeeder::class,
        ]);

        // Create initial System Super Admin
        $user = User::create([
            'uuid' => Str::uuid(),
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@crm.com',
            'password' => Hash::make('password'),
            'user_type' => 'system',
            'status' => 'active',
        ]);

        // Attach Super Admin Role
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $user->roles()->attach($superAdminRole->id);
        }
    }
}
