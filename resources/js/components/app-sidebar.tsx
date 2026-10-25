import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavGroup } from '@/types';
import { Link } from '@inertiajs/react';
import {
    ArrowRightLeft,
    BarChart3,
    Boxes,
    Building2,
    CreditCard,
    History,
    Landmark,
    LayoutGrid,
    Package,
    ReceiptText,
    RotateCcw,
    Settings,
    ShieldCheck,
    ShoppingCart,
    SlidersHorizontal,
    Tags,
    Truck,
    Warehouse,
} from 'lucide-react';
import { useLayoutEffect, useRef } from 'react';
import AppLogo from './app-logo';

const mainNavGroups: NavGroup[] = [
    {
        title: 'Platform',
        items: [
            {
                title: 'Operasional',
                url: '/platform/operational-settings',
                icon: SlidersHorizontal,
                badge: 'Aktif',
                badgeTone: 'amber',
                permission: 'platform.operational-settings.view',
            },
            {
                title: 'Dasbor',
                url: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Identitas & Akses',
                url: '/platform/identity',
                icon: ShieldCheck,
                badge: 'Aktif',
                badgeTone: 'violet',
                permission: ['platform.identity.view', 'platform.identity.users.view'],
            },
            {
                title: 'Pengaturan toko',
                url: '/platform/company-settings',
                icon: Settings,
                badge: 'Aktif',
                badgeTone: 'blue',
                permission: 'platform.company-settings.view',
            },
        ],
    },
    {
        title: 'Inventori',
        items: [
            {
                title: 'Barang',
                url: '/inventory/items',
                icon: Package,
                badge: 'Aktif',
                badgeTone: 'emerald',
                permission: 'inventory.view',
            },
            {
                title: 'Gudang',
                url: '/inventory/warehouses',
                icon: Warehouse,
                badge: 'Aktif',
                badgeTone: 'emerald',
                permission: 'inventory.view',
            },
            {
                title: 'Satuan',
                url: '/inventory/units',
                icon: Tags,
                badge: 'Aktif',
                badgeTone: 'amber',
                permission: 'inventory.view',
            },
            {
                title: 'Stok',
                url: '/inventory/stock',
                icon: Boxes,
                badge: 'Aktif',
                badgeTone: 'amber',
                permission: 'inventory.stock.view',
            },
            {
                title: 'Transfer antar-gudang',
                url: '/inventory/transfers',
                icon: ArrowRightLeft,
                badge: 'Aktif',
                badgeTone: 'blue',
                permission: 'inventory.transfers.view',
            },
        ],
    },
    {
        title: 'Penjualan',
        items: [
            {
                title: 'Pelanggan',
                url: '/sales/customers',
                icon: Building2,
                badge: 'Aktif',
                badgeTone: 'blue',
                permission: 'sales.parties.view',
            },
            {
                title: 'POS',
                url: '/pos',
                icon: ShoppingCart,
                badge: 'Aktif',
                badgeTone: 'emerald',
                permission: 'sales.pos.invoices.create',
            },
            {
                title: 'Riwayat Penjualan',
                url: '/sales/invoices',
                icon: History,
                badge: 'Aktif',
                badgeTone: 'blue',
                permission: ['sales.pos.invoices.post', 'sales.pos.invoices.view'],
            },
            {
                title: 'Retur Penjualan',
                url: '/sales/returns',
                icon: RotateCcw,
                badge: 'Aktif',
                badgeTone: 'rose',
                permission: 'sales.returns.view',
            },
        ],
    },
    {
        title: 'Pembelian',
        items: [
            {
                title: 'Supplier',
                url: '/purchasing/suppliers',
                icon: Truck,
                permission: 'purchasing.suppliers.view',
                badge: 'Aktif',
                badgeTone: 'blue',
            },
            {
                title: 'Order Pembelian',
                url: '/purchasing/purchase-orders',
                icon: ReceiptText,
                permission: 'purchasing.purchase-orders.view',
                badge: 'Aktif',
                badgeTone: 'amber',
            },
            {
                title: 'Retur Pembelian',
                url: '/purchasing/returns',
                icon: RotateCcw,
                permission: 'purchasing.returns.view',
                badge: 'Aktif',
                badgeTone: 'violet',
            },
        ],
    },
    {
        title: 'Keuangan',
        items: [
            {
                title: 'Kas dan bank',
                url: '/finance/payment-accounts',
                icon: Landmark,
                badge: 'Aktif',
                badgeTone: 'emerald',
                permission: 'finance.payments.view',
            },
            {
                title: 'Metode pembayaran',
                url: '/finance/payment-methods',
                icon: CreditCard,
                badge: 'Aktif',
                badgeTone: 'violet',
                permission: 'finance.payments.view',
            },
            {
                title: 'Pembayaran',
                url: '/finance/payments/history',
                icon: CreditCard,
                badge: 'Aktif',
                badgeTone: 'violet',
                permission: 'finance.payments.history.view',
            },
        ],
    },
    {
        title: 'Laporan',
        items: [
            {
                title: 'Laporan Operasional',
                url: '/reports/operations',
                icon: BarChart3,
                badge: 'Aktif',
                badgeTone: 'blue',
                permission: 'reporting.operational.view',
            },
        ],
    },
];

// Page layouts remount during Inertia navigation; retain only the sidebar offset.
let sidebarScrollTop = 0;

export function AppSidebar() {
    const contentRef = useRef<HTMLDivElement>(null);
    useLayoutEffect(() => {
        const content = contentRef.current;
        if (!content) return;

        content.scrollTop = sidebarScrollTop;
        const rememberScroll = () => {
            sidebarScrollTop = content.scrollTop;
        };
        content.addEventListener('scroll', rememberScroll, { passive: true });

        return () => {
            rememberScroll();
            content.removeEventListener('scroll', rememberScroll);
        };
    }, []);
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" tooltip="Dasbor" asChild>
                            <Link href="/dashboard">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent ref={contentRef} className="scrollbar-none group-data-[collapsible=icon]:overflow-auto">
                <NavMain groups={mainNavGroups} />
            </SidebarContent>
        </Sidebar>
    );
}
