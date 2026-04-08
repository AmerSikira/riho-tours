import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Map, ReceiptText, Rows3, UsersRound } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import {
    reservationStatusBadgeClass,
    reservationStatusLabel,
} from '@/lib/status-badge';
import { formatDateDisplay } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type StatCards = {
    broj_putnika: number;
    broj_aranzmana: number;
    broj_programa: number;
    broj_rezervacija: number;
};

type LatestRezervacija = {
    id: number;
    putnici: string;
    broj_putnika: number;
    status: string;
    aranzman: {
        id: number | null;
        sifra: string | null;
        naziv_putovanja: string | null;
    };
    created_at: string | null;
};

type LatestArrangement = {
    id: number;
    sifra: string;
    naziv_putovanja: string;
    destinacija: string;
    datum_polaska: string | null;
    datum_povratka: string | null;
};

type Props = {
    stats: StatCards;
    latest_rezervacije: LatestRezervacija[];
    latest_aranzmani: LatestArrangement[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Nadzorna ploča',
        href: dashboard(),
    },
];

export default function Dashboard({
    stats,
    latest_rezervacije,
    latest_aranzmani,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nadzorna ploča" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="grid w-full gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card className="border-emerald-200 bg-emerald-50/70">
                        <CardHeader>
                            <CardDescription>Broj putnika registrovanih</CardDescription>
                            <CardTitle className="text-3xl">{stats.broj_putnika}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <UsersRound className="size-6 text-emerald-700" />
                        </CardContent>
                    </Card>

                    <Card className="border-amber-200 bg-amber-50/70">
                        <CardHeader>
                            <CardDescription>Broj aranžmana</CardDescription>
                            <CardTitle className="text-3xl">{stats.broj_aranzmana}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <Map className="size-6 text-amber-700" />
                        </CardContent>
                    </Card>

                    <Card className="border-sky-200 bg-sky-50/70">
                        <CardHeader>
                            <CardDescription>Broj programa</CardDescription>
                            <CardTitle className="text-3xl">{stats.broj_programa}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <Rows3 className="size-6 text-sky-700" />
                        </CardContent>
                    </Card>

                    <Card className="border-violet-200 bg-violet-50/70">
                        <CardHeader>
                            <CardDescription>Broj rezervacija</CardDescription>
                            <CardTitle className="text-3xl">{stats.broj_rezervacija}</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center justify-end">
                            <ReceiptText className="size-6 text-violet-700" />
                        </CardContent>
                    </Card>
                </section>

                <div className="grid gap-4 xl:grid-cols-2">
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70">
                        <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-base font-semibold">Rezervacije</h2>
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/rezervacije">
                                    Sve rezervacije
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </header>

                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Putnici</th>
                                    <th className="px-4 py-3 font-medium">Aranžman</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {latest_rezervacije.length > 0 ? (
                                    latest_rezervacije.map((rezervacija) => (
                                        <tr
                                            key={rezervacija.id}
                                            className="cursor-pointer border-t border-sidebar-border/70 transition-colors hover:bg-muted/40"
                                            onClick={() => {
                                                router.visit(
                                                    `/rezervacije/${rezervacija.id}/uredi`,
                                                );
                                            }}
                                        >
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{rezervacija.putnici}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    Putnika: {rezervacija.broj_putnika}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                {(rezervacija.aranzman.sifra ?? '-') +
                                                    ' - ' +
                                                    (rezervacija.aranzman.naziv_putovanja ?? '-')}
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
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-4 py-6 text-center text-muted-foreground"
                                        >
                                            Nema rezervacija.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </section>

                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70">
                        <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-base font-semibold">Nadolazeća putovanja</h2>
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/aranzmani">
                                    Sva nadolazeća putovanja
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </header>

                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Šifra</th>
                                    <th className="px-4 py-3 font-medium">Naziv</th>
                                    <th className="px-4 py-3 font-medium">Datumi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {latest_aranzmani.length > 0 ? (
                                    latest_aranzmani.map((aranzman) => (
                                        <tr
                                            key={aranzman.id}
                                            className="cursor-pointer border-t border-sidebar-border/70 transition-colors hover:bg-muted/40"
                                            onClick={() => {
                                                router.visit(
                                                    `/rezervacije?aranzman_id=${aranzman.id}`,
                                                );
                                            }}
                                        >
                                            <td className="px-4 py-3">{aranzman.sifra}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{aranzman.naziv_putovanja}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {aranzman.destinacija}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatDateDisplay(aranzman.datum_polaska)} -{' '}
                                                {formatDateDisplay(aranzman.datum_povratka)}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-4 py-6 text-center text-muted-foreground"
                                        >
                                            Nema aranžmana.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
