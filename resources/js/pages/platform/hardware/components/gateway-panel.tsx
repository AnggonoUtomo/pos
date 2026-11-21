import { Autocomplete } from '@/components/shared/autocomplete';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { LoaderCircle, Printer, RefreshCw, Wifi, WifiOff } from 'lucide-react';
import type { useQzGateway } from '../hooks/use-qz-gateway';

export function GatewayPanel({ connection, hosts, disabled }: { connection: ReturnType<typeof useQzGateway>; hosts: string[]; disabled: boolean }) {
    return (
        <section className="space-y-4 border-y border-[var(--app-border)] bg-[var(--app-panel)] p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="flex items-center gap-2 text-base font-semibold"><Wifi className="size-5 text-cyan-600 dark:text-cyan-300" />Gateway QZ Tray</h2>
                <Badge variant="outline" className={connection.connected ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'}>
                    {connection.connected ? <Wifi className="mr-1 size-3.5" /> : <WifiOff className="mr-1 size-3.5" />}
                    {connection.connected ? 'Gateway terhubung' : 'Belum terhubung'}
                </Badge>
            </div>
            <div className="flex flex-wrap items-end gap-3">
                <div className="min-w-48 flex-1 space-y-2">
                    <Label htmlFor="qz-host">Gateway</Label>
                    <Autocomplete id="qz-host" value={connection.host} onValueChange={connection.setHost}
                        options={hosts.map((host) => ({ value: host, label: host }))} disabled={disabled || connection.busy} />
                </div>
                <Button onClick={() => void connection.discover()} disabled={disabled || connection.busy || !connection.host}>
                    {connection.busy ? <LoaderCircle className="animate-spin" /> : <RefreshCw />}Cari printer
                </Button>
            </div>
            {connection.error && <p role="alert" className="text-destructive text-sm">{connection.error}</p>}
            {connection.connected && <div className="flex items-center gap-2 text-sm text-muted-foreground"><Printer className="size-4 text-violet-600 dark:text-violet-300" />
                {connection.printers.length} printer terdaftar pada OS. Kesiapan fisik belum dikonfirmasi.
            </div>}
        </section>
    );
}
