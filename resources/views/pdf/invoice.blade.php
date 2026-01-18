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
            color: #34c38f; {{-- Couleur success pour les factures --}}
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
            border-bottom: 2px solid #34c38f;
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
            background-color: #34c38f;
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
                <h2 style="color: #34c38f; margin: 0;">{{ $invoice->company->name }}</h2>
            </div>
            <div class="company-info">
                <strong>{{ $invoice->company->name }}</strong><br>
                {{ $invoice->company->address }}<br>
                {{ $invoice->company->city }}, {{ $invoice->company->country }}<br>
                Tel: {{ $invoice->company->phone }}<br>
                Email: {{ $invoice->company->email }}
            </div>
            <div class="clear"></div>
        </div>

        <div class="invoice-title">
            <h1>FACTURE</h1>
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
                <strong>{{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}</strong><br>
                @if($invoice->customer->company_name)
                    {{ $invoice->customer->company_name }}<br>
                @endif
                {{ $invoice->customer->address }}<br>
                {{ $invoice->customer->phone }}<br>
                {{ $invoice->customer->email }}
            </div>
            <div class="invoice-meta">
                <h3>INFOS FACTURE</h3>
                <strong>Date facture:</strong> {{ $invoice->invoice_date->format('d/m/Y') }}<br>
                <strong>Échéance:</strong> {{ $invoice->due_date->format('d/m/Y') }}<br>
                @if($invoice->quote)
                    <strong>Réf Devis:</strong> {{ $invoice->quote->quote_number }}<br>
                @endif
            </div>
            <div class="clear"></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right" style="width: 80px;">Qté</th>
                    <th class="text-right" style="width: 120px;">P.U (HT)</th>
                    <th class="text-right" style="width: 80px;">TVA</th>
                    <th class="text-right" style="width: 150px;">Total (HT)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                    <td class="text-right">{{ $item->tax_rate }}%</td>
                    <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 2, ',', ' ') }} DH</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="text-right" style="border: none;">Total H.T :</td>
                <td class="text-right fw-bold" style="border: none; width: 120px;">{{ number_format($invoice->subtotal, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td class="text-right" style="border: none;">TVA Totale :</td>
                <td class="text-right fw-bold" style="border: none;">{{ number_format($invoice->tax_amount, 2, ',', ' ') }} DH</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr>
                <td class="text-right" style="border: none;">Remise :</td>
                <td class="text-right fw-bold" style="border: none;">-{{ number_format($invoice->discount_amount, 2, ',', ' ') }} DH</td>
            </tr>
            @endif
            <tr class="grand-total-row">
                <td class="text-right" style="color: white; font-weight: bold; padding: 10px;">TOTAL TTC :</td>
                <td class="text-right" style="color: white; font-weight: bold; font-size: 18px; padding: 10px;">{{ number_format($invoice->total, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td class="text-right" style="border: none; padding-top: 10px;">Montant Payé :</td>
                <td class="text-right fw-bold" style="border: none; color: #34c38f; padding-top: 10px;">{{ number_format($invoice->paid_amount, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td class="text-right" style="border: none;">Reste à payer :</td>
                <td class="text-right fw-bold" style="border: none; color: #f46a6a;">{{ number_format($invoice->balance, 2, ',', ' ') }} DH</td>
            </tr>
        </table>
        <div class="clear"></div>

        @if($invoice->notes)
        <div class="notes">
            <h4>Notes / Instructions de paiement :</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif

        <div class="footer">
            {{ $invoice->company->name }} - {{ $invoice->company->tax_id }} - {{ $invoice->company->address }}
            <br>
            Merci de votre confiance !
        </div>
    </div>
</body>
</html>
