import { Head, router } from '@inertiajs/react';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
    ArcElement,
} from 'chart.js';
import { Download, Filter, Map, ReceiptText, UsersRound, Wallet } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { reservationStatusLabel } from '@/lib/status-badge';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
);

type Summary = {
    total_revenue: number;
    total_people: number;
    total_reservations: number;
    total_arrangements: number;
};

type StatusBreakdown = {
    status: string;
    count: number;
};

type DailyTrend = {
    date: string;
    reservations_count: number;
    people_count: number;
    total_revenue: number;
};

type ArrangementPerformance = {
    arrangement_id: string;
    arrangement_code: string;
    arrangement_name: string;
    reservations_count: number;
    people_count: number;
    total_revenue: number;
};

type ReservationRow = {
    reservation_id: string;
    reservation_number: string;
    created_at: string;
    status: string;
    payment_status: string;
    people_count: number;
    client_names: string;
    arrangement_code: string;
    arrangement_name: string;
    destination: string;
    revenue: number;
};

type Props = {
    filters: {
        datum_od: string;
        datum_do: string;
    };
    summary: Summary;
    status_breakdown: StatusBreakdown[];
    daily_trend: DailyTrend[];
    arrangement_performance: ArrangementPerformance[];
    reservation_rows: ReservationRow[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Izvještaji',
        href: '/izvjestaji',
    },
];

