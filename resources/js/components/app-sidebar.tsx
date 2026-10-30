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
                title: 'Dasbor',
                url: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Identitas & Akses',
                url: '/platform/identity',
                icon: ShieldCheck,
                badgeTone: 'violet',
                permission: ['platform.identity.view', 'platform.identity.users.view'],
            },
            {
                title: 'Pengaturan toko',
                url: '/platform/company-settings',
                icon: Settings,
                badgeTone: 'blue',
                permission: 'platform.company-settings.view',
            },
            {
                title: 'Operasional',
                url: '/platform/operational-settings',
                icon: SlidersHorizontal,
                badgeTone: 'amber',
                permission: 'platform.operational-settings.view',
            },
            {
                title: 'Log Aktivitas',
                url: '/platform/audit-log',
                icon: History,
                badgeTone: 'blue',
                permission: 'platform.audit-log.view',
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
                badgeTone: 'emerald',
                permission: 'inventory.view',
            },
            {
                title: 'Gudang',
                url: '/inventory/warehouses',
                icon: Warehouse,
                badgeTone: 'emerald',
                permission: 'inventory.view',
            },
            {
                title: 'Satuan',
                url: '/inventory/units',
                icon: Tags,
                badgeTone: 'amber',
                permission: 'inventory.view',
            },
            {
                title: 'Stok',
                url: '/inventory/stock',
                icon: Boxes,
                badgeTone: 'amber',
                permission: 'inventory.stock.view',
            },
            {
                title: 'Transfer antar-gudang',
                url: '/inventory/transfers',
                icon: ArrowRightLeft,
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
                badgeTone: 'blue',
                permission: 'sales.parties.view',
            },
            {
                title: 'POS',
                url: '/pos',
                icon: ShoppingCart,
                badgeTone: 'emerald',
                permission: 'sales.pos.invoices.create',
            },
            {
                title: 'Riwayat Penjualan',
                url: '/sales/invoices',
                icon: History,
                badgeTone: 'blue',
                permission: ['sales.pos.invoices.post', 'sales.pos.invoices.view'],
            },
            {
                title: 'Shift Kasir',
                url: '/sales/cashier-shifts',
                icon: ReceiptText,
                badgeTone: 'amber',
                permission: ['sales.pos.invoices.post', 'sales.pos.shifts.view'],
            },
            {
                title: 'Retur Penjualan',
                url: '/sales/returns',
                icon: RotateCcw,
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
                badgeTone: 'blue',
            },
            {
                title: 'Order Pembelian',
                url: '/purchasing/purchase-orders',
                icon: ReceiptText,
                permission: 'purchasing.purchase-orders.view',
                badgeTone: 'amber',
            },
            {
                title: 'Retur Pembelian',
                url: '/purchasing/returns',
                icon: RotateCcw,
                permission: 'purchasing.returns.view',
                badgeTone: 'violet',
            },
        ],
    },
    {
        title: 'Keuangan',
        items: [
            { title: 'Buku Kas', url: '/finance/cashbook', icon: Landmark, badgeTone: 'emerald', permission: 'finance.cashbook.view' },
            {
                title: 'Kas dan bank',
                url: '/finance/payment-accounts',
                icon: Landmark,
                badgeTone: 'emerald',
                permission: 'finance.payments.view',
            },
            {
                title: 'Metode pembayaran',
                url: '/finance/payment-methods',
                icon: CreditCard,
                badgeTone: 'violet',
                permission: 'finance.payments.view',
            },
            {
                title: 'Pembayaran',
                url: '/finance/payments/history',
                icon: CreditCard,
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
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader className="h-[76px] justify-center px-4 group-data-[collapsible=icon]:px-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            tooltip="Dasbor"
                            className="h-12 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-10! hover:bg-transparent"
                            asChild
                        >
                            <Link href="/dashboard">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent ref={contentRef} className="scrollbar-none gap-3 pb-5 group-data-[collapsible=icon]:overflow-auto">
                <NavMain groups={mainNavGroups} />
            </SidebarContent>
        </Sidebar>
    );
}
