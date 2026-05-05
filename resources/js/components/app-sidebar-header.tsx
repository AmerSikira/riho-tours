import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Monitor, Moon, Sun } from 'lucide-react';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto flex items-center rounded-md border border-sidebar-border/70 p-0.5">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className={cn(
                        'h-8 w-8',
                        appearance === 'light' && 'bg-accent text-accent-foreground',
                    )}
                    aria-label="Svijetli način"
                    onClick={() => updateAppearance('light')}
                >
                    <Sun className="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className={cn(
                        'h-8 w-8',
                        appearance === 'dark' && 'bg-accent text-accent-foreground',
                    )}
                    aria-label="Tamni način"
                    onClick={() => updateAppearance('dark')}
                >
                    <Moon className="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className={cn(
                        'h-8 w-8',
                        appearance === 'system' && 'bg-accent text-accent-foreground',
                    )}
                    aria-label="Sistemski način"
                    onClick={() => updateAppearance('system')}
                >
                    <Monitor className="size-4" />
                </Button>
            </div>
        </header>
    );
}
