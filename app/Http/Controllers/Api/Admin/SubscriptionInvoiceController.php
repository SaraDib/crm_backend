<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use Illuminate\Http\Request;

class SubscriptionInvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        if ($request->user()->user_type !== 'system') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $invoices = SubscriptionInvoice::with(['company', 'subscription.plan'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($invoices);
    }

    /**
     * Display invoice details.
     */
    public function show(SubscriptionInvoice $invoice)
    {
        return response()->json($invoice->load(['company', 'subscription.plan']));
    }

    /**
     * Update invoice status (e.g., mark as paid manually).
     */
    public function updateStatus(Request $request, SubscriptionInvoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:paid,canceled,overdue,sent',
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'paid' && !$invoice->paid_at) {
            $updateData['paid_at'] = now();
        }

        $invoice->update($updateData);

        return response()->json([
            'message' => 'Statut de la facture mis à jour.',
            'invoice' => $invoice->load(['company', 'subscription.plan'])
        ]);
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(SubscriptionInvoice $invoice)
    {
        $invoice->load(['company', 'subscription.plan']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.subscription_invoice', compact('invoice'));

        return $pdf->download('Facture-Abonnement-' . $invoice->invoice_number . '.pdf');
    }
}
