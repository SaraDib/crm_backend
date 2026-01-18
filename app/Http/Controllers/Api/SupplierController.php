<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::orderBy('name')->paginate(15);
        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json([
            'message' => 'Fournisseur créé avec succès',
            'supplier' => $supplier
        ], 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier->load('invoices'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return response()->json([
            'message' => 'Fournisseur mis à jour avec succès',
            'supplier' => $supplier
        ]);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(['message' => 'Fournisseur supprimé avec succès']);
    }

    public function listAll()
    {
        return response()->json(Supplier::orderBy('name')->get());
    }
}
