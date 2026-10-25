import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuBadge, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermission } from '@/hooks/use-permission';
import { cn } from '@/lib/utils';
import { type NavGroup, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

function isCurrentUrl(currentUrl: string, itemUrl: string): boolean {
    const path = currentUrl.split('?')[0];
    return path === itemUrl || path.startsWith(`${itemUrl}/`);
}

const badgeToneClasses: Record<NonNullable<NavItem['badgeTone']>, string> = {
    neutral: 'bg-muted text-muted-foreground',
    blue: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
    emerald: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    amber: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    rose: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    violet: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
};

const iconToneClasses: Record<NonNullable<NavItem['badgeTone']>, string> = {
    neutral: 'text-sky-600 dark:text-sky-300',
    blue: 'text-sky-600 dark:text-sky-300',
    emerald: 'text-emerald-600 dark:text-emerald-300',
    amber: 'text-amber-600 dark:text-amber-300',
    rose: 'text-rose-600 dark:text-rose-300',
    violet: 'text-violet-600 dark:text-violet-300',
};

function NavBadge({ item }: { item: NavItem }) {
    if (!item.badge) {
        return null;
    }

    return (
        <SidebarMenuBadge
            className={cn(
                'peer-data-[active=true]/menu-button:text-sidebar-primary-foreground rounded-full px-1.5 text-[10px] font-medium peer-data-[active=true]/menu-button:bg-white/15 peer-data-[size=default]/menu-button:top-2.5',
                badgeToneClasses[item.badgeTone ?? 'neutral'],
            )}
        >
            {item.badge}
        </SidebarMenuBadge>
    );
}

function NavMainItem({ item }: { item: NavItem }) {
    const page = usePage();
    const { can, canAny } = usePermission();
    const isPermissionBlocked = item.permission ? !(typeof item.permission === 'string' ? can(item.permission) : canAny(item.permission)) : false;
    const isDisabled = item.disabled === true || item.comingSoon === true || isPermissionBlocked || item.url.length === 0;
    const isActive = item.isActive ?? (!isDisabled && isCurrentUrl(page.url, item.url));
    const tooltip = item.comingSoon ? `${item.title} segera hadir` : item.title;

    if (isDisabled) {
        return (
            <SidebarMenuItem>
                <SidebarMenuButton
                    aria-disabled="true"
                    className={cn(
                        'h-10 cursor-not-allowed opacity-60 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-10!',
                        item.badge && 'pr-14',
                    )}
                    tooltip={tooltip}
                >
                    {item.icon && <item.icon className={iconToneClasses[item.badgeTone ?? 'neutral']} />}
                    <span>{item.title}</span>
                </SidebarMenuButton>
                <NavBadge item={item} />
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={isActive}
                tooltip={item.title}
                className={cn(
                    'data-[active=true]:bg-sidebar-primary data-[active=true]:text-sidebar-primary-foreground h-10 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-10! data-[active=true]:shadow-[0_3px_8px_color-mix(in_oklab,var(--primary)_18%,transparent)]',
                    item.badge && 'pr-14',
                )}
            >
                <Link href={item.url}>
                    {item.icon && <item.icon className={isActive ? 'text-current' : iconToneClasses[item.badgeTone ?? 'neutral']} />}
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
                <SidebarGroup key={group.title} className="px-3 py-0 group-data-[collapsible=icon]:px-2">
                    <SidebarGroupLabel className="h-9 text-[11px] font-semibold uppercase">{group.title}</SidebarGroupLabel>
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
