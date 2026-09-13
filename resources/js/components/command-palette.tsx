import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { BarChart3, Boxes, Building2, CreditCard, LayoutGrid, Package, ReceiptText, RotateCcw, Search, Settings, ShieldCheck, ShoppingCart, Tags, Truck, Warehouse } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const commandItems = [
    { title: 'Dasbor', description: 'Ringkasan operasional admin', url: '/dashboard', icon: LayoutGrid, tone: 'text-sky-600 dark:text-sky-300' },
    { title: 'POS', description: 'Buka layar kasir fullscreen', url: '/pos', icon: ShoppingCart, tone: 'text-emerald-600 dark:text-emerald-300' },
    { title: 'Identitas & Akses', description: 'Kelola user, role, dan permission', icon: ShieldCheck, tone: 'text-violet-600 dark:text-violet-300', disabled: true },
    { title: 'Pengaturan Perusahaan', description: 'Konfigurasi perusahaan dan preferensi sistem', icon: Settings, tone: 'text-slate-600 dark:text-slate-300', disabled: true },
    { title: 'Katalog', description: 'Kelola item, barcode, satuan, dan harga', icon: Package, tone: 'text-teal-600 dark:text-teal-300', disabled: true },
    { title: 'Gudang', description: 'Kelola gudang dan lokasi stok', icon: Warehouse, tone: 'text-lime-700 dark:text-lime-300', disabled: true },
    { title: 'Stok', description: 'Pantau stok, FIFO layer, dan movement', icon: Boxes, tone: 'text-amber-600 dark:text-amber-300', disabled: true },
    { title: 'Pelanggan', description: 'Kelola pelanggan dan level harga', icon: Building2, tone: 'text-cyan-600 dark:text-cyan-300', disabled: true },
    { title: 'Retur Penjualan', description: 'Proses retur dan koreksi transaksi posted', icon: RotateCcw, tone: 'text-rose-600 dark:text-rose-300', disabled: true },
    { title: 'Supplier', description: 'Kelola supplier pembelian', icon: Truck, tone: 'text-blue-600 dark:text-blue-300', disabled: true },
    { title: 'Order Pembelian', description: 'Rencana dan dokumen pembelian', icon: ReceiptText, tone: 'text-orange-600 dark:text-orange-300', disabled: true },
    { title: 'Pembayaran', description: 'Finance Lite dan payment tracking', icon: CreditCard, tone: 'text-fuchsia-600 dark:text-fuchsia-300', disabled: true },
    { title: 'Laporan Operasional', description: 'Laporan ringkas penjualan dan stok', icon: BarChart3, tone: 'text-indigo-600 dark:text-indigo-300', disabled: true },
    { title: 'Harga', description: 'Harga level pelanggan dan multi satuan', icon: Tags, tone: 'text-yellow-700 dark:text-yellow-300', disabled: true },
];

export function CommandPalette() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                setOpen((current) => !current);
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    const filteredItems = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        if (!normalizedQuery) {
            return commandItems;
        }

        return commandItems.filter((item) => `${item.title} ${item.description}`.toLowerCase().includes(normalizedQuery));
    }, [query]);

    const runCommand = (item: (typeof commandItems)[number]) => {
        if (item.disabled || !item.url) {
            return;
        }

        setOpen(false);
        setQuery('');
        router.visit(item.url);
    };

    return (
        <>
            <Button type="button" variant="outline" size="sm" className="hidden h-8 w-8 shrink-0 gap-0 px-0 md:flex xl:w-auto xl:gap-2 xl:px-3" onClick={() => setOpen(true)}>
                <Search className="size-4 text-cyan-600 dark:text-cyan-300" />
                <span className="hidden xl:inline">Command</span>
                <kbd className="bg-muted text-muted-foreground hidden rounded border px-1.5 py-0.5 text-[11px] leading-none font-medium xl:inline-flex">Ctrl K</kbd>
            </Button>
            <Button type="button" variant="ghost" size="icon" className="size-10 md:hidden" onClick={() => setOpen(true)} aria-label="Buka command palette">
                <Search className="size-4" />
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="top-[18%] max-w-2xl translate-y-0 p-0">
                    <DialogHeader className="sr-only">
                        <DialogTitle>Command Palette</DialogTitle>
                        <DialogDescription>Cari dan buka navigasi atau operasi aplikasi.</DialogDescription>
                    </DialogHeader>
                    <div className="border-b p-3">
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                autoFocus
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder="Cari menu atau operasi..."
                                className="h-11 pl-9"
                            />
                        </div>
                    </div>
                    <div className="max-h-[420px] overflow-y-auto p-2">
                        {filteredItems.length === 0 && <div className="text-muted-foreground px-3 py-8 text-center text-sm">Tidak ada command yang cocok.</div>}
                        {filteredItems.map((item) => (
                            <button
                                key={item.title}
                                type="button"
                                disabled={item.disabled}
                                onClick={() => runCommand(item)}
                                className={cn(
                                    'flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm outline-hidden transition-colors',
                                    item.disabled ? 'cursor-not-allowed opacity-50' : 'hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground',
                                )}
                            >
                                <span className="bg-muted flex size-9 shrink-0 items-center justify-center rounded-lg">
                                    <item.icon className={cn('size-4', item.tone)} />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block font-medium">{item.title}</span>
                                    <span className="text-muted-foreground block truncate text-xs">{item.description}</span>
                                </span>
                                {item.disabled && <span className="text-muted-foreground text-xs">Segera</span>}
                            </button>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
