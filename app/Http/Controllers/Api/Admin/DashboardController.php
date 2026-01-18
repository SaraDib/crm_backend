<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get statistics for the Super Admin dashboard.
     */
    public function stats(Request $request)
    {
        if ($request->user()->user_type !== 'system') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 1. Total Companies
        $totalCompanies = Company::count();
        $previousMonthCompanies = Company::where('created_at', '<', Carbon::now()->startOfMonth())->count();
        $companiesGrowth = $previousMonthCompanies > 0 
            ? (($totalCompanies - $previousMonthCompanies) / $previousMonthCompanies) * 100 
            : 0;

        // 2. Active Subscriptions
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        
        // 3. Monthly Revenue (from PAID subscription invoices)
        $currentMonthRevenue = DB::table('subscription_invoices')
            ->where('status', 'paid')
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('total_amount');

        // Total TVA to be paid to the state (sum of tax_amount from all paid invoices)
        $totalTva = DB::table('subscription_invoices')
            ->where('status', 'paid')
            ->sum('tax_amount');

        // 4. Dynamic Activities
        $recentActivities = $this->getDynamicActivities();

        // 5. Growth Chart Data (last 6 months)
        $growthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Company::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
            
            $growthData[] = [
                'month' => $month->format('M'),
                'count' => $count
            ];
        }

        return response()->json([
            'stats' => [
                'total_companies' => [
                    'total' => $totalCompanies,
                    'growth' => round($companiesGrowth, 1) . '%'
                ],
                'active_subscriptions' => [
                    'total' => $activeSubscriptions,
                ],
                'monthly_revenue' => [
                    'total' => number_format($currentMonthRevenue, 0, '.', ' ') . ' MAD',
                ],
                'total_tva' => [
                    'total' => number_format($totalTva, 0, '.', ' ') . ' MAD',
                ],
                'support_tickets' => [
                    'total' => DB::table('support_tickets')->count(),
                ]
            ],
            'recent_activities' => $recentActivities,
            'growth_data' => $growthData
        ]);
    }

    private function getDynamicActivities()
    {
        $activities = [];

        // Recent Companies
        $companies = Company::latest()->limit(3)->get();
        foreach ($companies as $c) {
            $activities[] = [
                'type' => 'registration',
                'title' => $c->name,
                'description' => 'Nouvelle entreprise inscrite',
                'time' => $c->created_at->diffForHumans(),
                'color' => 'primary',
                'icon' => 'mdi-account-plus'
            ];
        }

        // Recent Payments
        $payments = SubscriptionInvoice::where('status', 'paid')->with('company')->latest()->limit(2)->get();
        foreach ($payments as $p) {
            $activities[] = [
                'type' => 'payment',
                'title' => $p->company->name ?? 'Inconnu',
                'description' => 'Paiement de ' . $p->total_amount . ' MAD reçu',
                'time' => $p->paid_at ? $p->paid_at->diffForHumans() : $p->updated_at->diffForHumans(),
                'color' => 'success',
                'icon' => 'mdi-cash'
            ];
        }

        // Recent Tickets
        $tickets = SupportTicket::with('company')->latest()->limit(2)->get();
        foreach ($tickets as $t) {
            $activities[] = [
                'type' => 'ticket',
                'title' => $t->company->name ?? 'Client',
                'description' => 'Nouveau ticket: ' . $t->subject,
                'time' => $t->created_at->diffForHumans(),
                'color' => 'warning',
                'icon' => 'mdi-help-circle'
            ];
        }

        return $activities;
    }

    public function salesStats(Request $request)
    {
        if (!in_array($request->user()->user_type, ['system'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 1. Trials Status
        $activeTrials = Subscription::where('status', 'trialing')
            ->where('trial_ends_at', '>', Carbon::now())
            ->count();

        $expiringTrials = Subscription::where('status', 'trialing')
            ->where('trial_ends_at', '>', Carbon::now())
            ->where('trial_ends_at', '<=', Carbon::now()->addDays(3))
            ->count();

        // 2. Recent Trials
        $recentTrials = Subscription::with(['company', 'plan'])
            ->where('status', 'trialing')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 3. Conversion Rate (Trial to Active) - simplified
        $convertedCount = Subscription::where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->count();
            
        $totalTrialsEver = Subscription::whereNotNull('trial_ends_at')->count();
        $conversionRate = $totalTrialsEver > 0 ? ($convertedCount / $totalTrialsEver) * 100 : 0;

        return response()->json([
            'active_trials' => $activeTrials,
            'expiring_trials_3_days' => $expiringTrials,
            'recent_trials' => $recentTrials,
            'conversion_rate' => round($conversionRate, 1) . '%'
        ]);
    }
}
