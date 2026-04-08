import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import ContractTemplateEditor from '@/components/contracts/template-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Template = {
    id: string;
    template_key: string;
    version: number;
    name: string;
    description: string | null;
    html_template: string;
    is_active: boolean;
    subagentski_ugovor: boolean;
};

type Props = {
    template: Template;
    status?: string;
    error?: string;
};

export default function ContractTemplateEdit({ template, status, error }: Props) {
    const [name, setName] = useState(template.name);
    const [description, setDescription] = useState(template.description ?? '');
    const [htmlTemplate, setHtmlTemplate] = useState(template.html_template);
    const [isActive, setIsActive] = useState(template.is_active);
    const [isSubagentContract, setIsSubagentContract] = useState(template.subagentski_ugovor);
    const [isPreviewing, setIsPreviewing] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Predlošci ugovora',
            href: '/ugovori/predlosci',
        },
        {
            title: 'Uredite predložak',
            href: `/ugovori/predlosci/${template.id}/uredi`,
        },
    ];

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.patch(`/ugovori/predlosci/${template.id}`, {
            name,
            description,
            html_template: htmlTemplate,
            is_active: isActive,
            subagentski_ugovor: isSubagentContract,
        });
    };

    const handlePreviewTemplate = async () => {
        const previewWindow = window.open('', '_blank');

        if (!previewWindow) {
            return;
        }

        setIsPreviewing(true);

        try {
            const csrfTokenFromMeta =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';
            const xsrfCookie = document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='));
            const csrfTokenFromCookie = xsrfCookie
                ? decodeURIComponent(xsrfCookie.split('=').slice(1).join('='))
                : '';
            const csrfToken = csrfTokenFromMeta || csrfTokenFromCookie;

            const response = await fetch('/ugovori/predlosci/pregled', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/html,application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-XSRF-TOKEN': csrfTokenFromCookie,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    html_template: htmlTemplate,
                    subagentski_ugovor: isSubagentContract,
                }),
            });

            if (!response.ok) {
                previewWindow.close();

                let message = 'Pregled predloška nije moguće generisati.';
                const contentType = response.headers.get('content-type') ?? '';

                if (contentType.includes('application/json')) {
                    const errorPayload = (await response.json()) as {
                        message?: string;
                        errors?: Record<string, string[]>;
                    };
                    const firstError = Object.values(errorPayload.errors ?? {})
                        .flat()
                        .find((value) => value && value.trim() !== '');
                    message = firstError ?? errorPayload.message ?? message;
                } else {
                    const responseText = await response.text();

                    if (responseText.trim() !== '') {
                        message = `${message} (HTTP ${response.status})`;
                    }
                }

                throw new Error(message);
            }

            const htmlPreview = await response.text();
            previewWindow.document.open();
            previewWindow.document.write(htmlPreview);
            previewWindow.document.close();
        } catch (error) {
            window.alert(
                error instanceof Error
                    ? error.message
                    : 'Pregled predloška nije moguće generisati.',
            );
        } finally {
            setIsPreviewing(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Uredite predložak ugovora" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Uredite predložak ugovora</h1>
                        <p className="text-sm text-muted-foreground">
                            {template.template_key} / v{template.version}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/ugovori/predlosci">Nazad</Link>
                    </Button>
                </div>

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

                <form onSubmit={handleSubmit} className="space-y-3 rounded-xl border border-sidebar-border/70 p-4">
                    <Input
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        placeholder="Naziv"
                    />

                    <Input
                        value={description}
                        onChange={(event) => setDescription(event.target.value)}
                        placeholder="Opis"
                    />

                    <div className="grid gap-2">
                        <span className="text-sm font-medium">Subagentski ugovor</span>
                        <button
                            type="button"
                            role="switch"
                            aria-checked={isSubagentContract}
                            onClick={() => setIsSubagentContract(!isSubagentContract)}
                            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                isSubagentContract ? 'bg-primary' : 'bg-muted-foreground/30'
                            }`}
                        >
                            <span
                                className={`inline-block h-5 w-5 rounded-full bg-white transition-transform ${
                                    isSubagentContract ? 'translate-x-5' : 'translate-x-1'
                                }`}
                            />
                        </button>
                    </div>

                    <div className="grid gap-2">
                        <span className="text-sm font-medium">Aktivan predložak</span>
                        <button
                            type="button"
                            role="switch"
                            aria-checked={isActive}
                            onClick={() => setIsActive(!isActive)}
                            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                isActive ? 'bg-primary' : 'bg-muted-foreground/30'
                            }`}
                        >
                            <span
                                className={`inline-block h-5 w-5 rounded-full bg-white transition-transform ${
                                    isActive ? 'translate-x-5' : 'translate-x-1'
                                }`}
                            />
                        </button>
                    </div>

                    <ContractTemplateEditor
                        value={htmlTemplate}
                        onChange={setHtmlTemplate}
                        minHeightClassName="min-h-[280px]"
                    />

                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => void handlePreviewTemplate()}
                            disabled={isPreviewing}
                        >
                            {isPreviewing
                                ? 'Generišem pregled...'
                                : 'Pregled predloška'}
                        </Button>
                        <Button type="submit">Sačuvajte izmjene</Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
