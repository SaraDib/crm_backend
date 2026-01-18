<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // System Level Roles (Our Team)
        $systemRoles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'level' => 100, 'type' => 'system', 'description' => 'Full System Control'],
            ['name' => 'Admin', 'slug' => 'admin', 'level' => 80, 'type' => 'system', 'description' => 'Company & Financial Management'],
            ['name' => 'Support', 'slug' => 'support', 'level' => 50, 'type' => 'system', 'description' => 'Company Assistance & Monitoring'],
            ['name' => 'Sales', 'slug' => 'sales', 'level' => 40, 'type' => 'system', 'description' => 'Lead & Trial Management'],
        ];

        // Company Level Roles (Client Employees)
        $companyRoles = [
            ['name' => 'Company Admin', 'slug' => 'company-admin', 'level' => 100, 'type' => 'company', 'description' => 'Full Company Account Control', 'is_default' => true],
            ['name' => 'Manager', 'slug' => 'manager', 'level' => 70, 'type' => 'company', 'description' => 'Team & Customer Oversight'],
            ['name' => 'Agent', 'slug' => 'agent', 'level' => 40, 'type' => 'company', 'description' => 'Customer Interaction & Updates'],
            ['name' => 'Viewer', 'slug' => 'viewer', 'level' => 10, 'type' => 'company', 'description' => 'Read-Only Access'],
        ];

        foreach (array_merge($systemRoles, $companyRoles) as $role) {
            Role::create($role);
        }
    }
}
