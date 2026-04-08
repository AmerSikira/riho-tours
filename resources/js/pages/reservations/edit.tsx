import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronDown, Plus, Save, Trash2 } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type PackageOption = {
    id: number;
    naziv: string;
    cijena: string;
};

type ArrangementOption = {
    id: number;
    sifra: string;
    naziv_putovanja: string;
    destinacija: string;
    datum_polaska: string | null;
    datum_povratka: string | null;
    paketi: PackageOption[];
};

type ClientFormData = {
    ime: string;
    prezime: string;
    broj_dokumenta: string;
    datum_rodjenja: string;
    city: string;
    adresa: string;
    broj_telefona: string;
    email: string;
    dodatno_na_cijenu: string;
    popust: string;
    paket_id: string;
    fotografija: File | null;
    fotografija_url?: string | null;
};

type RezervacijaForm = {
    id: number;
    order_num: number | null;
    contract_share_url: string;
    financial_document_links: Array<{
        key: string;
        label: string;
        internal_url: string;
        share_url: string;
    }>;
    aranzman_id: number;
    status: string;
    broj_fiskalnog_racuna: string;
    placanje: 'placeno' | 'na_rate' | 'na_odgodeno';
    broj_rata: number | null;
    rate: Array<{
        datum_predracuna?: string;
        iznos_predracuna?: string;
        datum_uplate: string;
        iznos_uplate: string;
        datum_avansne_fakture?: string;
        iznos_avansne_fakture?: string;
    }>;
    napomena: string;
    klijenti: Array<{
        ime: string;
        prezime: string;
        broj_dokumenta: string;
        datum_rodjenja: string;
        city: string;
        adresa: string;
        broj_telefona: string;
        email: string;
        dodatno_na_cijenu: string | number | null;
        popust: string | number | null;
        paket_id: number;
        fotografija_url: string | null;
    }>;
};

type ClientSuggestion = {
    id: number;
    ime: string;
    prezime: string;
    broj_dokumenta: string;
    datum_rodjenja: string | null;
    city: string | null;
    adresa: string;
    broj_telefona: string;
    email: string | null;
    fotografija_url: string | null;
};
type ClientAutocompleteField = 'ime' | 'prezime' | 'broj_dokumenta';

type InstallmentFormData = {
    iznos_predracuna: string;
    datum_predracuna: string;
    datum_uplate: string;
    iznos_uplate: string;
    datum_avansne_fakture: string;
    iznos_avansne_fakture: string;
};

type PaymentOption = 'placeno' | 'na_rate' | 'na_odgodeno';

type Props = {
    aranzmani: ArrangementOption[];
    settings: {
        company_name: string;
        invoice_prefix: string;
        company_id: string;
        maticni_broj_subjekta_upisa: string;
        pdv: string;
        u_pdv_sistemu: boolean;
        trn: string;
        broj_kase: string;
        banka: string;
        iban: string;
        swift: string;
        email: string;
        phone: string;
        address: string;
        city: string;
        zip: string;
        logo_url: string | null;
        potpis_url: string | null;
        pecat_url: string | null;
    };
    rezervacija: RezervacijaForm;
};

const emptyClient = (): ClientFormData => ({
    ime: '',
    prezime: '',
    broj_dokumenta: '',
    datum_rodjenja: '',
    city: '',
    adresa: '',
    broj_telefona: '',
    email: '',
    dodatno_na_cijenu: '',
    popust: '',
    paket_id: '',
    fotografija: null,
    fotografija_url: null,
});

