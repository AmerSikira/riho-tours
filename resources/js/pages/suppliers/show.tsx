import { Head, Link, router } from '@inertiajs/react';
import { CalendarRange, HandCoins, MapPinned, ReceiptText, UsersRound } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Supplier = {
    id: string;
    company_name: string;
    company_id: string | null;
    pdv: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    zip: string | null;
};

type SupplierReport = {
    reservation_count: number;
    people_count: number;
    arrangement_count: number;
    income: number;
    total_profit: number;
    avg_profit_per_person: number;
    avg_profit_per_arrangement: number;
    avg_profit_per_reservation: number;
};

type ArrangementBreakdownRow = {
    arrangement_id: string;
    arrangement_code: string;
    arrangement_name: string;
    destination: string;
    reservations_count: number;
    people_count: number;
    income: number;
    profit: number;
};

type Props = {
    dobavljac: Supplier;
    filters: {
        datum_od: string;
        datum_do: string;
    };
    report: SupplierReport;
    arrangement_breakdown: ArrangementBreakdownRow[];
};

const formatMoney = (value: number): string =>
    new Intl.NumberFormat('bs-BA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);

export default function ShowSupplierReport({
    dobavljac: supplier,
    filters,
    report,
    arrangement_breakdown: arrangementBreakdown,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dobavljači',
            href: '/dobavljaci',
        },
        {
            title: supplier.company_name,
            href: `/dobavljaci/${supplier.id}`,
        },
    ];

    const [dateFrom, setDateFrom] = useState(filters.datum_od);
    const [dateTo, setDateTo] = useState(filters.datum_do);

    const applyDateFilter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            `/dobavljaci/${supplier.id}`,
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Dobavljač - ${supplier.company_name}`} />

            <div className="mx-auto flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="rounded-2xl border border-slate-200 bg-gradient-to-r from-cyan-100 via-sky-50 to-indigo-100 p-5 shadow-sm">
                    <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
                                {supplier.company_name}
                            </h1>
                            <p className="text-sm text-slate-700">
                                ID: {supplier.company_id || '-'} | PDV: {supplier.pdv || '-'}
                            </p>
                            <p className="text-sm text-slate-700">
                                Kontakt: {supplier.phone || '-'} | {supplier.email || '-'}
                            </p>
                            <p className="text-sm text-slate-700">
                                Adresa: {[supplier.address, supplier.city, supplier.zip]
                                    .filter(Boolean)
                                    .join(', ') || '-'}
                            </p>
                        </div>
                        <Button asChild variant="outline">
                            <Link href={`/dobavljaci/${supplier.id}/uredi`}>Uredite dobavljača</Link>
                        </Button>
                    </div>

                    <form onSubmit={applyDateFilter} className="mt-4 grid gap-2 md:grid-cols-[180px_180px_auto]">
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
                            <CalendarRange className="mr-2 size-4" />
                            Primijeni period
                        </Button>
                    </form>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card className="border-sky-200 bg-sky-50/70">
                        <CardHeader>
                            <CardDescription>Broj rezervacija</CardDescription>
                            <CardTitle className="text-3xl">{report.reservation_count}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <ReceiptText className="size-6 text-sky-700" />
                        </CardContent>
                    </Card>

                    <Card className="border-emerald-200 bg-emerald-50/70">
                        <CardHeader>
                            <CardDescription>Broj putnika</CardDescription>
                            <CardTitle className="text-3xl">{report.people_count}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <UsersRound className="size-6 text-emerald-700" />
                        </CardContent>
                    </Card>

                    <Card className="border-amber-200 bg-amber-50/70">
                        <CardHeader>
                            <CardDescription>Prihod</CardDescription>
                            <CardTitle className="text-3xl">{formatMoney(report.income)} KM</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <HandCoins className="size-6 text-amber-700" />
                        </CardContent>
                    </Card>

                    <Card className="border-violet-200 bg-violet-50/70">
                        <CardHeader>
                            <CardDescription>Ukupna zarada</CardDescription>
                            <CardTitle className="text-3xl">{formatMoney(report.total_profit)} KM</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <MapPinned className="size-6 text-violet-700" />
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardDescription>Prosjek zarade po osobi</CardDescription>
                            <CardTitle className="text-2xl">{formatMoney(report.avg_profit_per_person)} KM</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Prosjek zarade po aranžmanu</CardDescription>
                            <CardTitle className="text-2xl">{formatMoney(report.avg_profit_per_arrangement)} KM</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Prosjek zarade po rezervaciji</CardDescription>
                            <CardTitle className="text-2xl">{formatMoney(report.avg_profit_per_reservation)} KM</CardTitle>
                        </CardHeader>
                    </Card>
                </section>

                <Card>
                    <CardHeader>
                        <CardTitle>Analiza po aranžmanima</CardTitle>
                        <CardDescription>
                            Aranžmani povezani sa dobavljačem u odabranom periodu.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-left">
                                    <tr>
                                        <th className="px-3 py-2 font-medium">Aranžman</th>
                                        <th className="px-3 py-2 font-medium">Destinacija</th>
                                        <th className="px-3 py-2 font-medium">Rezervacije</th>
                                        <th className="px-3 py-2 font-medium">Putnici</th>
                                        <th className="px-3 py-2 font-medium text-right">Prihod</th>
                                        <th className="px-3 py-2 font-medium text-right">Zarada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {arrangementBreakdown.length > 0 ? (
                                        arrangementBreakdown.map((row) => (
                                            <tr key={row.arrangement_id} className="border-t border-sidebar-border/70">
                                                <td className="px-3 py-2">
                                                    {row.arrangement_code} - {row.arrangement_name}
                                                </td>
                                                <td className="px-3 py-2">{row.destination || '-'}</td>
                                                <td className="px-3 py-2">{row.reservations_count}</td>
                                                <td className="px-3 py-2">{row.people_count}</td>
                                                <td className="px-3 py-2 text-right">{formatMoney(row.income)} KM</td>
                                                <td className="px-3 py-2 text-right font-semibold">{formatMoney(row.profit)} KM</td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={6} className="px-3 py-4 text-center text-muted-foreground">
                                                Nema podataka za odabrani period.
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
