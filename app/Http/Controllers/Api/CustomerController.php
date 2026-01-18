<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index(Request $request)
    {
        $customers = Customer::with('category')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('customer_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->category_id, fn ($q, $cat) => $q->where('category_id', $cat))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json($customers);
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        // 1. Check Quota (Manual check as an example)
        $company = $request->user()->companies()->first();
        $plan = $company->subscription->plan;
        
        if ($plan->customer_limit > 0 && $company->customers()->count() >= $plan->customer_limit) {
            return response()->json([
                'message' => "Limite de clients atteinte pour votre forfait ({$plan->customer_limit} clients)."
            ], 403);
        }

        // 2. Generate unique customer number
        $customerCount = Customer::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->count();
        $customerNumber = 'CUST-' . str_pad($customerCount + 1, 5, '0', STR_PAD_LEFT);

        // 3. Create customer
        $customer = Customer::create(array_merge(
            $request->validated(),
            ['customer_number' => $customerNumber]
        ));

        return response()->json([
            'message' => 'Client créé avec succès.',
            'customer' => $customer
        ], 201);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        return response()->json($customer->load('category', 'assignedUser'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return response()->json([
            'message' => 'Client mis à jour avec succès.',
            'customer' => $customer
        ]);
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['message' => 'Client supprimé avec succès.']);
    }
}
