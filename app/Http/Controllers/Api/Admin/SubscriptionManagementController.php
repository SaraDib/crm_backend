<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Traits\NotifiesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionManagementController extends Controller
{
    use NotifiesUsers;
    public function pending()
    {
        $subscriptions = Subscription::with(['company', 'plan'])
            ->where('status', 'pending_payment')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    public function allSubscriptions(Request $request)
    {
        $query = Subscription::with(['company', 'plan', 'validator']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($subscriptions);
    }

    public function statistics()
    {
        $stats = [
            'pending_count' => Subscription::where('status', 'pending_payment')->count(),
            'active_count' => Subscription::where('status', 'active')->count(),
            'expired_count' => Subscription::where('status', 'expired')->count(),
            'total_revenue_month' => Subscription::where('status', 'active')
                ->whereMonth('paid_at', now()->month)
                ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
                ->sum('subscription_plans.price'),
        ];

        return response()->json($stats);
    }

    public function createForCompany(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'activate_immediately' => 'boolean',
            'payment_method' => 'nullable|in:bank_transfer,cash,check,mobile_money,other',
            'payment_reference' => 'nullable|string',
            'payment_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $plan = \App\Models\SubscriptionPlan::findOrFail($validated['plan_id']);
            
            $subscriptionData = [
                'company_id' => $validated['company_id'],
                'plan_id' => $validated['plan_id'],
                'status' => $validated['activate_immediately'] ? 'active' : 'pending_payment',
            ];

            if ($validated['activate_immediately']) {
                $subscriptionData['starts_at'] = now();
                $subscriptionData['ends_at'] = $this->calculateEndDate($plan);
                $subscriptionData['paid_at'] = now();
                $subscriptionData['validated_by'] = auth()->id();
                
                if (isset($validated['payment_method'])) {
                    $subscriptionData['payment_method'] = $validated['payment_method'];
                }
                if (isset($validated['payment_reference'])) {
                    $subscriptionData['payment_reference'] = $validated['payment_reference'];
                }
                if (isset($validated['payment_notes'])) {
                    $subscriptionData['payment_notes'] = $validated['payment_notes'];
                }
            }

            $subscription = Subscription::create($subscriptionData);

            // Generate invoice if activated
            if ($validated['activate_immediately']) {
                $this->generateInvoice($subscription, $plan, $validated['payment_method'] ?? null);
            }

            DB::commit();

            // Notify super-admins if pending
            if (!$validated['activate_immediately']) {
                $subscription->load('company');
                $this->notifySuperAdmins(
                    "Nouvel Abonnement en Attente",
                    "La société " . $subscription->company->name . " a souscrit au plan " . $plan->name . ". Paiement à valider.",
                    "/admin/subscriptions/pending",
                    "warning"
                );
            } else {
                // Notify company staff if already activated
                $this->notifyCompanyStaff(
                    $validated['company_id'],
                    "Abonnement Activé",
                    "Votre abonnement au plan " . $plan->name . " est désormais actif.",
                    "/subscription/current",
                    "success"
                );
            }

            return response()->json([
                'message' => $validated['activate_immediately'] 
                    ? 'Abonnement créé et activé avec succès. Facture générée.' 
                    : 'Abonnement créé en attente de paiement',
                'subscription' => $subscription->load(['company', 'plan'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la création: ' . $e->getMessage()], 500);
        }
    }

    public function validatePayment(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,cash,check,mobile_money,other',
            'payment_reference' => 'required|string',
            'payment_notes' => 'nullable|string',
        ]);

        $subscription = Subscription::with(['company', 'plan'])->findOrFail($id);

        if ($subscription->status !== 'pending_payment') {
            return response()->json(['error' => 'Cet abonnement n\'est pas en attente de paiement'], 400);
        }

        DB::beginTransaction();
        try {
            // Update subscription
            $subscription->update([
                'status' => 'active',
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
                'payment_notes' => $validated['payment_notes'] ?? null,
                'paid_at' => now(),
                'validated_by' => auth()->id(),
                'starts_at' => now(),
                'ends_at' => $this->calculateEndDate($subscription->plan),
            ]);

            // Generate invoice
            $this->generateInvoice($subscription, $subscription->plan, $validated['payment_method']);

            DB::commit();

            // Notify company staff
            $this->notifyCompanyStaff(
                $subscription->company_id,
                "Paiement Validé",
                "Votre paiement pour le plan " . $subscription->plan->name . " a été validé. Votre accès est maintenant actif.",
                "/subscription/current",
                "success"
            );

            return response()->json([
                'message' => 'Abonnement activé avec succès. Facture générée.',
                'subscription' => $subscription->fresh(['company', 'plan', 'validator'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de l\'activation: ' . $e->getMessage()], 500);
        }
    }

    private function generateInvoice($subscription, $plan, $paymentMethod = null)
    {
        // Get tax rate from system settings
        $taxRate = (float) DB::table('system_settings')->where('key', 'tax_rate')->value('value') ?? 20;
        
        $subtotal = (float) $plan->price;
        $taxAmount = ($subtotal * $taxRate) / 100;
        $totalAmount = $subtotal + $taxAmount;

        // Generate unique invoice number
        $invoiceNumber = 'INV-' . date('Ym') . '-' . str_pad(\App\Models\SubscriptionInvoice::count() + 1, 4, '0', STR_PAD_LEFT);

        \App\Models\SubscriptionInvoice::create([
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'vat_amount' => $taxAmount, // Explicitly setting VAT as requested
            'total_amount' => $totalAmount,
            'currency' => 'MAD',
            'period_start' => $subscription->starts_at,
            'period_end' => $subscription->ends_at,
            'status' => 'paid',
            'due_date' => now(),
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'notes' => "Abonnement {$plan->name} - {$plan->duration_months} mois (TVA {$taxRate}%)",
        ]);
    }

    private function calculateEndDate($plan)
    {
        $duration = $plan->duration_months ?? 1;
        return now()->addMonths($duration);
    }

    /**
     * Store a credit note for a subscription invoice.
     */
    public function storeCreditNote(Request $request, $invoiceId)
    {
        $invoice = \App\Models\SubscriptionInvoice::findOrFail($invoiceId);

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $maxRefund = (float) $invoice->total_amount;
        if ((float)$validated['amount'] > $maxRefund) {
            return response()->json(['error' => 'Le montant de l\'avoir ne peut pas dépasser le montant total de la facture (' . $maxRefund . ' MAD)'], 400);
        }

        // Get tax rate from the original invoice if possible, or from notes, or default
        // For simplicity, we'll re-calculate proportionally if partial, or use the system setting for taxes on the given amount
        $taxRate = (float) DB::table('system_settings')->where('key', 'tax_rate')->value('value') ?? 20;

        $totalAvoir = (float) $validated['amount'];
        $subtotalAvoir = $totalAvoir / (1 + ($taxRate / 100));
        $taxAmountAvoir = $totalAvoir - $subtotalAvoir;

        // Generate unique credit note number
        $creditNoteNumber = 'AVO-' . date('Ym') . '-' . str_pad(\App\Models\SubscriptionInvoice::where('type', 'credit_note')->count() + 1, 4, '0', STR_PAD_LEFT);

        $creditNote = \App\Models\SubscriptionInvoice::create([
            'company_id' => $invoice->company_id,
            'subscription_id' => $invoice->subscription_id,
            'parent_id' => $invoice->id,
            'invoice_number' => $creditNoteNumber,
            'type' => 'credit_note',
            'amount' => -$subtotalAvoir,
            'tax_amount' => -$taxAmountAvoir,
            'vat_amount' => -$taxAmountAvoir,
            'total_amount' => -$totalAvoir,
            'currency' => $invoice->currency,
            'period_start' => $invoice->period_start,
            'period_end' => $invoice->period_end,
            'status' => 'paid',
            'due_date' => now(),
            'paid_at' => now(),
            'notes' => $validated['notes'] ?? "Avoir pour la facture " . $invoice->invoice_number,
        ]);

        return response()->json([
            'message' => 'Avoir créé avec succès',
            'credit_note' => $creditNote
        ], 201);
    }
}
