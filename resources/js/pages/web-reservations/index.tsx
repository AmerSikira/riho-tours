import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import PaginationControls from '@/components/pagination-controls';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type WebReservationRow = {
    id: string;
    ime: string | null;
    prezime: string | null;
    email: string | null;
    broj_telefona: string | null;
    broj_putnika: number;
    status: string;
    source_domain: string | null;
    created_at: string | null;
    arrangement: {
        id: string;
        sifra: string;
        naziv_putovanja: string;
        destinacija: string;
    } | null;
    package: {
        id: string;
        naziv: string;
    } | null;
    converted_reservation: {
        id: string;
        order_num: number | null;
    } | null;
};

type Props = {
    web_reservations: {
        data: WebReservationRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        pretraga?: string;
    };
    analytics: {
        totals: {
            all: number;
            new: number;
            contacted: number;
            converted: number;
            conversion_rate: number;
            average_conversion_hours: number;
        };
        top_source_domains: Array<{ label: string; total: number }>;
        top_utm_sources: Array<{ label: string; total: number }>;
        top_utm_campaigns: Array<{ label: string; total: number }>;
        lead_trend_30d: Array<{ date: string; total: number }>;
    };
    status?: string;
    error?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Web rezervacije',
        href: '/web-rezervacije',
    },
];

const statusLabel = (value: string): string => {
    if (value === 'konvertovano') {
        return 'Konvertovano';
    }

    if (value === 'kontaktiran') {
        return 'Kontaktiran';
    }

    return 'Novo';
};

export default function WebReservationsIndex({ web_reservations, filters, analytics, status, error }: Props) {
    const [search, setSearch] = useState(filters.pretraga ?? '');
    const trendMax = Math.max(...analytics.lead_trend_30d.map((item) => item.total), 1);

    const handleSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get('/web-rezervacije', {
            pretraga: search.trim() || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const goToPage = (page: number) => {
        router.get('/web-rezervacije', {
            pretraga: search.trim() || undefined,
            page,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Web rezervacije" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Web rezervacije</h1>
                        <p className="text-sm text-muted-foreground">
                            Leads sa web stranice koji čekaju obradu i prebacivanje u rezervacije.
                        </p>
                    </div>
                </div>

                {status && (
                    <div className="rounded-md border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                        {status}
                    </div>
                )}
                {error && (
                    <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSearch} className="flex w-full max-w-md items-center gap-2">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Pretraga po imenu, emailu, telefonu, domeni"
                    />
                    <Button type="submit" variant="secondary">
                        <Search className="mr-2 size-4" />
                        Pretražite
                    </Button>
                </form>

                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                    <div className="rounded-xl border border-sidebar-border/70 p-3">
                        <div className="text-xs text-muted-foreground">Ukupno leadova</div>
                        <div className="text-2xl font-semibold">{analytics.totals.all}</div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-3">
                        <div className="text-xs text-muted-foreground">Novi</div>
                        <div className="text-2xl font-semibold">{analytics.totals.new}</div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-3">
                        <div className="text-xs text-muted-foreground">Kontaktirani</div>
                        <div className="text-2xl font-semibold">{analytics.totals.contacted}</div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-3">
                        <div className="text-xs text-muted-foreground">Konvertovani</div>
                        <div className="text-2xl font-semibold">{analytics.totals.converted}</div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-3">
                        <div className="text-xs text-muted-foreground">Konverzija</div>
                        <div className="text-2xl font-semibold">{analytics.totals.conversion_rate.toFixed(2)}%</div>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-3">
                        <div className="text-xs text-muted-foreground">Prosjek do konverzije</div>
                        <div className="text-2xl font-semibold">{analytics.totals.average_conversion_hours.toFixed(1)}h</div>
                    </div>
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <div className="rounded-xl border border-sidebar-border/70 p-4">
                        <h2 className="text-sm font-semibold">Top domene</h2>
                        <div className="mt-3 space-y-2">
                            {analytics.top_source_domains.length === 0 && (
                                <p className="text-sm text-muted-foreground">Nema podataka.</p>
                            )}
                            {analytics.top_source_domains.map((item) => (
                                <div key={item.label} className="flex items-center justify-between text-sm">
                                    <span className="truncate">{item.label}</span>
                                    <span className="font-medium">{item.total}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 p-4">
                        <h2 className="text-sm font-semibold">Top UTM source</h2>
                        <div className="mt-3 space-y-2">
                            {analytics.top_utm_sources.length === 0 && (
                                <p className="text-sm text-muted-foreground">Nema podataka.</p>
                            )}
                            {analytics.top_utm_sources.map((item) => (
                                <div key={item.label} className="flex items-center justify-between text-sm">
                                    <span className="truncate">{item.label}</span>
                                    <span className="font-medium">{item.total}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 p-4">
                        <h2 className="text-sm font-semibold">Top UTM kampanje</h2>
                        <div className="mt-3 space-y-2">
                            {analytics.top_utm_campaigns.length === 0 && (
                                <p className="text-sm text-muted-foreground">Nema podataka.</p>
                            )}
                            {analytics.top_utm_campaigns.map((item) => (
                                <div key={item.label} className="flex items-center justify-between text-sm">
                                    <span className="truncate">{item.label}</span>
                                    <span className="font-medium">{item.total}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border border-sidebar-border/70 p-4">
                    <h2 className="text-sm font-semibold">Lead trend zadnjih 30 dana</h2>
                    <div className="mt-4 flex h-24 items-end gap-1">
                        {analytics.lead_trend_30d.map((item) => (
                            <div
                                key={item.date}
                                className="flex-1 rounded-sm bg-primary/25"
                                title={`${item.date}: ${item.total}`}
                                style={{ height: `${Math.max((item.total / trendMax) * 100, item.total > 0 ? 6 : 0)}%` }}
                            />
                        ))}
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                <th className="px-4 py-3">Klijent</th>
                                <th className="px-4 py-3">Aranžman</th>
                                <th className="px-4 py-3">Domena</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Kreirano</th>
                                <th className="px-4 py-3 text-right">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {web_reservations.data.map((row) => (
                                <tr key={row.id} className="border-t border-sidebar-border/70">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">
                                            {[row.ime, row.prezime].filter(Boolean).join(' ') || '-'}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {row.email || row.broj_telefona || '-'}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.arrangement
                                            ? `${row.arrangement.sifra} - ${row.arrangement.naziv_putovanja}`
                                            : '-'}
                                    </td>
                                    <td className="px-4 py-3">{row.source_domain || '-'}</td>
                                    <td className="px-4 py-3">
                                        <Badge variant="secondary">{statusLabel(row.status)}</Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatDateDisplay(row.created_at)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button variant="outline" asChild>
                                            <Link href={`/web-rezervacije/${row.id}`}>
                                                Detalji
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <PaginationControls
                    currentPage={web_reservations.current_page}
                    lastPage={web_reservations.last_page}
                    total={web_reservations.total}
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
