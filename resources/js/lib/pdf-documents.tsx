import { Document, Font, Image, Page, StyleSheet, Text, View, pdf } from '@react-pdf/renderer';
import type { ReactElement } from 'react';

const PAGE_WIDTH_PT = 595.28; // A4 width in points
const PAGE_HORIZONTAL_PADDING_PT = 30 * 2; // left + right
const WRITABLE_WIDTH_PT = PAGE_WIDTH_PT - PAGE_HORIZONTAL_PADDING_PT;
const LOGO_MAX_WIDTH_PT = WRITABLE_WIDTH_PT / 3;
const LOGO_MAX_HEIGHT_PT = 85.04; // 3cm in points

type CompanyData = {
    company_name: string;
    invoice_prefix: string;
    company_id: string;
    maticni_broj_subjekta_upisa: string;
    pdv: string;
    u_pdv_sistemu: boolean;
    trn: string;
    broj_kase: string;
    banka: string;
    iban: string;
    swift: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    zip: string;
    logo_url: string | null;
    potpis_url: string | null;
    pecat_url: string | null;
};

type ArrangementData = {
    sifra: string;
    naziv_putovanja: string;
    destinacija: string;
    datum_polaska: string | null;
    datum_povratka: string | null;
};

type InvoiceClientData = {
    ime: string;
    prezime: string;
    adresa: string;
    broj_telefona: string;
    email: string;
};

type LineItem = {
    description: string;
    amount: number;
};

type Totals = {
    addOnsTotal: number;
    subtotalWithoutPdv: number;
    pdvAmount: number;
    discountTotal: number;
    finalTotal: number;
};

type InvoicePayload = {
    title: string;
    number: string;
    date: string;
    company: CompanyData;
    arrangement: ArrangementData;
    reservationStatus: string;
    note: string;
    lineItems: LineItem[];
    selectedClient: InvoiceClientData;
    totals: Totals | null;
    fiscalInvoiceNumber?: string;
};

type ContractPayload = {
    number: string;
    date: string;
    company: CompanyData;
    arrangement: ArrangementData;
    reservationStatus: string;
    note: string;
    travelers: Array<{ fullName: string; packageName: string }>;
    totalAmount: number;
};

Font.register({
    family: 'NotoSans',
    fonts: [
        {
            src: '/fonts/NotoSans-Regular.ttf',
            fontWeight: 400,
        },
        {
            src: '/fonts/NotoSans-Bold.ttf',
            fontWeight: 700,
        },
    ],
});

const styles = StyleSheet.create({
    page: { paddingTop: 34, paddingHorizontal: 30, paddingBottom: 24, fontSize: 10, color: '#0f172a', fontFamily: 'NotoSans' },
    row: { flexDirection: 'row' },
    between: { justifyContent: 'space-between' },
    header: { marginBottom: 18 },
    logoWrap: { width: '52%', gap: 7 },
    companyWrap: { width: '40%', gap: 2, alignItems: 'flex-end' },
    title: { fontSize: 14, marginBottom: 3, fontWeight: 700, color: '#190F6E' },
    titleBig: { fontSize: 20, marginBottom: 3, fontWeight: 700, color: '#190F6E' },
    muted: { color: '#475569' },
    section: { marginTop: 12 },
    box: { borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 4, padding: 8 },
    table: { marginTop: 10 },
    tableHeader: { backgroundColor: '#190F6E', color: '#ffffff', fontWeight: 700 },
    tableRow: { borderBottomWidth: 1, borderBottomColor: '#cbd5e1' },
    cell: { paddingVertical: 8, paddingHorizontal: 6 },
    totalsWrap: { width: 220, marginLeft: 'auto', marginTop: 10, gap: 4 },
    totalDue: { backgroundColor: '#190F6E', color: '#ffffff', paddingVertical: 8, paddingHorizontal: 10, marginTop: 6 },
    footerLine: { marginTop: 18, borderTopWidth: 1, borderTopColor: '#94a3b8', paddingTop: 12 },
    bottomCol: { width: '31%' },
    footerHeading: { color: '#190F6E', fontSize: 10, fontWeight: 700, marginBottom: 4 },
    signWrap: { marginTop: 8, marginLeft: 'auto', width: 160, height: 86, position: 'relative' },
    signature: { width: 124, height: 54, position: 'absolute', left: 22, top: 14, objectFit: 'contain' },
    stamp: { width: 132, height: 84, position: 'absolute', left: 18, top: 6, objectFit: 'contain' },
    logoImage: {
        maxWidth: LOGO_MAX_WIDTH_PT,
        maxHeight: LOGO_MAX_HEIGHT_PT,
        objectFit: 'contain',
    },
});

