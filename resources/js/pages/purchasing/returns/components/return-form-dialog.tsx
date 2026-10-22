import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDecimalInput, parseDecimalInput } from '@/lib/decimal-input';
import { router, useForm } from '@inertiajs/react';
import { LoaderCircle, Search, Save } from 'lucide-react';
import { useCallback, useEffect, useRef, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import type { ReturnPurchaseSource, ReturnRow, ReturnSourceLine } from '../types';

type FormLine = { purchase_order_line_id: string; quantity: string };

export function ReturnFormDialog({ record, readOnly, onClose }: { record: ReturnRow | null; readOnly: boolean; onClose: () => void }) {
    const form = useForm({
        purchase_order_id: record?.purchase_order_id ?? '',
        notes: record?.notes ?? '',
        version: record?.version ?? 1,
        lines: record?.lines.map((line) => ({ purchase_order_line_id: line.purchase_order_line_id, quantity: formatDecimalInput(line.quantity) })) ?? [],
    });
    const errors = form.errors as Record<string, string>;
    const [source, setSource] = useState<ReturnPurchaseSource | null>(null);
    const [sourceSearch, setSourceSearch] = useState('');
    const [matches, setMatches] = useState<ReturnPurchaseSource[]>([]);
    const [searching, setSearching] = useState(false);
    const [loadingSource, setLoadingSource] = useState(false);
    const [sourceError, setSourceError] = useState('');
    const [searchError, setSearchError] = useState('');
    const [stale, setStale] = useState(false);
    const [requestedSourceId, setRequestedSourceId] = useState(record?.purchase_order_id ?? '');
    const searchForm = useRef<HTMLFormElement>(null);
    const submitting = useRef(false);
    const searchRequest = useRef<AbortController | null>(null);
    const sourceRequest = useRef<AbortController | null>(null);
    const disabled = readOnly || form.processing;
    const setFormData = form.setData;

    const loadSource = useCallback(async (id: string) => {
        sourceRequest.current?.abort();
        const controller = new AbortController();
        sourceRequest.current = controller;
        setRequestedSourceId(id);
        setLoadingSource(true);
        setSourceError('');
        setSource(null);
        try {
            const response = await fetch(route('purchasing.returns.purchase', { id }), {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(response.status === 403 ? 'Anda tidak memiliki akses sumber pembelian.' : 'Sumber pembelian gagal dimuat.');
            const result = (await response.json()) as ReturnPurchaseSource;
            if (sourceRequest.current === controller) {
                setSource(result);
                setFormData('purchase_order_id', result.id);
                if (!record) setFormData('lines', []);
            }
        } catch (error) {
            if (!controller.signal.aborted && sourceRequest.current === controller) {
                setSourceError(error instanceof Error ? error.message : 'Sumber pembelian gagal dimuat.');
                toast.error('Sumber pembelian belum dapat dimuat.');
            }
        } finally {
            if (sourceRequest.current === controller) setLoadingSource(false);
        }
    }, [setFormData, record]);

    useEffect(() => {
        if (record && readOnly) {
            setSource({ ...record.snapshot, lines: record.lines.map((line) => ({ ...line.snapshot, id: line.purchase_order_line_id })) as ReturnSourceLine[] });
        } else if (record) {
            void loadSource(record.purchase_order_id);
        }
        return () => {
            searchRequest.current?.abort();
            sourceRequest.current?.abort();
        };
    }, [loadSource, readOnly, record]);

    const searchSources = async (event: FormEvent) => {
        event.preventDefault();
        searchRequest.current?.abort();
        const controller = new AbortController();
        searchRequest.current = controller;
        setSearching(true);
        setSearchError('');
        setMatches([]);
        try {
            const response = await fetch(`${route('purchasing.returns.purchases')}?search=${encodeURIComponent(sourceSearch)}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(response.status === 403 ? 'Anda tidak memiliki akses sumber pembelian.' : 'Pencarian pembelian gagal.');
            const result = (await response.json()) as { data: ReturnPurchaseSource[] };
            if (searchRequest.current === controller) setMatches(result.data);
        } catch (error) {
            if (!controller.signal.aborted) {
                const message = error instanceof Error ? error.message : 'Pencarian pembelian gagal.';
                setSearchError(message);
                toast.error(message);
            }
        } finally {
            if (searchRequest.current === controller) setSearching(false);
        }
    };

    const changeQuantity = (lineId: string, quantity: string) => {
        const lines = form.data.lines.filter((line) => line.purchase_order_line_id !== lineId);
        if (quantity.trim() !== '') lines.push({ purchase_order_line_id: lineId, quantity });
        form.setData('lines', lines);
        form.clearErrors();
        setStale(false);
    };

    const scaledQuantity = (value: string): bigint => {
        const [whole, fraction = ''] = value.split('.');
        return BigInt(whole) * 1_000_000n + BigInt(fraction.padEnd(6, '0'));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (disabled || submitting.current) return;
        form.clearErrors();
        const lines: FormLine[] = [];
        let invalid = false;
        source?.lines.forEach((sourceLine) => {
            const line = form.data.lines.find((entry) => entry.purchase_order_line_id === sourceLine.id);
            if (!line || line.quantity.trim() === '') return;
            const quantity = parseDecimalInput(line.quantity);
            const errorKey = `lines.${lines.length}.quantity` as keyof typeof form.data;
            if (quantity === null || scaledQuantity(quantity) <= 0n) {
                form.setError(errorKey, 'Masukkan kuantitas positif dengan format Indonesia.');
                invalid = true;
                return;
            }
            if (scaledQuantity(quantity) > scaledQuantity(sourceLine.quantity)) {
                form.setError(errorKey, `Maksimal ${formatDecimalInput(sourceLine.quantity)} ${sourceLine.unit_name}.`);
                invalid = true;
                return;
            }
            lines.push({ purchase_order_line_id: sourceLine.id, quantity });
        });
        if (!source) {
            form.setError('purchase_order_id', 'Pilih pembelian diterima dan isi kuantitas minimal satu barang.');
            invalid = true;
        } else if (lines.length === 0 && !invalid) {
            form.setError('lines', 'Isi kuantitas positif pada minimal satu barang.');
            invalid = true;
        }
        if (invalid) {
            toast.error('Draft belum tersimpan. Periksa sumber dan kuantitas.');
            return;
        }
        submitting.current = true;
        form.transform((data) => ({ ...data, lines }));
        const config = {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(record ? 'Draft retur berhasil diperbarui.' : 'Draft retur berhasil disimpan.');
                onClose();
            },
            onError: (validationErrors: Record<string, string>) => {
                const conflicts = Boolean(validationErrors.version || validationErrors.purchase_return);
                setStale(conflicts);
                toast.error(conflicts ? 'Draft berubah sejak dibuka. Muat ulang sebelum melanjutkan.' : 'Draft belum tersimpan. Periksa pesan kesalahan.');
            },
            onFinish: () => { submitting.current = false; },
        };
        if (record) form.put(route('purchasing.returns.update', { id: record.id }), config);
        else form.post(route('purchasing.returns.store'), config);
    };

    const requestClose = (open: boolean) => {
        if (!open && !form.processing) onClose();
    };

    return (
        <Dialog open onOpenChange={requestClose}>
            <DialogContent
                className="max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-5xl"
                onEscapeKeyDown={(event) => form.processing && event.preventDefault()}
                onPointerDownOutside={(event) => form.processing && event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>{readOnly ? 'Detail retur pembelian' : record ? 'Edit draft retur pembelian' : 'Draft retur pembelian baru'}</DialogTitle>
                    <DialogDescription>{record ? `${record.number} · Revisi ${record.version}` : 'Pilih pembelian yang sudah diterima untuk memulai draft.'}</DialogDescription>
                </DialogHeader>
                {!record && !readOnly && (
                    <>
                        <form ref={searchForm} className="flex flex-wrap gap-2" onSubmit={searchSources}>
                            <Input aria-label="Cari pembelian diterima" className="min-w-48 flex-1" maxLength={150} placeholder="Nomor pembelian, supplier, atau gudang" value={sourceSearch} onChange={(event) => setSourceSearch(event.target.value)} />
                            <Button type="submit" variant="outline" disabled={searching}>
                                {searching ? <LoaderCircle className="size-4 animate-spin" /> : <Search className="size-4 text-sky-600 dark:text-sky-300" />}
                                Cari pembelian
                            </Button>
                        </form>
                        {searchError && <div role="alert" className="flex w-full flex-wrap items-center justify-between gap-2 rounded-md border border-destructive/40 p-3 text-sm"><span>{searchError}</span><Button size="sm" variant="outline" disabled={searching} onClick={() => searchForm.current?.requestSubmit()}>Ulangi pencarian</Button></div>}
                    </>
                )}
                {!record && matches.length > 0 && !source && (
                    <div className="max-h-44 divide-y overflow-y-auto rounded-md border" aria-label="Hasil pembelian diterima">
                        {matches.map((purchase) => (
                            <button key={purchase.id} type="button" className="hover:bg-muted/60 flex w-full flex-wrap justify-between gap-2 p-3 text-left text-sm" onClick={() => void loadSource(purchase.id)}>
                                <span><strong>{purchase.number}</strong><span className="text-muted-foreground ml-2">{purchase.supplier_name}</span></span>
                                <span className="text-muted-foreground">{purchase.warehouse_name}</span>
                            </button>
                        ))}
                    </div>
                )}
                {source && (
                    <section className="grid gap-2 border-y py-3 text-sm sm:grid-cols-3" aria-label="Snapshot pembelian asal">
                        <p><span className="text-muted-foreground">Pembelian </span><strong>{source.number}</strong></p>
                        <p><span className="text-muted-foreground">Supplier </span>{source.supplier_name}</p>
                        <p><span className="text-muted-foreground">Gudang asal </span>{source.warehouse_name}</p>
                    </section>
                )}
                {loadingSource && <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm"><LoaderCircle className="size-4 animate-spin" />Memuat snapshot pembelian...</p>}
                {sourceError && <div role="alert" className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-destructive/40 p-3 text-sm"><span>{sourceError}</span><Button size="sm" variant="outline" disabled={!requestedSourceId || loadingSource} onClick={() => requestedSourceId && void loadSource(requestedSourceId)}>Coba lagi</Button></div>}
                <InputError message={errors.purchase_order_id} />
                {source && (
                    <form onSubmit={submit} className="min-w-0 space-y-4" onKeyDown={(event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && !readOnly) {
                            event.preventDefault();
                            event.currentTarget.requestSubmit();
                        }
                    }}>
                        <InputError message={errors.lines} />
                        <div className="min-w-0 overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[720px] text-sm">
                                <thead className="bg-muted/40 text-left"><tr>{['Barang', 'Satuan', 'Diterima', 'Harga beli asal', readOnly ? 'Diretur' : 'Kuantitas retur'].map((label) => <th key={label} className="p-3 font-medium">{label}</th>)}</tr></thead>
                                <tbody>
                                    {source.lines.map((line, index) => {
                                        const existing = form.data.lines.find((entry) => entry.purchase_order_line_id === line.id);
                                        const returnLine = record?.lines.find((entry) => entry.purchase_order_line_id === line.id);
                                        const submittedIndex = source.lines.slice(0, index).filter((sourceItem) => form.data.lines.some((entry) => entry.purchase_order_line_id === sourceItem.id && entry.quantity.trim() !== '')).length;
                                        return <tr key={line.id} className="border-t">
                                            <td className="max-w-64 p-3 break-words"><strong>{line.sku}</strong><div className="text-muted-foreground">{line.item_name}</div></td>
                                            <td className="p-3">{line.unit_name}<div className="text-muted-foreground text-xs">Dasar: {line.base_unit_name}</div></td>
                                            <td className="p-3 whitespace-nowrap">{formatDecimalInput(line.quantity)}</td>
                                            <td className="p-3 whitespace-nowrap">Rp {formatDecimalInput(line.unit_cost)}</td>
                                            <td className="p-3">
                                                {readOnly ? <span className="whitespace-nowrap">{returnLine ? formatDecimalInput(returnLine.quantity) : '0'}</span> : <div className="min-w-32 space-y-1">
                                                    <Input inputMode="decimal" aria-label={`Kuantitas retur ${line.item_name}`} aria-invalid={Boolean(errors[`lines.${submittedIndex}.quantity`])} disabled={disabled} placeholder="Contoh: 1,5" value={existing?.quantity ?? ''} onChange={(event) => changeQuantity(line.id, event.target.value)} />
                                                    <InputError message={errors[`lines.${submittedIndex}.quantity`]} />
                                                </div>}
                                            </td>
                                        </tr>;
                                    })}
                                </tbody>
                            </table>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="return-notes">Catatan</Label>
                            <Textarea id="return-notes" maxLength={500} readOnly={readOnly} disabled={form.processing} value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                            <InputError message={errors.notes} />
                        </div>
                        <div className="bg-muted/40 flex flex-wrap items-center gap-2 rounded-md p-3 text-sm">
                            <Badge variant={record?.status === 'posted' ? 'default' : 'secondary'}>{record?.status === 'posted' ? 'Diserahkan' : 'Draft'}</Badge>
                            <span className="text-muted-foreground">{record?.status === 'posted' ? 'Stok dari penerimaan asal sudah dikeluarkan. Pengembalian uang supplier dicatat terpisah.' : 'Belum mengubah stok/FIFO dan tidak mencatat pengembalian uang supplier.'}</span>
                        </div>
                        {stale && <div role="alert" className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-amber-500/40 p-3 text-sm"><span>Revisi draft sudah berubah. Perubahan pada form ini belum disimpan.</span><Button type="button" variant="outline" onClick={() => { onClose(); router.reload(); }}>Tutup dan muat ulang</Button></div>}
                        <DialogFooter>
                            <Button type="button" variant="outline" disabled={form.processing} onClick={onClose}>Tutup</Button>
                            {!readOnly && <Button type="submit" disabled={form.processing || loadingSource}>{form.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4 text-emerald-600 dark:text-emerald-300" />}{record ? 'Simpan perubahan' : 'Simpan draft'}</Button>}
                        </DialogFooter>
                    </form>
                )}
                {!source && !loadingSource && !sourceError && matches.length === 0 && !record && <p className="text-muted-foreground text-sm">Cari pembelian berstatus diterima untuk memilih sumber retur.</p>}
                {record && !source && !loadingSource && sourceError && <DialogFooter><Button variant="outline" onClick={onClose}>Tutup</Button></DialogFooter>}
            </DialogContent>
        </Dialog>
    );
}
