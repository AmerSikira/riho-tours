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
    permissions: string[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uloge',
        href: '/uloge',
    },
    {
        title: 'Dodajte ulogu',
        href: '/uloge/dodaj',
    },
];

export default function CreateRole({ permissions }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        permissions: [] as string[],
    });

    const handlePermissionToggle = (
        permissionName: string,
        checked: boolean
    ) => {
        setData(
            'permissions',
            checked
                ? [...data.permissions, permissionName]
                : data.permissions.filter(
                      (permission) => permission !== permissionName
                  )
        );
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // Create role and attach selected permissions from checkboxes.
        post('/uloge');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dodajte ulogu" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Dodajte novu ulogu</h1>
                        <p className="text-sm text-muted-foreground">
                            Kreirajte ulogu i označite dozvole koje smije koristiti.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/uloge">Nazad na listu</Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-2xl space-y-5 rounded-xl border border-sidebar-border/70 p-5"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Naziv uloge</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            placeholder="npr. supervizor"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-3">
                        <Label>Dozvole za ovu ulogu</Label>
                        <div className="grid gap-2">
                            {permissions.map((permission) => (
                                <label
                                    key={permission}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        checked={data.permissions.includes(
                                            permission
                                        )}
                                        onChange={(event) =>
                                            handlePermissionToggle(
                                                permission,
                                                event.target.checked
                                            )
                                        }
                                    />
                                    <span>{permission}</span>
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.permissions} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 size-4" />
                        Sačuvajte ulogu
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
