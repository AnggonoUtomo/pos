import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LoaderCircle, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { InvoiceHeader } from '../types';

export function InvoicePicker({ value, disabled, onChange }: { value: string; disabled: boolean; onChange: (id: string) => void }) {
    const [search, setSearch] = useState('');
    const [choices, setChoices] = useState<Pick<InvoiceHeader, 'id' | 'number' | 'customer_name'>[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);
    const [retry, setRetry] = useState(0);
    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError(false);
        const timer = window.setTimeout(async () => {
            try {
                const response = await fetch(route('sales.returns.invoices', { search }), {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Lookup gagal.');
                const data = await response.json();
                if (!controller.signal.aborted) setChoices(data);
            } catch {
                if (!controller.signal.aborted) {
                    setChoices([]);
                    setError(true);
                }
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        }, 250);
        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [search, retry]);
    return (
        <div className="grid min-w-0 gap-3 md:grid-cols-2">
            <div className="min-w-0 space-y-2">
                <Label htmlFor="invoice-search">Cari nomor invoice</Label>
                <Input id="invoice-search" value={search} maxLength={150} disabled={disabled} onChange={(e) => setSearch(e.target.value)} />
            </div>
            <div className="min-w-0 space-y-2">
                <Label htmlFor="source-invoice">Invoice asal</Label>
                <Select value={value} disabled={disabled || loading || error} onValueChange={onChange}>
                    <SelectTrigger id="source-invoice" className="w-full min-w-0" aria-label="Invoice asal">
                        <SelectValue placeholder="Pilih invoice" />
                    </SelectTrigger>
                    <SelectContent>
                        {value && !choices.some((choice) => choice.id === value) && <SelectItem value={value}>Invoice terpilih</SelectItem>}
                        {choices.map((choice) => (
                            <SelectItem key={choice.id} value={choice.id}>
                                {choice.number} / {choice.customer_name}
                            </SelectItem>
                        ))}
                        {!choices.length && <div className="text-muted-foreground p-3 text-sm">Tidak ada invoice.</div>}
                    </SelectContent>
                </Select>
                {loading && (
                    <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                        <LoaderCircle className="size-4 animate-spin" />
                        Memuat invoice...
                    </p>
                )}
                {error && (
                    <div role="alert" className="text-destructive flex flex-wrap items-center gap-2 text-sm">
                        Invoice belum dapat dimuat.
                        <Button type="button" variant="outline" size="sm" onClick={() => setRetry((n) => n + 1)}>
                            <RefreshCw className="size-4" />
                            Coba lagi
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
}
