import { Head, router } from '@inertiajs/react';
import { Download, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import type { FormEvent } from 'react';
import PaginationControls from '@/components/pagination-controls';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Uplata = {
    id: string;
    uplatio: string;
    datum: string;
    za_sta: string;
    nacin_uplate: string;
    nacin_uplate_label: string;
};

type ArrangementOption = {
    id: string;
    sifra: string;
    naziv_putovanja: string;
    destinacija: string;
    datum_polaska: string | null;
    datum_povratka: string | null;
};

type Props = {
    uplate: {
        data: Uplata[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        pretraga?: string;
        aranzman_id?: string;
        datum_od?: string;
        datum_do?: string;
    };
    selected_aranzman?: ArrangementOption | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Blagajna',
        href: '/blagajna',
    },
];

const formatArrangementOption = (arrangement: ArrangementOption): string =>
    `${arrangement.sifra} - ${arrangement.naziv_putovanja} (${formatDateDisplay(arrangement.datum_polaska)} / ${formatDateDisplay(arrangement.datum_povratka)})`;

export default function BlagajnaIndex({ uplate, filters, selected_aranzman: selectedArrangement }: Props) {
    const [searchQuery, setSearchQuery] = useState(
        selectedArrangement
            ? formatArrangementOption(selectedArrangement)
            : (filters.pretraga ?? ''),
    );
    const [arrangementId, setArrangementId] = useState(filters.aranzman_id ?? '');
    const [dateFrom, setDateFrom] = useState(filters.datum_od ?? '');
    const [dateTo, setDateTo] = useState(filters.datum_do ?? '');
    const [arrangementSuggestions, setArrangementSuggestions] = useState<ArrangementOption[]>([]);
    const [isArrangementOpen, setIsArrangementOpen] = useState(false);
    const searchRequest = useRef<AbortController | null>(null);

    const fetchArrangementSuggestions = (query: string) => {
        const normalizedQuery = query.trim();

        if (normalizedQuery.length < 2) {
            setArrangementSuggestions([]);

            return;
        }

        searchRequest.current?.abort();
        const controller = new AbortController();
        searchRequest.current = controller;

        void fetch(`/blagajna/aranzmani/pretraga?q=${encodeURIComponent(normalizedQuery)}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Neuspješno učitavanje aranžmana.');
                }

                const suggestions = (await response.json()) as ArrangementOption[];
                setArrangementSuggestions(suggestions);
            })
            .catch((error: unknown) => {
                if ((error as { name?: string })?.name === 'AbortError') {
                    return;
                }

                setArrangementSuggestions([]);
            });
    };

    const handleArrangementSelect = (arrangement: ArrangementOption) => {
        setSearchQuery(formatArrangementOption(arrangement));
        setArrangementId(arrangement.id);
        setArrangementSuggestions([]);
        setIsArrangementOpen(false);
    };

    const handleArrangementInputChange = (value: string) => {
        setSearchQuery(value);
        setArrangementId('');
        fetchArrangementSuggestions(value);
    };

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/blagajna',
            {
                aranzman_id: arrangementId || undefined,
                pretraga: arrangementId ? undefined : searchQuery.trim() || undefined,
                datum_od: dateFrom || undefined,
                datum_do: dateTo || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/blagajna',
            {
                aranzman_id: arrangementId || undefined,
                pretraga: arrangementId ? undefined : searchQuery.trim() || undefined,
                datum_od: dateFrom || undefined,
                datum_do: dateTo || undefined,
                page,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleCsvExport = () => {
        const searchParams = new URLSearchParams();

        if (arrangementId) {
            searchParams.set('aranzman_id', arrangementId);
        } else if (searchQuery.trim() !== '') {
            searchParams.set('pretraga', searchQuery.trim());
        }

        if (dateFrom) {
            searchParams.set('datum_od', dateFrom);
        }

        if (dateTo) {
            searchParams.set('datum_do', dateTo);
        }

        window.location.assign(`/blagajna/izvoz/csv?${searchParams.toString()}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Blagajna" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Blagajna</h1>
                        <p className="text-sm text-muted-foreground">
                            Pregled svih uplata sa filterima i izvozom u CSV.
                        </p>
                    </div>

                    <Button type="button" variant="outline" onClick={handleCsvExport}>
                        <Download className="mr-2 size-4" />
                        Izvezi CSV
                    </Button>
                </div>

                <form onSubmit={handleSearchSubmit} className="grid gap-2 md:grid-cols-[1fr_180px_180px_auto]">
                    <div className="relative">
                        <Input
                            value={searchQuery}
                            onChange={(event) => handleArrangementInputChange(event.target.value)}
                            onFocus={() => setIsArrangementOpen(true)}
                            onBlur={() => {
                                window.setTimeout(() => setIsArrangementOpen(false), 120);
                            }}
                            placeholder="Pretraga po osobi, aranžmanu ili šifri"
                            aria-label="Pretraga uplata"
                        />
                        {isArrangementOpen && arrangementSuggestions.length > 0 && (
                            <div className="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border bg-background shadow-sm">
                                {arrangementSuggestions.map((arrangement) => (
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

                    <Input type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} />
                    <Input type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} />

                    <Button type="submit">
                        <Search className="mr-2 size-4" />
                        Filtriraj
                    </Button>
                </form>

                <div className="overflow-hidden rounded-lg border bg-background">
                    <table className="min-w-full divide-y divide-border text-sm">
                        <thead className="bg-muted/30">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Ko je uplatio</th>
                                <th className="px-4 py-3 text-left font-medium">Kada</th>
                                <th className="px-4 py-3 text-left font-medium">Za šta</th>
                                <th className="px-4 py-3 text-left font-medium">Način uplate</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {uplate.data.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-6 text-center text-muted-foreground">
                                        Nema uplata za zadane filtere.
                                    </td>
                                </tr>
                            )}
                            {uplate.data.map((uplata) => (
                                <tr key={uplata.id} className="hover:bg-muted/20">
                                    <td className="px-4 py-3">{uplata.uplatio || '-'}</td>
                                    <td className="px-4 py-3">{formatDateDisplay(uplata.datum)}</td>
                                    <td className="px-4 py-3">{uplata.za_sta || '-'}</td>
                                    <td className="px-4 py-3">{uplata.nacin_uplate_label}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <PaginationControls
                    currentPage={uplate.current_page}
                    lastPage={uplate.last_page}
                    total={uplate.total}
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
