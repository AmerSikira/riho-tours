import { Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import {
    Building2,
    Boxes,
    CalendarCheck2,
    Cog,
    ContactRound,
    FileText,
    LayoutGrid,
    ShieldCheck,
    Users,
    Plane,
    History,
    BarChart3,
    Globe,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Nadzorna ploča',
        href: dashboard(),
        icon: LayoutGrid,
        permission: 'pregled dashboarda',
    },
    {
        title: 'Aranžmani',
        href: '/aranzmani',
        icon: Plane,
        permission: 'pregled aranžmana',
    },
    {
        title: 'Paketi',
        href: '/paketi',
        icon: Boxes,
        permission: 'pregled paketa',
    },
    {
        title: 'Rezervacije',
        href: '/rezervacije',
        icon: CalendarCheck2,
        permission: 'pregled rezervacija',
    },
    {
        title: 'Web rezervacije',
        href: '/web-rezervacije',
        icon: Globe,
        permission: 'pregled rezervacija',
    },
    {
        title: 'Klijenti',
        href: '/klijenti',
        icon: ContactRound,
        permission: 'pregled klijenata',
    },
    {
        title: 'Dobavljači',
        href: '/dobavljaci',
        icon: Building2,
        permission: 'pregled dobavljača',
    },
    {
        title: 'Upravljanje korisnicima',
        href: '/korisnici',
        icon: Users,
        permission: 'pregled korisnika',
    },
    {
        title: 'Uloge',
        href: '/uloge',
        icon: ShieldCheck,
        permission: 'pregled rola',
    },
    {
        title: 'Izmjene',
        href: '/izmjene',
        icon: History,
        permission: 'pregled izmjena',
    },
    {
        title: 'Izvještaji',
        href: '/izvjestaji',
        icon: BarChart3,
        permission: 'pregled izvještaja',
    },
    {
        title: 'Ugovori',
        href: '/ugovori/predlosci',
        icon: FileText,
        permission: 'pregled ugovora',
    },
    {
        title: 'Postavke',
        href: '/postavke',
        icon: Cog,
        permission: 'pregled postavki kompanije',
    },
];

export function AppSidebar() {
    const { auth } = usePage().props as {
        auth?: { user?: { permissions?: string[] } | null };
    };
    const grantedPermissions = new Set(auth?.user?.permissions ?? []);
    const visibleNavItems = mainNavItems.filter(
        (item) =>
            !item.permission ||
            grantedPermissions.has(item.permission),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
