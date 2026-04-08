export const reservationStatusLabel = (status: string): string => {
    if (status === 'potvrdjena') {
        return 'Potvrđena';
    }

    if (status === 'otkazana') {
        return 'Otkazana';
    }

    return 'Na čekanju';
};

export const reservationStatusBadgeClass = (status: string): string => {
    if (status === 'potvrdjena') {
        return 'border-transparent bg-emerald-100 text-emerald-800';
    }

    if (status === 'otkazana') {
        return 'border-transparent bg-red-100 text-red-800';
    }

    return 'border-transparent bg-amber-100 text-amber-800';
};

export const activeStatusLabel = (isActive: boolean): string =>
    isActive ? 'Aktivan' : 'Neaktivan';

export const activeStatusBadgeClass = (isActive: boolean): string =>
    isActive
        ? 'border-transparent bg-emerald-100 text-emerald-800'
        : 'border-transparent bg-slate-200 text-slate-700';
