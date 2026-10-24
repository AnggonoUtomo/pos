<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Platform\CompanySettings\Application\Contracts\CompanyProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPosReadiness extends Command
{
    protected $signature = 'pos:readiness {--production : Periksa konfigurasi deploy produksi juga}';

    protected $description = 'Periksa kesiapan runtime POS tanpa mengubah data atau menampilkan credential';

    public function handle(CompanyProfile $company): int
    {
        $failed = false;
        $check = function (bool $ok, string $label) use (&$failed): void {
            $this->line(($ok ? 'PASS: ' : 'FAIL: ').$label);
            $failed = $failed || ! $ok;
        };
        try {
            $check(DB::connection()->getDriverName() === 'mysql', 'Database MySQL terhubung');
            $check(is_file(public_path('build/manifest.json')), 'Build asset tersedia');
            $files = array_merge(glob(database_path('migrations/*.php')) ?: [], glob(app_path('Modules/*/*/Database/Migrations/*.php')) ?: []);
            $expected = array_map(fn ($file) => pathinfo($file, PATHINFO_FILENAME), $files);
            $check(array_diff($expected, DB::table('migrations')->pluck('migration')->all()) === [], 'Migration aplikasi lengkap');
            $check(! in_array('registration', config('fortify.features', []), true), 'Registrasi publik dimatikan');
            $check(User::query()->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'super-system')->where('guard_name', 'web'))->exists(), 'Super-system aktif tersedia');
            if (trim($company->read()['name']) === '') {
                if ($this->option('production')) {
                    $check(false, 'Identitas toko aktual belum diisi');
                } else {
                    $this->warn('PENDING OPERATOR: isi identitas toko pada Pengaturan toko.');
                }
            } else {
                $check(true, 'Identitas toko tersedia');
            }
            $layers = DB::table('fifo_layers')->select('warehouse_id', 'item_id')->selectRaw('SUM(remaining_quantity) AS q, SUM(remaining_value) AS v')->groupBy('warehouse_id', 'item_id');
            $invalid = DB::table('stock_balances as b')->leftJoinSub($layers, 'l', fn ($join) => $join->on('b.warehouse_id', '=', 'l.warehouse_id')->on('b.item_id', '=', 'l.item_id'))
                ->whereRaw('b.quantity < 0 OR b.total_value < 0 OR b.quantity != COALESCE(l.q, 0) OR b.total_value != COALESCE(l.v, 0)')->exists();
            $orphan = DB::table('fifo_layers as l')->leftJoin('stock_balances as b', fn ($join) => $join->on('b.warehouse_id', '=', 'l.warehouse_id')->on('b.item_id', '=', 'l.item_id'))->whereNull('b.id')->exists();
            $check(! $invalid && ! $orphan, 'Saldo dan FIFO konsisten, tanpa stok minus');
            if ($this->option('production')) {
                $check(app()->environment('production') && ! config('app.debug'), 'Environment production dan debug mati');
                $check(str_starts_with(config('app.url'), 'https://') && (bool) config('session.secure'), 'HTTPS dan cookie secure');
                $check(! is_file(public_path('hot')), 'Tidak bergantung pada Vite development server');
            }
            $this->line('MANUAL: persetujuan operator, printer nyata, salinan backup terpisah dan jadwal backup tetap wajib.');

            return $failed ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable) {
            $this->error('FAIL: runtime/schema belum siap. Periksa layanan dan migration; credential tidak ditampilkan.');

            return self::FAILURE;
        }
    }
}
