import { useState } from 'react';
import { Button } from '@/components/ui/button';
import TiptapEditor from '@/components/ui/tiptap-editor';

type ContractTemplateEditorProps = {
    value: string;
    onChange: (value: string) => void;
    minHeightClassName?: string;
};

type ShortcutItem = {
    label: string;
    token: string;
};

const SHORTCUTS: ShortcutItem[] = [
    { label: 'Naziv kompanije', token: '{{ kompanija.naziv }}' },
    { label: 'Adresa kompanije', token: '{{ kompanija.adresa }}' },
    { label: 'ID broj', token: '{{ kompanija.id_broj }}' },
    { label: 'Matični broj', token: '{{ kompanija.maticni_broj_subjekta_upisa }}' },
    { label: 'PDV broj', token: '{{ kompanija.pdv_broj }}' },
    { label: 'TRN', token: '{{ kompanija.trn }}' },
    { label: 'Banka', token: '{{ kompanija.banka }}' },
    { label: 'IBAN', token: '{{ kompanija.iban }}' },
    { label: 'SWIFT', token: '{{ kompanija.swift }}' },
    { label: 'Telefon kompanije', token: '{{ kompanija.telefon }}' },
    { label: 'Email kompanije', token: '{{ kompanija.email }}' },
    { label: 'Slika potpisa', token: '{{ kompanija.potpis_slika }}' },
    { label: 'Slika pečata', token: '{{ kompanija.pecat_slika }}' },
    { label: 'Insurance company', token: '{{ company.insurance_company }}' },
    { label: 'Osiguravajuće društvo', token: '{{ kompanija.osiguravajuce_drustvo }}' },
    { label: 'Broj ugovora', token: '{{ ugovor.broj }}' },
    { label: 'Datum ugovora', token: '{{ ugovor.datum }}' },
    { label: 'Puno ime putnika', token: '{{ putnik.puno_ime }}' },
    { label: 'Adresa putnika', token: '{{ putnik.adresa }}' },
    { label: 'Telefon putnika', token: '{{ putnik.telefon }}' },
    { label: 'Email putnika', token: '{{ putnik.email }}' },
    { label: 'Lista putnika', token: '{{ lista_putnika }}' },
    { label: 'Naziv aranžmana', token: '{{ aranzman.naziv }}' },
    { label: 'Period aranžmana', token: '{{ aranzman.period }}' },
    { label: 'Destinacija aranžmana', token: '{{ aranzman.destinacija }}' },
    { label: 'Šifra aranžmana', token: '{{ aranzman.sifra }}' },
    { label: 'Polisa osiguranja', token: '{{ aranzman.polisa_osiguranja }}' },
    { label: 'Ukupno finansije', token: '{{ finansije.ukupno }}' },
    { label: 'Tabela stavki', token: '{{ tabela_stavki }}' },
    { label: 'Prijelom stranice', token: '{{ prijelom_stranice }}' },
    { label: 'Page break', token: '{{ page_break }}' },
    { label: 'Puni pravni blok', token: '{{ kompanija.puni_pravni_blok }}' },
];

/**
 * Rich text editor for contract HTML templates with shortcut insertion.
 */
export default function ContractTemplateEditor({
    value,
    onChange,
    minHeightClassName = 'min-h-[300px]',
}: ContractTemplateEditorProps) {
    const [mode, setMode] = useState<'visual' | 'html'>('visual');
    const [copyState, setCopyState] = useState<string>('');

    const copyShortcut = async (token: string) => {
        await navigator.clipboard.writeText(token);
        setCopyState(token);
        window.setTimeout(() => setCopyState(''), 1200);
    };

    return (
        <div className="space-y-3">
            <div className="rounded-md border border-sidebar-border/70 p-3">
                <p className="mb-2 text-sm font-medium">Dostupni shortcode-ovi (kliknite za kopiranje)</p>
                <div className="flex flex-wrap gap-2">
                    {SHORTCUTS.map((item) => (
                        <button
                            type="button"
                            key={item.token}
                            onClick={() => void copyShortcut(item.token)}
                            className={`rounded-full border px-3 py-1.5 text-xs font-medium transition-colors ${
                                copyState === item.token
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-sidebar-border/60 bg-muted/20 hover:bg-muted'
                            }`}
                            title={item.label}
                        >
                            <code>{item.token}</code>
                        </button>
                    ))}
                </div>
            </div>

            <div className="rounded-md border border-sidebar-border/70 p-3">
                <div className="mb-3 flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        variant={mode === 'visual' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => setMode('visual')}
                    >
                        Vizualni editor
                    </Button>
                    <Button
                        type="button"
                        variant={mode === 'html' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => setMode('html')}
                    >
                        HTML kod
                    </Button>
                </div>

                {mode === 'visual' && (
                    <TiptapEditor
                        value={value}
                        onChange={onChange}
                        minHeightClassName={minHeightClassName}
                    />
                )}

                {mode === 'html' && (
                    <textarea
                        className={`${minHeightClassName} w-full rounded-md border border-input bg-background p-3 text-sm`}
                        value={value}
                        onChange={(event) => onChange(event.target.value)}
                    />
                )}
            </div>
        </div>
    );
}
