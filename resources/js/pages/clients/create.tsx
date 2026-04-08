import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

export default function CreateClient() {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Klijenti',
            href: '/klijenti',
        },
        {
            title: 'Dodajte klijenta',
            href: '/klijenti/dodaj',
        },
    ];

    const { data, setData, post, processing, errors } = useForm({
        ime: '',
        prezime: '',
        broj_dokumenta: '',
        datum_rodjenja: '',
        adresa: '',
        city: '',
        broj_telefona: '',
        email: '',
        fotografija: null as File | null,
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/klijenti', {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dodajte klijenta" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Dodajte klijenta</h1>
                        <p className="text-sm text-muted-foreground">
                            Unesite osnovne podatke novog klijenta.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/klijenti">Nazad na listu</Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-2xl space-y-5 rounded-xl border border-sidebar-border/70 p-5"
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="ime">Ime</Label>
                            <Input
                                id="ime"
                                value={data.ime}
                                onChange={(event) => setData('ime', event.target.value)}
                                required
                            />
                            <InputError message={errors.ime} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="prezime">Prezime</Label>
                            <Input
                                id="prezime"
                                value={data.prezime}
                                onChange={(event) => setData('prezime', event.target.value)}
                                required
                            />
                            <InputError message={errors.prezime} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="broj_dokumenta">Broj dokumenta</Label>
                            <Input
                                id="broj_dokumenta"
                                value={data.broj_dokumenta}
                                onChange={(event) =>
                                    setData('broj_dokumenta', event.target.value)
                                }
                            />
                            <InputError message={errors.broj_dokumenta} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="datum_rodjenja">Datum rođenja</Label>
                            <Input
                                id="datum_rodjenja"
                                type="date"
                                value={data.datum_rodjenja}
                                onChange={(event) => setData('datum_rodjenja', event.target.value)}
                            />
                            <InputError message={errors.datum_rodjenja} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="city">Grad</Label>
                            <Input
                                id="city"
                                value={data.city}
                                onChange={(event) => setData('city', event.target.value)}
                            />
                            <InputError message={errors.city} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="adresa">Adresa</Label>
                            <Input
                                id="adresa"
                                value={data.adresa}
                                onChange={(event) => setData('adresa', event.target.value)}
                                required
                            />
                            <InputError message={errors.adresa} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="broj_telefona">Broj telefona</Label>
                            <Input
                                id="broj_telefona"
                                value={data.broj_telefona}
                                onChange={(event) => setData('broj_telefona', event.target.value)}
                                required
                            />
                            <InputError message={errors.broj_telefona} />
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
                            <Label htmlFor="fotografija">Fotografija</Label>
                            <Input
                                id="fotografija"
                                type="file"
                                accept="image/*"
                                onChange={(event) => setData('fotografija', event.target.files?.[0] ?? null)}
                            />
                            <InputError message={errors.fotografija} />
                        </div>
                    </div>

                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 size-4" />
                        Sačuvajte klijenta
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
