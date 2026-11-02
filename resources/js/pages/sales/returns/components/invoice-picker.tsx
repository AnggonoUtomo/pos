import { Autocomplete } from '@/components/shared/autocomplete';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
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
        <div className="min-w-0">
            <div className="min-w-0 space-y-2">
                <Label htmlFor="source-invoice">Invoice asal</Label>
                <Autocomplete
                    value={value}
                    disabled={disabled}
                    onValueChange={onChange}
                    onSearchChange={setSearch}
                    id="source-invoice"
                    className="w-full min-w-0"
                    aria-label="Invoice asal"
                    placeholder="Pilih invoice"
                    options={[
                        value &&
                            !choices.some((choice) => choice.id === value) && {
                                value: value,
                                label: <>Invoice terpilih</>,
                            },
                        choices.map((choice) => ({
                            value: choice.id,
                            label: (
                                <>
                                    {choice.number} / {choice.customer_name}
                                </>
                            ),
                        })),
                    ]}
                />
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
