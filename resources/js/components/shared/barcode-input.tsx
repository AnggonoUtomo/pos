import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LoaderCircle, ScanBarcode } from 'lucide-react';

export function BarcodeInput({
    id,
    disabled,
    busy = false,
    onScan,
}: {
    id: string;
    disabled: boolean;
    busy?: boolean;
    onScan: (code: string) => void;
}) {
    return (
        <div className="min-w-0 space-y-2">
            <Label htmlFor={id}>Scan barcode</Label>
            <div className="relative">
                <ScanBarcode className="pointer-events-none absolute top-3 left-3 size-4 text-sky-600 dark:text-sky-300" />
                <Input
                    id={id}
                    className="pr-10 pl-9"
                    autoComplete="off"
                    spellCheck={false}
                    maxLength={64}
                    disabled={disabled}
                    onKeyDown={(event) => {
                        if (event.key !== 'Enter' || event.ctrlKey || event.metaKey) return;
                        event.preventDefault();
                        event.stopPropagation();
                        const code = event.currentTarget.value.trim();
                        if (code) onScan(code);
                        event.currentTarget.value = '';
                    }}
                />
                {busy && (
                    <LoaderCircle aria-label="Memproses scan" className="absolute top-3 right-3 size-4 animate-spin text-sky-600 dark:text-sky-300" />
                )}
            </div>
        </div>
    );
}
