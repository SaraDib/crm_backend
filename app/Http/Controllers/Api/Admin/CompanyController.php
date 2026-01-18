<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of all companies for Super Admin.
     */
    public function index(Request $request)
    {
        // Ensure only system admins can access
        if ($request->user()->user_type !== 'system') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $companies = Company::with(['subscription.plan'])
            ->withCount(['users', 'customers'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json($companies);
    }

    /**
     * Store a new company (Manual Onboarding).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string|default:MA',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $validated['uuid'] = (string) \Illuminate\Support\Str::uuid();
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        $validated['currency'] = 'MAD';

        $company = Company::create($validated);

        return response()->json($company, 201);
    }

    /**
     * Display the specified company with details.
     */
    public function show(Company $company)
    {
        return response()->json($company->load([
            'subscription.plan',
            'users' => function($query) {
                $query->select('users.id', 'first_name', 'last_name', 'email', 'phone', 'user_type')
                      ->withPivot('role_id', 'job_title', 'is_owner');
            }
        ])->loadCount(['users', 'customers']));
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:companies,email,{$company->id}",
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    /**
     * Remove the specified company (Soft Delete).
     */
    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(['message' => 'Entreprise supprimée avec succès.']);
    }

    /**
     * Toggle the company status between active and inactive.
     */
    public function toggleStatus(Company $company)
    {
        $newStatus = $company->status === 'active' ? 'inactive' : 'active';
        $company->update(['status' => $newStatus]);

        return response()->json([
            'message' => "Statut de l'entreprise mis à jour avec succès.",
            'company' => $company
        ]);
    }

    /**
     * Assign a subscription plan to the company manually.
     */
    public function assignPlan(Request $request, Company $company)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'duration_months' => 'required|integer|min:1',
        ]);

        $plan = \App\Models\SubscriptionPlan::find($validated['plan_id']);
        
        $subscription = \App\Models\Subscription::updateOrCreate(
            ['company_id' => $company->id],
            [
                'plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addMonths($validated['duration_months']),
                'status' => 'active',
                'notes' => 'Assigné manuellement par Super Admin',
            ]
        );

        return response()->json([
            'message' => 'Plan d\'abonnement assigné avec succès.',
            'subscription' => $subscription->load('plan')
        ]);
    }
}
