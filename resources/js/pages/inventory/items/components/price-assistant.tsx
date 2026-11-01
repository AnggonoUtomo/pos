import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDecimalInput, parseDecimalInput } from '@/lib/decimal-input';
import { Calculator, Check, LoaderCircle, Percent } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import type { ItemPriceInput, ItemUnitInput, PriceLevel, UnitOption } from '../types';

type Preview = {
    reference: null | { number: string; received_at: string; unit_name: string; unit_cost: string; conversion_ratio: string };
    prices: ItemPriceInput[];
};

export function PriceAssistant({
    itemId,
    units,
    levels,
    prices,
    options,
    disabled,
    onChange,
}: {
    itemId: string | null;
    units: ItemUnitInput[];
    levels: PriceLevel[];
    prices: ItemPriceInput[];
    options: UnitOption[];
    disabled: boolean;
    onChange: (rows: ItemPriceInput[]) => void;
}) {
    const [percents, setPercents] = useState<Record<string, string>>({});
    const [preview, setPreview] = useState<Preview | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const request = useRef<AbortController | null>(null);
    const calculateButton = useRef<HTMLButtonElement>(null);
    const applyButton = useRef<HTMLButtonElement>(null);
    const selected = units.filter((unit) => unit.is_sale_unit && unit.unit_id);
    const signature = JSON.stringify([itemId, selected, levels, percents]);
    useEffect(() => {
        request.current?.abort();
        request.current = null;
        setPreview(null);
        setBusy(false);
        setError('');
        return () => {
            request.current?.abort();
            request.current = null;
        };
    }, [signature]);
    useEffect(() => {
        const key = (e: KeyboardEvent) => {
            if (!e.altKey || e.ctrlKey || e.metaKey || e.shiftKey || e.repeat) return;
            if (e.key.toLowerCase() === 'g') {
                e.preventDefault();
                calculateButton.current?.click();
            }
            if (e.key.toLowerCase() === 'a') {
                e.preventDefault();
                applyButton.current?.click();
            }
        };
        window.addEventListener('keydown', key);
        return () => window.removeEventListener('keydown', key);
    }, []);
    const calculate = async () => {
        if (!itemId || busy || disabled) return;
        setPreview(null);
        setError('');
        const markups = levels
            .filter((level) => (percents[level.id] ?? '').trim() !== '')
            .map((level) => ({ customer_level_id: level.id, percent: parseDecimalInput(percents[level.id], 2) }));
        const saleUnits = selected.map((unit) => ({ unit_id: unit.unit_id, conversion_ratio: parseDecimalInput(unit.conversion_ratio) }));
        if (
            markups.length === 0 ||
            markups.some((row) => row.percent === null) ||
            saleUnits.length === 0 ||
            saleUnits.some((row) => row.conversion_ratio === null)
        ) {
            setError('Isi persen untuk sedikitnya satu level dan periksa konversi satuan.');
            return;
        }
        request.current?.abort();
        const controller = new AbortController();
        request.current = controller;
        setBusy(true);
        const timer = window.setTimeout(() => controller.abort(), 20000);
        try {
            const token = document.cookie
                .split('; ')
                .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
                ?.slice(11);
            const response = await fetch(route('inventory.items.price-preview', { id: itemId }), {
                method: 'POST',
                credentials: 'same-origin',
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
                },
                body: JSON.stringify({ units: saleUnits, markups }),
            });
            const body = await response.json().catch(() => null);
            if (!response.ok) {
                const fields = body?.errors as Record<string, string[]> | undefined;
                throw new Error(
                    Object.values(fields ?? {})[0]?.[0] ??
                        (response.status === 403 ? 'Akses biaya acuan tidak diizinkan.' : 'Usulan belum dapat dihitung. Muat ulang atau coba lagi.'),
                );
            }
            if (
                !body ||
                !Array.isArray(body.prices) ||
                !('reference' in body) ||
                (body.reference !== null &&
                    (typeof body.reference !== 'object' ||
                        typeof body.reference.number !== 'string' ||
                        typeof body.reference.unit_name !== 'string' ||
                        typeof body.reference.unit_cost !== 'string' ||
                        !/^\d{1,14}(?:\.\d{1,6})?$/.test(body.reference.unit_cost) ||
                        !Number.isFinite(Date.parse(body.reference.received_at)))) ||
                body.prices.some(
                    (row: ItemPriceInput) =>
                        !row ||
                        typeof row.unit_id !== 'string' ||
                        typeof row.customer_level_id !== 'string' ||
                        typeof row.amount !== 'string' ||
                        !/^\d{1,18}\.\d{2}$/.test(row.amount),
                )
            ) {
                throw new Error('Respons usulan tidak valid. Coba lagi.');
            }
            if (!controller.signal.aborted && request.current === controller) setPreview(body);
        } catch (failure) {
            if (request.current === controller && !controller.signal.aborted)
                setError(failure instanceof Error ? failure.message : 'Usulan belum dapat dihitung.');
            else if (request.current === controller && controller.signal.aborted)
                setError('Permintaan dibatalkan atau melewati batas waktu. Hitung ulang bila diperlukan.');
        } finally {
            window.clearTimeout(timer);
            if (request.current === controller) {
                request.current = null;
                setBusy(false);
            }
        }
    };
    const apply = () => {
        if (!preview?.reference || preview.prices.length === 0 || busy || disabled) return;
        const suggested = new Map(preview.prices.map((price) => [`${price.unit_id}:${price.customer_level_id}`, price]));
        onChange(
            prices.map((price) => {
                const row = suggested.get(`${price.unit_id}:${price.customer_level_id}`);
                return row ? { ...price, amount: formatDecimalInput(row.amount) } : price;
            }),
        );
        setPreview(null);
        toast.success('Usulan diterapkan ke form. Harga belum disimpan.');
    };
    return (
        <section aria-label="Bantuan harga persen" className="space-y-3 border-y border-sky-500/20 bg-sky-500/5 p-3">
            <div className="flex items-center gap-2 text-sm font-semibold">
                <Percent className="size-4 text-sky-600 dark:text-sky-300" />
                Markup dari biaya beli (%)
            </div>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {levels.map((level) => (
                    <div className="min-w-0 space-y-1" key={level.id}>
                        <Label htmlFor={`markup-${level.id}`} className="block text-xs break-words">
                            {level.name}
                        </Label>
                        <Input
                            id={`markup-${level.id}`}
                            aria-label={`Markup ${level.name}`}
                            inputMode="decimal"
                            placeholder="-"
                            maxLength={12}
                            value={percents[level.id] ?? ''}
                            disabled={disabled || !itemId}
                            onChange={(e) => setPercents((values) => ({ ...values, [level.id]: e.target.value }))}
                        />
                    </div>
                ))}
            </div>
            <div className="flex flex-wrap gap-2">
                <Button
                    type="button"
                    ref={calculateButton}
                    size="sm"
                    variant="outline"
                    disabled={disabled || busy || !itemId}
                    onClick={() => void calculate()}
                >
                    {busy ? <LoaderCircle className="size-4 animate-spin" /> : <Calculator className="size-4 text-sky-600 dark:text-sky-300" />}Hitung
                    usulan
                </Button>
                <Button
                    type="button"
                    ref={applyButton}
                    size="sm"
                    disabled={disabled || busy || !preview?.reference || preview.prices.length === 0}
                    onClick={apply}
                >
                    <Check className="size-4" />
                    Terapkan ke form
                </Button>
            </div>
            {!itemId && <p className="text-muted-foreground text-xs">Belum ada pembelian diterima untuk barang baru.</p>}
            {error && (
                <p className="text-destructive text-sm" role="alert">
                    {error}
                </p>
            )}
            {preview && !preview.reference && (
                <p className="text-muted-foreground text-sm" role="status">
                    Belum ada pembelian diterima. Harga manual tetap tersedia.
                </p>
            )}
            {preview?.reference && (
                <>
                    <p className="text-muted-foreground text-xs">
                        Acuan {preview.reference.number} ·{' '}
                        {new Date(preview.reference.received_at).toLocaleString('id-ID', {
                            timeZone: 'Asia/Jakarta',
                            dateStyle: 'medium',
                            timeStyle: 'short',
                        })}{' '}
                        WIB · Rp{formatDecimalInput(preview.reference.unit_cost)}/{preview.reference.unit_name}
                    </p>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="workspace-table w-full text-sm">
                            <thead>
                                <tr>
                                    <th className="px-3 py-2 text-left">Satuan / level</th>
                                    <th className="px-3 py-2 text-right">Harga di form</th>
                                    <th className="px-3 py-2 text-right">Usulan (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {preview.prices.map((row) => (
                                    <tr key={`${row.unit_id}:${row.customer_level_id}`}>
                                        <td className="px-3 py-2">
                                            {options.find((u) => u.id === row.unit_id)?.name} /{' '}
                                            {levels.find((l) => l.id === row.customer_level_id)?.name}
                                        </td>
                                        <td className="px-3 py-2 text-right tabular-nums">
                                            {prices.find((p) => p.unit_id === row.unit_id && p.customer_level_id === row.customer_level_id)?.amount ||
                                                '-'}
                                        </td>
                                        <td className="px-3 py-2 text-right font-medium tabular-nums">{formatDecimalInput(row.amount)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            )}
        </section>
    );
}
