import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import ContractTemplateEditor from '@/components/contracts/template-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type TemplateVersion = {
    id: string;
    template_key: string;
    version: number;
    name: string;
    description: string | null;
    is_active: boolean;
    subagentski_ugovor: boolean;
    created_at: string;
};

type Props = {
    templatesByKey: TemplateVersion[][];
    status?: string;
    error?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Predlošci ugovora',
        href: '/ugovori/predlosci',
    },
];

export default function ContractTemplatesIndex({ templatesByKey, status, error }: Props) {
    const [templateKey, setTemplateKey] = useState('standard-contract');
    const [name, setName] = useState('Standard Contract');
    const [description, setDescription] = useState('Default contract template');
    const [isSubagentContract, setIsSubagentContract] = useState(false);
    const [htmlTemplate, setHtmlTemplate] = useState(`<section>\n  <h1>Ugovor {{ contract.number }}</h1>\n  <p>Kompanija: {{ company.name }}</p>\n  <p>Putnik: {{ traveler.full_name }}</p>\n  {{ travelers_list }}\n  {{ items_table }}\n</section>`);
    const [isPreviewing, setIsPreviewing] = useState(false);

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.post('/ugovori/predlosci', {
            template_key: templateKey,
            name,
            description,
            html_template: htmlTemplate,
            is_active: true,
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
            <Head title="Predlošci ugovora" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">Predlošci ugovora</h1>
                    <p className="text-sm text-muted-foreground">
                        Pregled verzioniranih predložaka i kreiranje nove verzije.
                    </p>
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

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Template key</th>
                                <th className="px-4 py-3 font-medium">Verzije</th>
                                <th className="px-4 py-3 font-medium">Subagentski</th>
                                <th className="px-4 py-3 text-right font-medium">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {templatesByKey.length > 0 ? (
                                templatesByKey.map((group) => (
                                    <tr key={group[0].template_key} className="border-t border-sidebar-border/70">
                                        <td className="px-4 py-3">{group[0].template_key}</td>
                                        <td className="px-4 py-3">
                                            {group.map((item) => `v${item.version} (${item.name})`).join(', ')}
                                        </td>
                                        <td className="px-4 py-3">
                                            {group.some((item) => item.subagentski_ugovor) ? 'Da' : 'Ne'}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                {group.map((item) => (
                                                    <Button key={item.id} variant="outline" size="sm" asChild>
                                                        <Link href={`/ugovori/predlosci/${item.id}/uredi`}>
                                                            Uredite v{item.version}
                                                        </Link>
                                                    </Button>
                                                ))}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={4} className="px-4 py-6 text-center text-muted-foreground">
                                        Nema unesenih predložaka ugovora.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <form onSubmit={handleSubmit} className="space-y-3 rounded-xl border border-sidebar-border/70 p-4">
                    <h2 className="text-base font-semibold">Nova verzija predloška</h2>

                    <Input
                        value={templateKey}
                        onChange={(event) => setTemplateKey(event.target.value)}
                        placeholder="template_key"
                    />

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
                        <p className="text-xs text-muted-foreground">
                            Zadano stanje je isključeno.
                        </p>
                    </div>

                    <ContractTemplateEditor
                        value={htmlTemplate}
                        onChange={setHtmlTemplate}
                        minHeightClassName="min-h-[220px]"
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
                        <Button type="submit">Sačuvajte predložak</Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
