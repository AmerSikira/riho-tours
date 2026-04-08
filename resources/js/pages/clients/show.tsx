import { Head, Link } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    Clock3,
    CreditCard,
    Mail,
    MapPin,
    PenSquare,
    Phone,
    Plane,
    Users,
    XCircle,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { reservationStatusBadgeClass } from '@/lib/status-badge';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Aktivnost = {
    id: string;
    status: string;
    status_label: string;
    rezervacija_id: string | null;
    created_at: string | null;
    broj_putnika: number | null;
    paket: {
        naziv: string | null;
        cijena: string | null;
    };
    dodatno_na_cijenu: string | null;
    popust: string | null;
    aranzman: {
        sifra: string | null;
        naziv_putovanja: string | null;
        destinacija: string | null;
        datum_polaska: string | null;
        datum_povratka: string | null;
    };
};

type Props = {
    klijent: {
        id: string;
        ime: string;
        prezime: string;
        broj_dokumenta: string;
        datum_rodjenja: string | null;
        city: string | null;
        adresa: string;
        broj_telefona: string;
        email: string | null;
        fotografija_url: string | null;
        created_at: string | null;
    };
    statistika: {
        ukupno_rezervacija: number;
        potvrdjene: number;
        na_cekanju: number;
        otkazane: number;
        ukupna_potrosnja: number;
    };
    aktivnosti: Aktivnost[];
};

const money = (value: number | string | null | undefined): string => `${Number(value ?? 0).toFixed(2)} KM`;

