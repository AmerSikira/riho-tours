import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronDown, Images, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SimpleRichTextEditor from '@/components/ui/simple-rich-text-editor';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type PackageFormData = {
    id?: number;
    naziv: string;
    opis: string;
    cijena: string;
    commission_percent?: string;
    smjestaj_trosak: string;
    transport_trosak: string;
    fakultativne_stvari_trosak: string;
    ostalo_trosak: string;
    is_active: string;
};

type FormData = {
    sifra: string;
    destinacija: string;
    naziv_putovanja: string;
    opis_putovanja: string;
    plan_putovanja: string;
    datum_polaska: string;
    datum_povratka: string;
    tip_prevoza: string;
    tip_smjestaja: string;
    napomena: string;
    is_active: string;
    subagentski_aranzman: string;
    supplier_id: string;
    supplier_search: string;
    paketi: PackageFormData[];
    slike: File[];
    main_image_selection: string;
};

type Props = {
    paketNazivSuggestions: string[];
    dobavljacOptions: Array<{
        id: string;
        company_name: string;
        company_id: string | null;
    }>;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Aranžmani',
        href: '/aranzmani',
    },
    {
        title: 'Dodajte aranžman',
        href: '/aranzmani/dodaj',
    },
];

const emptyPackage = (): PackageFormData => ({
    naziv: '',
    opis: '',
    cijena: '0',
    commission_percent: '0',
    smjestaj_trosak: '0',
    transport_trosak: '0',
    fakultativne_stvari_trosak: '0',
    ostalo_trosak: '0',
    is_active: '1',
});

