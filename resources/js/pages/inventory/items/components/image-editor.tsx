import InputError from '@/components/input-error';
import { ItemImage } from '@/components/shared/item-image';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { RotateCcw, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export function ImageEditor({
    currentUrl,
    file,
    removed,
    name,
    disabled,
    error,
    onChange,
    onError,
}: {
    currentUrl: string | null;
    file: File | null;
    removed: boolean;
    name: string;
    disabled: boolean;
    error?: string;
    onChange: (file: File | null, removed: boolean) => void;
    onError: (message: string) => void;
}) {
    const input = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);
    useEffect(() => {
        if (!file) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(file);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [file]);
    const reset = (remove: boolean) => {
        if (input.current) input.current.value = '';
        onChange(null, remove);
    };
    return (
        <div className="flex flex-wrap items-start gap-4 border-y py-4">
            <ItemImage src={removed ? null : (preview ?? currentUrl)} name={name || 'Barang'} className="size-24" />
            <div className="min-w-0 flex-1 space-y-2">
                <Label htmlFor="item-image">Gambar utama</Label>
                <Input
                    ref={input}
                    id="item-image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    disabled={disabled}
                    aria-invalid={Boolean(error)}
                    aria-describedby="item-image-limit"
                    className="max-w-sm"
                    onChange={(event) => {
                        const chosen = event.target.files?.[0];
                        if (!chosen) return;
                        if (!['image/jpeg', 'image/png', 'image/webp'].includes(chosen.type) || chosen.size > 2 * 1024 * 1024) {
                            event.target.value = '';
                            onError('Pilih JPG, PNG atau WebP maksimal 2 MB.');
                            return;
                        }
                        onChange(chosen, false);
                    }}
                />
                <p id="item-image-limit" className="text-muted-foreground text-xs">
                    JPG, PNG, WebP. Maks. 2 MB, 4096 x 4096 piksel.
                </p>
                <InputError message={error} />
            </div>
            <div className="flex shrink-0 gap-1">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            aria-label="Hapus gambar"
                            disabled={disabled || removed || (!file && !currentUrl)}
                            onClick={() => reset(Boolean(currentUrl))}
                        >
                            <Trash2 className="size-4 text-rose-600 dark:text-rose-300" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Hapus gambar</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            aria-label="Batalkan perubahan gambar"
                            disabled={disabled || (!file && !removed)}
                            onClick={() => reset(false)}
                        >
                            <RotateCcw className="size-4 text-amber-600 dark:text-amber-300" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Batalkan perubahan gambar</TooltipContent>
                </Tooltip>
            </div>
        </div>
    );
}
