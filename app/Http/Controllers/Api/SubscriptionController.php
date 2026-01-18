<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Get current company's subscription.
     */
    public function current()
    {
        $company = Auth::user()->companies()->first();
        
        if (!$company) {
            return response()->json(['message' => 'No company associated with user.'], 404);
        }

        $subscription = Subscription::with('plan')
            ->where('company_id', $company->id)
            ->whereIn('status', ['active', 'pending_payment'])
            ->latest()
            ->first();

        return response()->json($subscription);
    }

    /**
     * List all available plans for the company to upgrade/downgrade.
     */
    public function availablePlans()
    {
        $plans = SubscriptionPlan::where('status', 'active')->orderBy('price', 'asc')->get();
        return response()->json($plans);
    }
}