export default function CreateArrangement({
    paketNazivSuggestions: packageNameSuggestions,
    dobavljacOptions,
}: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        sifra: '',
        destinacija: '',
        naziv_putovanja: '',
        opis_putovanja: '',
        plan_putovanja: '',
        datum_polaska: '',
        datum_povratka: '',
        tip_prevoza: '',
        tip_smjestaja: '',
        napomena: '',
        is_active: '1',
        subagentski_aranzman: '0',
        supplier_id: '',
        supplier_search: '',
        paketi: [emptyPackage()],
        slike: [],
        main_image_selection: '',
    });
    const [isWebsiteInfoOpen, setIsWebsiteInfoOpen] = useState(false);

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/aranzmani', {
            forceFormData: true,
        });
    };

    const addPackageRow = () => {
        setData('paketi', [...data.paketi, emptyPackage()]);
    };

    const updatePackageRow = (
        index: number,
        field: keyof PackageFormData,
        value: string,
    ) => {
        setData(
            'paketi',
            data.paketi.map((paket, paketIndex) => {
                if (paketIndex !== index) {
                    return paket;
                }

                return {
                    ...paket,
                    [field]: value,
                };
            }),
        );
    };

    const removePackageRow = (index: number) => {
        if (data.paketi.length === 1) {
            return;
        }

        setData(
            'paketi',
            data.paketi.filter((_, paketIndex) => paketIndex !== index),
        );
    };

    const parseMoney = (value: string): number => {
        const normalized = value.replace(',', '.').trim();
        const parsed = Number.parseFloat(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    };

    const isSubagentArrangement = data.subagentski_aranzman === '1';
    const packageCommissionPercent = (paket: PackageFormData): number =>
        parseMoney(paket.commission_percent ?? paket.smjestaj_trosak);
    const packagePotentialProfit = (paket: PackageFormData): number =>
        isSubagentArrangement
            ? (parseMoney(paket.cijena) * packageCommissionPercent(paket)) / 100
            : parseMoney(paket.cijena) -
              (parseMoney(paket.smjestaj_trosak) +
                  parseMoney(paket.transport_trosak) +
                  parseMoney(paket.fakultativne_stvari_trosak) +
                  parseMoney(paket.ostalo_trosak));
    const packageTotalCosts = (paket: PackageFormData): number =>
        isSubagentArrangement
            ? parseMoney(paket.cijena) - packagePotentialProfit(paket)
            : parseMoney(paket.smjestaj_trosak) +
              parseMoney(paket.transport_trosak) +
              parseMoney(paket.fakultativne_stvari_trosak) +
              parseMoney(paket.ostalo_trosak);

    const averagePotentialProfit =
        data.paketi.length > 0
            ? data.paketi.reduce((sum, paket) => sum + packagePotentialProfit(paket), 0) /
              data.paketi.length
            : 0;

    const handleImagesChange = (files: FileList | null) => {
        const nextImages = files ? Array.from(files) : [];

        setData('slike', nextImages);

        if (nextImages.length === 0) {
            setData('main_image_selection', '');

            return;
        }

        setData('main_image_selection', 'new:0');
    };

    const supplierDisplayLabel = (supplier: {
        company_name: string;
        company_id: string | null;
    }): string =>
        supplier.company_id
            ? `${supplier.company_name} (${supplier.company_id})`
            : supplier.company_name;

    const resolveSupplierIdByLabel = (label: string): string | null => {
        const exactMatch = dobavljacOptions.find(
            (supplier) => supplierDisplayLabel(supplier) === label,
        );

        return exactMatch?.id ?? null;
    };
    const hasWebsiteInfoErrors =
        Boolean(errors.plan_putovanja) ||
        Boolean(errors.slike) ||
        Boolean(errors.main_image_selection) ||
        Object.keys(errors).some((key) => key.startsWith('slike.'));
    const forceWebsiteInfoOpen = hasWebsiteInfoErrors || data.slike.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dodajte aranžman" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Dodajte aranžman</h1>
                        <p className="text-sm text-muted-foreground">
                            Unesite osnovne podatke putnog aranžmana.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/aranzmani">Nazad na listu</Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl space-y-5 rounded-xl border border-sidebar-border/70 p-5"
                >
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="sifra">Šifra aranžmana</Label>
                            <Input
                                id="sifra"
                                value={data.sifra}
                                onChange={(event) =>
                                    setData('sifra', event.target.value)
                                }
                                placeholder="npr. LTO-2026-01"
                                required
                            />
                            <InputError message={errors.sifra} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="destinacija">Destinacija</Label>
                            <Input
                                id="destinacija"
                                value={data.destinacija}
                                onChange={(event) =>
                                    setData('destinacija', event.target.value)
                                }
                                placeholder="npr. Turska"
                                required
                            />
                            <InputError message={errors.destinacija} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="naziv_putovanja">Naziv putovanja</Label>
                        <Input
                            id="naziv_putovanja"
                            value={data.naziv_putovanja}
                            onChange={(event) =>
                                setData('naziv_putovanja', event.target.value)
                            }
                            placeholder="npr. Ljeto u Antaliji"
                            required
                        />
                        <InputError message={errors.naziv_putovanja} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="opis_putovanja">Opis putovanja</Label>
                        <textarea
                            id="opis_putovanja"
                            value={data.opis_putovanja}
                            onChange={(event) =>
                                setData('opis_putovanja', event.target.value)
                            }
                            className="min-h-28 rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Opis aranžmana, sadržaj, plan putovanja..."
                        />
                        <InputError message={errors.opis_putovanja} />
                    </div>

                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="datum_polaska">Datum polaska</Label>
                            <Input
                                id="datum_polaska"
                                type="date"
                                value={data.datum_polaska}
                                onChange={(event) =>
                                    setData('datum_polaska', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.datum_polaska} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="datum_povratka">Datum povratka</Label>
                            <Input
                                id="datum_povratka"
                                type="date"
                                value={data.datum_povratka}
                                onChange={(event) =>
                                    setData('datum_povratka', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.datum_povratka} />
                        </div>

                    </div>

                    <div className="grid gap-5 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="tip_prevoza">Tip prevoza</Label>
                            <Input
                                id="tip_prevoza"
                                value={data.tip_prevoza}
                                onChange={(event) =>
                                    setData('tip_prevoza', event.target.value)
                                }
                                placeholder="npr. Avion"
                            />
                            <InputError message={errors.tip_prevoza} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="tip_smjestaja">Tip smještaja</Label>
                            <Input
                                id="tip_smjestaja"
                                value={data.tip_smjestaja}
                                onChange={(event) =>
                                    setData('tip_smjestaja', event.target.value)
                                }
                                placeholder="npr. Hotel"
                            />
                            <InputError message={errors.tip_smjestaja} />
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
                            >
                                <option value="1">Aktivan</option>
                                <option value="0">Neaktivan</option>
                            </select>
                            <InputError message={errors.is_active} />
                        </div>
                    </div>

                    <div className="grid gap-5 md:grid-cols-[220px_1fr] md:items-end">
                        <div className="grid gap-2">
                            <Label>Subagentski aranžman</Label>
                            <button
                                type="button"
                                role="switch"
                                aria-checked={data.subagentski_aranzman === '1'}
                                onClick={() => {
                                    const nextValue =
                                        data.subagentski_aranzman === '1' ? '0' : '1';

                                    setData('subagentski_aranzman', nextValue);

                                    if (nextValue === '0') {
                                        setData('supplier_id', '');
                                        setData('supplier_search', '');
                                    }
                                }}
                                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                    data.subagentski_aranzman === '1'
                                        ? 'bg-primary'
                                        : 'bg-muted-foreground/30'
                                }`}
                            >
                                <span
                                    className={`inline-block h-5 w-5 rounded-full bg-white transition-transform ${
                                        data.subagentski_aranzman === '1'
                                            ? 'translate-x-5'
                                            : 'translate-x-1'
                                    }`}
                                />
                            </button>
                            <p className="text-xs text-muted-foreground">
                                Zadano stanje je isključeno.
                            </p>
                            <InputError message={errors.subagentski_aranzman} />
                        </div>

                        {data.subagentski_aranzman === '1' && (
                            <div className="grid gap-2">
                                <Label htmlFor="supplier_search">Dobavljač</Label>
                                <Input
                                    id="supplier_search"
                                    list="dobavljac-suggestions"
                                    value={data.supplier_search}
                                    onChange={(event) => {
                                        const nextValue = event.target.value;
                                        const matchedSupplierId = resolveSupplierIdByLabel(nextValue);

                                        setData('supplier_search', nextValue);
                                        setData('supplier_id', matchedSupplierId ?? '');
                                    }}
                                    placeholder="Počnite kucati naziv dobavljača..."
                                />
                                <datalist id="dobavljac-suggestions">
                                    {dobavljacOptions.map((supplier) => (
                                        <option
                                            key={supplier.id}
                                            value={supplierDisplayLabel(supplier)}
                                        />
                                    ))}
                                </datalist>
                                <InputError message={errors.supplier_id} />
                            </div>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="napomena">Napomena</Label>
                        <textarea
                            id="napomena"
                            value={data.napomena}
                            onChange={(event) =>
                                setData('napomena', event.target.value)
                            }
                            className="min-h-24 rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Dodatne interne napomene (opcionalno)..."
                        />
                        <InputError message={errors.napomena} />
                    </div>

                    <section className="space-y-4 rounded-lg border border-dashed border-sidebar-border/70 p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-base font-semibold">Paketi aranžmana</h2>
                                <p className="text-sm text-muted-foreground">
                                    Paketi se primarno dodaju i uređuju unutar aranžmana.
                                </p>
                            </div>
                            <Button type="button" variant="outline" onClick={addPackageRow}>
                                <Plus className="mr-2 size-4" />
                                Dodajte paket
                            </Button>
                        </div>

                        <InputError message={errors.paketi} />

                        <datalist id="paket-naziv-suggestions">
                            {packageNameSuggestions.map((naziv) => (
                                <option key={naziv} value={naziv} />
                            ))}
                        </datalist>

                        <div className="space-y-3">
                            {data.paketi.map((paket, index) => (
                                <div
                                    key={`paket-${index}`}
                                    className="grid gap-3 rounded-md border border-sidebar-border/70 p-3"
                                >
                                    <div className="grid gap-3 md:grid-cols-[1fr_160px_180px_auto] md:items-end">
                                        <div className="grid gap-2">
                                            <Label htmlFor={`paketi.${index}.naziv`}>
                                                Naziv paketa
                                            </Label>
                                            <Input
                                                id={`paketi.${index}.naziv`}
                                                list="paket-naziv-suggestions"
                                                value={paket.naziv}
                                                onChange={(event) =>
                                                    updatePackageRow(
                                                        index,
                                                        'naziv',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="npr. Sa doručkom"
                                                required
                                            />
                                            <InputError
                                                message={errors[`paketi.${index}.naziv`]}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor={`paketi.${index}.cijena`}>
                                                Cijena
                                            </Label>
                                            <Input
                                                id={`paketi.${index}.cijena`}
                                                type="number"
                                                min={0}
                                                step="0.01"
                                                value={paket.cijena}
                                                onChange={(event) =>
                                                    updatePackageRow(
                                                        index,
                                                        'cijena',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={errors[`paketi.${index}.cijena`]}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor={`paketi.${index}.is_active`}>
                                                Status paketa
                                            </Label>
                                            <select
                                                id={`paketi.${index}.is_active`}
                                                value={paket.is_active}
                                                onChange={(event) =>
                                                    updatePackageRow(
                                                        index,
                                                        'is_active',
                                                        event.target.value,
                                                    )
                                                }
                                                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                                required
                                            >
                                                <option value="1">Aktivan</option>
                                                <option value="0">Neaktivan</option>
                                            </select>
                                            <InputError
                                                message={
                                                    errors[
                                                        `paketi.${index}.is_active`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="text-destructive"
                                            onClick={() => removePackageRow(index)}
                                            disabled={data.paketi.length === 1}
                                        >
                                            <Trash2 className="mr-2 size-4" />
                                            Uklonite
                                        </Button>
                                    </div>

                                    {isSubagentArrangement ? (
                                        <div className="grid gap-3 md:grid-cols-1">
                                            <div className="grid gap-2">
                                                <Label htmlFor={`paketi.${index}.commission_percent`}>
                                                    Procenat zarade (%)
                                                </Label>
                                                <Input
                                                    id={`paketi.${index}.commission_percent`}
                                                    type="number"
                                                    min={0}
                                                    max={100}
                                                    step="0.01"
                                                    value={paket.commission_percent ?? ''}
                                                    onChange={(event) =>
                                                        updatePackageRow(
                                                            index,
                                                            'commission_percent',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors[`paketi.${index}.commission_percent`]}
                                                />
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="grid gap-3 md:grid-cols-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor={`paketi.${index}.smjestaj_trosak`}>
                                                    Smještaj
                                                </Label>
                                                <Input
                                                    id={`paketi.${index}.smjestaj_trosak`}
                                                    type="number"
                                                    min={0}
                                                    step="0.01"
                                                    value={paket.smjestaj_trosak}
                                                    onChange={(event) =>
                                                        updatePackageRow(
                                                            index,
                                                            'smjestaj_trosak',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors[`paketi.${index}.smjestaj_trosak`]}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor={`paketi.${index}.transport_trosak`}>
                                                    Transport
                                                </Label>
                                                <Input
                                                    id={`paketi.${index}.transport_trosak`}
                                                    type="number"
                                                    min={0}
                                                    step="0.01"
                                                    value={paket.transport_trosak}
                                                    onChange={(event) =>
                                                        updatePackageRow(
                                                            index,
                                                            'transport_trosak',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors[`paketi.${index}.transport_trosak`]}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor={`paketi.${index}.fakultativne_stvari_trosak`}>
                                                    Fakultativne stvari
                                                </Label>
                                                <Input
                                                    id={`paketi.${index}.fakultativne_stvari_trosak`}
                                                    type="number"
                                                    min={0}
                                                    step="0.01"
                                                    value={paket.fakultativne_stvari_trosak}
                                                    onChange={(event) =>
                                                        updatePackageRow(
                                                            index,
                                                            'fakultativne_stvari_trosak',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors[`paketi.${index}.fakultativne_stvari_trosak`]}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor={`paketi.${index}.ostalo_trosak`}>
                                                    Ostalo
                                                </Label>
                                                <Input
                                                    id={`paketi.${index}.ostalo_trosak`}
                                                    type="number"
                                                    min={0}
                                                    step="0.01"
                                                    value={paket.ostalo_trosak}
                                                    onChange={(event) =>
                                                        updatePackageRow(
                                                            index,
                                                            'ostalo_trosak',
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors[`paketi.${index}.ostalo_trosak`]}
                                                />
                                            </div>
                                        </div>
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor={`paketi.${index}.opis`}>
                                            Opis paketa
                                        </Label>
                                        <textarea
                                            id={`paketi.${index}.opis`}
                                            value={paket.opis}
                                            onChange={(event) =>
                                                updatePackageRow(
                                                    index,
                                                    'opis',
                                                    event.target.value,
                                                )
                                            }
                                            className="min-h-20 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            placeholder="Detalji paketa (opcionalno)..."
                                        />
                                        <InputError
                                            message={errors[`paketi.${index}.opis`]}
                                        />
                                    </div>

                                    <div className="rounded-md border border-sidebar-border/70 bg-muted/30 p-3 text-sm">
                                        <div>
                                            {isSubagentArrangement
                                                ? `Provizija: ${packageCommissionPercent(paket).toFixed(2)} %`
                                                : `Ukupni trošak: ${packageTotalCosts(paket).toFixed(2)} KM`}
                                        </div>
                                        <div>
                                            Potencijalna zarada po osobi: {packagePotentialProfit(paket).toFixed(2)} KM
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="rounded-md border border-sidebar-border/70 bg-muted/30 p-3 text-sm font-medium">
                            Potencijalna prosječna zarada po osobi (aranžman): {averagePotentialProfit.toFixed(2)} KM
                        </div>
                    </section>

                    <details
                        open={forceWebsiteInfoOpen || isWebsiteInfoOpen}
                        onToggle={(event) =>
                            setIsWebsiteInfoOpen(event.currentTarget.open)
                        }
                        className="rounded-lg border border-dashed border-sidebar-border/70 p-4"
                    >
                        <summary className="flex cursor-pointer list-none items-center justify-between text-base font-semibold">
                            <span className="flex items-center gap-2">
                                Website informacije
                                {hasWebsiteInfoErrors && (
                                    <span className="text-xs font-medium text-destructive">
                                        Provjerite obavezna polja
                                    </span>
                                )}
                            </span>
                            <ChevronDown className="size-4 text-muted-foreground" />
                        </summary>

                        <div className="mt-4 space-y-5">
                            <div className="grid gap-2">
                                <Label>Plan putovanja</Label>
                                <SimpleRichTextEditor
                                    value={data.plan_putovanja}
                                    onChange={(nextValue) =>
                                        setData('plan_putovanja', nextValue)
                                    }
                                />
                                <InputError message={errors.plan_putovanja} />
                            </div>

                            <section className="space-y-4 rounded-lg border border-sidebar-border/70 p-4">
                                <div className="flex items-center gap-2">
                                    <Images className="size-4" />
                                    <h2 className="text-base font-semibold">Slike aranžmana</h2>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="slike">Upload slika</Label>
                                    <Input
                                        id="slike"
                                        type="file"
                                        multiple
                                        accept="image/*"
                                        onChange={(event) =>
                                            handleImagesChange(event.target.files)
                                        }
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Možete dodati više slika. Jedna mora biti označena kao glavna.
                                    </p>
                                    <InputError message={errors.slike} />
                                </div>

                                {data.slike.length > 0 && (
                                    <div className="space-y-2">
                                        <Label>Odabir glavne slike</Label>
                                        <div className="space-y-2">
                                            {data.slike.map((slika, index) => (
                                                <label
                                                    key={`${slika.name}-${index}`}
                                                    className="flex items-center gap-3 rounded-md border border-sidebar-border/70 p-2 text-sm"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="main_image_selection"
                                                        checked={
                                                            data.main_image_selection ===
                                                            `new:${index}`
                                                        }
                                                        onChange={() =>
                                                            setData(
                                                                'main_image_selection',
                                                                `new:${index}`,
                                                            )
                                                        }
                                                    />
                                                    <span className="truncate">
                                                        {slika.name}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                <InputError message={errors.main_image_selection} />
                            </section>
                        </div>
                    </details>

                    {hasWebsiteInfoErrors && (
                        <p className="text-sm text-destructive">
                            Aranžman nije sačuvan. Otvorite sekciju "Website informacije" i ispravite greške.
                        </p>
                    )}

                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 size-4" />
                        Sačuvajte aranžman
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
