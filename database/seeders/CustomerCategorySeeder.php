<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CustomerCategory;
use App\Services\CompanyContext;
use Illuminate\Database\Seeder;

class CustomerCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();
        if (!$company) return;

        app(CompanyContext::class)->setCompanyId($company->id);

        $categories = [
            ['name' => 'VIP', 'color' => '#f1b44c', 'sort_order' => 1],
            ['name' => 'Wholesale', 'color' => '#556ee6', 'sort_order' => 2],
            ['name' => 'Retail', 'color' => '#34c38f', 'sort_order' => 3],
            ['name' => 'Partner', 'color' => '#50a5f1', 'sort_order' => 4],
        ];

        foreach ($categories as $cat) {
            CustomerCategory::updateOrCreate(
                ['name' => $cat['name'], 'company_id' => $company->id],
                $cat
            );
        }
    }
}
