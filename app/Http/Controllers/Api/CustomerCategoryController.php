<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerCategory;
use Illuminate\Http\Request;

class CustomerCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(CustomerCategory::orderBy('sort_order')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'integer',
        ]);

        $category = CustomerCategory::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerCategory $customerCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'integer',
        ]);

        $customerCategory->update($validated);

        return response()->json($customerCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerCategory $customerCategory)
    {
        $customerCategory->delete();
        return response()->json(['message' => 'Catégorie supprimée.']);
    }
}
