import { Button } from '@/components/ui/button';

type Props = {
    currentPage: number;
    lastPage: number;
    total: number;
    onPageChange: (page: number) => void;
    entityLabel?: string;
};

export default function PaginationControls({
    currentPage,
    lastPage,
    total,
    onPageChange,
    entityLabel = 'zapisa',
}: Props) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-2 text-sm">
            <p className="text-muted-foreground">
                Stranica {currentPage} od {lastPage} ({total} {entityLabel})
            </p>
            <div className="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    disabled={currentPage <= 1}
                    onClick={() => onPageChange(currentPage - 1)}
                >
                    Prethodna
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    disabled={currentPage >= lastPage}
                    onClick={() => onPageChange(currentPage + 1)}
                >
                    Sljedeća
                </Button>
            </div>
        </div>
    );
}