const money = (amount: number): string => `${amount.toFixed(2)} KM`;

const resolveImageSource = (value: string | null | undefined): string => {
    if (!value) {
        return '';
    }

    if (value.startsWith('data:') || value.startsWith('http://') || value.startsWith('https://')) {
        return value;
    }

    return new URL(value, window.location.origin).toString();
};

const Header = ({
    company,
    documentHeading,
}: {
    company: CompanyData;
    documentHeading: string;
}) => {
    const logo = resolveImageSource(company.logo_url);

    return (
        <View style={[styles.row, styles.between, styles.header]}>
            <View style={styles.logoWrap}>
                {logo ? <Image src={logo} style={styles.logoImage} /> : null}
                <Text>{company.address}</Text>
                <Text>{`${company.zip || ''} ${company.city || ''}`.trim()}</Text>
                {company.phone ? <Text>{company.phone}</Text> : null}
            </View>
            <View style={styles.companyWrap}>
                <Text style={styles.titleBig}>{documentHeading.toUpperCase()}</Text>
                {company.email ? <Text>{company.email}</Text> : null}
            </View>
        </View>
    );
};

const Footer = ({
    company,
    note,
}: {
    company: CompanyData;
    note: string;
}) => (
    <View style={styles.footerLine}>
        <Text style={{ color: '#190F6E', fontSize: 14, fontWeight: 700, marginBottom: 8 }}>
            Hvala na ukazanom povjerenju
        </Text>
        <View style={[styles.row, styles.between]}>
            <View style={styles.bottomCol}>
                <Text style={styles.footerHeading}>Kontakt</Text>
                <Text>{company.email || '-'}</Text>
                <Text>{company.phone || '-'}</Text>
                <Text>{`${company.address || '-'}, ${company.zip || ''} ${company.city || ''}`.trim()}</Text>
            </View>
            <View style={styles.bottomCol}>
                <Text style={styles.footerHeading}>Podaci firme i plaćanje</Text>
                <Text>ID: {company.company_id || '-'}</Text>
                <Text>MBS: {company.maticni_broj_subjekta_upisa || '-'}</Text>
                <Text>PDV: {company.u_pdv_sistemu ? company.pdv || '-' : 'Nije u PDV sistemu'}</Text>
                <Text>TRN: {company.trn || '-'}</Text>
                <Text>Banka: {company.banka || '-'}</Text>
                <Text>IBAN: {company.iban || '-'}</Text>
                <Text>SWIFT: {company.swift || '-'}</Text>
            </View>
            <View style={styles.bottomCol}>
                <Text style={styles.footerHeading}>Napomena / Uslovi</Text>
                <Text>{note || '-'}</Text>
            </View>
        </View>
    </View>
);

