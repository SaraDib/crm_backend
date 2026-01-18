<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\CompanyContext;
use App\Traits\NotifiesUsers;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    use NotifiesUsers;
    /**
     * Display a listing of quotes.
     */
    public function index(Request $request)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();
        
        $quotes = Quote::with(['customer', 'items', 'creator'])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->customer_id, fn($q, $cid) => $q->where('customer_id', $cid))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($quotes);
    }

    /**
     * Store a newly created quote.
     */
    public function store(Request $request)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();
        
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quote_date' => 'required|date',
            'valid_until' => 'nullable|date|after:quote_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        // Generate quote number
        $lastQuote = Quote::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->first();
        $quoteNumber = 'QT-' . date('Y') . '-' . str_pad(($lastQuote ? intval(substr($lastQuote->quote_number, -4)) + 1 : 1), 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $subtotal = 0;
        $taxAmount = 0;
        $itemDiscountSum = 0;

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $itemDiscount = $lineTotal * (($item['discount_rate'] ?? 0) / 100);
            $lineAfterDiscount = $lineTotal - $itemDiscount;
            $itemTax = $lineAfterDiscount * (($item['tax_rate'] ?? 0) / 100);
            
            $subtotal += $lineTotal;
            $itemDiscountSum += $itemDiscount;
            $taxAmount += $itemTax;
        }

        // Global discount calculation
        $discType = $request->discount_type ?? 'percentage';
        $discValue = $request->discount_value ?? 0;
        $globalDiscount = 0;
        
        if ($discType === 'percentage') {
            $globalDiscount = ($subtotal - $itemDiscountSum) * ($discValue / 100);
        } else {
            $globalDiscount = $discValue;
        }

        $totalDiscountAmount = $itemDiscountSum + $globalDiscount;
        $total = $subtotal - $itemDiscountSum - $globalDiscount + $taxAmount;

        // Create quote
        $quote = Quote::create([
            'company_id' => $companyId,
            'customer_id' => $validated['customer_id'],
            'quote_number' => $quoteNumber,
            'quote_date' => $validated['quote_date'],
            'valid_until' => $validated['valid_until'] ?? now()->addDays(30),
            'status' => 'draft',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $totalDiscountAmount,
            'discount_type' => $discType,
            'discount_value' => $discValue,
            'total' => $total,
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

            $quote->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'discount_rate' => $item['discount_rate'] ?? 0,
                'total' => $itemTotal,
            ]);
        }

        // Notify staff
        $this->notifyCompanyStaff(
            $companyId,
            "Nouveau Devis",
            "Un nouveau devis #{$quoteNumber} a été créé par " . auth()->user()->first_name,
            "/finance/quotes/{$quote->id}",
            "success",
            auth()->id()
        );

        return response()->json([
            'message' => 'Devis créé avec succès',
            'quote' => $quote->load('items', 'customer')
        ], 201);
    }

    /**
     * Display the specified quote.
     */
    public function show(Quote $quote)
    {
        return response()->json($quote->load('customer', 'items', 'creator', 'invoice'));
    }

    /**
     * Update the specified quote.
     */
    public function update(Request $request, Quote $quote)
    {
        if ($quote->status !== 'draft') {
            return response()->json(['message' => 'Seuls les devis en brouillon peuvent être modifiés'], 403);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quote_date' => 'required|date',
            'valid_until' => 'nullable|date|after:quote_date',
            'status' => 'nullable|in:draft,sent,accepted,rejected,expired',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        // Recalculate totals
        $subtotal = 0;
        $taxAmount = 0;
        $itemDiscountSum = 0;

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $itemDiscount = $lineTotal * (($item['discount_rate'] ?? 0) / 100);
            $lineAfterDiscount = $lineTotal - $itemDiscount;
            $itemTax = $lineAfterDiscount * (($item['tax_rate'] ?? 0) / 100);
            
            $subtotal += $lineTotal;
            $itemDiscountSum += $itemDiscount;
            $taxAmount += $itemTax;
        }

        // Global discount calculation
        $discType = $request->discount_type ?? 'percentage';
        $discValue = $request->discount_value ?? 0;
        $globalDiscount = 0;
        
        if ($discType === 'percentage') {
            $globalDiscount = ($subtotal - $itemDiscountSum) * ($discValue / 100);
        } else {
            $globalDiscount = $discValue;
        }

        $totalDiscountAmount = $itemDiscountSum + $globalDiscount;
        $total = $subtotal - $itemDiscountSum - $globalDiscount + $taxAmount;

        $quote->update([
            'customer_id' => $validated['customer_id'],
            'quote_date' => $validated['quote_date'],
            'valid_until' => $validated['valid_until'],
            'status' => $validated['status'] ?? 'draft',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $totalDiscountAmount,
            'discount_type' => $discType,
            'discount_value' => $discValue,
            'total' => $total,
            'notes' => $validated['notes'] ?? null,
            'terms' => $validated['terms'] ?? null,
        ]);

        // Delete old items and create new ones
        $quote->items()->delete();
        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $itemDiscount = $lineTotal * (($item['discount_rate'] ?? 0) / 100);
            $lineAfterDiscount = $lineTotal - $itemDiscount;
            $itemTax = $lineAfterDiscount * (($item['tax_rate'] ?? 0) / 100);
            $itemTotal = $lineAfterDiscount + $itemTax;

            $quote->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'discount_rate' => $item['discount_rate'] ?? 0,
                'total' => $itemTotal,
            ]);
        }

        return response()->json([
            'message' => 'Devis mis à jour avec succès',
            'quote' => $quote->load('items', 'customer')
        ]);
    }

    /**
     * Remove the specified quote.
     */
    public function destroy(Quote $quote)
    {
        if ($quote->status === 'accepted') {
            return response()->json(['message' => 'Impossible de supprimer un devis accepté'], 403);
        }

        $quote->delete();
        return response()->json(['message' => 'Devis supprimé avec succès']);
    }

    /**
     * Convert a quote to an invoice.
     */
    public function convertToInvoice(Quote $quote)
    {
        if ($quote->status === 'rejected' || $quote->status === 'expired') {
            return response()->json(['message' => 'Un devis rejeté ou expiré ne peut pas être converti'], 403);
        }

        $companyId = app(CompanyContext::class)->getCompanyId();

        // Check if invoice already exists for this quote
        if ($quote->invoice) {
            return response()->json(['message' => 'Une facture existe déjà pour ce devis', 'invoice' => $quote->invoice], 409);
        }

        // Generate unique invoice number
        $lastInvoice = \App\Models\CustomerInvoice::where('company_id', $companyId)
            ->where('invoice_number', 'LIKE', 'INV-' . date('Y') . '-%')
            ->orderBy('id', 'desc')
            ->first();
            
        $nextNumber = 1;
        if ($lastInvoice) {
            // Extrait le numéro à la fin (ex: INV-2026-0002 -> 2)
            $parts = explode('-', $lastInvoice->invoice_number);
            $nextNumber = intval(end($parts)) + 1;
        }

        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Verification de sécurité supplémentaire pour éviter le doublon SQL
        while (\App\Models\CustomerInvoice::where('company_id', $companyId)->where('invoice_number', $invoiceNumber)->exists()) {
            $nextNumber++;
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }

        // Create invoice
        $invoice = \App\Models\CustomerInvoice::create([
            'company_id' => $companyId,
            'customer_id' => $quote->customer_id,
            'quote_id' => $quote->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now(),
            'due_date' => now()->addDays(15), // Par défaut 15 jours
            'status' => 'draft',
            'subtotal' => $quote->subtotal,
            'tax_amount' => $quote->tax_amount,
            'discount_amount' => $quote->discount_amount,
            'discount_type' => $quote->discount_type,
            'discount_value' => $quote->discount_value,
            'total' => $quote->total,
            'paid_amount' => 0,
            'balance' => $quote->total,
            'notes' => $quote->notes,
            'terms' => $quote->terms,
            'created_by' => auth()->id(),
        ]);

        // Copy items
        foreach ($quote->items as $item) {
            $invoice->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'discount_rate' => $item->discount_rate,
                'total' => $item->total,
            ]);
        }

        // Mark quote as accepted
        $quote->update(['status' => 'accepted']);

        // Notify staff
        $this->notifyCompanyStaff(
            $companyId,
            "Devis Converti",
            "Le devis #{$quote->quote_number} a été converti en facture #{$invoice->invoice_number}",
            "/finance/invoices/{$invoice->id}",
            "info",
            auth()->id()
        );

        return response()->json([
            'message' => 'Devis converti en facture avec succès',
            'invoice' => $invoice->load('items', 'customer')
        ], 201);
    }

    /**
     * Download quote as PDF.
     */
    public function downloadPdf(Quote $quote)
    {
        $quote->load(['customer', 'items', 'company']);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.quote', compact('quote'));
        
        return $pdf->download('Devis-' . $quote->quote_number . '.pdf');
    }
}
