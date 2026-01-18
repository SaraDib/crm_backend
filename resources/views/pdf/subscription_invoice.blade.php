<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            margin-bottom: 50px;
        }
        .logo {
            float: left;
            width: 150px;
        }
        .company-info {
            float: right;
            text-align: right;
            font-size: 12px;
        }
        .clear {
            clear: both;
        }
        .invoice-title {
            margin-top: 50px;
            margin-bottom: 30px;
        }
        .invoice-title h1 {
            color: #556ee6;
            margin: 0;
            font-size: 28px;
        }
        .invoice-title p {
            margin: 5px 0 0 0;
            color: #777;
        }
        .details {
            margin-bottom: 40px;
        }
        .client-info {
            float: left;
            width: 50%;
        }
        .invoice-meta {
            float: right;
            width: 40%;
            text-align: right;
        }
        .client-info h3, .invoice-meta h3 {
            border-bottom: 2px solid #556ee6;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 12px;
            text-align: left;
            color: #495057;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .text-right {
            text-align: right;
        }
        .totals-table {
            float: right;
            width: 40%;
            border: none;
        }
        .grand-total-row {
            background-color: #556ee6;
        }
        .fw-bold {
            font-weight: bold;
        }
        .footer {
            margin-top: 100px;
            font-size: 11px;
            color: #777;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .notes {
            margin-top: 40px;
            font-size: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .status-paid { background-color: #34c38f; color: white; }
        .status-unpaid { background-color: #f46a6a; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <h2 style="color: #556ee6; margin: 0;">VOTRE CRM</h2>
            </div>
            <div class="company-info">
                <strong>VOTRE ENTREPRISE</strong><br>
                Adresse de votre entreprise<br>
                Ville, Pays<br>
                Tel: +212 6XX XX XX XX<br>
                Email: contact@votrecrm.com
            </div>
            <div class="clear"></div>
        </div>

        <div class="invoice-title">
            <h1>FACTURE D'ABONNEMENT</h1>
            <p>N°: {{ $invoice->invoice_number }}</p>
            @if($invoice->status === 'paid')
                <div class="status-badge status-paid">PAYÉE</div>
            @else
                <div class="status-badge status-unpaid">EN ATTENTE</div>
            @endif
        </div>

        <div class="details">
            <div class="client-info">
                <h3>FACTURÉ À</h3>
                <strong>{{ $invoice->company->name }}</strong><br>
                @if($invoice->company->address)
                    {{ $invoice->company->address }}<br>
                @endif
                @if($invoice->company->phone)
                    {{ $invoice->company->phone }}<br>
                @endif
                {{ $invoice->company->email }}
            </div>
            <div class="invoice-meta">
                <h3>INFOS FACTURE</h3>
                <strong>Date facture:</strong> {{ $invoice->created_at->format('d/m/Y') }}<br>
                <strong>Échéance:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}<br>
                @if($invoice->paid_at)
                    <strong>Payée le:</strong> {{ $invoice->paid_at->format('d/m/Y') }}<br>
                @endif
            </div>
            <div class="clear"></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Période</th>
                    <th class="text-right" style="width: 150px;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $invoice->subscription->plan->name }}</strong><br>
                        <small>{{ $invoice->notes }}</small>
                    </td>
                    <td>{{ $invoice->period_start->format('d/m/Y') }} - {{ $invoice->period_end->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($invoice->amount, 2, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="text-right" style="border: none;">Sous-total :</td>
                <td class="text-right fw-bold" style="border: none; width: 120px;">{{ number_format($invoice->amount, 2, ',', ' ') }} {{ $invoice->currency }}</td>
            </tr>
            <tr>
                <td class="text-right" style="border: none;">TVA :</td>
                <td class="text-right fw-bold" style="border: none;">{{ number_format($invoice->vat_amount, 2, ',', ' ') }} {{ $invoice->currency }}</td>
            </tr>
            <tr class="grand-total-row">
                <td class="text-right" style="color: white; font-weight: bold; padding: 10px;">TOTAL :</td>
                <td class="text-right" style="color: white; font-weight: bold; font-size: 18px; padding: 10px;">{{ number_format($invoice->total_amount, 2, ',', ' ') }} {{ $invoice->currency }}</td>
            </tr>
        </table>
        <div class="clear"></div>

        @if($invoice->payment_method)
        <div class="notes">
            <h4>Mode de paiement :</h4>
            <p>{{ ucfirst(str_replace('_', ' ', $invoice->payment_method)) }}</p>
        </div>
        @endif

        <div class="footer">
            VOTRE CRM - Merci pour votre confiance !
            <br>
            Ce document est une facture générée automatiquement.
        </div>
    </div>
</body>
</html>
