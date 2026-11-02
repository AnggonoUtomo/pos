import { Autocomplete } from '@/components/shared/autocomplete';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { WorkspaceShortcutDropdown } from '@/components/workspace-shortcuts';
import { usePermission } from '@/hooks/use-permission';
import AppLayout from '@/layouts/app-layout';
import { formatDecimalInput, parseDecimalInput } from '@/lib/decimal-input';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ClipboardCheck, LockKeyhole, Plus, RotateCcw, Save, ScanBarcode, Send, X } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { decimal } from '../types';
import { ConfirmationDialog, type CountOperation } from './components/confirmation-dialog';
import { statusLabels, type CountShowProps } from './types';

export default function CountShow({ record, options }: CountShowProps) {
    const { can } = usePermission();
    const canManage = can('inventory.stock.counts.manage');
    const canPost = can('inventory.stock.counts.post');
    const editable = canManage && record.status === 'counting';
    const active = record.status === 'counting' || record.status === 'review';
    const initial = () =>
        Object.fromEntries(record.lines.map((row) => [row.id, row.counted_quantity === null ? '' : formatDecimalInput(row.counted_quantity)]));
    const [values, setValues] = useState<Record<string, string>>(initial);
    const [costs, setCosts] = useState<Record<string, string>>({});
    const [search, setSearch] = useState('');
    const [itemId, setItemId] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [operation, setOperation] = useState<CountOperation | null>(null);
    const running = useRef(false);
    const scanRef = useRef<HTMLInputElement>(null);
    const loaded = useRef({ id: record.id, version: record.version });
    useEffect(() => {
        if (loaded.current.id === record.id && loaded.current.version === record.version) return;
        loaded.current = { id: record.id, version: record.version };
        setValues(
            Object.fromEntries(record.lines.map((row) => [row.id, row.counted_quantity === null ? '' : formatDecimalInput(row.counted_quantity)])),
        );
        setCosts({});
    }, [record]);
    const dirty = record.lines.some(
        (row) => (values[row.id] ?? '') !== (row.counted_quantity === null ? '' : formatDecimalInput(row.counted_quantity)),
    );
    useEffect(() => {
        const unload = (e: BeforeUnloadEvent) => {
            if (dirty) {
                e.preventDefault();
            }
        };
        window.addEventListener('beforeunload', unload);
        const remove = router.on('before', () => {
            if (dirty && !running.current && !window.confirm('Hasil hitung belum disimpan. Tinggalkan halaman?')) return false;
        });
        return () => {
            window.removeEventListener('beforeunload', unload);
            remove();
        };
    }, [dirty]);
    const send = useCallback(
        (action: string, payload: Record<string, string | number | { id: string; quantity: string }[] | { id: string; unit_cost: string }[]>) => {
            if (running.current) return;
            running.current = true;
            setBusy(true);
            setError('');
            router.post(
                route(`inventory.stock.counts.${action}`, record.id),
                { version: record.version, ...payload },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setOperation(null);
                        setItemId('');
                        toast.success('Opname diperbarui.');
                    },
                    onError: (issues) => {
                        const message = Object.values(issues).join(' ');
                        setError(message);
                        toast.error(message || 'Operasi opname belum berhasil.');
                    },
                    onFinish: () => {
                        running.current = false;
                        setBusy(false);
                    },
                },
            );
        },
        [record.id, record.version],
    );
    const save = useCallback(() => {
        if (!editable || running.current) return;
        const lines = [];
        for (const row of record.lines) {
            const input = values[row.id]?.trim() ?? '';
            if (input === '' && row.counted_quantity === null) continue;
            const quantity = parseDecimalInput(input);
            if (quantity === null) {
                toast.error('Hasil hitung wajib angka Indonesia; isi 0 bila fisik kosong.');
                return;
            }
            lines.push({ id: row.id, quantity });
        }
        if (lines.length === 0) {
            toast.error('Isi hasil hitung terlebih dahulu.');
            return;
        }
        send('save', { lines });
    }, [editable, record.lines, values, send]);
    useEffect(() => {
        const key = (e: KeyboardEvent) => {
            if (document.querySelector('[role="dialog"]')) return;
            if (e.altKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                scanRef.current?.focus();
            }
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                save();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, [save]);
    const confirm = (reason: string) => {
        if (!operation) return;
        if (operation !== 'post') {
            send(operation, operation === 'submit' ? {} : { reason });
            return;
        }
        const prices = [];
        for (const row of record.lines) {
            if (!row.difference || row.difference.startsWith('-') || /^0(?:\.0+)?$/.test(row.difference)) continue;
            const unit_cost = parseDecimalInput(costs[row.id] ?? '');
            if (unit_cost === null || /^0+(?:\.0+)?$/.test(unit_cost)) {
                setError('Isi harga pokok positif untuk setiap selisih tambah.');
                return;
            }
            prices.push({ id: row.id, unit_cost });
        }
        send('post', { reason, costs: prices });
    };
    const scan = () => {
        const code = search.trim();
        const item = options.items.find((item) => item.sku.toLowerCase() === code.toLowerCase() || item.units.some((unit) => unit.barcode === code));
        if (!item) {
            toast.error('Barcode atau SKU tidak ditemukan.');
            return;
        }
        const row = record.lines.find((row) => row.item_id === item.id);
        if (row) {
            setSearch(item.sku);
            requestAnimationFrame(() => document.getElementById(`quantity-${row.id}`)?.focus());
        } else if (editable && !dirty) {
            send('add', { item_id: item.id });
            setSearch('');
        } else {
            toast.error('Simpan hasil hitung sebelum menambahkan barang.');
        }
    };
    const shown = record.lines.filter(
        (row) =>
            `${row.sku} ${row.item_name}`.toLowerCase().includes(search.toLowerCase()) ||
            options.items.find((item) => item.id === row.item_id)?.units.some((unit) => unit.barcode === search.trim()),
    );
    const counted = record.lines.filter((row) => row.counted_quantity !== null).length;
    const pendingPositive = record.lines.filter(
        (row) => row.difference !== null && !row.difference.startsWith('-') && !/^0(?:\.0+)?$/.test(row.difference),
    );
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Stock opname', href: route('inventory.stock.counts.index') },
                { title: record.number, href: route('inventory.stock.counts.show', record.id) },
            ]}
        >
            <Head title={record.number} />
            <WorkspaceShortcutDropdown
                title="Hasil stock opname"
                description={`${counted}/${record.lines.length} dihitung`}
                shortcuts={[
                    { label: 'Cari / scan', description: 'Fokus barcode atau SKU', keys: ['Alt', 'F'] },
                    { label: 'Simpan hitung', description: 'Simpan semua isian', keys: ['Ctrl/Cmd', 'S'], disabled: !editable },
                    { label: 'Konfirmasi', description: 'Dialog aktif', keys: ['Ctrl/Cmd', 'Enter'] },
                    { label: 'Tutup', description: 'Tutup dialog', keys: ['Esc'] },
                ]}
            />
            <section className="min-w-0 flex-1 space-y-4 p-4 lg:p-6" aria-busy={busy}>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="font-mono text-lg font-semibold break-all">{record.number}</h1>
                        <p className="text-muted-foreground text-sm">{record.warehouse_name}</p>
                    </div>
                    <Badge variant={record.status === 'posted' ? 'default' : 'secondary'}>{statusLabels[record.status]}</Badge>
                </div>
                {active && (
                    <div
                        role="status"
                        className="flex items-center gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                    >
                        <LockKeyhole className="size-4 shrink-0" />
                        Mutasi stok gudang ditahan selama opname.
                    </div>
                )}
                <p className="text-muted-foreground text-sm">{record.notes}</p>
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="icon" title="Kembali ke daftar" aria-label="Kembali ke daftar" asChild>
                        <Link href={route('inventory.stock.counts.index')}>
                            <ArrowLeft className="text-sky-600" />
                        </Link>
                    </Button>
                    {editable && (
                        <>
                            <Button disabled={busy || !dirty} onClick={save}>
                                <Save />
                                Simpan hitung
                            </Button>
                            <Button
                                variant="outline"
                                disabled={busy || dirty || counted !== record.lines.length || counted === 0}
                                onClick={() => {
                                    setError('');
                                    setOperation('submit');
                                }}
                            >
                                <Send className="text-teal-600" />
                                Ajukan review
                            </Button>
                        </>
                    )}
                    {record.status === 'review' && canPost && (
                        <>
                            <Button
                                disabled={busy}
                                onClick={() => {
                                    setError('');
                                    setOperation('post');
                                }}
                            >
                                <ClipboardCheck />
                                Posting hasil
                            </Button>
                            <Button
                                variant="outline"
                                disabled={busy}
                                onClick={() => {
                                    setError('');
                                    setOperation('reopen');
                                }}
                            >
                                <RotateCcw className="text-amber-600" />
                                Hitung ulang
                            </Button>
                        </>
                    )}
                    {active && canManage && (
                        <Button
                            variant="outline"
                            disabled={busy || dirty}
                            onClick={() => {
                                setError('');
                                setOperation('cancel');
                            }}
                        >
                            <X className="text-rose-600" />
                            Batalkan sesi
                        </Button>
                    )}
                    <Button variant="outline" disabled={busy || dirty} onClick={() => router.reload()}>
                        <RotateCcw className="text-sky-600" />
                        Muat ulang
                    </Button>
                </div>
                <div className="workspace-filters flex flex-wrap gap-3">
                    <form
                        className="workspace-search flex min-w-0 flex-1 gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            scan();
                        }}
                    >
                        <Input
                            aria-label="Barcode atau cari barang opname"
                            placeholder="Barcode, SKU atau nama barang..."
                            ref={scanRef}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button size="icon" variant="outline" disabled={busy} title="Temukan barcode" aria-label="Temukan barcode">
                            <ScanBarcode className="text-violet-600" />
                        </Button>
                    </form>
                    {editable && (
                        <>
                            <Autocomplete
                                value={itemId}
                                onValueChange={setItemId}
                                disabled={busy || dirty}
                                aria-label="Barang tambahan opname"
                                className="w-64"
                                placeholder="Tambahkan barang"
                                options={[
                                    options.items
                                        .filter((item) => !record.lines.some((line) => line.item_id === item.id))
                                        .map((item) => ({
                                            value: item.id,
                                            label: (
                                                <>
                                                    {item.sku} - {item.name}
                                                </>
                                            ),
                                        })),
                                ]}
                            />
                            <Button
                                size="icon"
                                variant="outline"
                                title="Tambahkan barang"
                                aria-label="Tambahkan barang"
                                disabled={busy || dirty || !itemId}
                                onClick={() => send('add', { item_id: itemId })}
                            >
                                <Plus className="text-emerald-600" />
                            </Button>
                        </>
                    )}
                </div>
                {dirty && (
                    <p role="status" className="text-sm text-amber-700 dark:text-amber-300">
                        Ada hasil hitung yang belum disimpan.
                    </p>
                )}
                {error && !operation && (
                    <p role="alert" className="text-destructive text-sm">
                        {error}
                    </p>
                )}
                <div className="min-w-0 overflow-x-auto rounded-md border">
                    <Table className="workspace-table min-w-[960px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="min-w-56">Barang</TableHead>
                                <TableHead>Satuan dasar</TableHead>
                                <TableHead className="text-right">Sistem</TableHead>
                                <TableHead className="min-w-36">Fisik</TableHead>
                                <TableHead className="text-right">Selisih tersimpan</TableHead>
                                {record.status === 'review' && canPost && <TableHead className="min-w-40">HPP / satuan dasar</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {shown.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell>
                                        <p className="font-medium">{row.item_name}</p>
                                        <p className="text-muted-foreground font-mono text-xs">{row.sku}</p>
                                    </TableCell>
                                    <TableCell>{row.base_unit_name}</TableCell>
                                    <TableCell className="text-right tabular-nums">{decimal(row.system_quantity)}</TableCell>
                                    <TableCell>
                                        {editable ? (
                                            <Input
                                                id={`quantity-${row.id}`}
                                                aria-label={`Fisik ${row.sku}`}
                                                inputMode="decimal"
                                                className="w-36 text-right tabular-nums"
                                                value={values[row.id] ?? ''}
                                                maxLength={30}
                                                disabled={busy}
                                                onChange={(e) => setValues((old) => ({ ...old, [row.id]: e.target.value }))}
                                            />
                                        ) : row.counted_quantity === null ? (
                                            <span className="text-muted-foreground">Belum dihitung</span>
                                        ) : (
                                            decimal(row.counted_quantity)
                                        )}
                                    </TableCell>
                                    <TableCell
                                        className={`text-right tabular-nums ${row.difference?.startsWith('-') ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300'}`}
                                    >
                                        {row.difference === null ? '-' : decimal(row.difference)}
                                    </TableCell>
                                    {record.status === 'review' && canPost && (
                                        <TableCell>
                                            {pendingPositive.some((line) => line.id === row.id) ? (
                                                <Input
                                                    aria-label={`Harga pokok ${row.sku}`}
                                                    className="w-40 text-right"
                                                    inputMode="decimal"
                                                    maxLength={30}
                                                    value={costs[row.id] ?? ''}
                                                    disabled={busy}
                                                    onChange={(e) => setCosts((old) => ({ ...old, [row.id]: e.target.value }))}
                                                />
                                            ) : (
                                                '-'
                                            )}
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                            {shown.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-muted-foreground py-8 text-center">
                                        Tidak ada barang ditemukan.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
                {record.reason && (
                    <div className="border-t pt-3 text-sm">
                        <p className="font-medium">Hasil pemeriksaan / alasan</p>
                        <p className="text-muted-foreground break-words whitespace-pre-wrap">{record.reason}</p>
                    </div>
                )}
            </section>
            {operation && (
                <ConfirmationDialog operation={operation} busy={busy} error={error} onConfirm={confirm} onClose={() => setOperation(null)} />
            )}
        </AppLayout>
    );
}
