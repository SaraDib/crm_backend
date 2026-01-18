<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\CompanyContext;

class CompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) return;

        // 1. Create a Company Admin User
        $adminEmail = 'owner@' . $company->slug . '.com';
        $adminUser = User::where('email', $adminEmail)->first();
        
        if (!$adminUser) {
            $adminUser = User::create([
                'first_name' => 'John',
                'last_name' => 'Owner',
                'email' => $adminEmail,
                'password' => Hash::make('password'),
                'user_type' => 'company',
                'status' => 'active',
            ]);

            // Link to company
            DB::table('company_users')->insert([
                'company_id' => $company->id,
                'user_id' => $adminUser->id,
                'role_id' => 5, // company-admin
                'is_owner' => true,
                'created_at' => now(),
            ]);
        }

        // 2. Create some customers for this company
        // Set context manually for seeder
        app(CompanyContext::class)->setCompanyId($company->id);

        $customers = [
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Alami',
                'company_name' => 'Alami Tech',
                'email' => 'ahmed@alami.ma',
                'phone' => '0622334455',
                'city' => 'Casablanca',
                'type' => 'company',
                'status' => 'customer',
            ],
            [
                'first_name' => 'Sara',
                'last_name' => 'Berrada',
                'email' => 'sara@gmail.com',
                'phone' => '0677889900',
                'city' => 'Marrakech',
                'type' => 'individual',
                'status' => 'lead',
            ],
            [
                'first_name' => 'Youssef',
                'last_name' => 'Tahiri',
                'company_name' => 'Tahiri Logistics',
                'email' => 'contact@tahiri-log.ma',
                'phone' => '0522112233',
                'city' => 'Tanger',
                'type' => 'company',
                'status' => 'prospect',
            ],
        ];

        foreach ($customers as $index => $data) {
            $exists = Customer::where('email', $data['email'])->exists();
            if (!$exists) {
                Customer::create(array_merge($data, [
                    'customer_number' => 'CUST-' . str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                    'currency' => 'MAD',
                ]));
            }
        }
    }
}