export const InvoicePdfDocument = ({ payload }: { payload: InvoicePayload }) => {
    const signature = resolveImageSource(payload.company.potpis_url);
    const stamp = resolveImageSource(payload.company.pecat_url);

    return (
        <Document>
            <Page size="A4" style={styles.page}>
                <Header company={payload.company} documentHeading={payload.title} />
                <Text style={styles.title}>{payload.title}</Text>
                <Text style={styles.muted}>Datum: {payload.date}</Text>
                <Text style={styles.muted}>Broj: {payload.number}</Text>

                <View style={[styles.section, styles.row, { gap: 10 }]}>
                    <View style={{ width: '57%' }}>
                        <Text>Aranžman: {payload.arrangement.sifra} - {payload.arrangement.naziv_putovanja}</Text>
                        <Text>Destinacija: {payload.arrangement.destinacija}</Text>
                        <Text>Termin: {payload.arrangement.datum_polaska ?? '-'} - {payload.arrangement.datum_povratka ?? '-'}</Text>
                        <Text>Status: {payload.reservationStatus}</Text>
                        {payload.fiscalInvoiceNumber ? <Text>Broj fiskalnog računa: {payload.fiscalInvoiceNumber}</Text> : null}
                        <Text>Napomena: {payload.note || '-'}</Text>
                    </View>
                    <View style={[styles.box, { width: '43%' }]}>
                        <Text style={{ fontWeight: 700, color: '#190F6E', marginBottom: 4 }}>Za:</Text>
                        <Text>{`${payload.selectedClient.ime} ${payload.selectedClient.prezime}`.trim() || '-'}</Text>
                        <Text>{payload.selectedClient.adresa || '-'}</Text>
                        <Text>{payload.selectedClient.broj_telefona || '-'}</Text>
                        <Text>{payload.selectedClient.email || '-'}</Text>
                    </View>
                </View>

                <View style={styles.table}>
                    <View style={[styles.row, styles.tableHeader]}>
                        <Text style={[styles.cell, { width: '10%' }]}>#</Text>
                        <Text style={[styles.cell, { width: '55%' }]}>Opis stavke</Text>
                        <Text style={[styles.cell, { width: '17%', textAlign: 'right' }]}>Jed. cijena</Text>
                        <Text style={[styles.cell, { width: '8%', textAlign: 'center' }]}>Kol.</Text>
                        <Text style={[styles.cell, { width: '10%', textAlign: 'right' }]}>Ukupno</Text>
                    </View>
                    {payload.lineItems.map((item, index) => (
                        <View style={[styles.row, styles.tableRow]} key={`${item.description}-${index}`}>
                            <Text style={[styles.cell, { width: '10%' }]}>{index + 1}</Text>
                            <Text style={[styles.cell, { width: '55%' }]}>{item.description}</Text>
                            <Text style={[styles.cell, { width: '17%', textAlign: 'right' }]}>{money(item.amount)}</Text>
                            <Text style={[styles.cell, { width: '8%', textAlign: 'center' }]}>1</Text>
                            <Text style={[styles.cell, { width: '10%', textAlign: 'right' }]}>{money(item.amount)}</Text>
                        </View>
                    ))}
                </View>

                {payload.totals ? (
                    <View style={styles.totalsWrap}>
                        {([
                            ['Dodaci', payload.totals.addOnsTotal],
                            [payload.company.u_pdv_sistemu ? 'Međuzbir (bez PDV)' : 'Međuzbir', payload.totals.subtotalWithoutPdv],
                            ...(payload.company.u_pdv_sistemu ? ([['PDV (17%)', payload.totals.pdvAmount]] as Array<[string, number]>) : []),
                            ['Popust', payload.totals.discountTotal],
                        ] as Array<[string, number]>).map(([label, amount]) => (
                            <View style={[styles.row, styles.between]} key={label}>
                                <Text>{label}</Text>
                                <Text>{money(amount)}</Text>
                            </View>
                        ))}
                        <View style={[styles.row, styles.between, styles.totalDue]}>
                            <Text style={{ color: '#AFEB01', fontWeight: 700 }}>UKUPNO ZA UPLATU:</Text>
                            <Text style={{ color: '#ffffff', fontWeight: 700 }}>{money(payload.totals.finalTotal)}</Text>
                        </View>
                    </View>
                ) : null}

                {signature || stamp ? (
                    <View style={styles.signWrap}>
                        {stamp ? <Image src={stamp} style={styles.stamp} /> : null}
                        {signature ? <Image src={signature} style={styles.signature} /> : null}
                    </View>
                ) : null}

                <Footer company={payload.company} note={payload.note} />
            </Page>
        </Document>
    );
};

export const ContractPdfDocument = ({ payload }: { payload: ContractPayload }) => {
    return (
        <Document>
            <Page size="A4" style={styles.page}>
                <Header company={payload.company} documentHeading="Ugovor" />
                <Text style={styles.title}>Ugovor</Text>
                <Text style={styles.muted}>Datum: {payload.date}</Text>
                <Text style={styles.muted}>Broj: {payload.number}</Text>

                <View style={styles.section}>
                    <Text>Aranžman: {payload.arrangement.sifra} - {payload.arrangement.naziv_putovanja}</Text>
                    <Text>Destinacija: {payload.arrangement.destinacija}</Text>
                    <Text>Termin: {payload.arrangement.datum_polaska ?? '-'} - {payload.arrangement.datum_povratka ?? '-'}</Text>
                    <Text>Status rezervacije: {payload.reservationStatus}</Text>
                    <Text>Ukupan iznos: {money(payload.totalAmount)}</Text>
                    <Text>Napomena: {payload.note || '-'}</Text>
                </View>

                <View style={styles.section}>
                    <Text style={{ fontWeight: 700, marginBottom: 6 }}>Putnici</Text>
                    {payload.travelers.map((traveler, index) => (
                        <Text key={`${traveler.fullName}-${index}`}>{index + 1}. {traveler.fullName || 'Putnik'} - {traveler.packageName || 'Paket'}</Text>
                    ))}
                </View>

                <Footer company={payload.company} note={payload.note} />
            </Page>
        </Document>
    );
};

export const openPdfPreview = async (doc: ReactElement<any>, title: string): Promise<void> => {
    const blob = await pdf(doc).toBlob();
    const blobUrl = URL.createObjectURL(blob);
    const previewWindow = window.open(blobUrl, '_blank', 'noopener,noreferrer');

    if (!previewWindow) {
        URL.revokeObjectURL(blobUrl);
        return;
    }

    previewWindow.document.title = title;
    window.setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000);
};
