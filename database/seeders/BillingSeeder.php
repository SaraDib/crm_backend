<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;
use App\Services\CompanyContext;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $this->command->info('Aucune entreprise trouvée. Veuillez d\'abord créer une entreprise.');
            return;
        }

        // Set company context
        app(CompanyContext::class)->setCompanyId($company->id);

        // Get customers
        $customers = Customer::where('company_id', $company->id)->get();
        if ($customers->isEmpty()) {
            $this->command->info('Aucun client trouvé. Veuillez d\'abord créer des clients.');
            return;
        }

        // Create a quote
        $quote = Quote::create([
            'company_id' => $company->id,
            'customer_id' => $customers->first()->id,
            'quote_number' => 'QT-2026-0001',
            'quote_date' => now()->subDays(15),
            'valid_until' => now()->addDays(15),
            'status' => 'accepted',
            'subtotal' => 15000,
            'tax_amount' => 3000,
            'discount_amount' => 500,
            'total' => 17500,
            'notes' => 'Devis pour services de développement',
            'created_by' => 1,
        ]);

        // Add quote items
        $quote->items()->create([
            'description' => 'Développement site web',
            'quantity' => 1,
            'unit_price' => 10000,
            'tax_rate' => 20,
            'discount_rate' => 0,
            'total' => 12000,
        ]);

        $quote->items()->create([
            'description' => 'Hébergement annuel',
            'quantity' => 1,
            'unit_price' => 5000,
            'tax_rate' => 20,
            'discount_rate' => 10,
            'total' => 5400,
        ]);

        // Create invoices
        $invoices = [];

        // Invoice 1 - Fully paid
        $invoice1 = CustomerInvoice::create([
            'company_id' => $company->id,
            'customer_id' => $customers->first()->id,
            'quote_id' => $quote->id,
            'invoice_number' => 'INV-2026-0001',
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->addDays(20),
            'status' => 'paid',
            'subtotal' => 17500,
            'tax_amount' => 3500,
            'discount_amount' => 0,
            'total' => 21000,
            'paid_amount' => 21000,
            'balance' => 0,
            'notes' => 'Facture pour développement web',
            'created_by' => 1,
        ]);

        $invoice1->items()->create([
            'description' => 'Développement site web complet',
            'quantity' => 1,
            'unit_price' => 17500,
            'tax_rate' => 20,
            'discount_rate' => 0,
            'total' => 21000,
        ]);

        // Payment for invoice 1
        CustomerPayment::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice1->id,
            'customer_id' => $customers->first()->id,
            'payment_number' => 'PAY-2026-0001',
            'payment_date' => now()->subDays(5),
            'amount' => 21000,
            'method' => 'bank_transfer',
            'reference' => 'VIRT-20260109-12345',
            'notes' => 'Virement bancaire',
            'received_by' => 1,
        ]);

        // Invoice 2 - Partial payment
        if ($customers->count() > 1) {
            $invoice2 = CustomerInvoice::create([
                'company_id' => $company->id,
                'customer_id' => $customers[1]->id,
                'invoice_number' => 'INV-2026-0002',
                'invoice_date' => now()->subDays(7),
                'due_date' => now()->addDays(23),
                'status' => 'partial',
                'subtotal' => 8500,
                'tax_amount' => 1700,
                'discount_amount' => 200,
                'total' => 10000,
                'paid_amount' => 5000,
                'balance' => 5000,
                'notes' => 'Campagne marketing digitale',
                'created_by' => 1,
            ]);

            $invoice2->items()->create([
                'description' => 'Gestion réseaux sociaux - 3 mois',
                'quantity' => 3,
                'unit_price' => 3000,
                'tax_rate' => 20,
                'discount_rate' => 5,
                'total' => 10260,
            ]);

            // Partial payment
            CustomerPayment::create([
                'company_id' => $company->id,
                'invoice_id' => $invoice2->id,
                'customer_id' => $customers[1]->id,
                'payment_number' => 'PAY-2026-0002',
                'payment_date' => now()->subDays(3),
                'amount' => 5000,
                'method' => 'check',
                'reference' => 'CHQ-123456',
                'notes' => 'Acompte 50%',
                'received_by' => 1,
            ]);
        }

        // Invoice 3 - Unpaid (sent)
        if ($customers->count() > 2) {
            $invoice3 = CustomerInvoice::create([
                'company_id' => $company->id,
                'customer_id' => $customers[2]->id,
                'invoice_number' => 'INV-2026-0003',
                'invoice_date' => now()->subDays(3),
                'due_date' => now()->addDays(27),
                'status' => 'sent',
                'subtotal' => 12000,
                'tax_amount' => 2400,
                'discount_amount' => 0,
                'total' => 14400,
                'paid_amount' => 0,
                'balance' => 14400,
                'notes' => 'Prestation de conseil',
                'created_by' => 1,
            ]);

            $invoice3->items()->create([
                'description' => 'Audit SEO complet',
                'quantity' => 1,
                'unit_price' => 7000,
                'tax_rate' => 20,
                'discount_rate' => 0,
                'total' => 8400,
            ]);

            $invoice3->items()->create([
                'description' => 'Optimisation technique',
                'quantity' => 1,
                'unit_price' => 5000,
                'tax_rate' => 20,
                'discount_rate' => 0,
                'total' => 6000,
            ]);
        }

        // Invoice 4 - Draft
        $invoice4 = CustomerInvoice::create([
            'company_id' => $company->id,
            'customer_id' => $customers->first()->id,
            'invoice_number' => 'INV-2026-0004',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'subtotal' => 6000,
            'tax_amount' => 1200,
            'discount_amount' => 300,
            'total' => 6900,
            'paid_amount' => 0,
            'balance' => 6900,
            'notes' => 'Formation WordPress',
            'created_by' => 1,
        ]);

        $invoice4->items()->create([
            'description' => 'Formation WordPress - 2 jours',
            'quantity' => 2,
            'unit_price' => 3000,
            'tax_rate' => 20,
            'discount_rate' => 5,
            'total' => 6840,
        ]);

        $this->command->info('✅ Données de facturation créées avec succès !');
        $this->command->info("- 1 Devis");
        $this->command->info("- 4 Factures (Payée, Partielle, Envoyée, Brouillon)");
        $this->command->info("- 2 Paiements");
    }
}
