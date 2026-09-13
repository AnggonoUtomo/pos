import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavGroup } from '@/types';
import { Link } from '@inertiajs/react';
import { BarChart3, Boxes, Building2, CreditCard, LayoutGrid, Package, ReceiptText, RotateCcw, Settings, ShieldCheck, ShoppingCart, Tags, Truck, Warehouse } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavGroups: NavGroup[] = [
    {
        title: 'Platform',
        items: [
            {
                title: 'Dasbor',
                url: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Identitas & Akses',
                url: '',
                icon: ShieldCheck,
                badge: 'Segera',
                comingSoon: true,
                permission: 'identity.view',
            },
            {
                title: 'Pengaturan Perusahaan',
                url: '',
                icon: Settings,
                badge: 'Segera',
                comingSoon: true,
            },
        ],
    },
    {
        title: 'Inventori',
        items: [
            {
                title: 'Katalog',
                url: '',
                icon: Package,
                badge: 'Segera',
                comingSoon: true,
            },
            {
                title: 'Gudang',
                url: '',
                icon: Warehouse,
                badge: 'Segera',
                comingSoon: true,
            },
            {
                title: 'Stok',
                url: '',
                icon: Boxes,
                badge: 'Segera',
                comingSoon: true,
            },
        ],
    },
    {
        title: 'Penjualan',
        items: [
            {
                title: 'Pelanggan',
                url: '',
                icon: Building2,
                badge: 'Segera',
                comingSoon: true,
            },
            {
                title: 'POS',
                url: '/pos',
                icon: ShoppingCart,
                permission: 'sales.pos.invoices.create',
            },
            {
                title: 'Retur Penjualan',
                url: '',
                icon: RotateCcw,
                badge: 'Segera',
                comingSoon: true,
            },
        ],
    },
    {
        title: 'Pembelian',
        items: [
            {
                title: 'Supplier',
                url: '',
                icon: Truck,
                badge: 'Segera',
                comingSoon: true,
            },
            {
                title: 'Order Pembelian',
                url: '',
                icon: ReceiptText,
                badge: 'Segera',
                comingSoon: true,
            },
        ],
    },
    {
        title: 'Keuangan',
        items: [
            {
                title: 'Pembayaran',
                url: '',
                icon: CreditCard,
                badge: 'Segera',
                comingSoon: true,
            },
        ],
    },
    {
        title: 'Laporan',
        items: [
            {
                title: 'Laporan Operasional',
                url: '',
                icon: BarChart3,
                badge: 'Segera',
                comingSoon: true,
            },
            {
                title: 'Harga',
                url: '',
                icon: Tags,
                badge: 'Segera',
                comingSoon: true,
            },
        ],
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={mainNavGroups} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
