import { Breadcrumbs } from '@/components/breadcrumbs';
import { CommandPalette } from '@/components/command-palette';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { ThemeMenu } from '@/components/theme-menu';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { MonitorCog, ShoppingCart, Warehouse } from 'lucide-react';
import { useEffect, useState } from 'react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { auth } = usePage<SharedData>().props;
    const [isScrolled, setIsScrolled] = useState(false);

    useEffect(() => {
        const updateScrolledState = () => {
            setIsScrolled(window.scrollY > 8);
        };

        updateScrolledState();
        window.addEventListener('scroll', updateScrolledState, { passive: true });

        return () => window.removeEventListener('scroll', updateScrolledState);
    }, []);

    return (
        <header
            data-scrolled={isScrolled}
            className="sticky top-0 z-30 flex min-h-16 shrink-0 flex-wrap items-center gap-3 border-b border-[var(--app-border)] bg-[var(--app-topbar)] px-4 py-3 shadow-none transition-[width,height,background-color,box-shadow] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:min-h-12 data-[scrolled=true]:bg-[color-mix(in_oklab,var(--app-topbar)_78%,transparent)] data-[scrolled=true]:shadow-sm data-[scrolled=true]:shadow-primary/10 data-[scrolled=true]:backdrop-blur lg:flex-nowrap lg:px-5"
        >
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1 shrink-0" />
                <Separator orientation="vertical" className="mr-1 hidden h-6 md:block" />
                <div className="min-w-0">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>

            <nav aria-label="Navigasi cepat admin" className="order-3 flex w-full items-center gap-2 overflow-x-auto lg:order-none lg:w-auto lg:overflow-visible">
                <Button asChild variant="ghost" size="sm" className="h-8 shrink-0 gap-2 border border-transparent text-primary hover:border-[var(--app-border)] hover:bg-[var(--app-panel-strong)]">
                    <Link href="/dashboard" prefetch>
                        <MonitorCog className="size-4" />
                        Dasbor
                    </Link>
                </Button>
                <Button asChild variant="outline" size="sm" className="h-8 shrink-0 gap-2 border-[var(--app-border)] bg-[var(--app-panel)] text-primary">
                    <Link href="/pos" prefetch>
                        <ShoppingCart className="size-4 text-[var(--chart-3)]" />
                        POS
                    </Link>
                </Button>
                <Badge variant="outline" className="shrink-0 gap-1 border-[var(--app-border)] bg-[var(--app-panel)] text-primary">
                    <Warehouse className="size-3 text-[var(--chart-2)]" />
                    Gudang Utama
                </Badge>
                <Badge variant="outline" className="shrink-0 border-[var(--app-border)] bg-[var(--app-panel)] text-primary">
                    Finance Lite
                </Badge>
            </nav>

            {auth.user && (
                <div className="ml-auto flex shrink-0 items-center gap-1">
                    <CommandPalette />
                    <ThemeMenu />
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-10 gap-2 px-2 [&_.grid]:hidden xl:[&_.grid]:grid">
                                <UserInfo user={auth.user} />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="w-56" align="end">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            )}
        </header>
    );
}
