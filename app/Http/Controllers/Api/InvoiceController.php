<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerInvoice;
use App\Models\Quote;
use App\Services\CompanyContext;
use App\Traits\NotifiesUsers;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use NotifiesUsers;
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $invoices = CustomerInvoice::with(['customer', 'items', 'payments', 'creator', 'creditNotes'])
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->customer_id, fn($q, $cid) => $q->where('customer_id', $cid))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($invoices);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();
        
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Generate invoice number
        $lastInvoice = CustomerInvoice::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->first();
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(($lastInvoice ? intval(substr($lastInvoice->invoice_number, -4)) + 1 : 1), 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $itemDiscount = $lineTotal * (($item['discount_rate'] ?? 0) / 100);
            $lineAfterDiscount = $lineTotal - $itemDiscount;
            $itemTax = $lineAfterDiscount * (($item['tax_rate'] ?? 0) / 100);
            
            $subtotal += $lineTotal;
            $discountAmount += $itemDiscount;
            $taxAmount += $itemTax;
        }

        $total = $subtotal - $discountAmount + $taxAmount;

        // Create invoice
        $invoice = CustomerInvoice::create([
            'company_id' => $companyId,
            'customer_id' => $validated['customer_id'],
            'quote_id' => $validated['quote_id'] ?? null,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'status' => 'draft',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'paid_amount' => 0,
            'balance' => $total,
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Create items
        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $itemDiscount = $lineTotal * (($item['discount_rate'] ?? 0) / 100);
            $lineAfterDiscount = $lineTotal - $itemDiscount;
            $itemTax = $lineAfterDiscount * (($item['tax_rate'] ?? 0) / 100);
            $itemTotal = $lineAfterDiscount + $itemTax;

            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'discount_rate' => $item['discount_rate'] ?? 0,
                'total' => $itemTotal,
            ]);
        }

        // If created from quote, mark quote as accepted
        if ($validated['quote_id']) {
            Quote::find($validated['quote_id'])->update(['status' => 'accepted']);
        }

        // Notify staff
        $this->notifyCompanyStaff(
            $companyId,
            "Nouvelle Facture",
            "Une nouvelle facture #{$invoiceNumber} a été créée par " . auth()->user()->first_name,
            "/finance/invoices/{$invoice->id}",
            "success",
            auth()->id()
        );

        return response()->json([
            'message' => 'Facture créée avec succès',
            'invoice' => $invoice->load('items', 'customer')
        ], 201);
    }

    /**
     * Display the specified invoice.
     */
    public function show(CustomerInvoice $invoice)
    {
        return response()->json($invoice->load('customer', 'items', 'payments', 'creator', 'quote'));
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, CustomerInvoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:draft,sent,paid,partial,overdue,cancelled',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($validated);

        // Notify staff of update
        $this->notifyCompanyStaff(
            $invoice->company_id,
            "Facture Mise à Jour",
            "La facture #{$invoice->invoice_number} a été mise à jour (Statut: {$invoice->status})",
            "/finance/invoices/{$invoice->id}",
            "info",
            auth()->id()
        );

        return response()->json([
            'message' => 'Facture mise à jour avec succès',
            'invoice' => $invoice
        ]);
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(CustomerInvoice $invoice)
    {
        if ($invoice->paid_amount > 0) {
            return response()->json(['message' => 'Impossible de supprimer une facture avec des paiements'], 403);
        }

        $invoice->delete();
        return response()->json(['message' => 'Facture supprimée avec succès']);
    }

    /**
     * Download invoice as PDF.
     */
    public function downloadPdf(CustomerInvoice $invoice)
    {
        $invoice->load(['customer', 'items', 'company', 'quote']);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice'));
        
        return $pdf->download('Facture-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Store a credit note for an existing invoice.
     */
    public function storeCreditNote(Request $request, CustomerInvoice $invoice)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:billing_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Generate credit note number
        $lastCreditNote = CustomerInvoice::where('company_id', $companyId)
            ->where('type', 'credit_note')
            ->orderBy('created_at', 'desc')
            ->first();
        $creditNoteNumber = 'AVO-' . date('Y') . '-' . str_pad(($lastCreditNote ? intval(substr($lastCreditNote->invoice_number, -4)) + 1 : 1), 4, '0', STR_PAD_LEFT);

        // Calculate totals (negative for credit notes)
        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;

        $invoiceItems = $invoice->items;
        $itemsToCreate = [];

        foreach ($validated['items'] as $inputItem) {
            $originalItem = $invoiceItems->firstWhere('id', $inputItem['id']);
            if (!$originalItem) continue;

            $quantity = $inputItem['quantity'];
            $unitPrice = $originalItem->unit_price;
            $taxRate = $originalItem->tax_rate;
            $discountRate = $originalItem->discount_rate;

            $lineTotal = $quantity * $unitPrice;
            $itemDiscount = $lineTotal * ($discountRate / 100);
            $lineAfterDiscount = $lineTotal - $itemDiscount;
            $itemTax = $lineAfterDiscount * ($taxRate / 100);
            
            $subtotal += $lineTotal;
            $discountAmount += $itemDiscount;
            $taxAmount += $itemTax;

            $itemsToCreate[] = [
                'description' => "Avoir sur: " . $originalItem->description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'discount_rate' => $discountRate,
                'total' => $lineAfterDiscount + $itemTax,
            ];
        }

        $total = $subtotal - $discountAmount + $taxAmount;

        // Create credit note
        $creditNote = CustomerInvoice::create([
            'company_id' => $companyId,
            'customer_id' => $invoice->customer_id,
            'parent_id' => $invoice->id,
            'invoice_number' => $creditNoteNumber,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['invoice_date'],
            'type' => 'credit_note',
            'status' => 'paid', // Credit notes are usually considered "paid" as they reduce balance
            'subtotal' => -$subtotal,
            'tax_amount' => -$taxAmount,
            'discount_amount' => -$discountAmount,
            'total' => -$total,
            'paid_amount' => 0,
            'balance' => 0,
            'notes' => $validated['notes'] ?? "Avoir pour la facture " . $invoice->invoice_number,
            'created_by' => auth()->id(),
        ]);

        // Create items for credit note
        foreach ($itemsToCreate as $item) {
            $creditNote->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
                'discount_rate' => $item['discount_rate'],
                'total' => -$item['total'],
            ]);
        }

        // Update original invoice balance if necessary
        $invoice->balance -= $total;
        if ($invoice->balance <= 0) {
            $invoice->status = 'paid';
            $invoice->balance = 0;
        } elseif ($invoice->balance < $invoice->total) {
            $invoice->status = 'partial';
        }
        $invoice->save();

        return response()->json([
            'message' => 'Avoir créé avec succès',
            'credit_note' => $creditNote->load('items', 'customer')
        ], 201);
    }
}
