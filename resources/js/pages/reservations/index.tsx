import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, ChevronsUpDown, MoreHorizontal, Plus, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import type { FormEvent } from 'react';
import PaginationControls from '@/components/pagination-controls';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import {
    reservationStatusBadgeClass,
    reservationStatusLabel,
} from '@/lib/status-badge';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Rezervacija = {
    id: string;
    order_num: number | null;
    ime_prezime: string;
    email: string | null;
    telefon: string | null;
    broj_putnika: number;
    status: string;
    payment_status: {
        label: string;
        tone: 'success' | 'warning' | 'neutral';
    };
    aranzman: {
        id: string;
        sifra: string;
        naziv_putovanja: string;
        destinacija: string;
        datum_polaska: string | null;
        datum_povratka: string | null;
    };
};

type Props = {
    rezervacije: {
        data: Rezervacija[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        pretraga?: string;
        aranzman_id?: string;
        datum_od?: string;
        datum_do?: string;
        sort_by?: SortColumn;
        sort_direction?: SortDirection;
    };
    selected_aranzman?: {
        id: string;
        sifra: string;
        naziv_putovanja: string;
        destinacija: string;
        datum_polaska: string | null;
        datum_povratka: string | null;
    } | null;
    status?: string;
};

type SortColumn = 'putnik' | 'aranzman' | 'datumi' | 'broj_putnika';
type SortDirection = 'asc' | 'desc';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rezervacije',
        href: '/rezervacije',
    },
];

const formatArrangementOption = (arrangement: NonNullable<Props['selected_aranzman']>): string =>
    `${arrangement.sifra} - ${arrangement.naziv_putovanja} (${formatDateDisplay(
        arrangement.datum_polaska,
    )} / ${formatDateDisplay(arrangement.datum_povratka)})`;

const paymentStatusBadgeClass = (tone: Rezervacija['payment_status']['tone']): string => {
    if (tone === 'success') {
        return 'border-transparent bg-emerald-100 text-emerald-800';
    }

    if (tone === 'warning') {
        return 'border-transparent bg-amber-100 text-amber-800';
    }

    return 'border-transparent bg-slate-200 text-slate-700';
};

