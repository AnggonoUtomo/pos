import { Breadcrumbs } from '@/components/breadcrumbs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { MonitorCog, ShoppingCart, Warehouse } from 'lucide-react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <header className="bg-background/95 sticky top-0 z-30 flex min-h-16 shrink-0 flex-wrap items-center gap-3 border-b px-4 py-3 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:min-h-12 lg:flex-nowrap lg:px-5">
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1 shrink-0" />
                <Separator orientation="vertical" className="mr-1 hidden h-6 md:block" />
                <div className="min-w-0">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>

            <nav aria-label="Navigasi cepat admin" className="order-3 flex w-full items-center gap-2 overflow-x-auto lg:order-none lg:w-auto lg:overflow-visible">
                <Button asChild variant="ghost" size="sm" className="h-8 shrink-0 gap-2">
                    <Link href="/dashboard" prefetch>
                        <MonitorCog className="size-4" />
                        Dasbor
                    </Link>
                </Button>
                <Button asChild variant="outline" size="sm" className="h-8 shrink-0 gap-2">
                    <Link href="/pos" prefetch>
                        <ShoppingCart className="size-4" />
                        POS
                    </Link>
                </Button>
                <Badge variant="outline" className="shrink-0 gap-1 border-emerald-200 bg-emerald-500/10 text-emerald-700 dark:border-emerald-900 dark:text-emerald-300">
                    <Warehouse className="size-3" />
                    Gudang Utama
                </Badge>
                <Badge variant="outline" className="shrink-0 border-violet-200 bg-violet-500/10 text-violet-700 dark:border-violet-900 dark:text-violet-300">
                    Finance Lite
                </Badge>
            </nav>

            {auth.user && (
                <div className="ml-auto flex shrink-0 items-center">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-10 gap-2 px-2">
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
