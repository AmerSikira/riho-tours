import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Props = {
    dobavljac: {
        id: string;
        company_name: string;
        company_id: string | null;
        maticni_broj_subjekta_upisa: string | null;
        pdv: string | null;
        trn: string | null;
        banka: string | null;
        iban: string | null;
        swift: string | null;
        osiguravajuce_drustvo: string | null;
        email: string | null;
        phone: string | null;
        address: string | null;
        city: string | null;
        zip: string | null;
    };
};

export default function EditSupplier({ dobavljac: supplier }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dobavljači',
            href: '/dobavljaci',
        },
        {
            title: 'Uredite dobavljača',
            href: `/dobavljaci/${supplier.id}/uredi`,
        },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        company_name: supplier.company_name,
        company_id: supplier.company_id ?? '',
        maticni_broj_subjekta_upisa: supplier.maticni_broj_subjekta_upisa ?? '',
        pdv: supplier.pdv ?? '',
        trn: supplier.trn ?? '',
        banka: supplier.banka ?? '',
        iban: supplier.iban ?? '',
        swift: supplier.swift ?? '',
        osiguravajuce_drustvo: supplier.osiguravajuce_drustvo ?? '',
        email: supplier.email ?? '',
        phone: supplier.phone ?? '',
        address: supplier.address ?? '',
        city: supplier.city ?? '',
        zip: supplier.zip ?? '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        patch(`/dobavljaci/${supplier.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Uredite dobavljača" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Uredite dobavljača</h1>
                        <p className="text-sm text-muted-foreground">
                            Ažurirajte poslovne podatke dobavljača.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/dobavljaci">Nazad na listu</Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl space-y-5 rounded-xl border border-sidebar-border/70 p-5"
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="company_name">Naziv kompanije</Label>
                            <Input
                                id="company_name"
                                value={data.company_name}
                                onChange={(event) => setData('company_name', event.target.value)}
                                required
                            />
                            <InputError message={errors.company_name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="company_id">ID</Label>
                            <Input
                                id="company_id"
                                value={data.company_id}
                                onChange={(event) => setData('company_id', event.target.value)}
                            />
                            <InputError message={errors.company_id} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="maticni_broj_subjekta_upisa">
                                Matični broj subjekta upisa
                            </Label>
                            <Input
                                id="maticni_broj_subjekta_upisa"
                                value={data.maticni_broj_subjekta_upisa}
                                placeholder="000-0-Reg-00-000000"
                                onChange={(event) =>
                                    setData('maticni_broj_subjekta_upisa', event.target.value)
                                }
                            />
                            <InputError message={errors.maticni_broj_subjekta_upisa} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="pdv">PDV</Label>
                            <Input
                                id="pdv"
                                value={data.pdv}
                                onChange={(event) => setData('pdv', event.target.value)}
                            />
                            <InputError message={errors.pdv} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="trn">TRN</Label>
                            <Input
                                id="trn"
                                value={data.trn}
                                onChange={(event) => setData('trn', event.target.value)}
                            />
                            <InputError message={errors.trn} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="osiguravajuce_drustvo">Osiguravajuće društvo</Label>
                            <Input
                                id="osiguravajuce_drustvo"
                                value={data.osiguravajuce_drustvo}
                                onChange={(event) =>
                                    setData('osiguravajuce_drustvo', event.target.value)
                                }
                            />
                            <InputError message={errors.osiguravajuce_drustvo} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="banka">Banka</Label>
                            <Input
                                id="banka"
                                value={data.banka}
                                onChange={(event) => setData('banka', event.target.value)}
                            />
                            <InputError message={errors.banka} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="iban">IBAN</Label>
                            <Input
                                id="iban"
                                value={data.iban}
                                onChange={(event) => setData('iban', event.target.value)}
                            />
                            <InputError message={errors.iban} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="swift">SWIFT</Label>
                            <Input
                                id="swift"
                                value={data.swift}
                                onChange={(event) => setData('swift', event.target.value)}
                            />
                            <InputError message={errors.swift} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                            />
                            <InputError message={errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Telefon</Label>
                            <Input
                                id="phone"
                                value={data.phone}
                                onChange={(event) => setData('phone', event.target.value)}
                            />
                            <InputError message={errors.phone} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="address">Adresa</Label>
                            <Input
                                id="address"
                                value={data.address}
                                onChange={(event) => setData('address', event.target.value)}
                            />
                            <InputError message={errors.address} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="city">Grad</Label>
                            <Input
                                id="city"
                                value={data.city}
                                onChange={(event) => setData('city', event.target.value)}
                            />
                            <InputError message={errors.city} />
                        </div>
                    </div>

                    <div className="grid gap-2 max-w-xs">
                        <Label htmlFor="zip">Poštanski broj</Label>
                        <Input
                            id="zip"
                            value={data.zip}
                            onChange={(event) => setData('zip', event.target.value)}
                        />
                        <InputError message={errors.zip} />
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