export default function ReservationsIndex({ rezervacije, filters, selected_aranzman: selectedArrangement, status }: Props) {
    const todayIsoDate = new Date().toISOString().slice(0, 10);
    const [arrangementQuery, setArrangementQuery] = useState(
        selectedArrangement
            ? formatArrangementOption(selectedArrangement)
            : (filters.pretraga ?? ''),
    );
    const [arrangementId, setArrangementId] = useState(filters.aranzman_id ?? '');
    const [dateFrom, setDateFrom] = useState(filters.datum_od ?? '');
    const [dateTo, setDateTo] = useState(filters.datum_do ?? '');
    const [arrangementSuggestions, setArrangementSuggestions] = useState<
        NonNullable<Props['selected_aranzman']>[]
    >([]);
    const [isArrangementOpen, setIsArrangementOpen] = useState(false);
    const [selectedReservationIds, setSelectedReservationIds] = useState<Set<string>>(new Set());
    const [sortBy, setSortBy] = useState<SortColumn | ''>(filters.sort_by ?? '');
    const [sortDirection, setSortDirection] = useState<SortDirection>(filters.sort_direction ?? 'asc');
    const searchRequest = useRef<AbortController | null>(null);
    const visibleReservationIds = rezervacije.data.map((rezervacija) => String(rezervacija.id));
    const selectedCountOnPage = visibleReservationIds.filter((reservationId) =>
        selectedReservationIds.has(reservationId),
    ).length;
    const areAllReservationsOnPageSelected =
        visibleReservationIds.length > 0 && selectedCountOnPage === visibleReservationIds.length;
    const areSomeReservationsOnPageSelected =
        selectedCountOnPage > 0 && !areAllReservationsOnPageSelected;

    const fetchArrangementSuggestions = (query: string) => {
        const normalizedQuery = query.trim();

        if (normalizedQuery.length < 2) {
            setArrangementSuggestions([]);

            return;
        }

        searchRequest.current?.abort();
        const controller = new AbortController();
        searchRequest.current = controller;

        void fetch(`/rezervacije/aranzmani/pretraga?q=${encodeURIComponent(normalizedQuery)}`, {
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

                const suggestions = (await response.json()) as NonNullable<Props['selected_aranzman']>[];
                setArrangementSuggestions(suggestions);
            })
            .catch((error: unknown) => {
                if ((error as { name?: string })?.name === 'AbortError') {
                    return;
                }

                setArrangementSuggestions([]);
            });
    };

    const handleArrangementSelect = (arrangement: NonNullable<Props['selected_aranzman']>) => {
        setArrangementQuery(formatArrangementOption(arrangement));
        setArrangementId(arrangement.id);
        setArrangementSuggestions([]);
        setIsArrangementOpen(false);
    };

    const handleArrangementInputChange = (value: string) => {
        setArrangementQuery(value);
        setArrangementId('');
        fetchArrangementSuggestions(value);
    };

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/rezervacije',
            {
                aranzman_id: arrangementId || undefined,
                pretraga: arrangementId ? undefined : arrangementQuery.trim() || undefined,
                datum_od: dateFrom || undefined,
                datum_do: dateTo || undefined,
                sort_by: sortBy || undefined,
                sort_direction: sortBy ? sortDirection : undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleDelete = (rezervacija: Rezervacija) => {
        if (!window.confirm('Da li ste sigurni da želite obrisati ovu rezervaciju?')) {
            return;
        }

        router.delete(`/rezervacije/${rezervacija.id}`);
    };

    const goToPage = (page: number) => {
        router.get(
            '/rezervacije',
            {
                aranzman_id: arrangementId || undefined,
                pretraga: arrangementId ? undefined : arrangementQuery.trim() || undefined,
                datum_od: dateFrom || undefined,
                datum_do: dateTo || undefined,
                sort_by: sortBy || undefined,
                sort_direction: sortBy ? sortDirection : undefined,
                page,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const toggleReservationSelection = (reservationId: string, isChecked: boolean) => {
        setSelectedReservationIds((previousValue) => {
            const nextValue = new Set(previousValue);

            if (isChecked) {
                nextValue.add(reservationId);
            } else {
                nextValue.delete(reservationId);
            }

            return nextValue;
        });
    };

    const toggleSelectAllReservationsOnPage = (isChecked: boolean) => {
        setSelectedReservationIds((previousValue) => {
            const nextValue = new Set(previousValue);

            visibleReservationIds.forEach((reservationId) => {
                if (isChecked) {
                    nextValue.add(reservationId);
                } else {
                    nextValue.delete(reservationId);
                }
            });

            return nextValue;
        });
    };

    const handleExportSelectedReservations = () => {
        if (selectedReservationIds.size === 0) {
            return;
        }

        const searchParams = new URLSearchParams();

        Array.from(selectedReservationIds).forEach((reservationId) => {
            searchParams.append('reservation_ids[]', reservationId);
        });

        window.location.assign(`/rezervacije/izvoz/putnici?${searchParams.toString()}`);
    };

    const handleSort = (column: SortColumn) => {
        const nextDirection: SortDirection =
            sortBy === column && sortDirection === 'asc' ? 'desc' : 'asc';

        setSortBy(column);
        setSortDirection(nextDirection);

        router.get(
            '/rezervacije',
            {
                aranzman_id: arrangementId || undefined,
                pretraga: arrangementId ? undefined : arrangementQuery.trim() || undefined,
                datum_od: dateFrom || undefined,
                datum_do: dateTo || undefined,
                sort_by: column,
                sort_direction: nextDirection,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const renderSortIcon = (column: SortColumn) => {
        if (sortBy !== column) {
            return <ChevronsUpDown className="size-3.5 text-muted-foreground" aria-hidden />;
        }

        if (sortDirection === 'asc') {
            return <ChevronUp className="size-3.5" aria-hidden />;
        }

        return <ChevronDown className="size-3.5" aria-hidden />;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rezervacije" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Rezervacije</h1>
                        <p className="text-sm text-muted-foreground">
                            Pretraga po aranžmanu kroz autocomplete i rasponu putovanja.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href="/rezervacije/dodaj">
                            <Plus className="mr-2 size-4" />
                            Dodajte rezervaciju
                        </Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSearchSubmit}
                    className="grid gap-2 md:grid-cols-[1fr_180px_180px_auto]"
                >
                    <div className="relative">
                        <Input
                            value={arrangementQuery}
                            onChange={(event) => handleArrangementInputChange(event.target.value)}
                            onFocus={() => setIsArrangementOpen(true)}
                            onBlur={() => {
                                window.setTimeout(() => setIsArrangementOpen(false), 120);
                            }}
                            placeholder="Počnite kucati šifru, naziv ili destinaciju aranžmana"
                            aria-label="Pretraga aranžmana"
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

                    <Input
                        type="date"
                        value={dateFrom}
                        onChange={(event) => setDateFrom(event.target.value)}
                        aria-label="Datum putovanja od"
                    />

                    <Input
                        type="date"
                        value={dateTo}
                        onChange={(event) => setDateTo(event.target.value)}
                        aria-label="Datum putovanja do"
                    />

                    <Button type="submit" variant="secondary">
                        <Search className="mr-2 size-4" />
                        Pretraži
                    </Button>
                </form>

                {status && (
                    <div className="rounded-md border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                        {status}
                    </div>
                )}

                <div className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-sidebar-border/70 px-3 py-2">
                    <div className="text-sm text-muted-foreground">
                        Odabrane rezervacije: {selectedReservationIds.size}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => toggleSelectAllReservationsOnPage(true)}
                            disabled={visibleReservationIds.length === 0}
                        >
                            Označi sve
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setSelectedReservationIds(new Set())}
                            disabled={selectedReservationIds.size === 0}
                        >
                            Poništi odabir
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            onClick={handleExportSelectedReservations}
                            disabled={selectedReservationIds.size === 0}
                        >
                            Izvoz odabranih (XLSX)
                        </Button>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                <th className="w-12 px-4 py-3 font-medium">
                                    <Checkbox
                                        aria-label="Odaberi sve rezervacije na stranici"
                                        checked={areAllReservationsOnPageSelected ? true : (areSomeReservationsOnPageSelected ? 'indeterminate' : false)}
                                        onCheckedChange={(checked) => {
                                            toggleSelectAllReservationsOnPage(checked === true);
                                        }}
                                    />
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    <button
                                        type="button"
                                        className="inline-flex items-center gap-1.5 hover:text-foreground"
                                        onClick={() => handleSort('putnik')}
                                    >
                                        Putnik
                                        {renderSortIcon('putnik')}
                                    </button>
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    <button
                                        type="button"
                                        className="inline-flex items-center gap-1.5 hover:text-foreground"
                                        onClick={() => handleSort('aranzman')}
                                    >
                                        Aranžman
                                        {renderSortIcon('aranzman')}
                                    </button>
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    <button
                                        type="button"
                                        className="inline-flex items-center gap-1.5 hover:text-foreground"
                                        onClick={() => handleSort('datumi')}
                                    >
                                        Datumi
                                        {renderSortIcon('datumi')}
                                    </button>
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    <button
                                        type="button"
                                        className="inline-flex items-center gap-1.5 hover:text-foreground"
                                        onClick={() => handleSort('broj_putnika')}
                                    >
                                        Broj putnika
                                        {renderSortIcon('broj_putnika')}
                                    </button>
                                </th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Plaćanje</th>
                                <th className="px-4 py-3 text-right font-medium">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rezervacije.data.length > 0 ? (
                                rezervacije.data.map((rezervacija) => {
                                    const tripStartedOrPassed =
                                        rezervacija.aranzman.datum_polaska !== null &&
                                        rezervacija.aranzman.datum_polaska <= todayIsoDate;

                                    return (
                                        <tr
                                            key={rezervacija.id}
                                            className={`border-t border-sidebar-border/70 ${
                                                tripStartedOrPassed
                                                    ? 'bg-muted/30 text-muted-foreground'
                                                    : ''
                                            }`}
                                        >
                                        <td className="px-4 py-3">
                                            <Checkbox
                                                aria-label={`Odaberi rezervaciju ${rezervacija.order_num ?? rezervacija.id}`}
                                                checked={selectedReservationIds.has(String(rezervacija.id))}
                                                onCheckedChange={(checked) => {
                                                    toggleReservationSelection(String(rezervacija.id), checked === true);
                                                }}
                                            />
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium">
                                                {rezervacija.ime_prezime}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {rezervacija.email ?? rezervacija.telefon ?? '-'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            {rezervacija.aranzman.sifra} -{' '}
                                            {rezervacija.aranzman.naziv_putovanja}
                                        </td>
                                        <td className="px-4 py-3">
                                            {formatDateDisplay(rezervacija.aranzman.datum_polaska)} -{' '}
                                            {formatDateDisplay(rezervacija.aranzman.datum_povratka)}
                                        </td>
                                        <td className="px-4 py-3">
                                            {rezervacija.broj_putnika}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant="outline"
                                                className={reservationStatusBadgeClass(
                                                    rezervacija.status,
                                                )}
                                            >
                                                {reservationStatusLabel(
                                                    rezervacija.status,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant="outline"
                                                className={paymentStatusBadgeClass(
                                                    rezervacija.payment_status.tone,
                                                )}
                                            >
                                                {rezervacija.payment_status.label}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Akcije rezervacije"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`/rezervacije/${rezervacija.id}/uredi`}
                                                        >
                                                            Uredite
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onClick={() =>
                                                            handleDelete(rezervacija)
                                                        }
                                                    >
                                                        Obrisati
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Nema rezervacija za zadane filtere.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationControls
                    currentPage={rezervacije.current_page}
                    lastPage={rezervacije.last_page}
                    total={rezervacije.total}
                    entityLabel="rezervacija"
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
