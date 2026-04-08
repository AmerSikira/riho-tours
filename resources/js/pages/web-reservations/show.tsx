import { Head, Link, router } from '@inertiajs/react';
import { ArrowRightLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';

type Props = {
    web_reservation: {
        id: string;
        ime: string | null;
        prezime: string | null;
        email: string | null;
        broj_telefona: string | null;
        adresa: string | null;
        broj_putnika: number;
        napomena: string | null;
        status: string;
        source_domain: string | null;
        source_url: string | null;
        landing_page_url: string | null;
        referrer_url: string | null;
        utm_source: string | null;
        utm_medium: string | null;
        utm_campaign: string | null;
        utm_term: string | null;
        utm_content: string | null;
        created_at: string | null;
        payload: Record<string, unknown> | null;
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
    status?: string;
    error?: string;
};

export default function WebReservationShow({ web_reservation, status, error }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Web rezervacije', href: '/web-rezervacije' },
        { title: 'Detalji', href: `/web-rezervacije/${web_reservation.id}` },
    ];

    const handleConvert = () => {
        if (!window.confirm('Da li ste sigurni da želite prebaciti ovu web rezervaciju u rezervacije?')) {
            return;
        }

        router.post(`/web-rezervacije/${web_reservation.id}/prebaci-u-rezervacije`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Web rezervacija" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
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

                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Web rezervacija</h1>
                        <p className="text-sm text-muted-foreground">
                            Kreirano: {formatDateDisplay(web_reservation.created_at)}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {web_reservation.converted_reservation ? (
                            <Button asChild>
                                <Link href={`/rezervacije/${web_reservation.converted_reservation.id}/uredi`}>
                                    Otvorite rezervaciju
                                </Link>
                            </Button>
                        ) : (
                            <Button onClick={handleConvert}>
                                <ArrowRightLeft className="mr-2 size-4" />
                                Prebacite u rezervacije
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href="/web-rezervacije">Nazad</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 md:grid-cols-2">
                    <div>
                        <div className="text-xs text-muted-foreground">Ime i prezime</div>
                        <div className="font-medium">{[web_reservation.ime, web_reservation.prezime].filter(Boolean).join(' ') || '-'}</div>
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground">Kontakt</div>
                        <div className="font-medium">{web_reservation.email || web_reservation.broj_telefona || '-'}</div>
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground">Aranžman</div>
                        <div className="font-medium">
                            {web_reservation.arrangement
                                ? `${web_reservation.arrangement.sifra} - ${web_reservation.arrangement.naziv_putovanja}`
                                : '-'}
                        </div>
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground">Paket</div>
                        <div className="font-medium">{web_reservation.package?.naziv || '-'}</div>
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground">Broj putnika</div>
                        <div className="font-medium">{web_reservation.broj_putnika}</div>
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground">Domena izvora</div>
                        <div className="font-medium">{web_reservation.source_domain || '-'}</div>
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground">UTM Source</div>
                        <div className="font-medium">{web_reservation.utm_source || '-'}</div>
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground">UTM Medium</div>
                        <div className="font-medium">{web_reservation.utm_medium || '-'}</div>
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground">UTM Campaign</div>
                        <div className="font-medium">{web_reservation.utm_campaign || '-'}</div>
                    </div>
                    <div className="md:col-span-2">
                        <div className="text-xs text-muted-foreground">Landing page</div>
                        <div className="font-medium break-all">{web_reservation.landing_page_url || '-'}</div>
                    </div>
                    <div className="md:col-span-2">
                        <div className="text-xs text-muted-foreground">Referrer</div>
                        <div className="font-medium break-all">{web_reservation.referrer_url || '-'}</div>
                    </div>
                    <div className="md:col-span-2">
                        <div className="text-xs text-muted-foreground">Napomena</div>
                        <div className="font-medium whitespace-pre-wrap">{web_reservation.napomena || '-'}</div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
