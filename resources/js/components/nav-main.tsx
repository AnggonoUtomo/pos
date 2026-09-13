import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuBadge, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermission } from '@/hooks/use-permission';
import { cn } from '@/lib/utils';
import { type NavGroup, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

function isCurrentUrl(currentUrl: string, itemUrl: string): boolean {
    return currentUrl === itemUrl || currentUrl.startsWith(`${itemUrl}/`);
}

const badgeToneClasses: Record<NonNullable<NavItem['badgeTone']>, string> = {
    neutral: 'bg-muted text-muted-foreground',
    blue: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
    emerald: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    amber: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    rose: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    violet: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
};

function NavBadge({ item }: { item: NavItem }) {
    if (!item.badge) {
        return null;
    }

    return <SidebarMenuBadge className={cn('rounded-full px-2 font-medium', badgeToneClasses[item.badgeTone ?? 'neutral'])}>{item.badge}</SidebarMenuBadge>;
}

function NavMainItem({ item }: { item: NavItem }) {
    const page = usePage();
    const { can } = usePermission();
    const isPermissionBlocked = item.permission ? !can(item.permission) : false;
    const isDisabled = item.disabled === true || item.comingSoon === true || isPermissionBlocked || item.url.length === 0;
    const isActive = item.isActive ?? (!isDisabled && isCurrentUrl(page.url, item.url));
    const tooltip = item.comingSoon ? `${item.title} segera hadir` : item.title;

    if (isDisabled) {
        return (
            <SidebarMenuItem>
                <SidebarMenuButton aria-disabled="true" className="cursor-not-allowed opacity-60" tooltip={tooltip}>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                </SidebarMenuButton>
                <NavBadge item={item} />
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarMenuItem>
            <SidebarMenuButton asChild isActive={isActive} tooltip={item.title}>
                <Link href={item.url} prefetch>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                </Link>
            </SidebarMenuButton>
            <NavBadge item={item} />
        </SidebarMenuItem>
    );
}

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
    return (
        <>
            {groups.map((group) => (
                <SidebarGroup key={group.title} className="px-2 py-0">
                    <SidebarGroupLabel>{group.title}</SidebarGroupLabel>
                    <SidebarMenu>
                        {group.items.map((item) => (
                            <NavMainItem key={`${group.title}-${item.title}`} item={item} />
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
