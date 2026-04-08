import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
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
    paket: Package;
};

export default function EditArrangementPackage({ aranzman: arrangement, paket: packageItem }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Aranžmani',
            href: '/aranzmani',
        },
        {
            title: 'Paketi',
            href: `/aranzmani/${arrangement.id}/paketi`,
        },
        {
            title: 'Uredite paket',
            href: `/aranzmani/${arrangement.id}/paketi/${packageItem.id}/uredi`,
        },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        naziv: packageItem.naziv,
        opis: packageItem.opis ?? '',
        cijena: packageItem.cijena,
        smjestaj_trosak: packageItem.smjestaj_trosak,
        transport_trosak: packageItem.transport_trosak,
        fakultativne_stvari_trosak: packageItem.fakultativne_stvari_trosak,
        ostalo_trosak: packageItem.ostalo_trosak,
        commission_percent: packageItem.smjestaj_trosak,
        is_active: packageItem.is_active ? '1' : '0',
    });

    const parseMoney = (value: string): number => {
        const normalized = value.replace(',', '.').trim();
        const parsed = Number.parseFloat(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    };

    const commissionPercent = parseMoney(data.commission_percent);
    const potentialProfit = arrangement.subagentski_aranzman
        ? (parseMoney(data.cijena) * commissionPercent) / 100
        : parseMoney(data.cijena) -
          (parseMoney(data.smjestaj_trosak) +
              parseMoney(data.transport_trosak) +
              parseMoney(data.fakultativne_stvari_trosak) +
              parseMoney(data.ostalo_trosak));
    const totalCosts = arrangement.subagentski_aranzman
        ? parseMoney(data.cijena) - potentialProfit
        : parseMoney(data.smjestaj_trosak) +
          parseMoney(data.transport_trosak) +
          parseMoney(data.fakultativne_stvari_trosak) +
          parseMoney(data.ostalo_trosak);

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // Persist package updates in a dedicated package table.
        patch(`/aranzmani/${arrangement.id}/paketi/${packageItem.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Uredite paket" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Uredite paket</h1>
                        <p className="text-sm text-muted-foreground">
                            Aranžman: {arrangement.sifra} - {arrangement.naziv_putovanja}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={`/aranzmani/${arrangement.id}/paketi`}>
                            Nazad na pakete
                        </Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="max-w-2xl space-y-5 rounded-xl border border-sidebar-border/70 p-5"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="naziv">Naziv paketa</Label>
                        <Input
                            id="naziv"
                            value={data.naziv}
                            onChange={(event) =>
                                setData('naziv', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.naziv} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="opis">Opis</Label>
                        <textarea
                            id="opis"
                            value={data.opis}
                            onChange={(event) =>
                                setData('opis', event.target.value)
                            }
                            className="min-h-24 rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <InputError message={errors.opis} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cijena">Cijena</Label>
                        <Input
                            id="cijena"
                            type="number"
                            min={0}
                            step="0.01"
                            value={data.cijena}
                            onChange={(event) =>
                                setData('cijena', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.cijena} />
                    </div>

                    {arrangement.subagentski_aranzman ? (
                        <div className="grid gap-2">
                            <Label htmlFor="commission_percent">Procenat zarade (%)</Label>
                            <Input
                                id="commission_percent"
                                type="number"
                                min={0}
                                max={100}
                                step="0.01"
                                value={data.commission_percent}
                                onChange={(event) =>
                                    setData('commission_percent', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.commission_percent} />
                        </div>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="smjestaj_trosak">Smještaj</Label>
                                <Input
                                    id="smjestaj_trosak"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.smjestaj_trosak}
                                    onChange={(event) =>
                                        setData('smjestaj_trosak', event.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.smjestaj_trosak} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="transport_trosak">Transport</Label>
                                <Input
                                    id="transport_trosak"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.transport_trosak}
                                    onChange={(event) =>
                                        setData('transport_trosak', event.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.transport_trosak} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="fakultativne_stvari_trosak">Fakultativne stvari</Label>
                                <Input
                                    id="fakultativne_stvari_trosak"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.fakultativne_stvari_trosak}
                                    onChange={(event) =>
                                        setData('fakultativne_stvari_trosak', event.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.fakultativne_stvari_trosak} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="ostalo_trosak">Ostalo</Label>
                                <Input
                                    id="ostalo_trosak"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.ostalo_trosak}
                                    onChange={(event) =>
                                        setData('ostalo_trosak', event.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.ostalo_trosak} />
                            </div>
                        </div>
                    )}

                    <div className="rounded-md border border-sidebar-border/70 bg-muted/30 p-3 text-sm">
                        <div>Ukupni trošak: {totalCosts.toFixed(2)} KM</div>
                        <div>Potencijalna zarada po osobi: {potentialProfit.toFixed(2)} KM</div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="is_active">Status</Label>
                        <select
                            id="is_active"
                            value={data.is_active}
                            onChange={(event) =>
                                setData('is_active', event.target.value)
                            }
                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            <option value="1">Aktivan</option>
                            <option value="0">Neaktivan</option>
                        </select>
                        <InputError message={errors.is_active} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 size-4" />
                        Sačuvajte izmjene
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
