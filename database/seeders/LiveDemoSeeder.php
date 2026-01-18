<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionInvoice;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::first();
        if (!$plan) return;

        // Create 10 companies over the last 6 months
        for ($i = 0; $i < 10; $i++) {
            $date = Carbon::now()->subDays(rand(0, 180));
            $name = 'Entreprise ' . ($i + 1);
            $company = Company::create([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'slug' => Str::slug($name) . '-' . rand(100, 999),
                'email' => 'contact' . $i . Str::random(5) . '@entreprise' . $i . '.com',
                'status' => 'active',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Add a subscription
            $sub = Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'status' => rand(0, 1) ? 'active' : 'pending',
                'starts_at' => $date,
                'ends_at' => (clone $date)->addYear(),
                'trial_ends_at' => (clone $date)->addDays(14),
                'created_at' => $date,
            ]);

            // Add a paid invoice if active
            if ($sub->status === 'active') {
                SubscriptionInvoice::create([
                    'company_id' => $company->id,
                    'subscription_id' => $sub->id,
                    'invoice_number' => 'INV-' . date('Y') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'amount' => 500,
                    'total_amount' => 500,
                    'currency' => 'MAD',
                    'period_start' => $date,
                    'period_end' => (clone $date)->addMonth(),
                    'status' => 'paid',
                    'paid_at' => (clone $date)->addDays(1),
                    'created_at' => $date,
                ]);
            }
        }

        // Add some more tickets
        for ($i = 0; $i < 5; $i++) {
            SupportTicket::create([
                'company_id' => Company::inRandomOrder()->first()->id,
                'user_id' => 1,
                'subject' => 'Besoin d\'aide #' . $i,
                'description' => 'Support request sample',
                'priority' => 'normal',
                'status' => 'open',
                'category' => 'technical',
                'created_at' => Carbon::now()->subHours(rand(1, 48)),
            ]);
        }
    }
}
