<!doctype html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 portrait; margin: 34pt 30pt 24pt; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #0f172a; margin: 0; }
        .page { width: 100%; margin: 0; }
        .header { width: 100%; margin-bottom: 18pt; }
        .header-left { float: left; width: 52%; line-height: 1.35; }
        .header-right { float: right; width: 40%; line-height: 1.35; text-align: right; }
        .logo { max-height: 85.04pt; max-width: 178.43pt; object-fit: contain; margin-bottom: 7pt; }
        .clear { clear: both; }
        .document-heading { color: #190F6E; font-size: 20pt; font-weight: 700; margin: 0 0 3pt; text-transform: uppercase; }
        .title { color: #190F6E; font-size: 14pt; font-weight: 700; margin: 0 0 3pt; }
        .muted { color: #475569; font-size: 10pt; margin: 0 0 2pt; }
        .section { margin-top: 12pt; }
        .line { margin: 0 0 3pt; }
        .reservation-main { float: left; width: 57%; line-height: 1.35; }
        .invoice-client { float: right; width: 40%; border: 1pt solid #cbd5e1; border-radius: 4pt; padding: 8pt; line-height: 1.35; }
        .invoice-client-title { color: #190F6E; margin: 0 0 4pt; font-weight: 700; }
        .invoice-client-line { margin: 0 0 3pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 10pt; }
        th, td { border: 0; border-bottom: 1pt solid #cbd5e1; padding: 8pt 6pt; font-size: 10pt; vertical-align: top; }
        th { background: #190F6E; color: #ffffff; font-weight: 700; text-align: left; }
        .center { text-align: center; }
        .right { text-align: right; }
        .totals { width: 220pt; margin-left: auto; margin-top: 10pt; border-collapse: collapse; }
        .totals td { border: 0; padding: 3pt 0; font-size: 10pt; }
        .total-due td { background: #190F6E; color: #ffffff; font-weight: 700; padding: 8pt 10pt; }
        .total-due .label { color: #AFEB01; }
        .sign-wrap { margin-top: 8pt; width: 160pt; height: 86pt; margin-left: auto; position: relative; }
        .sign { position: absolute; z-index: 2; left: 22pt; top: 14pt; max-width: 124pt; max-height: 54pt; object-fit: contain; }
        .stamp { position: absolute; z-index: 1; left: 24pt; top: 6pt; max-width: 116pt; max-height: 84pt; object-fit: contain; }
        .footer-wrap { margin-top: 18pt; border-top: 1pt solid #94a3b8; padding-top: 12pt; }
        .footer-title { color: #190F6E; font-size: 14pt; font-weight: 700; margin: 0 0 8pt; }
        .footer-col { float: left; width: 31%; margin-right: 3%; line-height: 1.35; }
        .footer-col.last { margin-right: 0; }
        .footer-heading { color: #190F6E; font-size: 10pt; font-weight: 700; margin: 0 0 4pt; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-left">
            @if(!empty($company['logo_url']))
                <img src="{{ $company['logo_url'] }}" alt="Logo" class="logo">
            @endif
            <div>{{ $company['address'] ?? '' }}</div>
            <div>{{ trim(($company['zip'] ?? '').' '.($company['city'] ?? '')) }}</div>
            @if(!empty($company['phone']))<div>{{ $company['phone'] }}</div>@endif
        </div>
        <div class="header-right">
            <h1 class="document-heading">{{ mb_strtoupper($title, 'UTF-8') }}</h1>
            @if(!empty($company['email']))<div>Email: {{ $company['email'] }}</div>@endif
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
        <div class="reservation-main">
            <p class="line">Aranžman: {{ $reservation['arrangement']['code'] ?? '' }} - {{ $reservation['arrangement']['name'] ?? '' }}</p>
            <p class="line">Destinacija: {{ $reservation['arrangement']['destination'] ?? '' }}</p>
            <p class="line">Termin: {{ $reservation['arrangement']['departure_date'] ?? '-' }} - {{ $reservation['arrangement']['return_date'] ?? '-' }}</p>
            <p class="line">Status: {{ $reservation['status'] ?? '' }}</p>
            @if($is_racun && !empty($reservation['fiscal_invoice_number']))
                <p class="line">Broj fiskalnog računa: {{ $reservation['fiscal_invoice_number'] }}</p>
            @endif
            <p class="line">Napomena: {{ $reservation['note'] ?: '-' }}</p>
        </div>
        <div class="invoice-client">
            <p class="invoice-client-title">Za:</p>
            <p class="invoice-client-line">{{ $invoice_client['full_name'] ?? '-' }}</p>
            <p class="invoice-client-line">{{ $invoice_client['address'] ?? '-' }}</p>
            <p class="invoice-client-line">{{ $invoice_client['phone'] ?? '-' }}</p>
            <p class="invoice-client-line">{{ $invoice_client['email'] ?? '-' }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th style="width: 10%">#</th>
                    <th style="width: 55%">Opis stavke</th>
                    <th class="right" style="width: 17%">Jed. cijena</th>
                    <th class="center" style="width: 8%">Kol.</th>
                    <th class="right" style="width: 10%">Ukupno</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line_items as $item)
                    <tr>
                        <td>{{ $item['index'] }}</td>
                        <td>{{ $item['description'] }}</td>
                        <td class="right">{{ number_format((float) $item['amount'], 2, '.', '') }} KM</td>
                        <td class="center">1</td>
                        <td class="right">{{ number_format((float) $item['amount'], 2, '.', '') }} KM</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(!empty($totals))
        <table class="totals">
            <tr>
                <td>Dodaci</td>
                <td class="right">{{ number_format((float) $totals['add_ons_total'], 2, '.', '') }} KM</td>
            </tr>
            <tr>
                <td>{{ !empty($company['u_pdv_sistemu']) ? 'Međuzbir (bez PDV)' : 'Međuzbir' }}</td>
                <td class="right">{{ number_format((float) $totals['subtotal_without_pdv'], 2, '.', '') }} KM</td>
            </tr>
            @if(!empty($company['u_pdv_sistemu']))
                <tr>
                    <td>PDV (17%)</td>
                    <td class="right">{{ number_format((float) $totals['pdv_amount'], 2, '.', '') }} KM</td>
                </tr>
            @endif
            <tr>
                <td>Popust</td>
                <td class="right">{{ number_format((float) $totals['discount_total'], 2, '.', '') }} KM</td>
            </tr>
            <tr class="total-due">
                <td class="label">UKUPNO ZA UPLATU:</td>
                <td class="right">{{ number_format((float) $totals['final_total'], 2, '.', '') }} KM</td>
            </tr>
        </table>
    @else
        <table class="totals">
            <tr class="total-due">
                <td class="label">UKUPNO ZA UPLATU:</td>
                <td class="right">{{ number_format((float) $total, 2, '.', '') }} KM</td>
            </tr>
        </table>
    @endif

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

    <div class="footer-wrap">
        <p class="footer-title">Hvala na ukazanom povjerenju</p>
        <div class="footer-col">
            <p class="footer-heading">Kontakt</p>
            <div>{{ $company['email'] ?: '-' }}</div>
            <div>{{ $company['phone'] ?: '-' }}</div>
            <div>{{ trim(($company['address'] ?: '-').', '.($company['zip'] ?? '').' '.($company['city'] ?? '')) }}</div>
        </div>
        <div class="footer-col">
            <p class="footer-heading">Podaci firme i plaćanje</p>
            <div>ID: {{ $company['id_number'] ?: '-' }}</div>
            <div>MBS: {{ $company['maticni_broj_subjekta_upisa'] ?: '-' }}</div>
            <div>PDV: {{ !empty($company['u_pdv_sistemu']) ? ($company['vat_number'] ?: '-') : 'Nije u PDV sistemu' }}</div>
            <div>Broj računa: {{ $company['trn'] ?: '-' }}</div>
            <div>Banka: {{ $company['bank'] ?: '-' }}</div>
            <div>IBAN: {{ $company['iban'] ?: '-' }}</div>
            <div>SWIFT: {{ $company['swift'] ?: '-' }}</div>
        </div>
        <div class="footer-col last">
            <p class="footer-heading">Napomena / Uslovi</p>
            <div>{{ $reservation['note'] ?: '-' }}</div>
        </div>
        <div class="clear"></div>
    </div>
</div>
</body>
</html>
