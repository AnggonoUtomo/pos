import { cn } from '@/lib/utils';
import { ImageIcon } from 'lucide-react';
import { useState } from 'react';

export function ItemImage({ src, name, className }: { src: string | null; name: string; className?: string }) {
    const [failedSource, setFailedSource] = useState<string | null>(null);
    return (
        <div className={cn('bg-muted/30 flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-md border', className)}>
            {src && src !== failedSource ? (
                <img src={src} alt={name} className="h-full w-full object-contain" loading="lazy" onError={() => setFailedSource(src)} />
            ) : (
                <ImageIcon aria-label={`Belum ada gambar ${name}`} className="size-5 text-teal-600/70 dark:text-teal-300/70" />
            )}
        </div>
    );
}
