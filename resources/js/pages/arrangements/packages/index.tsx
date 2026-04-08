import { Head, Link, router } from '@inertiajs/react';
import { MoreHorizontal, Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { activeStatusBadgeClass, activeStatusLabel } from '@/lib/status-badge';
import type { BreadcrumbItem } from '@/types';

type Arrangement = {
    id: number;
    sifra: string;
    naziv_putovanja: string;
    subagentski_aranzman: boolean;
};

type Package = {
    id: number;
    naziv: string;
    opis: string | null;
    cijena: string;
    smjestaj_trosak: string;
    transport_trosak: string;
    fakultativne_stvari_trosak: string;
    ostalo_trosak: string;
    is_active: boolean;
};

type Props = {
    aranzman: Arrangement;
    paketi: Package[];
    status?: string;
};

export default function ArrangementPackagesIndex({
    aranzman,
    paketi,
    status,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Aranžmani',
            href: '/aranzmani',
        },
        {
            title: 'Paketi',
            href: `/aranzmani/${aranzman.id}/paketi`,
        },
    ];

    const handleDelete = (packageId: number) => {
        router.delete(`/aranzmani/${aranzman.id}/paketi/${packageId}`);
    };

    const parseMoney = (value: string): number => {
        const normalized = value.replace(',', '.').trim();
        const parsed = Number.parseFloat(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Paketi aranžmana" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Paketi aranžmana</h1>
                        <p className="text-sm text-muted-foreground">
                            Aranžman: {aranzman.sifra} - {aranzman.naziv_putovanja}
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={`/aranzmani/${aranzman.id}/paketi/dodaj`}>
                            <Plus className="mr-2 size-4" />
                            Dodajte paket
                        </Link>
                    </Button>
                </div>

                {status && (
                    <div className="rounded-md border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                        {status}
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Naziv paketa</th>
                                <th className="px-4 py-3 font-medium">Opis</th>
                                <th className="px-4 py-3 font-medium">Cijena</th>
                                <th className="px-4 py-3 font-medium">
                                    {aranzman.subagentski_aranzman ? 'Provizija (%)' : 'Ukupni trošak'}
                                </th>
                                <th className="px-4 py-3 font-medium">Potencijalna zarada/osobi</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 text-right font-medium">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {paketi.length > 0 ? (
                                paketi.map((paket) => (
                                    (() => {
                                        const commissionPercent = parseMoney(
                                            paket.smjestaj_trosak,
                                        );
                                        const potentialProfit = aranzman.subagentski_aranzman
                                            ? (parseMoney(paket.cijena) * commissionPercent) / 100
                                            : parseMoney(paket.cijena) -
                                              (parseMoney(paket.smjestaj_trosak) +
                                                  parseMoney(paket.transport_trosak) +
                                                  parseMoney(paket.fakultativne_stvari_trosak) +
                                                  parseMoney(paket.ostalo_trosak));
                                        const totalCosts = aranzman.subagentski_aranzman
                                            ? parseMoney(paket.cijena) - potentialProfit
                                            : parseMoney(paket.smjestaj_trosak) +
                                              parseMoney(paket.transport_trosak) +
                                              parseMoney(paket.fakultativne_stvari_trosak) +
                                              parseMoney(paket.ostalo_trosak);

                                        return (
                                    <tr
                                        key={paket.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3">{paket.naziv}</td>
                                        <td className="px-4 py-3">
                                            {paket.opis ?? '-'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {Number(paket.cijena).toFixed(2)} KM
                                        </td>
                                        <td className="px-4 py-3">
                                            {aranzman.subagentski_aranzman
                                                ? `${commissionPercent.toFixed(2)} %`
                                                : `${totalCosts.toFixed(2)} KM`}
                                        </td>
                                        <td className="px-4 py-3">
                                            {potentialProfit.toFixed(2)} KM
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant="outline"
                                                className={activeStatusBadgeClass(
                                                    paket.is_active,
                                                )}
                                            >
                                                {activeStatusLabel(
                                                    paket.is_active,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Akcije paketa"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`/aranzmani/${aranzman.id}/paketi/${paket.id}/uredi`}
                                                        >
                                                            Uredite
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onClick={() =>
                                                            handleDelete(paket.id)
                                                        }
                                                    >
                                                        Obrisati
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    </tr>
                                        );
                                    })()
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Nema paketa za ovaj aranžman.
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