export default function EditReservation({
    aranzmani: arrangements,
    settings,
    rezervacija,
}: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        aranzman_id: String(rezervacija.aranzman_id),
        klijenti:
            rezervacija.klijenti.length > 0
                ? rezervacija.klijenti.map((client) => ({
                    ime: client.ime,
                    prezime: client.prezime,
                    broj_dokumenta: client.broj_dokumenta,
                    datum_rodjenja: client.datum_rodjenja,
                    city: client.city,
                    adresa: client.adresa,
                    broj_telefona: client.broj_telefona,
                    email: client.email,
                    dodatno_na_cijenu:
                        client.dodatno_na_cijenu === null
                            ? ''
                            : String(client.dodatno_na_cijenu),
                    popust:
                        client.popust === null ? '' : String(client.popust),
                    paket_id: String(client.paket_id),
                    fotografija: null,
                    fotografija_url: client.fotografija_url,
                }))
                : [emptyClient()],
        status: rezervacija.status,
        broj_fiskalnog_racuna: rezervacija.broj_fiskalnog_racuna,
        placanje: rezervacija.placanje,
        broj_rata:
            rezervacija.broj_rata === null ? '' : String(rezervacija.broj_rata),
        rate: rezervacija.rate.map((rata) => ({
            iznos_predracuna: rata.iznos_predracuna ?? rata.iznos_uplate ?? '',
            datum_predracuna: rata.datum_predracuna ?? rata.datum_uplate ?? '',
            datum_uplate: rata.datum_uplate ?? '',
            iznos_uplate: rata.iznos_uplate ?? '',
            datum_avansne_fakture: rata.datum_avansne_fakture ?? '',
            iznos_avansne_fakture: rata.iznos_avansne_fakture ?? '',
        })) as InstallmentFormData[],
        napomena: rezervacija.napomena,
    });

    const selectedArrangement = arrangements.find(
        (item) => String(item.id) === data.aranzman_id,
    );
    const availablePackages = selectedArrangement?.paketi ?? [];
    const formatArrangementOption = (arrangement: ArrangementOption): string => {
        return `${arrangement.sifra} - ${arrangement.naziv_putovanja} (${formatDateDisplay(
            arrangement.datum_polaska,
        )} / ${formatDateDisplay(arrangement.datum_povratka)})`;
    };
    const [arrangementQuery, setArrangementQuery] = useState(() =>
        selectedArrangement ? formatArrangementOption(selectedArrangement) : '',
    );
    const [isArrangementOpen, setIsArrangementOpen] = useState(false);
    const [documentNumberSuggestions, setDocumentNumberSuggestions] = useState<
        Record<number, ClientSuggestion[]>
    >({});
    const [activeClientSuggestions, setActiveClientSuggestions] = useState<{
        index: number;
        field: ClientAutocompleteField;
    } | null>(null);
    const [selectedFinancialDocumentKey, setSelectedFinancialDocumentKey] =
        useState<string>(() => rezervacija.financial_document_links[0]?.key ?? 'predracun');
    const suggestionRequests = useRef<Record<number, AbortController | null>>(
        {},
    );

    const filteredArrangements = useMemo(() => {
        const query = arrangementQuery.trim().toLowerCase();

        if (query === '') {
            return arrangements.slice(0, 8);
        }

        return arrangements
            .filter((arrangement) =>
                `${arrangement.sifra} ${arrangement.naziv_putovanja} ${arrangement.destinacija}`
                    .toLowerCase()
                    .includes(query),
            )
            .slice(0, 8);
    }, [arrangements, arrangementQuery]);

    const isSuggestionOpenForField = (
        index: number,
        field: ClientAutocompleteField,
    ): boolean => {
        return activeClientSuggestions?.index === index && activeClientSuggestions.field === field;
    };

    const parseMoney = (value: string | number | null | undefined): number => {
        if (typeof value === 'number') {
            return Number.isFinite(value) ? value : 0;
        }

        if (typeof value !== 'string') {
            return 0;
        }

        const normalized = value.replace(',', '.').trim();
        const parsed = Number.parseFloat(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    };

    const packageTotal = data.klijenti.reduce((sum, clientItem) => {
        const selectedPackage = availablePackages.find(
            (paket) => String(paket.id) === clientItem.paket_id,
        );

        return sum + parseMoney(selectedPackage?.cijena);
    }, 0);

    const extraChargeTotal = data.klijenti.reduce(
        (sum, clientItem) => sum + parseMoney(clientItem.dodatno_na_cijenu),
        0,
    );

    const grossTotal = packageTotal + extraChargeTotal;
    const isInVatSystem = settings.u_pdv_sistemu;
    const subtotalWithoutPdv = isInVatSystem ? grossTotal / 1.17 : grossTotal;
    const pdvAmount = isInVatSystem ? grossTotal - subtotalWithoutPdv : 0;
    const discountTotal = data.klijenti.reduce(
        (sum, clientItem) => sum + parseMoney(clientItem.popust),
        0,
    );
    const finalTotal = grossTotal - discountTotal;

    const formatCurrency = (amount: number): string =>
        `${amount.toFixed(2)} KM`;

    const remainingAmountAfterInstallment = (installmentIndex: number): number => {
        const totalPaidToRow = data.rate
            .slice(0, installmentIndex + 1)
            .reduce((sum, row) => sum + parseMoney(row.iznos_uplate), 0);

        return Math.max(finalTotal - totalPaidToRow, 0);
    };
    const formatDocumentDate = (value: string | null | undefined): string => {
        const formatted = formatDateDisplay(value);

        return formatted.endsWith('.') ? formatted.slice(0, -1) : formatted;
    };
    const buildInvoiceNumber = (orderNumber: number, year: number, suffix = ''): string => {
        const prefix = (settings.invoice_prefix ?? 'WEB').trim();
        const base = prefix !== '' ? `${prefix}-${orderNumber}/${year}` : `${orderNumber}/${year}`;

        return `${base}${suffix}`;
    };

    const normalizeRateCount = (value: string): number => {
        const parsed = Number.parseInt(value, 10);

        if (!Number.isFinite(parsed) || parsed < 2) {
            return 2;
        }

        if (parsed > 36) {
            return 36;
        }

        return parsed;
    };

    const buildRateRows = (
        count: number,
        existingRates: InstallmentFormData[],
    ): InstallmentFormData[] => {
        return Array.from({ length: count }, (_, index) => ({
            iznos_predracuna:
                existingRates[index]?.iznos_predracuna ??
                existingRates[index]?.iznos_uplate ??
                '',
            datum_predracuna:
                existingRates[index]?.datum_predracuna ??
                existingRates[index]?.datum_uplate ??
                '',
            datum_uplate: existingRates[index]?.datum_uplate ?? '',
            iznos_uplate: existingRates[index]?.iznos_uplate ?? '',
            datum_avansne_fakture: existingRates[index]?.datum_avansne_fakture ?? '',
            iznos_avansne_fakture: existingRates[index]?.iznos_avansne_fakture ?? '',
        }));
    };

    const handlePaymentChange = (paymentOption: PaymentOption) => {
        setData((currentData) => {
            if (paymentOption !== 'na_rate') {
                return {
                    ...currentData,
                    placanje: paymentOption,
                    broj_rata: '',
                    rate: [],
                };
            }

            const installmentCount = normalizeRateCount(currentData.broj_rata || '2');

            return {
                ...currentData,
                placanje: paymentOption,
                broj_rata: String(installmentCount),
                rate: buildRateRows(installmentCount, currentData.rate),
            };
        });
    };

    const handleInstallmentCountChange = (value: string) => {
        const installmentCount = normalizeRateCount(value);

        setData((currentData) => ({
            ...currentData,
            broj_rata: String(installmentCount),
            rate: buildRateRows(installmentCount, currentData.rate),
        }));
    };

    const updateRateProformaAmount = (index: number, value: string) => {
        setData((currentData) => ({
            ...currentData,
            rate: currentData.rate.map((rata, rowIndex) =>
                rowIndex === index ? { ...rata, iznos_predracuna: value } : rata,
            ),
        }));
    };

    const updateRateProformaDate = (index: number, value: string) => {
        setData((currentData) => ({
            ...currentData,
            rate: currentData.rate.map((rata, rowIndex) =>
                rowIndex === index ? { ...rata, datum_predracuna: value } : rata,
            ),
        }));
    };

    const updateRateDate = (index: number, value: string) => {
        setData((currentData) => ({
            ...currentData,
            rate: currentData.rate.map((rata, rowIndex) =>
                rowIndex === index ? { ...rata, datum_uplate: value } : rata,
            ),
        }));
    };

    const updateRateAmount = (index: number, value: string) => {
        setData((currentData) => ({
            ...currentData,
            rate: currentData.rate.map((rata, rowIndex) =>
                rowIndex === index ? { ...rata, iznos_uplate: value } : rata,
            ),
        }));
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Rezervacije',
            href: '/rezervacije',
        },
        {
            title: 'Uredite rezervaciju',
            href: `/rezervacije/${rezervacija.id}/uredi`,
        },
    ];

    const updateClient = <K extends keyof ClientFormData>(
        index: number,
        field: K,
        value: ClientFormData[K],
    ) => {
        setData((currentData) => ({
            ...currentData,
            klijenti: currentData.klijenti.map((client, clientIndex) =>
                clientIndex === index ? { ...client, [field]: value } : client,
            ),
        }));
    };

    const addClient = () => {
        setData((currentData) => ({
            ...currentData,
            klijenti: [...currentData.klijenti, emptyClient()],
        }));
        setDocumentNumberSuggestions({});
        setActiveClientSuggestions(null);
    };

    const removeClient = (index: number) => {
        if (data.klijenti.length === 1) {
            return;
        }

        setData((currentData) => ({
            ...currentData,
            klijenti: currentData.klijenti.filter(
                (_, clientIndex) => clientIndex !== index,
            ),
        }));
        setDocumentNumberSuggestions({});
        setActiveClientSuggestions(null);
    };

    const handleClientAutocompleteChange = (
        index: number,
        field: ClientAutocompleteField,
        event: ChangeEvent<HTMLInputElement>,
    ) => {
        const value = event.target.value;

        updateClient(index, field, value);
        setActiveClientSuggestions({ index, field });

        if (value.trim().length < 2) {
            setDocumentNumberSuggestions((current) => ({
                ...current,
                [index]: [],
            }));

            return;
        }

        void fetch(
            `/klijenti/pretraga?pretraga=${encodeURIComponent(value)}`,
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: (() => {
                    suggestionRequests.current[index]?.abort();
                    const controller = new AbortController();
                    suggestionRequests.current[index] = controller;

                    return controller.signal;
                })(),
            },
        )
            .then(async (response) => {
                if (!response.ok) {
                    return;
                }

                const suggestions =
                    (await response.json()) as ClientSuggestion[];
                setDocumentNumberSuggestions((current) => ({
                    ...current,
                    [index]: suggestions,
                }));

                const exactMatch = suggestions.find(
                    (suggestion) => suggestion.broj_dokumenta === value,
                );

                if (field === 'broj_dokumenta' && exactMatch) {
                    handleSuggestionSelect(index, exactMatch);
                }
            })
            .catch(() => {
                // Ignore aborted and network errors for incremental autocomplete.
            });
    };

    const setArrangementValue = (value: string) => {
        setData((currentData) => ({
            ...currentData,
            aranzman_id: value,
            klijenti: currentData.klijenti.map((client) => ({
                ...client,
                paket_id: '',
            })),
        }));
        setDocumentNumberSuggestions({});
        setActiveClientSuggestions(null);
    };

    const handleArrangementInputChange = (
        event: ChangeEvent<HTMLInputElement>,
    ) => {
        setArrangementQuery(event.target.value);
        setIsArrangementOpen(true);
        setArrangementValue('');
    };

    const handleArrangementSelect = (arrangement: ArrangementOption) => {
        setArrangementQuery(formatArrangementOption(arrangement));
        setIsArrangementOpen(false);
        setArrangementValue(String(arrangement.id));
    };

    const handleSuggestionSelect = (
        index: number,
        suggestion: ClientSuggestion,
    ) => {
        setData((currentData) => ({
            ...currentData,
            klijenti: currentData.klijenti.map((client, clientIndex) => {
                if (clientIndex !== index) {
                    return client;
                }

                return {
                    ...client,
                    ime: suggestion.ime,
                    prezime: suggestion.prezime,
                    broj_dokumenta: suggestion.broj_dokumenta,
                    datum_rodjenja: suggestion.datum_rodjenja ?? '',
                    city: suggestion.city ?? '',
                    adresa: suggestion.adresa,
                    broj_telefona: suggestion.broj_telefona,
                    email: suggestion.email ?? '',
                    fotografija: null,
                    fotografija_url: suggestion.fotografija_url,
                };
            }),
        }));

        setActiveClientSuggestions(null);
        setDocumentNumberSuggestions((current) => ({
            ...current,
            [index]: [],
        }));
    };

    const errorFor = (path: string): string | undefined => {
        return (errors as Record<string, string | undefined>)[path];
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        patch(`/rezervacije/${rezervacija.id}`, {
            forceFormData: true,
        });
    };


    const escapeHtml = (value: string): string =>
        value
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');

    const resolveImageSource = (value: string | null | undefined): string => {
        if (!value) {
            return '';
        }

        if (
            value.startsWith('data:') ||
            value.startsWith('http://') ||
            value.startsWith('https://')
        ) {
            return value;
        }

        return new URL(value, window.location.origin).toString();
    };

    const generateInstallmentInvoicePreview = (
        installmentIndex: number,
        invoiceTitle: string = 'Predračun',
    ) => {
        if (!selectedArrangement) {
            window.alert('Odaberite aranžman prije generisanja predračuna rate.');

            return;
        }

        const installment = data.rate[installmentIndex];
        const isAdvanceInvoice = invoiceTitle.trim().toLowerCase() === 'avansna faktura';
        const proformaAmount = parseMoney(installment?.iznos_predracuna);
        const paidAmount = parseMoney(installment?.iznos_uplate);
        const storedAdvanceAmount = parseMoney(installment?.iznos_avansne_fakture);
        const invoiceAmount = isAdvanceInvoice
            ? (storedAdvanceAmount > 0 ? storedAdvanceAmount : paidAmount)
            : proformaAmount;

        if (invoiceAmount <= 0) {
            window.alert(
                isAdvanceInvoice
                    ? 'Unesite plaćeni iznos prije generisanja avansne fakture.'
                    : 'Unesite iznos za predračun prije generisanja predračuna.',
            );

            return;
        }

        if (isAdvanceInvoice && !installment?.datum_uplate) {
            window.alert('Unesite datum uplate prije generisanja avansne fakture.');

            return;
        }

        const installmentDescription = `Plaćanje ${installmentIndex + 1} za ${selectedArrangement.naziv_putovanja}`;
        const installmentDate =
            (isAdvanceInvoice
                ? (installment?.datum_avansne_fakture || installment?.datum_uplate)
                : installment?.datum_predracuna) ||
            new Date().toISOString().slice(0, 10);
        const invoiceDate = formatDocumentDate(installmentDate);
        const invoiceYear = new Date().getFullYear();
        const invoiceNumber = isAdvanceInvoice
            ? buildInvoiceNumber(rezervacija.order_num ?? 0, invoiceYear, `-AFR${installmentIndex + 1}`)
            : buildInvoiceNumber(rezervacija.order_num ?? 0, invoiceYear, `-R${installmentIndex + 1}`);

        if (isAdvanceInvoice && installment) {
            setData((currentData) => ({
                ...currentData,
                rate: currentData.rate.map((row, rowIndex) =>
                    rowIndex === installmentIndex
                        ? {
                            ...row,
                            datum_avansne_fakture: installmentDate,
                            iznos_avansne_fakture: invoiceAmount.toFixed(2),
                        }
                        : row,
                ),
            }));
        }

        const companyBlock = [
            settings.company_name,
            settings.address,
            [settings.zip, settings.city].filter(Boolean).join(' '),
            settings.phone ? `Tel: ${settings.phone}` : '',
            settings.email ? `Email: ${settings.email}` : '',
            settings.company_id ? `ID: ${settings.company_id}` : '',
            settings.u_pdv_sistemu && settings.pdv ? `PDV: ${settings.pdv}` : '',
            settings.trn ? `TRN: ${settings.trn}` : '',
        ]
            .filter(Boolean)
            .map(
                (line) => `<div class="document-header-company-line">${escapeHtml(line)}</div>`,
            )
            .join('');
        const footerLine = [
            `${settings.company_name || '-'}/${settings.address || ''}, ${settings.zip || ''}, ${settings.city || ''}`.replace(
                /,\s*,/g,
                ',',
            ),
            settings.company_id ? `ID: ${settings.company_id}` : '',
            settings.u_pdv_sistemu && settings.pdv ? `PDV broj: ${settings.pdv}` : '',
            settings.maticni_broj_subjekta_upisa
                ? `Matični broj subjekta upisa: ${settings.maticni_broj_subjekta_upisa}`
                : '',
            settings.banka ? `Banka: ${settings.banka}` : '',
            settings.trn ? `TRN: ${settings.trn}` : '',
            settings.iban ? `IBAN: ${settings.iban}` : '',
            settings.swift ? `SWIFT: ${settings.swift}` : '',
        ]
            .filter(Boolean)
            .map((part) => escapeHtml(String(part)))
            .join(' / ');
        const logoSource = resolveImageSource(settings.logo_url);
        const signatureSource = resolveImageSource(settings.potpis_url);
        const stampSource = resolveImageSource(settings.pecat_url);

        const logoBlock = logoSource
            ? `<img src="${escapeHtml(logoSource)}" alt="Logo" style="max-height:80px; max-width:220px; object-fit:contain;" />`
            : '';
        const potpisPecatBlock =
            signatureSource || stampSource
                ? `<div style="margin-top:20px; width:240px; height:150px; margin-left:auto; position:relative;">
      ${signatureSource
                    ? `<img src="${escapeHtml(signatureSource)}" alt="Potpis" style="position:absolute; z-index:2; left:50%; top:8px; transform:translateX(-50%); max-height:72px; max-width:210px; object-fit:contain;" />`
                    : ''
                }
      ${stampSource
                    ? `<img src="${escapeHtml(stampSource)}" alt="Pečat" style="position:absolute; z-index:1; left:50%; bottom:0; transform:translateX(-50%); max-height:112px; max-width:210px; object-fit:contain;" />`
                    : ''
                }
    </div>`
                : '';

        const installmentInvoiceHtml = `<!doctype html>
<html lang="bs">
<head>
  <meta charset="utf-8" />
  <title>${escapeHtml(invoiceTitle)}</title>
  <style>
    @page { size: A4 portrait; margin: 12mm 12mm 18mm; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; color: #111827; font-family: Arial, sans-serif; }
    h1, h2, h3, p { margin: 0; }
    .page { width: 186mm; min-height: 273mm; margin: 0 auto; padding-bottom: 18mm; }
    .document-header { display: flex; justify-content: space-between; margin-bottom: 16px; gap: 16px; align-items: flex-start; }
    .document-header-logo { width: 56%; }
    .document-header-company { width: 44%; font-size: 10px; font-weight: 700; line-height: 1.28; text-align: left; }
    .document-header-company-line { margin: 0; }
    .document-meta { margin-bottom: 14px; }
    .document-footer-wrap { position: fixed; left: 12mm; right: 12mm; bottom: 6mm; text-align: center; }
    .document-footer { display: inline-block; width: fit-content; max-width: 176mm; font-size: 10px; font-weight: 700; line-height: 1.25; text-align: center; white-space: normal; overflow-wrap: anywhere; word-break: break-word; padding: 0 2mm; }
    .muted { color: #6b7280; font-size: 10px; }
    .section { margin-top: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #d1d5db; padding: 6px; font-size: 11px; text-align: left; vertical-align: top; }
    th { background: #f3f4f6; }
    .left { text-align: left; }
    .center { text-align: center; }
    .right { text-align: right; }
    .totals { width: 72mm; margin-left: auto; margin-top: 12px; }
    .totals td { border: 1px solid #d1d5db; }
    .totals tr:last-child td { font-weight: 700; }
    .avoid-break { break-inside: avoid-page; page-break-inside: avoid; }

    @media screen {
      body { background: #f3f4f6; padding: 12px; }
      .page { width: 210mm; min-height: 297mm; background: #fff; padding: 12mm; box-shadow: 0 8px 24px rgba(17, 24, 39, 0.12); }
    }

    @media print {
      .page { width: auto; min-height: calc(297mm - 30mm); margin: 0; padding: 0; box-shadow: none; }
      tr, th, td { break-inside: avoid-page; page-break-inside: avoid; }
    }
  </style>
</head>
<body>
  <div class="page">
  <div class="document-header">
    <div class="document-header-logo">
      ${logoBlock}
    </div>
    <div class="document-header-company">
      ${companyBlock || '<div>-</div>'}
    </div>
  </div>
  <div class="document-meta">
    <h1>${escapeHtml(invoiceTitle)}</h1>
    <div class="muted">Datum: ${invoiceDate}</div>
    <div class="muted">Broj: ${invoiceNumber}</div>
  </div>

  <div class="section avoid-break">
    <h3>Podaci o rezervaciji</h3>
    <p><strong>Aranžman:</strong> ${escapeHtml(selectedArrangement.sifra)} - ${escapeHtml(
            selectedArrangement.naziv_putovanja,
        )}</p>
    <p><strong>Destinacija:</strong> ${escapeHtml(selectedArrangement.destinacija)}</p>
    <p><strong>Termin:</strong> ${escapeHtml(
            formatDocumentDate(selectedArrangement.datum_polaska),
        )} - ${escapeHtml(formatDocumentDate(selectedArrangement.datum_povratka))}</p>
    <p><strong>Status:</strong> ${escapeHtml(data.status)}</p>
    <p><strong>Napomena:</strong> ${escapeHtml(data.napomena || '-')}</p>
  </div>

  <div class="section">
    <h3>Stavke</h3>
    <table>
      <thead>
        <tr>
          <th class="center">#</th>
          <th class="left">Opis</th>
          <th class="right">Iznos</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="center">1</td>
          <td class="left">${escapeHtml(installmentDescription)}</td>
          <td class="right">${formatCurrency(invoiceAmount)}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <table class="totals avoid-break">
    <tr><td>Ukupno</td><td class="right">${formatCurrency(invoiceAmount)}</td></tr>
  </table>
  ${potpisPecatBlock}
  <div class="document-footer-wrap">
    <div class="document-footer">${footerLine}</div>
  </div>
  </div>
</body>
</html>`;

        const previewWindow = window.open('', '_blank');

        if (!previewWindow) {
            return;
        }

        previewWindow.document.open();
        previewWindow.document.write(installmentInvoiceHtml);
        previewWindow.document.close();
    };

    const reservationContractPdfPath = `/rezervacije/${rezervacija.id}/ugovor/pdf`;
    const reservationContractPdfUrl =
        rezervacija.contract_share_url && rezervacija.contract_share_url !== ''
            ? rezervacija.contract_share_url
            : typeof window === 'undefined'
                ? reservationContractPdfPath
                : `${window.location.origin}${reservationContractPdfPath}`;
    const currentYear = new Date().getFullYear();
    const reservationDocumentNumber = buildInvoiceNumber(rezervacija.order_num ?? 0, currentYear);
    const primaryClient = data.klijenti[0];
    const primaryClientEmail = primaryClient?.email?.trim() ?? '';
    const normalizePhoneForMessaging = (phone: string): string =>
        phone.replaceAll(/[^\d+]/g, '');
    const primaryClientPhone = normalizePhoneForMessaging(
        primaryClient?.broj_telefona ?? '',
    );
    const whatsappPhone = primaryClientPhone.replace(/^\+/, '');
    const contractShareText = `Poštovani,\n\nUgovor ${reservationDocumentNumber} možete pregledati na sljedećem linku:\n${reservationContractPdfUrl}`;
    const emailHref = primaryClientEmail
        ? `mailto:${encodeURIComponent(primaryClientEmail)}?subject=${encodeURIComponent(`Ugovor ${reservationDocumentNumber}`)}&body=${encodeURIComponent(contractShareText)}`
        : '';
    const viberHref = primaryClientPhone
        ? `viber://chat?number=${encodeURIComponent(primaryClientPhone)}&text=${encodeURIComponent(contractShareText)}`
        : '';
    const whatsappHref = whatsappPhone
        ? `https://wa.me/${encodeURIComponent(whatsappPhone)}?text=${encodeURIComponent(contractShareText)}`
        : '';
    const copyContractLink = async () => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(reservationContractPdfUrl);
            } else {
                const tempTextarea = document.createElement('textarea');
                tempTextarea.value = reservationContractPdfUrl;
                tempTextarea.setAttribute('readonly', '');
                tempTextarea.style.position = 'absolute';
                tempTextarea.style.left = '-9999px';
                document.body.appendChild(tempTextarea);
                tempTextarea.select();
                document.execCommand('copy');
                document.body.removeChild(tempTextarea);
            }

            window.alert('Link ugovora je kopiran.');
        } catch {
            window.alert('Kopiranje linka nije uspjelo. Kopirajte ručno.');
        }
    };
    const downloadContractPdfHref = `${reservationContractPdfPath}?download=1`;
    const financialDocuments = rezervacija.financial_document_links ?? [];
    const selectedFinancialDocument =
        financialDocuments.find(
            (document) => document.key === selectedFinancialDocumentKey,
        ) ?? financialDocuments[0];
    const openSelectedFinancialDocument = () => {
        if (!selectedFinancialDocument?.internal_url) {
            window.alert('Dokument trenutno nije dostupan.');

            return;
        }

        window.open(selectedFinancialDocument.internal_url, '_blank', 'noopener,noreferrer');
    };
    const financialShareText = selectedFinancialDocument
        ? `Poštovani,\n\n${selectedFinancialDocument.label} ${reservationDocumentNumber} možete pregledati na sljedećem linku:\n${selectedFinancialDocument.share_url}`
        : '';
    const financialEmailHref =
        selectedFinancialDocument && primaryClientEmail
            ? `mailto:${encodeURIComponent(primaryClientEmail)}?subject=${encodeURIComponent(`${selectedFinancialDocument.label} ${reservationDocumentNumber}`)}&body=${encodeURIComponent(financialShareText)}`
            : '';
    const financialViberHref =
        selectedFinancialDocument && primaryClientPhone
            ? `viber://chat?number=${encodeURIComponent(primaryClientPhone)}&text=${encodeURIComponent(financialShareText)}`
            : '';
    const financialWhatsappHref =
        selectedFinancialDocument && whatsappPhone
            ? `https://wa.me/${encodeURIComponent(whatsappPhone)}?text=${encodeURIComponent(financialShareText)}`
            : '';
    const copyFinancialDocumentLink = async () => {
        if (!selectedFinancialDocument?.share_url) {
            window.alert('Dokument trenutno nije dostupan.');

            return;
        }

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(selectedFinancialDocument.share_url);
            } else {
                const tempTextarea = document.createElement('textarea');
                tempTextarea.value = selectedFinancialDocument.share_url;
                tempTextarea.setAttribute('readonly', '');
                tempTextarea.style.position = 'absolute';
                tempTextarea.style.left = '-9999px';
                document.body.appendChild(tempTextarea);
                tempTextarea.select();
                document.execCommand('copy');
                document.body.removeChild(tempTextarea);
            }

            window.alert('Link dokumenta je kopiran.');
        } catch {
            window.alert('Kopiranje linka nije uspjelo. Kopirajte ručno.');
        }
    };
    const downloadFinancialDocumentHref = selectedFinancialDocument
        ? `${selectedFinancialDocument.internal_url}?download=1`
        : '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Uredite rezervaciju" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            Uredite rezervaciju
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Ažurirajte klijente i pakete u rezervaciji.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="flex items-center gap-2">
                            <select
                                value={selectedFinancialDocument?.key ?? ''}
                                onChange={(event) =>
                                    setSelectedFinancialDocumentKey(event.target.value)
                                }
                                className="h-10 min-w-[220px] rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {financialDocuments.map((document) => (
                                    <option key={document.key} value={document.key}>
                                        {document.label}
                                    </option>
                                ))}
                            </select>
                            <div className="inline-flex items-center">
                                <Button
                                    type="button"
                                    className="rounded-r-none"
                                    disabled={!selectedFinancialDocument}
                                    onClick={openSelectedFinancialDocument}
                                >
                                    Otvorite dokument
                                </Button>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            type="button"
                                            className="rounded-l-none border-l border-primary-foreground/30 px-2"
                                            disabled={!selectedFinancialDocument}
                                        >
                                            <ChevronDown className="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            disabled={!financialEmailHref}
                                            asChild={Boolean(financialEmailHref)}
                                        >
                                            {financialEmailHref ? (
                                                <a href={financialEmailHref}>
                                                    Pošalji putem emaila
                                                </a>
                                            ) : (
                                                <span>Pošalji putem emaila</span>
                                            )}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            disabled={!financialViberHref}
                                            asChild={Boolean(financialViberHref)}
                                        >
                                            {financialViberHref ? (
                                                <a href={financialViberHref}>
                                                    Pošalji putem Vibera
                                                </a>
                                            ) : (
                                                <span>Pošalji putem Vibera</span>
                                            )}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            disabled={!financialWhatsappHref}
                                            asChild={Boolean(financialWhatsappHref)}
                                        >
                                            {financialWhatsappHref ? (
                                                <a
                                                    href={financialWhatsappHref}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    Pošalji putem WhatsAppa
                                                </a>
                                            ) : (
                                                <span>Pošalji putem WhatsAppa</span>
                                            )}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            onSelect={(event) => {
                                                event.preventDefault();
                                                void copyFinancialDocumentLink();
                                            }}
                                        >
                                            Kopiraj link dokumenta
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            disabled={!downloadFinancialDocumentHref}
                                            asChild={Boolean(downloadFinancialDocumentHref)}
                                        >
                                            {downloadFinancialDocumentHref ? (
                                                <a href={downloadFinancialDocumentHref}>
                                                    Preuzmi dokument PDF
                                                </a>
                                            ) : (
                                                <span>Preuzmi dokument PDF</span>
                                            )}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                        <div className="inline-flex items-center">
                            <Button
                                asChild
                                className="rounded-r-none"
                            >
                                <Link href={reservationContractPdfPath}>
                                    Pošalji ugovor
                                </Link>
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        type="button"
                                        className="rounded-l-none border-l border-primary-foreground/30 px-2"
                                    >
                                        <ChevronDown className="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        disabled={!emailHref}
                                        asChild={Boolean(emailHref)}
                                    >
                                        {emailHref ? (
                                            <a href={emailHref}>
                                                Pošalji putem emaila
                                            </a>
                                        ) : (
                                            <span>
                                                Pošalji putem emaila
                                            </span>
                                        )}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        disabled={!viberHref}
                                        asChild={Boolean(viberHref)}
                                    >
                                        {viberHref ? (
                                            <a href={viberHref}>
                                                Pošalji putem Vibera
                                            </a>
                                        ) : (
                                            <span>
                                                Pošalji putem Vibera
                                            </span>
                                        )}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        disabled={!whatsappHref}
                                        asChild={Boolean(whatsappHref)}
                                    >
                                        {whatsappHref ? (
                                            <a
                                                href={whatsappHref}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                Pošalji putem WhatsAppa
                                            </a>
                                        ) : (
                                            <span>
                                                Pošalji putem WhatsAppa
                                            </span>
                                        )}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        onSelect={(event) => {
                                            event.preventDefault();
                                            void copyContractLink();
                                        }}
                                    >
                                        Kopiraj link ugovora
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        asChild
                                    >
                                        <a href={downloadContractPdfHref}>
                                            Preuzmi ugovor PDF
                                        </a>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href="/rezervacije">Nazad na listu</Link>
                        </Button>
                    </div>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-5xl space-y-5 rounded-xl border border-sidebar-border/70 p-5"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="aranzman_id">Aranžman</Label>
                        <div className="relative">
                            <Input
                                id="aranzman_id"
                                value={arrangementQuery}
                                onChange={handleArrangementInputChange}
                                onFocus={() => setIsArrangementOpen(true)}
                                onBlur={() => {
                                    window.setTimeout(
                                        () => setIsArrangementOpen(false),
                                        120,
                                    );
                                }}
                                placeholder="Počnite kucati naziv, šifru ili destinaciju"
                                required
                            />
                            {isArrangementOpen && filteredArrangements.length > 0 && (
                                <div className="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border bg-background shadow-sm">
                                    {filteredArrangements.map((arrangement) => (
                                        <button
                                            key={arrangement.id}
                                            type="button"
                                            className="w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                            onMouseDown={(event) => {
                                                event.preventDefault();
                                                handleArrangementSelect(arrangement);
                                            }}
                                        >
                                            {formatArrangementOption(arrangement)}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                        <InputError message={errors.aranzman_id} />
                    </div>

                    <section className="space-y-4 rounded-lg border border-dashed border-sidebar-border/70 p-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-base font-semibold">
                                Klijenti u rezervaciji
                            </h2>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={addClient}
                            >
                                <Plus className="mr-2 size-4" />
                                Dodajte klijenta
                            </Button>
                        </div>

                        {data.klijenti.map((clientItem, index) => (
                            <div
                                key={`client-${index}`}
                                className="space-y-4 rounded-md border p-4"
                            >
                                <div className="flex items-center justify-between">
                                    <p className="font-medium">
                                        Klijent #{index + 1}
                                    </p>
                                    {data.klijenti.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => removeClient(index)}
                                        >
                                            <Trash2 className="mr-2 size-4" />
                                            Uklonite
                                        </Button>
                                    )}
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Ime</Label>
                                        <div className="relative">
                                            <Input
                                                value={clientItem.ime}
                                                onChange={(event) =>
                                                    handleClientAutocompleteChange(
                                                        index,
                                                        'ime',
                                                        event,
                                                    )
                                                }
                                                onFocus={() =>
                                                    setActiveClientSuggestions({
                                                        index,
                                                        field: 'ime',
                                                    })
                                                }
                                                onBlur={() => {
                                                    window.setTimeout(
                                                        () =>
                                                            setActiveClientSuggestions(
                                                                null,
                                                            ),
                                                        120,
                                                    );
                                                }}
                                                required
                                            />
                                            {isSuggestionOpenForField(
                                                index,
                                                'ime',
                                            ) &&
                                                (documentNumberSuggestions[index]
                                                    ?.length ?? 0) > 0 && (
                                                    <div className="absolute z-20 mt-1 max-h-44 w-full overflow-auto rounded-md border bg-background shadow-sm">
                                                        {documentNumberSuggestions[
                                                            index
                                                        ].map((suggestion) => (
                                                            <button
                                                                key={`${suggestion.id}-ime`}
                                                                type="button"
                                                                className="w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                                                onMouseDown={(
                                                                    event,
                                                                ) => {
                                                                    event.preventDefault();
                                                                    handleSuggestionSelect(
                                                                        index,
                                                                        suggestion,
                                                                    );
                                                                }}
                                                            >
                                                                {
                                                                    suggestion.broj_dokumenta
                                                                }
                                                                ,{' '}
                                                                {suggestion.ime}{' '}
                                                                {
                                                                    suggestion.prezime
                                                                }
                                                            </button>
                                                        ))}
                                                    </div>
                                                )}
                                        </div>
                                        <InputError
                                            message={errorFor(
                                                `klijenti.${index}.ime`,
                                            )}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Prezime</Label>
                                        <div className="relative">
                                            <Input
                                                value={clientItem.prezime}
                                                onChange={(event) =>
                                                    handleClientAutocompleteChange(
                                                        index,
                                                        'prezime',
                                                        event,
                                                    )
                                                }
                                                onFocus={() =>
                                                    setActiveClientSuggestions({
                                                        index,
                                                        field: 'prezime',
                                                    })
                                                }
                                                onBlur={() => {
                                                    window.setTimeout(
                                                        () =>
                                                            setActiveClientSuggestions(
                                                                null,
                                                            ),
                                                        120,
                                                    );
                                                }}
                                                required
                                            />
                                            {isSuggestionOpenForField(
                                                index,
                                                'prezime',
                                            ) &&
                                                (documentNumberSuggestions[index]
                                                    ?.length ?? 0) > 0 && (
                                                    <div className="absolute z-20 mt-1 max-h-44 w-full overflow-auto rounded-md border bg-background shadow-sm">
                                                        {documentNumberSuggestions[
                                                            index
                                                        ].map((suggestion) => (
                                                            <button
                                                                key={`${suggestion.id}-prezime`}
                                                                type="button"
                                                                className="w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                                                onMouseDown={(
                                                                    event,
                                                                ) => {
                                                                    event.preventDefault();
                                                                    handleSuggestionSelect(
                                                                        index,
                                                                        suggestion,
                                                                    );
                                                                }}
                                                            >
                                                                {
                                                                    suggestion.broj_dokumenta
                                                                }
                                                                ,{' '}
                                                                {suggestion.ime}{' '}
                                                                {
                                                                    suggestion.prezime
                                                                }
                                                            </button>
                                                        ))}
                                                    </div>
                                                )}
                                        </div>
                                        <InputError
                                            message={errorFor(
                                                `klijenti.${index}.prezime`,
                                            )}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label>Paket</Label>
                                        <select
                                            value={clientItem.paket_id}
                                            onChange={(event) =>
                                                updateClient(
                                                    index,
                                                    'paket_id',
                                                    event.target.value,
                                                )
                                            }
                                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                            required
                                        >
                                            <option value="">
                                                Odaberite paket
                                            </option>
                                            {availablePackages.map((paket) => (
                                                <option
                                                    key={paket.id}
                                                    value={String(paket.id)}
                                                >
                                                    {paket.naziv} (
                                                    {paket.cijena} KM)
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errorFor(
                                                `klijenti.${index}.paket_id`,
                                            )}
                                        />
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label>Dodatno na cijenu</Label>
                                            <Input
                                                type="number"
                                                inputMode="decimal"
                                                min="0"
                                                step="0.01"
                                                value={clientItem.dodatno_na_cijenu}
                                                onChange={(event) =>
                                                    updateClient(
                                                        index,
                                                        'dodatno_na_cijenu',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="0.00"
                                            />
                                            <InputError
                                                message={errorFor(
                                                    `klijenti.${index}.dodatno_na_cijenu`,
                                                )}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label>Popust</Label>
                                            <Input
                                                type="number"
                                                inputMode="decimal"
                                                min="0"
                                                step="0.01"
                                                value={clientItem.popust}
                                                onChange={(event) =>
                                                    updateClient(
                                                        index,
                                                        'popust',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="0.00"
                                            />
                                            <InputError
                                                message={errorFor(
                                                    `klijenti.${index}.popust`,
                                                )}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label>Broj dokumenta</Label>
                                            <div className="relative">
                                                <Input
                                                    value={clientItem.broj_dokumenta}
                                                    onChange={(event) =>
                                                        handleClientAutocompleteChange(
                                                            index,
                                                            'broj_dokumenta',
                                                            event,
                                                        )
                                                    }
                                                    onFocus={() =>
                                                        setActiveClientSuggestions({
                                                            index,
                                                            field: 'broj_dokumenta',
                                                        })
                                                    }
                                                    onBlur={() => {
                                                        window.setTimeout(
                                                            () =>
                                                                setActiveClientSuggestions(
                                                                    null,
                                                                ),
                                                            120,
                                                        );
                                                    }}
                                                />
                                                {isSuggestionOpenForField(
                                                    index,
                                                    'broj_dokumenta',
                                                ) &&
                                                    (documentNumberSuggestions[index]
                                                        ?.length ?? 0) > 0 && (
                                                        <div className="absolute z-20 mt-1 max-h-44 w-full overflow-auto rounded-md border bg-background shadow-sm">
                                                            {documentNumberSuggestions[
                                                                index
                                                            ].map((suggestion) => (
                                                                <button
                                                                    key={
                                                                        suggestion.id
                                                                    }
                                                                    type="button"
                                                                    className="w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                                                    onMouseDown={(
                                                                        event,
                                                                    ) => {
                                                                        event.preventDefault();
                                                                        handleSuggestionSelect(
                                                                            index,
                                                                            suggestion,
                                                                        );
                                                                    }}
                                                                >
                                                                    {
                                                                        suggestion.broj_dokumenta
                                                                    }
                                                                    ,{' '}
                                                                    {suggestion.ime}{' '}
                                                                    {
                                                                        suggestion.prezime
                                                                    }
                                                                </button>
                                                            ))}
                                                        </div>
                                                    )}
                                            </div>
                                            <InputError
                                                message={errorFor(
                                                    `klijenti.${index}.broj_dokumenta`,
                                                )}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label>Datum rođenja</Label>
                                            <Input
                                                type="date"
                                                value={clientItem.datum_rodjenja}
                                                onChange={(event) =>
                                                    updateClient(
                                                        index,
                                                        'datum_rodjenja',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errorFor(
                                                    `klijenti.${index}.datum_rodjenja`,
                                                )}
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label>Grad</Label>
                                        <Input
                                            value={clientItem.city}
                                            onChange={(event) =>
                                                updateClient(
                                                    index,
                                                    'city',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errorFor(
                                                `klijenti.${index}.city`,
                                            )}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Adresa</Label>
                                        <Input
                                            value={clientItem.adresa}
                                            onChange={(event) =>
                                                updateClient(
                                                    index,
                                                    'adresa',
                                                    event.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errorFor(
                                                `klijenti.${index}.adresa`,
                                            )}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Broj telefona</Label>
                                        <Input
                                            value={clientItem.broj_telefona}
                                            onChange={(event) =>
                                                updateClient(
                                                    index,
                                                    'broj_telefona',
                                                    event.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errorFor(
                                                `klijenti.${index}.broj_telefona`,
                                            )}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label>Email</Label>
                                        <Input
                                            type="email"
                                            value={clientItem.email}
                                            onChange={(event) =>
                                                updateClient(
                                                    index,
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errorFor(
                                                `klijenti.${index}.email`,
                                            )}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Fotografija</Label>
                                        <Input
                                            type="file"
                                            accept="image/*"
                                            onChange={(event) =>
                                                setData((currentData) => ({
                                                    ...currentData,
                                                    klijenti:
                                                        currentData.klijenti.map(
                                                            (
                                                                client,
                                                                clientIndex,
                                                            ) =>
                                                                clientIndex ===
                                                                    index
                                                                    ? {
                                                                        ...client,
                                                                        fotografija:
                                                                            event
                                                                                .target
                                                                                .files?.[0] ??
                                                                            null,
                                                                        fotografija_url:
                                                                            null,
                                                                    }
                                                                    : client,
                                                        ),
                                                }))
                                            }
                                        />
                                        <InputError
                                            message={errorFor(
                                                `klijenti.${index}.fotografija`,
                                            )}
                                        />
                                        {clientItem.fotografija_url && (
                                            <img
                                                src={clientItem.fotografija_url}
                                                alt="Trenutna fotografija"
                                                className="h-16 w-16 rounded-md object-cover"
                                            />
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}

                        {data.aranzman_id !== '' &&
                            availablePackages.length === 0 && (
                                <p className="text-sm text-amber-600">
                                    Ovaj aranžman nema aktivnih paketa. Dodajte
                                    paket prije rezervacije.
                                </p>
                            )}
                    </section>

                    <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status rezervacije</Label>
                                    <select
                                        id="status"
                                        value={data.status}
                                        onChange={(event) =>
                                            setData('status', event.target.value)
                                        }
                                        className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                        required
                                    >
                                        <option value="na_cekanju">Na čekanju</option>
                                        <option value="potvrdjena">Potvrđena</option>
                                        <option value="otkazana">Otkazana</option>
                                    </select>
                                    <InputError message={errors.status} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Broj putnika</Label>
                                    <Input
                                        value={String(data.klijenti.length)}
                                        readOnly
                                    />
                                </div>

                            </div>

                    <div className="grid gap-2">
                                <Label htmlFor="napomena">Napomena</Label>
                                <textarea
                                    id="napomena"
                                    value={data.napomena}
                                    onChange={(event) =>
                                        setData('napomena', event.target.value)
                                    }
                                    className="min-h-24 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                                <InputError message={errors.napomena} />
                            </div>

                    <section className="space-y-4 rounded-lg border border-sidebar-border/70 p-4">
                                <h2 className="text-base font-semibold">Plaćanje</h2>

                                <div className="grid gap-2">
                                    <Label htmlFor="broj_fiskalnog_racuna">
                                        Broj fiskalnog računa
                                    </Label>
                                    <Input
                                        id="broj_fiskalnog_racuna"
                                        value={data.broj_fiskalnog_racuna}
                                        onChange={(event) =>
                                            setData(
                                                'broj_fiskalnog_racuna',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.broj_fiskalnog_racuna}
                                    />
                                </div>

                                <div className="grid gap-5 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="placanje">Način plaćanja</Label>
                                        <select
                                            id="placanje"
                                            value={data.placanje}
                                            onChange={(event) =>
                                                handlePaymentChange(
                                                    event.target
                                                        .value as PaymentOption,
                                                )
                                            }
                                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                            required
                                        >
                                            <option value="placeno">Plaćeno</option>
                                            <option value="na_rate">Na rate</option>
                                            <option value="na_odgodeno">
                                                Na odgođeno
                                            </option>
                                        </select>
                                        <InputError message={errors.placanje} />
                                    </div>

                                    {data.placanje === 'na_rate' && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="broj_rata">Broj rata</Label>
                                            <select
                                                id="broj_rata"
                                                value={data.broj_rata}
                                                onChange={(event) =>
                                                    handleInstallmentCountChange(
                                                        event.target.value,
                                                    )
                                                }
                                                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                                required
                                            >
                                                {Array.from(
                                                    { length: 35 },
                                                    (_, index) => index + 2,
                                                ).map((broj) => (
                                                    <option
                                                        key={broj}
                                                        value={String(broj)}
                                                    >
                                                        {broj}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.broj_rata} />
                                        </div>
                                    )}
                                </div>

                                {data.placanje === 'na_rate' && (
                                    <div className="space-y-4 rounded-md border border-sidebar-border/70 bg-muted/20 p-4">
                                        <h3 className="text-sm font-semibold">Evidencija uplata rata</h3>
                                        <div className="grid gap-3">
                                            {data.rate.map((rata, index) => (
                                                <div
                                                    key={`rata-${index}`}
                                                    className="space-y-3 rounded-md border border-sidebar-border/70 p-3"
                                                >
                                                    <div className="grid gap-3 rounded-md border border-sidebar-border/70 p-3 md:grid-cols-3">
                                                        <div className="grid gap-2">
                                                            <Label htmlFor={`rata_predracun_iznos_${index}`}>Iznos</Label>
                                                            <Input
                                                                id={`rata_predracun_iznos_${index}`}
                                                                type="number"
                                                                min={0}
                                                                step="0.01"
                                                                value={rata.iznos_predracuna}
                                                                onChange={(event) =>
                                                                    updateRateProformaAmount(index, event.target.value)
                                                                }
                                                                placeholder="0.00"
                                                            />
                                                        </div>
                                                        <div className="grid gap-2">
                                                            <Label htmlFor={`rata_predracun_datum_${index}`}>Datum</Label>
                                                            <Input
                                                                id={`rata_predracun_datum_${index}`}
                                                                type="date"
                                                                value={rata.datum_predracuna}
                                                                onChange={(event) =>
                                                                    updateRateProformaDate(index, event.target.value)
                                                                }
                                                            />
                                                        </div>
                                                        <div className="grid gap-2">
                                                            <Label>Dokument</Label>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                onClick={() =>
                                                                    generateInstallmentInvoicePreview(index)
                                                                }
                                                            >
                                                                Generišite predračun rate
                                                            </Button>
                                                        </div>
                                                    </div>

                                                    <div className="grid gap-3 md:grid-cols-4">
                                                        <div className="grid gap-2">
                                                            <Label htmlFor={`rata_iznos_${index}`}>Iznos uplate</Label>
                                                            <Input
                                                                id={`rata_iznos_${index}`}
                                                                type="number"
                                                                min={0}
                                                                step="0.01"
                                                                value={rata.iznos_uplate}
                                                                onChange={(event) =>
                                                                    updateRateAmount(index, event.target.value)
                                                                }
                                                                placeholder="0.00"
                                                            />
                                                            <InputError
                                                                message={errorFor(`rate.${index}.iznos_uplate`)}
                                                            />
                                                        </div>
                                                        <div className="grid gap-2">
                                                            <Label htmlFor={`rata_datum_${index}`}>Datum</Label>
                                                            <Input
                                                                id={`rata_datum_${index}`}
                                                                type="date"
                                                                value={rata.datum_uplate}
                                                                onChange={(event) =>
                                                                    updateRateDate(index, event.target.value)
                                                                }
                                                            />
                                                            <InputError
                                                                message={errorFor(`rate.${index}.datum_uplate`)}
                                                            />
                                                        </div>
                                                        <div className="grid gap-2">
                                                            <Label>Preostalo za uplatu</Label>
                                                            <Input
                                                                readOnly
                                                                value={formatCurrency(
                                                                    remainingAmountAfterInstallment(index),
                                                                )}
                                                            />
                                                        </div>
                                                        <div className="grid gap-2">
                                                            <Label>Dokumenti rate</Label>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                disabled={
                                                                    parseMoney(rata.iznos_uplate) <= 0 ||
                                                                    rata.datum_uplate === ''
                                                                }
                                                                onClick={() =>
                                                                    generateInstallmentInvoicePreview(
                                                                        index,
                                                                        'Avansna faktura',
                                                                    )
                                                                }
                                                            >
                                                                Avansna faktura
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        <InputError message={errors.rate} />
                                    </div>
                                )}
                            </section>

                    <section className="rounded-lg border border-sidebar-border/70">
                                <div className="border-b px-4 py-3">
                                    <h2 className="text-base font-semibold">
                                        Troškovi
                                    </h2>
                                </div>
                                <div className="p-4">
                                    <table className="w-full text-sm">
                                        <tbody>
                                            <tr className="border-b">
                                                <td className="py-2 text-muted-foreground">
                                                    Dodaci
                                                </td>
                                                <td className="py-2 text-right font-medium">
                                                    {formatCurrency(extraChargeTotal)}
                                                </td>
                                            </tr>
                                            <tr className="border-b">
                                                <td className="py-2 text-muted-foreground">
                                                    {isInVatSystem
                                                        ? 'Subtotal (bez PDV)'
                                                        : 'Subtotal'}
                                                </td>
                                                <td className="py-2 text-right font-medium">
                                                    {formatCurrency(subtotalWithoutPdv)}
                                                </td>
                                            </tr>
                                            {isInVatSystem && (
                                                <tr className="border-b">
                                                    <td className="py-2 text-muted-foreground">
                                                        PDV (17%)
                                                    </td>
                                                    <td className="py-2 text-right font-medium">
                                                        {formatCurrency(pdvAmount)}
                                                    </td>
                                                </tr>
                                            )}
                                            <tr className="border-b">
                                                <td className="py-2 text-muted-foreground">
                                                    Popust
                                                </td>
                                                <td className="py-2 text-right font-medium">
                                                    {formatCurrency(discountTotal)}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td className="py-2 font-semibold">
                                                    {isInVatSystem
                                                        ? 'Ukupno (sa PDV)'
                                                        : 'Ukupno'}
                                                </td>
                                                <td className="py-2 text-right text-base font-semibold">
                                                    {formatCurrency(finalTotal)}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                    </section>

                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 size-4" />
                        Sačuvajte izmjene
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
