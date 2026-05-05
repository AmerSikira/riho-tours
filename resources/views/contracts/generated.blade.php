<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $document_title ?? ($contract['number'] ?? 'Ugovor') }}</title>
    <style>
        @page { margin: 10mm 10mm 16mm; }
        body { font-family: DejaVu Serif, serif; font-size: 11px; line-height: 1.32; color: #000; margin: 0; }
        .page { padding: 0 0 16mm; }
        h1, h2, h3 { margin: 0 0 6px 0; line-height: 1.2; color: #000; }
        p { margin: 0 0 4px 0; color: #000; }
        .document-header { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .document-header td { vertical-align: top; color: #000; }
        .document-header-company { width: 55%; font-size: 9.5px; font-weight: 700; line-height: 1.25; }
        .document-header-logo { width: 45%; text-align: right; }
        .document-header-logo img { max-height: 58px; max-width: 210px; object-fit: contain; }
        .header-line { border-top: 1px solid #000; margin: 5px 0 8px; }
        .document-meta { margin-bottom: 8px; }
        .document-meta .meta-line { font-size: 10px; margin: 0 0 2px 0; }
        .contract-content { color: #000; }
        .contract-content .contract-title { text-align: center; font-size: 14px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; }
        .contract-content .section-label { font-size: 11px; font-weight: 700; text-decoration: underline; margin: 6px 0 3px; color: #000; }
        .contract-content .block { margin-bottom: 6px; }
        .contract-content .strong { font-weight: 700; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 6px; color: #000; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 4px 5px; font-size: 10px; }
        .items-table th { text-align: left; font-weight: 700; }
        .items-table tfoot td { font-weight: 700; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .travelers-list { margin: 4px 0; padding-left: 18px; }
        .travelers-list li { margin: 1px 0; }
        .page-break { page-break-before: always; break-before: page; }
        .document-footer-wrap { position: fixed; left: 10mm; right: 10mm; bottom: 6mm; border-top: 1px solid #000; padding-top: 3px; text-align: center; }
        .document-footer { display: inline-block; max-width: 100%; font-size: 9px; font-weight: 700; line-height: 1.2; text-align: center; color: #000; }
    </style>
</head>
<body>
<div class="page">
    @php
        $companyLines = array_filter([
            $company['name'] ?? '',
            $company['address'] ?? '',
            ($company['phone'] ?? '') !== '' ? 'Tel: '.$company['phone'] : '',
            ($company['email'] ?? '') !== '' ? 'Email: '.$company['email'] : '',
            ($company['id_number'] ?? '') !== '' ? 'ID: '.$company['id_number'] : '',
            ($company['vat_number'] ?? '') !== '' ? 'PDV: '.$company['vat_number'] : '',
            ($company['representative_name'] ?? '') !== '' ? 'TRN: '.$company['representative_name'] : '',
        ], static fn ($line) => trim((string) $line) !== '');
    @endphp

    <table class="document-header">
        <tr>
            <td class="document-header-company">
                @foreach ($companyLines as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </td>
            <td class="document-header-logo">
                @if (! empty($company['logo_url']))
                    <img src="{{ $company['logo_url'] }}" alt="Logo">
                @endif
            </td>
        </tr>
    </table>
    <div class="header-line"></div>

    <div class="document-meta">
        <div class="meta-line"><span class="strong">Datum:</span> {{ $contract['date'] ?? '' }}</div>
        <div class="meta-line"><span class="strong">Broj:</span> {{ $contract['number'] ?? '' }}</div>
    </div>

    <div class="contract-content">
        {!! $html !!}
    </div>

    @php
        $footerParts = array_filter([
            trim(implode(' ', array_filter([
                $company['name'] ?? '',
                '/',
                implode(', ', array_filter([
                    $company['address'] ?? '',
                ])),
            ]))),
            ($company['id_number'] ?? '') !== '' ? 'ID: '.$company['id_number'] : '',
            ($company['vat_number'] ?? '') !== '' ? 'PDV broj: '.$company['vat_number'] : '',
            ($company['registry_number'] ?? '') !== '' ? 'Matični broj subjekta upisa: '.$company['registry_number'] : '',
            ($company['bank_name'] ?? '') !== '' ? 'Bank name: '.$company['bank_name'] : '',
            ($company['representative_name'] ?? '') !== '' ? 'TRN: '.$company['representative_name'] : '',
            ($company['iban'] ?? '') !== '' ? 'IBAN: '.$company['iban'] : '',
            ($company['swift'] ?? '') !== '' ? 'SWIFT: '.$company['swift'] : '',
        ], static fn ($part) => trim((string) $part) !== '');
    @endphp

    <div class="document-footer-wrap">
        <div class="document-footer">{{ implode(' / ', $footerParts) }}</div>
    </div>
</div>
</body>
</html>
