import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { BookOpen, Info, LoaderCircle, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import { resolveGuide } from './resolve';
import type { PageGuideContent } from './types';

const loaders = import.meta.glob<PageGuideContent>('/resources/js/pages/**/*.guide.ts', { import: 'default' });

export function PageGuide({ variant = 'floating' }: { variant?: 'floating' | 'toolbar' }) {
    const { component, props } = usePage<SharedData>();
    if (!props.auth.user) return null;
    return <ContextualGuide key={component} component={component} variant={variant} />;
}

function ContextualGuide({ component, variant }: { component: string; variant: 'floating' | 'toolbar' }) {
    const [open, setOpen] = useState(false);
    const [content, setContent] = useState<PageGuideContent | null>(null);
    const [failed, setFailed] = useState(false);
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        if (!open) return;
        let current = true;
        setFailed(false);
        void resolveGuide(component, loaders).then(
            (guide) => {
                if (current) setContent(guide);
            },
            () => {
                if (current) setFailed(true);
            },
        );
        return () => {
            current = false;
        };
    }, [open, component, attempt]);

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (event.key !== 'F1' || event.altKey || event.ctrlKey || event.metaKey) return;
            event.preventDefault();
            if (event.repeat || (!open && document.querySelector('[role="dialog"], [role="alertdialog"]'))) return;
            setOpen((value) => !value);
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open]);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <TooltipProvider delayDuration={250}>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <SheetTrigger asChild>
                            <Button
                                type="button"
                                variant={variant === 'floating' ? 'default' : 'outline'}
                                size="icon"
                                aria-label="Panduan halaman"
                                aria-keyshortcuts="F1"
                                data-page-guide-trigger={variant}
                                className={cn(
                                    'shrink-0 print:hidden',
                                    variant === 'floating'
                                        ? 'ring-background fixed right-5 bottom-5 z-40 size-11 rounded-full shadow-lg ring-2'
                                        : 'size-9 text-sky-600 dark:text-sky-300',
                                )}
                            >
                                <BookOpen className="size-5" />
                            </Button>
                        </SheetTrigger>
                    </TooltipTrigger>
                    <TooltipContent side={variant === 'floating' ? 'left' : 'bottom'}>Panduan halaman (F1)</TooltipContent>
                </Tooltip>
            </TooltipProvider>
            <SheetContent
                side="right"
                closeLabel="Tutup panduan"
                overlayClassName="bg-black/30 motion-reduce:animate-none!"
                className="flex h-dvh w-full flex-col gap-0 border-[var(--app-border)] bg-[var(--app-panel)] p-0 motion-reduce:animate-none! motion-reduce:transition-none! sm:max-w-lg"
            >
                <SheetHeader className="shrink-0 border-b border-[var(--app-border)] px-6 py-5 pr-14 text-left">
                    <div className="text-primary mb-1 flex items-center gap-2">
                        <BookOpen className="size-5" aria-hidden="true" />
                        <span className="text-xs font-semibold uppercase">Panduan Pengguna</span>
                    </div>
                    <SheetTitle>{content?.title ?? 'Panduan halaman'}</SheetTitle>
                    <SheetDescription>{content?.description ?? 'Langkah penggunaan untuk halaman yang sedang Anda buka.'}</SheetDescription>
                </SheetHeader>
                <div
                    role="region"
                    aria-label="Isi panduan"
                    aria-busy={!content && !failed}
                    tabIndex={0}
                    className="focus-visible:ring-ring min-h-0 flex-1 overflow-y-auto overscroll-contain px-6 py-5 focus-visible:ring-2 focus-visible:outline-none focus-visible:ring-inset"
                >
                    {failed ? (
                        <div role="alert" className="space-y-3 text-sm">
                            <p>Panduan belum dapat dimuat. Periksa koneksi, lalu coba lagi.</p>
                            <Button type="button" variant="outline" size="sm" onClick={() => setAttempt((value) => value + 1)}>
                                <RefreshCw className="text-sky-600 dark:text-sky-300" /> Coba lagi
                            </Button>
                        </div>
                    ) : content ? (
                        <div className="space-y-6">
                            {content.sections.map((section) => (
                                <section key={section.title} className="space-y-3">
                                    <h3 className="text-sm font-semibold">{section.title}</h3>
                                    <ol className="marker:text-primary list-decimal space-y-3 pl-5 text-sm leading-relaxed marker:font-semibold">
                                        {section.steps.map((step) => (
                                            <li key={step} className="pl-1">
                                                {step}
                                            </li>
                                        ))}
                                    </ol>
                                </section>
                            ))}
                            <aside className="border-l-2 border-amber-500 bg-amber-500/5 px-4 py-3">
                                <h3 className="mb-2 flex items-center gap-2 text-sm font-semibold">
                                    <Info className="size-4 text-amber-600 dark:text-amber-300" /> Perlu diperhatikan
                                </h3>
                                <ul className="list-disc space-y-2 pl-4 text-sm leading-relaxed">
                                    {content.notes.map((note) => (
                                        <li key={note}>{note}</li>
                                    ))}
                                </ul>
                            </aside>
                        </div>
                    ) : (
                        <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                            <LoaderCircle className="size-4 animate-spin motion-reduce:animate-none" /> Memuat panduan...
                        </p>
                    )}
                </div>
                <div className="text-muted-foreground shrink-0 border-t border-[var(--app-border)] px-6 py-3 text-xs">
                    Tekan Esc atau F1 untuk kembali. Menutup panduan tidak menghapus isian halaman.
                </div>
            </SheetContent>
        </Sheet>
    );
}
