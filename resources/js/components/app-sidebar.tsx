import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavGroup } from '@/types';
import { Link } from '@inertiajs/react';
import {
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
    Tags,
    Truck,
    Warehouse,
} from 'lucide-react';
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
                badge: 'Aktif',
                badgeTone: 'violet',
                permission: ['platform.identity.view', 'platform.identity.users.view'],
            },
            {
                title: 'Pengaturan Perusahaan',
                url: '',
                icon: Settings,
                badge: 'Segera',
                badgeTone: 'blue',
                comingSoon: true,
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
                url: '',
                icon: BarChart3,
                badge: 'Segera',
                badgeTone: 'blue',
                comingSoon: true,
            },
            {
                title: 'Harga',
                url: '',
                icon: Tags,
                badge: 'Segera',
                badgeTone: 'amber',
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
                        <SidebarMenuButton size="lg" tooltip="Dasbor" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="scrollbar-none group-data-[collapsible=icon]:overflow-auto">
                <NavMain groups={mainNavGroups} />
            </SidebarContent>
        </Sidebar>
    );
}
