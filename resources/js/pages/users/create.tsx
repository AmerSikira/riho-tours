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
    roles: string[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Upravljanje korisnicima',
        href: '/korisnici',
    },
    {
        title: 'Dodajte korisnika',
        href: '/korisnici/dodaj',
    },
];

export default function CreateUser({ roles }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: roles.includes('agent') ? 'agent' : (roles[0] ?? ''),
        is_active: '1',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // Send user creation payload to backend and rely on server validation.
        post('/korisnici');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dodajte korisnika" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            Dodajte novog korisnika
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Unesite osnovne podatke i odaberite ulogu.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/korisnici">Nazad na listu</Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="max-w-2xl space-y-5 rounded-xl border border-sidebar-border/70 p-5"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Ime i prezime</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            placeholder="Unesite ime i prezime"
                            autoComplete="name"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email adresa</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                            placeholder="korisnik@firma.ba"
                            autoComplete="email"
                            required
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="role">RoleRow</Label>
                        <select
                            id="role"
                            value={data.role}
                            onChange={(event) =>
                                setData('role', event.target.value)
                            }
                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            {roles.map((role) => (
                                <option key={role} value={role}>
                                    {role}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.role} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="is_active">Status korisnika</Label>
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

                    <div className="grid gap-2">
                        <Label htmlFor="password">Lozinka</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                            placeholder="Unesite lozinku"
                            autoComplete="new-password"
                            required
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            Potvrda lozinke
                        </Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(event) =>
                                setData(
                                    'password_confirmation',
                                    event.target.value
                                )
                            }
                            placeholder="Ponovite lozinku"
                            autoComplete="new-password"
                            required
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 size-4" />
                        Sačuvajte korisnika
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
