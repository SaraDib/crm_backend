<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis #{{ $quote->quote_number }}</title>
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
        .quote-title {
            margin-top: 50px;
            margin-bottom: 30px;
        }
        .quote-title h1 {
            color: #5b73e8;
            margin: 0;
            font-size: 28px;
        }
        .quote-title p {
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
        .quote-meta {
            float: right;
            width: 40%;
            text-align: right;
        }
        .client-info h3, .quote-meta h3 {
            border-bottom: 2px solid #5b73e8;
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
            background-color: #5b73e8;
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
        .notes h4 {
            margin-bottom: 5px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                {{-- Si logo existe : <img src="{{ public_path('storage/'.$quote->company->logo) }}" width="150" alt="Logo"> --}}
                <h2 style="color: #5b73e8; margin: 0;">{{ $quote->company->name }}</h2>
            </div>
            <div class="company-info">
                <strong>{{ $quote->company->name }}</strong><br>
                {{ $quote->company->address }}<br>
                {{ $quote->company->city }}, {{ $quote->company->country }}<br>
                Tel: {{ $quote->company->phone }}<br>
                Email: {{ $quote->company->email }}
            </div>
            <div class="clear"></div>
        </div>

        <div class="quote-title">
            <h1>DEVIS</h1>
            <p>Réf: {{ $quote->quote_number }}</p>
        </div>

        <div class="details">
            <div class="client-info">
                <h3>DESTINATAIRE</h3>
                <strong>{{ $quote->customer->first_name }} {{ $quote->customer->last_name }}</strong><br>
                @if($quote->customer->company_name)
                    {{ $quote->customer->company_name }}<br>
                @endif
                {{ $quote->customer->address }}<br>
                {{ $quote->customer->phone }}<br>
                {{ $quote->customer->email }}
            </div>
            <div class="quote-meta">
                <h3>INFOS DEVIS</h3>
                <strong>Date:</strong> {{ $quote->quote_date->format('d/m/Y') }}<br>
                <strong>Validité:</strong> {{ $quote->valid_until->format('d/m/Y') }}<br>
                <strong>Statut:</strong> {{ ucfirst($quote->status) }}
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
                @foreach($quote->items as $item)
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
                <td class="text-right fw-bold" style="border: none; width: 120px;">{{ number_format($quote->subtotal, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td class="text-right" style="border: none;">TVA Totale :</td>
                <td class="text-right fw-bold" style="border: none;">{{ number_format($quote->tax_amount, 2, ',', ' ') }} DH</td>
            </tr>
            @if($quote->discount_amount > 0)
            <tr>
                <td class="text-right" style="border: none;">Remise :</td>
                <td class="text-right fw-bold" style="border: none;">-{{ number_format($quote->discount_amount, 2, ',', ' ') }} DH</td>
            </tr>
            @endif
            <tr class="grand-total-row">
                <td class="text-right" style="color: white; font-weight: bold; padding: 10px;">TOTAL TTC :</td>
                <td class="text-right" style="color: white; font-weight: bold; font-size: 18px; padding: 10px;">{{ number_format($quote->total, 2, ',', ' ') }} DH</td>
            </tr>
        </table>
        <div class="clear"></div>

        <div class="notes">
            @if($quote->notes)
                <h4>Notes :</h4>
                <p>{{ $quote->notes }}</p>
            @endif

            @if($quote->terms)
                <h4>Conditions Générales :</h4>
                <p>{{ $quote->terms }}</p>
            @endif
        </div>

        <div class="footer">
            {{ $quote->company->name }} - {{ $quote->company->tax_id }} - {{ $quote->company->address }}
            <br>
            Généré par CRM Entreprise le {{ date('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
