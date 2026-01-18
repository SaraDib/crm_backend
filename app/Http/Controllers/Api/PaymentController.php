<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Models\CustomerInvoice;
use App\Services\CompanyContext;
use App\Traits\NotifiesUsers;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use NotifiesUsers;
    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        $payments = CustomerPayment::with(['invoice.customer', 'customer', 'receiver'])
            ->when($request->invoice_id, fn($q, $id) => $q->where('invoice_id', $id))
            ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($payments);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();
        
        $validated = $request->validate([
            'invoice_id' => 'required|exists:customer_invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank_transfer,check,card,paypal,other',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Get invoice
        $invoice = CustomerInvoice::findOrFail($validated['invoice_id']);

        // Check if payment amount doesn't exceed balance
        if ($validated['amount'] > $invoice->balance) {
            return response()->json([
                'message' => "Le montant du paiement ({$validated['amount']}) dépasse le solde de la facture ({$invoice->balance})"
            ], 422);
        }


        // Generate unique payment number
        $year = date('Y');
        $lastPayment = CustomerPayment::where('company_id', $companyId)
            ->where('payment_number', 'LIKE', "PAY-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastPayment) {
            // Extract the last number and increment
            $lastNumber = intval(substr($lastPayment->payment_number, -4));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        $paymentNumber = 'PAY-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        // Ensure uniqueness (in case of race condition)
        while (CustomerPayment::where('payment_number', $paymentNumber)->exists()) {
            $nextNumber++;
            $paymentNumber = 'PAY-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }

        // Create payment
        $payment = CustomerPayment::create([
            'company_id' => $companyId,
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_number' => $paymentNumber,
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'received_by' => auth()->id(),
        ]);

        // Update invoice
        $newPaidAmount = $invoice->paid_amount + $validated['amount'];
        $newBalance = $invoice->total - $newPaidAmount;

        $newStatus = $newBalance == 0 ? 'paid' : 'partial';

        $invoice->update([
            'paid_amount' => $newPaidAmount,
            'balance' => $newBalance,
            'status' => $newStatus,
        ]);

        // Notify staff
        $this->notifyCompanyStaff(
            $companyId,
            "Paiement Reçu",
            "Un paiement de {$payment->amount} MAD a été reçu pour la facture #{$invoice->invoice_number}",
            "/finance/payments",
            "success",
            auth()->id()
        );

        return response()->json([
            'message' => 'Paiement enregistré avec succès',
            'payment' => $payment->load('invoice', 'customer')
        ], 201);
    }

    /**
     * Display the specified payment.
     */
    public function show(CustomerPayment $payment)
    {
        return response()->json($payment->load('invoice.customer', 'customer', 'receiver'));
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, CustomerPayment $payment)
    {
        $validated = $request->validate([
            'payment_date' => 'nullable|date',
            'method' => 'nullable|in:cash,bank_transfer,check,card,paypal,other',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);

        return response()->json([
            'message' => 'Paiement mis à jour avec succès',
            'payment' => $payment
        ]);
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(CustomerPayment $payment)
    {
        // Restore invoice balance
        $invoice = $payment->invoice;
        $invoice->update([
            'paid_amount' => $invoice->paid_amount - $payment->amount,
            'balance' => $invoice->balance + $payment->amount,
            'status' => ($invoice->balance + $payment->amount) == $invoice->total ? 'sent' : 'partial',
        ]);

        $payment->delete();

        return response()->json(['message' => 'Paiement supprimé avec succès']);
    }
}
