<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free Trial',
                'slug' => 'free-trial',
                'description' => '14 days trial for small businesses',
                'duration_months' => 0,
                'price' => 0.00,
                'final_price' => 0.00,
                'customer_limit' => 50,
                'user_limit' => 1,
                'email_credits' => 100,
                'sms_credits' => 0,
                'whatsapp_credits' => 0,
                'is_trial' => true,
                'trial_days' => 14,
                'features' => ['Basic Dashboard', 'Manual Exports (max 10)'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Monthly Plan',
                'slug' => 'monthly',
                'description' => 'Entry-level businesses',
                'duration_months' => 1,
                'price' => 29.00,
                'final_price' => 29.00,
                'customer_limit' => 500,
                'user_limit' => 5,
                'email_credits' => 1000,
                'sms_credits' => 500,
                'whatsapp_credits' => 500,
                'features' => ['API Access', 'Standard Reports'],
                'sort_order' => 2,
            ],
            [
                'name' => '3 Months Plan',
                'slug' => '3-months',
                'description' => 'Growing businesses',
                'duration_months' => 3,
                'price' => 75.00,
                'final_price' => 75.00,
                'customer_limit' => 1500,
                'user_limit' => 15,
                'email_credits' => 3000,
                'sms_credits' => 1500,
                'whatsapp_credits' => 1500,
                'features' => ['Advanced Analytics', 'Priority Support'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Annual Enterprise',
                'slug' => 'annual',
                'description' => 'Enterprise clients',
                'duration_months' => 12,
                'price' => 240.00,
                'final_price' => 240.00,
                'customer_limit' => 0, // Unlimited
                'user_limit' => 100,
                'email_credits' => 12000,
                'sms_credits' => 6000,
                'whatsapp_credits' => 6000,
                'features' => ['White-label', 'Custom Integrations', 'Unlimited Customers'],
                'sort_order' => 5,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}
