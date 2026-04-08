<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Reservation;
use App\Models\ReservationClient;
use App\Models\Setting;
use App\Services\Contracts\ContractDataBuilder;
use App\Services\Contracts\ContractGenerationService;
use App\Services\Contracts\ContractTemplateRenderer;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractsController extends Controller
{
    /**
     * Display template overview for contract administration.
     */
    public function templatesIndex(): Response
    {
        $templates = ContractTemplate::query()
            ->select([
                'id',
                'template_key',
                'version',
                'name',
                'description',
                'is_active',
                'subagentski_ugovor',
                'created_at',
            ])
            ->orderBy('template_key')
            ->orderByDesc('version')
            ->get()
            ->groupBy('template_key')
            ->map(fn ($rows) => $rows->values())
            ->values();

        return Inertia::render('contracts/templates/index', [
            'templatesByKey' => $templates,
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    /**
     * Store new contract template or create next version of an existing key.
     */
    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template_key' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'html_template' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'subagentski_ugovor' => ['nullable', 'boolean'],
            'previous_version_id' => ['nullable', 'exists:contract_templates,id'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $nextVersion = ContractTemplate::nextVersionFor((string) $validated['template_key']);

            ContractTemplate::query()->create([
                'template_key' => (string) $validated['template_key'],
                'version' => $nextVersion,
                'name' => (string) $validated['name'],
                'description' => $validated['description'] ?? null,
                'html_template' => (string) $validated['html_template'],
                'placeholder_hints_json' => null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'subagentski_ugovor' => (bool) ($validated['subagentski_ugovor'] ?? false),
                'previous_version_id' => $validated['previous_version_id'] ?? null,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);
        });

        return to_route('ugovori.predlosci.index')->with('status', 'Predložak ugovora je sačuvan.');
    }

    /**
     * Show edit page for one contract template version.
     */
    public function editTemplate(ContractTemplate $predlozak): Response
    {
        return Inertia::render('contracts/templates/edit', [
            'template' => [
                'id' => $predlozak->id,
                'template_key' => $predlozak->template_key,
                'version' => $predlozak->version,
                'name' => $predlozak->name,
                'description' => $predlozak->description,
                'html_template' => $predlozak->html_template,
                'is_active' => $predlozak->is_active,
                'subagentski_ugovor' => $predlozak->subagentski_ugovor,
            ],
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    /**
     * Update one existing contract template version.
     */
    public function updateTemplate(Request $request, ContractTemplate $predlozak): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'html_template' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'subagentski_ugovor' => ['nullable', 'boolean'],
        ]);

        $predlozak->update([
            'name' => (string) $validated['name'],
            'description' => $validated['description'] ?? null,
            'html_template' => (string) $validated['html_template'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'subagentski_ugovor' => (bool) ($validated['subagentski_ugovor'] ?? false),
            'updated_by' => $request->user()?->id,
        ]);

        return to_route('ugovori.predlosci.edit', $predlozak)
            ->with('status', 'Predložak ugovora je ažuriran.');
    }

    /**
     * Generate contract and return PDF download response.
     */
    public function generate(
        Request $request,
        Reservation $rezervacija,
        ContractGenerationService $generationService
    ): RedirectResponse|StreamedResponse {
        try {
            $validated = $request->validate([
                'contract_template_id' => ['nullable', 'exists:contract_templates,id'],
            ]);

            $template = $this->resolveTemplate($rezervacija, $validated['contract_template_id'] ?? null);
            if (! $template) {
                return back()->with('error', 'Ne postoji aktivan predložak ugovora za tip aranžmana.');
            }

            $generated = $generationService->generate($rezervacija, $template, $request->user()?->id);
            if (! $generated->rendered_pdf_path || ! Storage::disk('public')->exists($generated->rendered_pdf_path)) {
                return back()->with('error', 'PDF nije moguće preuzeti jer datoteka ne postoji.');
            }

            $filename = $this->buildContractFilename($generated->contract_number, (string) $generated->id);

            return Storage::disk('public')->download(
                $generated->rendered_pdf_path,
                $filename
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to generate contract PDF.', [
                'reservation_id' => (string) $rezervacija->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Generisanje ugovora trenutno nije moguće. Pokušajte ponovo.');
        }
    }

    /**
     * Generate reservation contract PDF and stream it inline or as download.
     */
    public function pdf(
        Request $request,
        Reservation $rezervacija,
        ContractGenerationService $generationService
    ): RedirectResponse|StreamedResponse|HttpResponse {
        try {
            $template = $this->resolveTemplate($rezervacija);
            if (! $template) {
                return back()->with('error', 'Ne postoji aktivan predložak ugovora za tip aranžmana.');
            }

            $generated = $generationService->generate($rezervacija, $template, $request->user()?->id);
            if (! $generated->rendered_pdf_path || ! Storage::disk('public')->exists($generated->rendered_pdf_path)) {
                return response()->view('contracts.generated', [
                    'html' => (string) ($generated->rendered_html ?? ''),
                    'company' => data_get($generated->snapshot_data_json, 'data.company', []),
                    'contract' => data_get($generated->snapshot_data_json, 'data.contract', []),
                    'document_title' => (string) ($generated->contract_number ?: 'Ugovor'),
                ]);
            }

            $filename = $this->buildContractFilename($generated->contract_number, (string) $generated->id);
            if ($request->boolean('download')) {
                return Storage::disk('public')->download($generated->rendered_pdf_path, $filename);
            }

            return Storage::disk('public')->response($generated->rendered_pdf_path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to open contract PDF.', [
                'reservation_id' => (string) $rezervacija->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Otvaranje ugovora trenutno nije moguće. Pokušajte ponovo.');
        }
    }

    /**
     * Generate reservation contract PDF for a signed public URL.
     */
    public function publicPdf(
        Request $request,
        Reservation $rezervacija,
        ContractGenerationService $generationService
    ): StreamedResponse|HttpResponse {
        try {
            $template = $this->resolveTemplate($rezervacija);
            if (! $template) {
                abort(404);
            }

            $generated = $generationService->generate($rezervacija, $template, null);
            if (! $generated->rendered_pdf_path || ! Storage::disk('public')->exists($generated->rendered_pdf_path)) {
                return response()->view('contracts.generated', [
                    'html' => (string) ($generated->rendered_html ?? ''),
                    'company' => data_get($generated->snapshot_data_json, 'data.company', []),
                    'contract' => data_get($generated->snapshot_data_json, 'data.contract', []),
                    'document_title' => (string) ($generated->contract_number ?: 'Ugovor'),
                ]);
            }

            $filename = $this->buildContractFilename($generated->contract_number, (string) $generated->id);
            if ($request->boolean('download')) {
                return Storage::disk('public')->download($generated->rendered_pdf_path, $filename);
            }

            return Storage::disk('public')->response($generated->rendered_pdf_path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to open public contract PDF.', [
                'reservation_id' => (string) $rezervacija->id,
                'error' => $exception->getMessage(),
            ]);

            abort(404);
        }
    }

    /**
     * Open financial reservation document as HTML preview and optionally download as PDF.
     */
    public function financialDocumentPreview(
        Request $request,
        Reservation $rezervacija,
        string $tip,
        ?int $rata = null
    ): \Symfony\Component\HttpFoundation\Response {
        $payload = $this->buildFinancialDocumentPayload($rezervacija, $tip, $rata);
        if (! $payload) {
            abort(404);
        }

        $pdf = DomPdf::loadView('documents.reservation-financial', $payload)
            ->setPaper('a4', 'portrait');

        if ($request->boolean('download')) {
            return $pdf->download((string) ($payload['filename'] ?? 'dokument.pdf'));
        }

        return $pdf->stream((string) ($payload['filename'] ?? 'dokument.pdf'));
    }

    /**
     * Open financial reservation document over signed public URL.
     */
    public function publicFinancialDocumentPreview(
        Request $request,
        Reservation $rezervacija,
        string $tip,
        ?int $rata = null
    ): \Symfony\Component\HttpFoundation\Response {
        return $this->financialDocumentPreview($request, $rezervacija, $tip, $rata);
    }

    public function previewTemplate(
        Request $request,
        ContractDataBuilder $dataBuilder,
        ContractTemplateRenderer $renderer
    ): HttpResponse {
        $validated = $request->validate([
            'html_template' => ['required', 'string'],
            'subagentski_ugovor' => ['nullable', 'boolean'],
        ]);

        $isSubagentContract = (bool) ($validated['subagentski_ugovor'] ?? false);
        $previewReservation = $this->resolveTemplatePreviewReservation($isSubagentContract)
            ?? $this->buildFallbackTemplatePreviewReservation($isSubagentContract);

        $data = $dataBuilder->build($previewReservation);
        $computed = $dataBuilder->buildComputedPlaceholders($data);
        $renderedHtml = $renderer->render((string) $validated['html_template'], $data, $computed);

        return response()->view('contracts.generated', [
            'html' => $renderedHtml,
            'company' => $data['company'] ?? [],
            'contract' => $data['contract'] ?? [],
            'document_title' => 'Pregled predloška ugovora',
        ]);
    }

    /**
     * Resolve contract template by explicit template id or arrangement subagent flag.
     */
    private function resolveTemplate(Reservation $rezervacija, ?string $templateId = null): ?ContractTemplate
    {
        if ($templateId !== null) {
            return ContractTemplate::query()->active()->find($templateId);
        }

        $rezervacija->loadMissing('arrangement:id,subagentski_aranzman');
        $isSubagentArrangement = (bool) ($rezervacija->arrangement?->subagentski_aranzman ?? false);

        return ContractTemplate::query()
            ->active()
            ->where('subagentski_ugovor', $isSubagentArrangement)
            ->orderByDesc('created_at')
            ->orderByDesc('version')
            ->first();
    }

    private function resolveTemplatePreviewReservation(bool $isSubagentContract): ?Reservation
    {
        return Reservation::query()
            ->whereHas('arrangement', function ($query) use ($isSubagentContract): void {
                $query->where('subagentski_aranzman', $isSubagentContract);
            })
            ->whereHas('reservationClients')
            ->latest('created_at')
            ->first();
    }

    private function buildFallbackTemplatePreviewReservation(bool $isSubagentContract): Reservation
    {
        $arrangement = Arrangement::query()
            ->where('subagentski_aranzman', $isSubagentContract)
            ->latest('created_at')
            ->first();

        if (! $arrangement) {
            $arrangement = new Arrangement([
                'sifra' => 'PREVIEW-001',
                'naziv_putovanja' => 'Primjer aranžmana',
                'destinacija' => 'Istanbul',
                'datum_polaska' => Carbon::now()->addDays(30)->toDateString(),
                'datum_povratka' => Carbon::now()->addDays(37)->toDateString(),
                'subagentski_aranzman' => $isSubagentContract,
            ]);
            $arrangement->id = (string) Str::uuid();
        }

        $package = ArrangementPackage::query()
            ->where('aranzman_id', (string) $arrangement->id)
            ->latest('created_at')
            ->first();

        if (! $package) {
            $package = new ArrangementPackage([
                'aranzman_id' => (string) $arrangement->id,
                'naziv' => 'Standard paket',
                'cijena' => 999.00,
            ]);
            $package->id = (string) Str::uuid();
        }

        $client = new Client([
            'ime' => 'Test',
            'prezime' => 'Putnik',
            'adresa' => 'Ulica 1, Sarajevo',
            'broj_telefona' => '+38761111222',
            'email' => 'putnik@example.com',
        ]);
        $client->id = (string) Str::uuid();

        $reservationClient = new ReservationClient([
            'paket_id' => (string) $package->id,
            'dodatno_na_cijenu' => 0,
            'popust' => 0,
        ]);
        $reservationClient->id = (string) Str::uuid();
        $reservationClient->setRelation('client', $client);
        $reservationClient->setRelation('package', $package);

        $reservation = new Reservation([
            'aranzman_id' => (string) $arrangement->id,
            'broj_putnika' => 1,
            'status' => 'potvrdjena',
            'placanje' => 'placeno',
            'napomena' => 'Ovo je automatski generisan pregled predloška.',
        ]);
        $reservation->id = (string) Str::uuid();
        $reservation->order_num = 1;
        $reservation->setRelation('arrangement', $arrangement);
        $reservation->setRelation('reservationClients', collect([$reservationClient]));
        $reservation->setRelation('client', $client);

        return $reservation;
    }

    /**
     * Build view payload for financial reservation documents.
     *
     * @return array<string, mixed>|null
     */
    private function buildFinancialDocumentPayload(
        Reservation $rezervacija,
        string $tip,
        ?int $rata = null
    ): ?array {
        $supportedTypes = ['predracun', 'racun', 'rata_predracun', 'rata_avansna'];
        if (! in_array($tip, $supportedTypes, true)) {
            return null;
        }

        $rezervacija->loadMissing([
            'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
            'reservationClients.client:id,ime,prezime',
            'reservationClients.package:id,naziv,cijena',
        ]);

        $setting = Setting::query()->first();
        $company = [
            'name' => (string) ($setting?->company_name ?? ''),
            'address' => trim((string) ($setting?->address ?? '')),
            'city' => trim((string) ($setting?->city ?? '')),
            'zip' => trim((string) ($setting?->zip ?? '')),
            'id_number' => (string) ($setting?->company_id ?? ''),
            'maticni_broj_subjekta_upisa' => (string) ($setting?->maticni_broj_subjekta_upisa ?? ''),
            'vat_number' => (string) ($setting?->pdv ?? ''),
            'trn' => (string) ($setting?->trn ?? ''),
            'bank' => (string) ($setting?->banka ?? ''),
            'iban' => (string) ($setting?->iban ?? ''),
            'swift' => (string) ($setting?->swift ?? ''),
            'email' => (string) ($setting?->email ?? ''),
            'phone' => (string) ($setting?->phone ?? ''),
            'broj_kase' => (string) ($setting?->broj_kase ?? ''),
            'u_pdv_sistemu' => (bool) ($setting?->u_pdv_sistemu ?? true),
            'logo_url' => $this->resolveDocumentImageSource($setting?->logo_path),
            'potpis_url' => $this->resolveDocumentImageSource($setting?->potpis_path),
            'pecat_url' => $this->resolveDocumentImageSource($setting?->pecat_path),
        ];

        $today = Carbon::now();
        $year = (int) $today->format('Y');
        $prefix = trim((string) ($setting?->invoice_prefix ?? ''));
        $orderNumber = (int) ($rezervacija->order_num ?? 0);
        $baseNumber = $prefix !== ''
            ? sprintf('%s-%d/%d', $prefix, $orderNumber, $year)
            : sprintf('%d/%d', $orderNumber, $year);

        $date = $today->format('d.m.Y');
        $title = 'Predračun';
        $number = $baseNumber;
        $lineItems = [];
        $total = 0.0;

        if ($tip === 'predracun' || $tip === 'racun') {
            $title = $tip === 'racun' ? 'Račun' : 'Predračun';
            $lineItems = $rezervacija->reservationClients->map(function (ReservationClient $item, int $index): array {
                $packagePrice = (float) ($item->package?->cijena ?? 0);
                $extra = (float) ($item->dodatno_na_cijenu ?? 0);
                $discount = (float) ($item->popust ?? 0);
                $lineTotal = max($packagePrice + $extra - $discount, 0);

                return [
                    'index' => $index + 1,
                    'description' => trim(sprintf(
                        '%s - %s %s',
                        (string) ($item->package?->naziv ?? 'Paket'),
                        (string) ($item->client?->ime ?? ''),
                        (string) ($item->client?->prezime ?? '')
                    )),
                    'amount' => $lineTotal,
                ];
            })->values()->all();
            $total = collect($lineItems)->sum('amount');
        }

        if ($tip === 'rata_predracun' || $tip === 'rata_avansna') {
            $index = max(($rata ?? 1) - 1, 0);
            $rateRows = is_array($rezervacija->rate) ? $rezervacija->rate : [];
            $row = $rateRows[$index] ?? null;
            if (! is_array($row)) {
                return null;
            }

            $amount = $tip === 'rata_avansna'
                ? (float) ($row['iznos_avansne_fakture'] ?? $row['iznos_uplate'] ?? 0)
                : (float) ($row['iznos_predracuna'] ?? $row['iznos_uplate'] ?? 0);
            $rawDate = $tip === 'rata_avansna'
                ? (string) ($row['datum_avansne_fakture'] ?? $row['datum_uplate'] ?? '')
                : (string) ($row['datum_predracuna'] ?? $row['datum_uplate'] ?? '');

            if ($amount <= 0) {
                return null;
            }

            $title = $tip === 'rata_avansna' ? 'Avansna faktura' : 'Predračun rate';
            $number = sprintf('%s-%s%d', $baseNumber, $tip === 'rata_avansna' ? 'A' : 'R', $index + 1);
            $date = $rawDate !== '' ? Carbon::parse($rawDate)->format('d.m.Y') : $today->format('d.m.Y');
            $total = $amount;
            $lineItems = [[
                'index' => 1,
                'description' => sprintf(
                    '%s #%d za aranžman %s',
                    $title,
                    $index + 1,
                    (string) ($rezervacija->arrangement?->naziv_putovanja ?? '')
                ),
                'amount' => $amount,
            ]];
        }

        return [
            'title' => $title,
            'number' => $number,
            'date' => $date,
            'reservation' => [
                'status' => (string) ($rezervacija->status ?? ''),
                'fiscal_invoice_number' => (string) ($rezervacija->broj_fiskalnog_racuna ?? ''),
                'note' => (string) ($rezervacija->napomena ?? ''),
                'arrangement' => [
                    'code' => (string) ($rezervacija->arrangement?->sifra ?? ''),
                    'name' => (string) ($rezervacija->arrangement?->naziv_putovanja ?? ''),
                    'destination' => (string) ($rezervacija->arrangement?->destinacija ?? ''),
                    'departure_date' => $rezervacija->arrangement?->datum_polaska?->format('d.m.Y') ?? '',
                    'return_date' => $rezervacija->arrangement?->datum_povratka?->format('d.m.Y') ?? '',
                ],
            ],
            'company' => $company,
            'line_items' => $lineItems,
            'total' => $total,
            'is_racun' => $tip === 'racun',
            'filename' => sprintf('%s.pdf', trim(strtolower(str_replace([' ', '/'], ['-', '-'], $number)), '-')),
        ];
    }

    /**
     * Resolve image source that works in generated PDFs.
     */
    private function resolveDocumentImageSource(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);
        $binary = @file_get_contents($absolutePath);
        $mimeType = @mime_content_type($absolutePath) ?: 'image/png';

        if ($binary !== false) {
            return sprintf('data:%s;base64,%s', $mimeType, base64_encode($binary));
        }

        return url(Storage::disk('public')->url($path));
    }

    /**
     * Build a safe PDF filename from contract number.
     */
    private function buildContractFilename(?string $contractNumber, string $fallbackId): string
    {
        $rawNumber = trim((string) ($contractNumber ?: $fallbackId));
        $safeNumber = str_replace(['/', '\\', ' '], '-', $rawNumber);
        $safeNumber = preg_replace('/[^A-Za-z0-9-]/', '', $safeNumber) ?: $fallbackId;
        $safeNumber = strtolower($safeNumber);
        $safeNumber = trim($safeNumber, '-');

        return sprintf('%s.pdf', $safeNumber);
    }
}
