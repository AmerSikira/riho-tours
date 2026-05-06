<!doctype html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 12mm 18mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 0; }
        .page { width: 186mm; margin: 0 auto; position: relative; padding-bottom: 22mm; }
        .header { width: 100%; margin-bottom: 14px; }
        .header-left { float: left; width: 56%; }
        .header-right { float: right; width: 44%; font-size: 10px; font-weight: 700; line-height: 1.3; text-align: left; }
        .logo { max-height: 80px; max-width: 220px; object-fit: contain; }
        .clear { clear: both; }
        .title { font-size: 22px; font-weight: 700; margin: 0 0 4px 0; }
        .muted { color: #6b7280; font-size: 10px; margin: 0 0 2px 0; }
        .section { margin-top: 12px; }
        .section-title { margin: 0 0 6px 0; font-size: 12px; font-weight: 700; }
        .line { margin: 0 0 3px 0; }
        .reservation-layout { display: flex; gap: 12px; align-items: flex-start; }
        .reservation-main { flex: 1 1 auto; min-width: 0; }
        .invoice-client { width: 42%; border: 1px solid #d1d5db; padding: 8px; border-radius: 6px; }
        .invoice-client-title { margin: 0 0 6px 0; font-size: 12px; font-weight: 700; }
        .invoice-client-line { margin: 0 0 3px 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; font-size: 10px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .center { text-align: center; }
        .right { text-align: right; }
        .totals { width: 72mm; margin-left: auto; margin-top: 12px; }
        .totals td { border: 1px solid #d1d5db; font-size: 11px; }
        .totals tr:last-child td { font-weight: 700; }
        .sign-wrap { margin-top: 14px; width: 240px; height: 150px; margin-left: auto; position: relative; }
        .sign { position: absolute; z-index: 2; left: 50%; top: 8px; transform: translateX(-50%); max-height: 72px; max-width: 210px; object-fit: contain; }
        .stamp { position: absolute; z-index: 1; left: 50%; bottom: 0; transform: translateX(-50%); max-height: 112px; max-width: 210px; object-fit: contain; }
        .footer-wrap { position: fixed; left: 12mm; right: 12mm; bottom: 6mm; text-align: center; }
        .footer { display: inline-block; max-width: 176mm; font-size: 9px; font-weight: 700; line-height: 1.3; text-align: center; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-left">
            @if(!empty($company['logo_url']))
                <img src="{{ $company['logo_url'] }}" alt="Logo" class="logo">
            @endif
        </div>
        <div class="header-right">
            <div>{{ $company['name'] ?? '' }}</div>
            <div>{{ trim(($company['address'] ?? '').' '.($company['zip'] ?? '').' '.($company['city'] ?? '')) }}</div>
            @if(!empty($company['id_number']))<div>ID: {{ $company['id_number'] }}</div>@endif
            @if(!empty($company['vat_number']))<div>PDV: {{ $company['vat_number'] }}</div>@endif
            @if(!empty($company['trn']))<div>TRN: {{ $company['trn'] }}</div>@endif
            @if(!empty($company['email']))<div>Email: {{ $company['email'] }}</div>@endif
            @if(!empty($company['phone']))<div>Tel: {{ $company['phone'] }}</div>@endif
        </div>
        <div class="clear"></div>
    </div>

    <div>
        <h1 class="title">{{ $title }}</h1>
        <p class="muted">Datum: {{ $date }}</p>
        <p class="muted">Broj: {{ $number }}</p>
        @if($is_racun && !empty($reservation['fiscal_invoice_number']))
            <p class="muted">Broj fiskalnog računa: {{ $reservation['fiscal_invoice_number'] }}</p>
        @endif
        @if($is_racun && !empty($company['broj_kase']))
            <p class="muted">Broj kase: {{ $company['broj_kase'] }}</p>
        @endif
    </div>

    <div class="section">
        <div class="reservation-layout">
            <div class="reservation-main">
                <h3 class="section-title">Podaci o rezervaciji</h3>
                <p class="line"><strong>Aranžman:</strong> {{ $reservation['arrangement']['code'] ?? '' }} - {{ $reservation['arrangement']['name'] ?? '' }}</p>
                <p class="line"><strong>Destinacija:</strong> {{ $reservation['arrangement']['destination'] ?? '' }}</p>
                <p class="line"><strong>Termin:</strong> {{ $reservation['arrangement']['departure_date'] ?? '' }} - {{ $reservation['arrangement']['return_date'] ?? '' }}</p>
                <p class="line"><strong>Status:</strong> {{ $reservation['status'] ?? '' }}</p>
                <p class="line"><strong>Napomena:</strong> {{ $reservation['note'] ?: '-' }}</p>
            </div>
            <div class="invoice-client">
                <p class="invoice-client-title">Klijent</p>
                <p class="invoice-client-line">{{ $invoice_client['full_name'] ?? '-' }}</p>
                <p class="invoice-client-line">{{ $invoice_client['address'] ?? '-' }}</p>
                <p class="invoice-client-line">{{ $invoice_client['phone'] ?? '-' }}</p>
                <p class="invoice-client-line">{{ $invoice_client['email'] ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="section">
        <h3 class="section-title">Stavke</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 8%">#</th>
                    <th>Opis</th>
                    <th class="right" style="width: 22%">Iznos (KM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line_items as $item)
                    <tr>
                        <td class="center">{{ $item['index'] }}</td>
                        <td>{{ $item['description'] }}</td>
                        <td class="right">{{ number_format((float) $item['amount'], 2, '.', '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="totals">
        <tr>
            <td>Ukupno</td>
            <td class="right">{{ number_format((float) $total, 2, '.', '') }} KM</td>
        </tr>
    </table>

    @if(!empty($company['potpis_url']) || !empty($company['pecat_url']))
        <div class="sign-wrap">
            @if(!empty($company['potpis_url']))
                <img src="{{ $company['potpis_url'] }}" alt="Potpis" class="sign">
            @endif
            @if(!empty($company['pecat_url']))
                <img src="{{ $company['pecat_url'] }}" alt="Pečat" class="stamp">
            @endif
        </div>
    @endif

    @php
        $footerParts = array_values(array_filter([
            trim((string) ($company['name'] ?? '')),
            trim((string) trim(($company['address'] ?? '').' '.($company['zip'] ?? '').' '.($company['city'] ?? ''))),
            trim((string) ($company['id_number'] ?? '')),
            trim((string) ($company['vat_number'] ?? '')),
            trim((string) ($company['maticni_broj_subjekta_upisa'] ?? '')),
            trim((string) ($company['bank'] ?? '')),
            trim((string) ($company['trn'] ?? '')),
            trim((string) ($company['iban'] ?? '')),
            trim((string) ($company['swift'] ?? '')),
        ], static fn (string $part): bool => $part !== ''));
    @endphp
    <div class="footer-wrap">
        <div class="footer">{{ implode(' ; ', $footerParts) }}</div>
    </div>
</div>
</body>
</html>
