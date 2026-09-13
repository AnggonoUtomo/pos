import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuBadge, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermission } from '@/hooks/use-permission';
import { type NavGroup, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

function isCurrentUrl(currentUrl: string, itemUrl: string): boolean {
    return currentUrl === itemUrl || currentUrl.startsWith(`${itemUrl}/`);
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
                {item.badge && <SidebarMenuBadge>{item.badge}</SidebarMenuBadge>}
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
            {item.badge && <SidebarMenuBadge>{item.badge}</SidebarMenuBadge>}
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
