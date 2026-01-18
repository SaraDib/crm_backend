<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupplierInvoice;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = SupplierInvoice::with('supplier')
            ->when($request->supplier_id, fn($q, $id) => $q->where('supplier_id', $id))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderBy('invoice_date', 'desc')
            ->paginate(15);
            
        return response()->json($invoices);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric',
            'status' => 'required|in:pending,paid,cancelled',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('supplier_invoices', 'public');
            $validated['file_path'] = $path;
        }

        $invoice = SupplierInvoice::create($validated);

        // Auto-create reminder if due_date is set
        if ($invoice->due_date) {
            Reminder::create([
                'company_id' => $invoice->company_id,
                'supplier_invoice_id' => $invoice->id,
                'title' => "Échéance Facture: {$invoice->invoice_number}",
                'description' => "Paiement de la facture {$invoice->invoice_number} à prévoir pour le " . $invoice->due_date->format('d/m/Y'),
                'type' => 'invoice',
                'priority' => 'high',
                'reminder_date' => $invoice->due_date->subDays(2), // Remind 2 days before
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'message' => 'Facture fournisseur enregistrée avec succès',
            'invoice' => $invoice->load('supplier')
        ], 201);
    }

    public function show(SupplierInvoice $supplierInvoice)
    {
        return response()->json($supplierInvoice->load('supplier'));
    }

    public function update(Request $request, SupplierInvoice $supplierInvoice)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric',
            'status' => 'required|in:pending,paid,cancelled',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
        ]);

        if ($request->hasFile('file')) {
            if ($supplierInvoice->file_path) {
                Storage::disk('public')->delete($supplierInvoice->file_path);
            }
            $path = $request->file('file')->store('supplier_invoices', 'public');
            $validated['file_path'] = $path;
        }

        $supplierInvoice->update($validated);

        return response()->json([
            'message' => 'Facture fournisseur mise à jour avec succès',
            'invoice' => $supplierInvoice->load('supplier')
        ]);
    }

    public function destroy(SupplierInvoice $supplierInvoice)
    {
        if ($supplierInvoice->file_path) {
            Storage::disk('public')->delete($supplierInvoice->file_path);
        }
        $supplierInvoice->delete();
        return response()->json(['message' => 'Facture fournisseur supprimée avec succès']);
    }
}
