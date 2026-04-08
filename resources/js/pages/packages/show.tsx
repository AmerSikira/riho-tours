import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { activeStatusBadgeClass, activeStatusLabel } from '@/lib/status-badge';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Paket = {
    id: number;
    naziv: string;
};

type ArrangementItem = {
    id: number;
    is_active: boolean;
    updated_at: string | null;
    aranzman: {
        id: number;
        sifra: string;
        naziv_putovanja: string;
        destinacija: string;
        datum_polaska: string | null;
        datum_povratka: string | null;
        is_active: boolean;
    };
};

type Props = {
    paket: Paket;
    aranzmani: ArrangementItem[];
};

export default function PackageShow({ paket: packageItem, aranzmani: arrangements }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Paketi',
            href: '/paketi',
        },
        {
            title: packageItem.naziv,
            href: `/paketi/${packageItem.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Paket: ${packageItem.naziv}`} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">{packageItem.naziv}</h1>
                        <p className="text-sm text-muted-foreground">
                            Aranžmani koji su vezani za ovaj paket.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/paketi">Nazad na pakete</Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Šifra</th>
                                <th className="px-4 py-3 font-medium">Aranžman</th>
                                <th className="px-4 py-3 font-medium">Destinacija</th>
                                <th className="px-4 py-3 font-medium">Datumi</th>
                                <th className="px-4 py-3 font-medium">Statusi</th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Otvori
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {arrangements.length > 0 ? (
                                arrangements.map((stavka) => (
                                    <tr
                                        key={stavka.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3">
                                            {stavka.aranzman.sifra}
                                        </td>
                                        <td className="px-4 py-3">
                                            {stavka.aranzman.naziv_putovanja}
                                        </td>
                                        <td className="px-4 py-3">
                                            {stavka.aranzman.destinacija}
                                        </td>
                                        <td className="px-4 py-3">
                                            {formatDateDisplay(stavka.aranzman.datum_polaska)} -{' '}
                                            {formatDateDisplay(stavka.aranzman.datum_povratka)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-col gap-2">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs text-muted-foreground">
                                                        Paket:
                                                    </span>
                                                    <Badge
                                                        variant="outline"
                                                        className={activeStatusBadgeClass(
                                                            stavka.is_active,
                                                        )}
                                                    >
                                                        {activeStatusLabel(
                                                            stavka.is_active,
                                                        )}
                                                    </Badge>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs text-muted-foreground">
                                                        Aranžman:
                                                    </span>
                                                    <Badge
                                                        variant="outline"
                                                        className={activeStatusBadgeClass(
                                                            stavka.aranzman.is_active,
                                                        )}
                                                    >
                                                        {activeStatusLabel(
                                                            stavka.aranzman.is_active,
                                                        )}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button variant="outline" asChild>
                                                <Link
                                                    href={`/aranzmani/${stavka.aranzman.id}/uredi`}
                                                >
                                                    Otvori aranžman
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Ovaj paket trenutno nije vezan ni za jedan aranžman.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
