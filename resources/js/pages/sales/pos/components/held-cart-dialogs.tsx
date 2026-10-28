import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ChevronLeft, ChevronRight, LoaderCircle, PauseCircle, RotateCcw, Trash2 } from 'lucide-react';
import type { useHeldCarts } from '../hooks/use-held-carts';

export function HeldCartDialogs({ held }: { held: ReturnType<typeof useHeldCarts> }) {
    const protectedClose = (event: Event) => {
        if (held.busy || held.uncertain) event.preventDefault();
    };
    return (
        <TooltipProvider>
            <Dialog
                open={held.mode !== null}
                onOpenChange={(open) => {
                    if (!open) held.close();
                }}
            >
                <DialogContent
                    className="max-h-[90dvh] overflow-y-auto sm:max-w-2xl"
                    onEscapeKeyDown={protectedClose}
                    onPointerDownOutside={protectedClose}
                >
                    <DialogHeader>
                        <DialogTitle>{held.mode === 'hold' ? 'Tahan keranjang' : 'Keranjang tertahan'}</DialogTitle>
                        <DialogDescription className="sr-only">Keranjang kasir sendiri</DialogDescription>
                    </DialogHeader>
                    {held.mode === 'hold' ? (
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                held.save();
                            }}
                            className="space-y-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="held-label">Label keranjang (opsional)</Label>
                                <Input
                                    id="held-label"
                                    autoFocus
                                    value={held.label}
                                    maxLength={80}
                                    disabled={held.busy || held.uncertain}
                                    onChange={(event) => held.setLabel(event.target.value)}
                                />
                            </div>
                            {held.error && (
                                <p role="alert" className="text-destructive text-sm">
                                    {held.error}
                                </p>
                            )}
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={held.close} disabled={held.busy || held.uncertain}>
                                    Batal
                                </Button>
                                <Button type="submit" disabled={held.busy}>
                                    {held.busy ? <LoaderCircle className="animate-spin" /> : <PauseCircle />}
                                    {held.uncertain ? 'Ulangi simpan' : 'Simpan tertahan'}
                                </Button>
                            </DialogFooter>
                        </form>
                    ) : (
                        <div className="space-y-4">
                            {held.error && (
                                <p role="alert" className="text-destructive text-sm">
                                    {held.error}
                                </p>
                            )}
                            {held.busy && (
                                <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                                    <LoaderCircle className="size-4 animate-spin" />
                                    Memuat keranjang...
                                </p>
                            )}
                            {!held.busy && !held.records?.data.length && (
                                <p className="text-muted-foreground py-6 text-center text-sm">Belum ada keranjang tertahan.</p>
                            )}
                            <div className="divide-y">
                                {held.records?.data.map((row) => (
                                    <div key={row.id} className="flex items-center gap-3 py-3">
                                        <div className="min-w-0 flex-1 space-y-1">
                                            <p className="text-sm font-medium break-words">{row.label || 'Tanpa label'}</p>
                                            <p className="text-muted-foreground text-xs break-words">
                                                {row.customer_name} / {row.warehouse_name}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {row.line_count} baris /{' '}
                                                {new Date(row.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })}
                                            </p>
                                        </div>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    size="icon"
                                                    variant="outline"
                                                    aria-label={`Panggil ${row.label || row.id}`}
                                                    disabled={held.busy}
                                                    onClick={() => held.requestRecall(row)}
                                                >
                                                    <RotateCcw className="text-sky-600 dark:text-sky-300" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Panggil keranjang</TooltipContent>
                                        </Tooltip>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    aria-label={`Batalkan ${row.label || row.id}`}
                                                    disabled={held.busy}
                                                    onClick={() => held.requestCancel(row)}
                                                >
                                                    <Trash2 className="text-rose-600 dark:text-rose-300" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Batalkan keranjang</TooltipContent>
                                        </Tooltip>
                                    </div>
                                ))}
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <Button variant="outline" disabled={held.busy} onClick={() => void held.load(held.records?.meta.current_page ?? 1)}>
                                    <RotateCcw className="text-sky-600 dark:text-sky-300" />
                                    Muat ulang
                                </Button>
                                <div className="flex items-center gap-2">
                                    <span className="text-muted-foreground text-xs">Halaman {held.records?.meta.current_page ?? 1}</span>
                                    <Button
                                        size="icon"
                                        variant="outline"
                                        aria-label="Keranjang halaman sebelumnya"
                                        disabled={held.busy || !held.records || held.records.meta.current_page <= 1}
                                        onClick={() => void held.load(held.records!.meta.current_page - 1)}
                                    >
                                        <ChevronLeft />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="outline"
                                        aria-label="Keranjang halaman berikutnya"
                                        disabled={held.busy || !held.records || held.records.meta.current_page >= held.records.meta.last_page}
                                        onClick={() => void held.load(held.records!.meta.current_page + 1)}
                                    >
                                        <ChevronRight />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
            <Dialog
                open={held.confirmation !== null}
                onOpenChange={(open) => {
                    if (!open && !held.busy) held.setConfirmation(null);
                }}
            >
                <DialogContent onEscapeKeyDown={protectedClose} onPointerDownOutside={protectedClose}>
                    <DialogHeader>
                        <DialogTitle>{held.confirmation?.kind === 'recall' ? 'Ganti keranjang aktif?' : 'Batalkan keranjang tertahan?'}</DialogTitle>
                        <DialogDescription>
                            {held.confirmation?.kind === 'recall'
                                ? 'Isian keranjang aktif akan diganti. Perubahan yang belum ditahan tidak disimpan.'
                                : 'Keranjang ini tidak dapat dipanggil atau dibayar lagi.'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" disabled={held.busy} onClick={() => held.setConfirmation(null)}>
                            Kembali
                        </Button>
                        <Button disabled={held.busy} onClick={held.confirm}>
                            {held.busy && <LoaderCircle className="animate-spin" />}
                            {held.confirmation?.kind === 'recall' ? 'Panggil keranjang' : 'Batalkan keranjang'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </TooltipProvider>
    );
}
