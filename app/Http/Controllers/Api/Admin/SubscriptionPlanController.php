<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('price', 'asc')->get();
        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'duration_months' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|between:0,100',
            'customer_limit' => 'required|integer|min:1',
            'user_limit' => 'required|integer|min:1',
            'email_credits' => 'nullable|integer|min:0',
            'whatsapp_credits' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'required|boolean',
            'is_trial' => 'required|boolean',
            'trial_days' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['final_price'] = $validated['price'] - ($validated['price'] * ($validated['discount_percentage'] ?? 0) / 100);

        $plan = SubscriptionPlan::create($validated);

        return response()->json($plan, 201);
    }

    public function show(SubscriptionPlan $plan)
    {
        return response()->json($plan);
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'duration_months' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|between:0,100',
            'customer_limit' => 'required|integer|min:1',
            'user_limit' => 'required|integer|min:1',
            'email_credits' => 'nullable|integer|min:0',
            'whatsapp_credits' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'required|boolean',
            'is_trial' => 'required|boolean',
            'trial_days' => 'nullable|integer|min:0',
        ]);

        $validated['final_price'] = $validated['price'] - ($validated['price'] * ($validated['discount_percentage'] ?? 0) / 100);
        $plan->update($validated);

        return response()->json($plan);
    }

    public function destroy(SubscriptionPlan $plan)
    {
        // Prevent deletion if there are active subscriptions (optional check)
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return response()->json(['message' => 'Cannot delete a plan with active subscriptions.'], 422);
        }

        $plan->delete();
        return response()->json(null, 204);
    }
}