const formatMoney = (value: number): string =>
    new Intl.NumberFormat('bs-BA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);

const statusChartPalette = ['#16a34a', '#d97706', '#ef4444', '#0f766e', '#6b7280', '#2563eb'];

export default function ReportsIndex({
    filters,
    summary,
    status_breakdown,
    daily_trend,
    arrangement_performance,
    reservation_rows,
}: Props) {
    const [dateFrom, setDateFrom] = useState(filters.datum_od);
    const [dateTo, setDateTo] = useState(filters.datum_do);

    const handleFilterSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/izvjestaji',
            {
                datum_od: dateFrom,
                datum_do: dateTo,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleExport = () => {
        const query = new URLSearchParams({
            datum_od: dateFrom,
            datum_do: dateTo,
        });

        window.location.assign(`/izvjestaji/izvoz?${query.toString()}`);
    };

    const trendChartData = useMemo(
        () => ({
            labels: daily_trend.map((row) => formatDateDisplay(row.date)),
            datasets: [
                {
                    label: 'Broj rezervacija',
                    data: daily_trend.map((row) => row.reservations_count),
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.2)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y',
                },
                {
                    label: 'Broj putnika',
                    data: daily_trend.map((row) => row.people_count),
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.2)',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y',
                },
                {
                    label: 'Iznos (KM)',
                    data: daily_trend.map((row) => row.total_revenue),
                    borderColor: '#9333ea',
                    backgroundColor: 'rgba(147, 51, 234, 0.15)',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1',
                },
            ],
        }),
        [daily_trend],
    );

    const arrangementChartData = useMemo(
        () => ({
            labels: arrangement_performance.map(
                (row) => `${row.arrangement_code} - ${row.arrangement_name}`,
            ),
            datasets: [
                {
                    label: 'Iznos (KM)',
                    data: arrangement_performance.map((row) => row.total_revenue),
                    backgroundColor: [
                        '#0369a1',
                        '#0f766e',
                        '#1d4ed8',
                        '#7c3aed',
                        '#b45309',
                        '#be123c',
                        '#334155',
                        '#16a34a',
                        '#1f2937',
                        '#4338ca',
                    ],
                    borderRadius: 8,
                },
            ],
        }),
        [arrangement_performance],
    );

    const statusChartData = useMemo(
        () => ({
            labels: status_breakdown.map((row) => reservationStatusLabel(row.status)),
            datasets: [
                {
                    label: 'Rezervacije',
                    data: status_breakdown.map((row) => row.count),
                    backgroundColor: status_breakdown.map(
                        (_, index) => statusChartPalette[index % statusChartPalette.length],
                    ),
                    borderColor: '#ffffff',
                    borderWidth: 2,
                },
            ],
        }),
        [status_breakdown],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Izvještaji" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="rounded-2xl border border-slate-200 bg-gradient-to-r from-sky-100 via-cyan-50 to-emerald-100 p-5 shadow-sm dark:border-slate-700 dark:from-sky-950/50 dark:via-cyan-950/40 dark:to-emerald-950/50">
                    <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Izvještaji poslovanja</h1>
                            <p className="mt-1 text-sm text-slate-700 dark:text-slate-300">
                                Analitika rezervacija, putnika, prihoda i aranžmana za odabrani period.
                            </p>
                        </div>
                        <Button type="button" onClick={handleExport} className="bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200">
                            <Download className="mr-2 size-4" />
                            Izvoz u XLSX
                        </Button>
                    </div>

                    <form onSubmit={handleFilterSubmit} className="mt-4 grid gap-2 md:grid-cols-[180px_180px_auto]">
                        <Input
                            type="date"
                            value={dateFrom}
                            onChange={(event) => setDateFrom(event.target.value)}
                            aria-label="Datum od"
                        />
                        <Input
                            type="date"
                            value={dateTo}
                            onChange={(event) => setDateTo(event.target.value)}
                            aria-label="Datum do"
                        />
                        <Button type="submit" variant="secondary">
                            <Filter className="mr-2 size-4" />
                            Primijeni filter
                        </Button>
                    </form>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card className="border-sky-200 bg-sky-50/70 dark:border-sky-900/60 dark:bg-sky-950/30">
                        <CardHeader>
                            <CardDescription>Ukupan iznos</CardDescription>
                            <CardTitle className="text-3xl">{formatMoney(summary.total_revenue)} KM</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <Wallet className="size-6 text-sky-700 dark:text-sky-300" />
                        </CardContent>
                    </Card>
                    <Card className="border-emerald-200 bg-emerald-50/70 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                        <CardHeader>
                            <CardDescription>Broj putnika</CardDescription>
                            <CardTitle className="text-3xl">{summary.total_people}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <UsersRound className="size-6 text-emerald-700 dark:text-emerald-300" />
                        </CardContent>
                    </Card>
                    <Card className="border-violet-200 bg-violet-50/70 dark:border-violet-900/60 dark:bg-violet-950/30">
                        <CardHeader>
                            <CardDescription>Broj rezervacija</CardDescription>
                            <CardTitle className="text-3xl">{summary.total_reservations}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <ReceiptText className="size-6 text-violet-700 dark:text-violet-300" />
                        </CardContent>
                    </Card>
                    <Card className="border-amber-200 bg-amber-50/70 dark:border-amber-900/60 dark:bg-amber-950/30">
                        <CardHeader>
                            <CardDescription>Broj aranžmana</CardDescription>
                            <CardTitle className="text-3xl">{summary.total_arrangements}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <Map className="size-6 text-amber-700 dark:text-amber-300" />
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <Card className="xl:col-span-2">
                        <CardHeader>
                            <CardTitle>Trend po danima</CardTitle>
                            <CardDescription>Rezervacije, putnici i prihod kroz odabrani period.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[340px]">
                            <Line
                                data={trendChartData}
                                options={{
                                    maintainAspectRatio: false,
                                    interaction: {
                                        mode: 'index',
                                        intersect: false,
                                    },
                                    scales: {
                                        y: {
                                            position: 'left',
                                            ticks: { precision: 0 },
                                        },
                                        y1: {
                                            position: 'right',
                                            grid: { drawOnChartArea: false },
                                        },
                                    },
                                }}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Status rezervacija</CardTitle>
                            <CardDescription>Distribucija statusa u periodu.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[340px]">
                            <Doughnut
                                data={statusChartData}
                                options={{
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                        },
                                    },
                                }}
                            />
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <Card className="xl:col-span-2">
                        <CardHeader>
                            <CardTitle>Top aranžmani po prihodu</CardTitle>
                            <CardDescription>Najprofitabilniji aranžmani u odabranom periodu.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[360px]">
                            <Bar
                                data={arrangementChartData}
                                options={{
                                    maintainAspectRatio: false,
                                    indexAxis: 'y',
                                    plugins: {
                                        legend: {
                                            display: false,
                                        },
                                    },
                                }}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Top lista aranžmana</CardTitle>
                            <CardDescription>Brz pregled rezultata.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {arrangement_performance.length > 0 ? (
                                arrangement_performance.slice(0, 6).map((row) => (
                                    <div key={row.arrangement_id} className="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                        <p className="text-sm font-medium">{row.arrangement_code} - {row.arrangement_name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {row.reservations_count} rezervacija • {row.people_count} putnika
                                        </p>
                                        <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                            {formatMoney(row.total_revenue)} KM
                                        </p>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">Nema podataka za odabrani period.</p>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <Card>
                    <CardHeader>
                        <CardTitle>Posljednje rezervacije u periodu</CardTitle>
                        <CardDescription>Detaljni pregled s iznosima i putnicima.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-left">
                                    <tr>
                                        <th className="px-3 py-2 font-medium">Broj</th>
                                        <th className="px-3 py-2 font-medium">Datum</th>
                                        <th className="px-3 py-2 font-medium">Putnici</th>
                                        <th className="px-3 py-2 font-medium">Aranžman</th>
                                        <th className="px-3 py-2 font-medium">Status</th>
                                        <th className="px-3 py-2 font-medium text-right">Iznos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reservation_rows.length > 0 ? (
                                        reservation_rows.slice(0, 15).map((row) => (
                                            <tr key={row.reservation_id} className="border-t border-sidebar-border/70">
                                                <td className="px-3 py-2">{row.reservation_number || '-'}</td>
                                                <td className="px-3 py-2">{formatDateDisplay(row.created_at)}</td>
                                                <td className="px-3 py-2">
                                                    <div className="font-medium">{row.client_names || '-'}</div>
                                                    <div className="text-xs text-muted-foreground">{row.people_count} putnika</div>
                                                </td>
                                                <td className="px-3 py-2">
                                                    {row.arrangement_code} - {row.arrangement_name}
                                                </td>
                                                <td className="px-3 py-2">{reservationStatusLabel(row.status)}</td>
                                                <td className="px-3 py-2 text-right font-semibold">
                                                    {formatMoney(row.revenue)} KM
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={6} className="px-3 py-4 text-center text-muted-foreground">
                                                Nema rezervacija u odabranom periodu.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