export default function ClientProfile({ klijent: client, statistika: statistics, aktivnosti: activities }: Props) {
    const getInitials = useInitials();
    const fullName = `${client.ime} ${client.prezime}`;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Klijenti',
            href: '/klijenti',
        },
        {
            title: 'Profil klijenta',
            href: `/klijent/${client.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${fullName} - profil`} />

            <div className="mx-auto flex h-full w-full max-w-6xl flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <section className="relative overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-sm">
                    <div className="h-36 bg-gradient-to-r from-sky-600 via-cyan-500 to-teal-500" />
                    <div className="absolute -top-8 right-8 size-28 rounded-full bg-white/10 blur-2xl" />
                    <div className="absolute top-8 right-28 size-20 rounded-full bg-white/20 blur-xl" />

                    <div className="absolute top-24 left-6 rounded-full border-4 border-background bg-card shadow-md">
                        <Avatar className="size-24">
                            <AvatarImage src={client.fotografija_url ?? undefined} alt={fullName} />
                            <AvatarFallback className="bg-slate-900 text-2xl font-semibold text-white">
                                {getInitials(fullName)}
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    <div className="space-y-4 px-6 pt-20 pb-6">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h1 className="text-2xl font-semibold">{fullName}</h1>
                                <p className="text-sm text-muted-foreground">
                                    Putnik od {formatDateDisplay(client.created_at)}
                                </p>
                            </div>

                            <div className="flex gap-2">
                                <Button variant="outline" asChild>
                                    <Link href={`/klijenti/${client.id}/uredi`}>
                                        <PenSquare className="mr-2 size-4" />
                                        Uredite profil
                                    </Link>
                                </Button>
                                <Button asChild>
                                    <Link href="/klijenti">Svi klijenti</Link>
                                </Button>
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            <div className="rounded-xl border border-sidebar-border/70 bg-muted/20 p-3">
                                <p className="text-xs text-muted-foreground">Rezervacije</p>
                                <p className="text-lg font-semibold">{statistics.ukupno_rezervacija}</p>
                            </div>
                            <div className="rounded-xl border border-sidebar-border/70 bg-muted/20 p-3">
                                <p className="text-xs text-muted-foreground">Potvrđene</p>
                                <p className="text-lg font-semibold">{statistics.potvrdjene}</p>
                            </div>
                            <div className="rounded-xl border border-sidebar-border/70 bg-muted/20 p-3">
                                <p className="text-xs text-muted-foreground">Na čekanju</p>
                                <p className="text-lg font-semibold">{statistics.na_cekanju}</p>
                            </div>
                            <div className="rounded-xl border border-sidebar-border/70 bg-muted/20 p-3">
                                <p className="text-xs text-muted-foreground">Otkazane</p>
                                <p className="text-lg font-semibold">{statistics.otkazane}</p>
                            </div>
                            <div className="rounded-xl border border-sidebar-border/70 bg-muted/20 p-3">
                                <p className="text-xs text-muted-foreground">Ukupna potrošnja</p>
                                <p className="text-lg font-semibold">{money(statistics.ukupna_potrosnja)}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <div className="grid gap-6 lg:grid-cols-[320px_1fr]">
                    <aside className="space-y-4">
                        <section className="space-y-3 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <h2 className="text-base font-semibold">Kontakt</h2>
                            <div className="space-y-3 text-sm">
                                <p className="inline-flex items-center gap-2 text-muted-foreground">
                                    <Phone className="size-4" />
                                    {client.broj_telefona}
                                </p>
                                <p className="inline-flex items-center gap-2 text-muted-foreground">
                                    <Mail className="size-4" />
                                    {client.email ?? 'Email nije postavljen'}
                                </p>
                                <p className="inline-flex items-center gap-2 text-muted-foreground">
                                    <MapPin className="size-4" />
                                    {client.city ? `${client.city}, ${client.adresa}` : client.adresa}
                                </p>
                            </div>
                        </section>

                        <section className="space-y-3 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <h2 className="text-base font-semibold">Lični podaci</h2>
                            <div className="space-y-3 text-sm text-muted-foreground">
                                <p className="inline-flex items-center gap-2">
                                    <CalendarDays className="size-4" />
                                    Rođenje: {formatDateDisplay(client.datum_rodjenja)}
                                </p>
                                <p className="inline-flex items-center gap-2">
                                    <CreditCard className="size-4" />
                                    Broj dokumenta: {client.broj_dokumenta || '-'}
                                </p>
                            </div>
                        </section>
                    </aside>

                    <section className="space-y-4">
                        <div className="flex items-center justify-between rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <div>
                                <h2 className="text-base font-semibold">Aktivnost putovanja</h2>
                                <p className="text-sm text-muted-foreground">
                                    Zadnje rezervacije prikazane kao timeline.
                                </p>
                            </div>
                        </div>

                        {activities.length > 0 ? (
                            activities.map((aktivnost) => (
                                <article
                                    key={aktivnost.id}
                                    className="space-y-3 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm"
                                >
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="inline-flex items-center gap-2 text-base font-semibold">
                                                <Plane className="size-4" />
                                                {aktivnost.aranzman.naziv_putovanja ?? 'Rezervacija'}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {aktivnost.aranzman.sifra ?? 'Bez šifre'} •{' '}
                                                {aktivnost.aranzman.destinacija ?? 'Nepoznata destinacija'}
                                            </p>
                                        </div>

                                        <Badge
                                            variant="outline"
                                            className={reservationStatusBadgeClass(
                                                aktivnost.status,
                                            )}
                                        >
                                            {aktivnost.status_label}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <p className="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-sm">
                                            <CalendarDays className="size-4" />
                                            {formatDateDisplay(aktivnost.aranzman.datum_polaska)} -{' '}
                                            {formatDateDisplay(aktivnost.aranzman.datum_povratka)}
                                        </p>
                                        <p className="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-sm">
                                            <Users className="size-4" />
                                            {aktivnost.broj_putnika ?? 0} putnika
                                        </p>
                                        <p className="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-sm">
                                            <CreditCard className="size-4" />
                                            {aktivnost.paket.naziv ?? 'Bez paketa'}
                                        </p>
                                        <p className="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-sm font-medium">
                                            {money(
                                                Number(aktivnost.paket.cijena ?? 0) +
                                                    Number(aktivnost.dodatno_na_cijenu ?? 0) -
                                                    Number(aktivnost.popust ?? 0),
                                            )}
                                        </p>
                                    </div>

                                    <div className="flex flex-col gap-2 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                                        <p className="inline-flex items-center gap-1">
                                            <Clock3 className="size-3.5" />
                                            Kreirano {formatDateDisplay(aktivnost.created_at)}
                                        </p>
                                        <div className="flex items-center gap-2">
                                            {aktivnost.status === 'potvrdjena' && (
                                                <CheckCircle2 className="size-4 text-emerald-600" />
                                            )}
                                            {aktivnost.status === 'na_cekanju' && (
                                                <Clock3 className="size-4 text-amber-600" />
                                            )}
                                            {aktivnost.status === 'otkazana' && (
                                                <XCircle className="size-4 text-red-600" />
                                            )}
                                            {aktivnost.rezervacija_id && (
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/rezervacije/${aktivnost.rezervacija_id}/uredi`}>
                                                        Otvori rezervaciju
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </article>
                            ))
                        ) : (
                            <div className="rounded-2xl border border-dashed border-sidebar-border/70 bg-card p-8 text-center text-sm text-muted-foreground">
                                Klijent još nema aktivnosti rezervacija.
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
