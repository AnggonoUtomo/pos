<?php

namespace App\Modules\Platform\Hardware\Infrastructure\Persistence\Repositories;

use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;
use App\Modules\Platform\Hardware\Application\DTOs\LabelJobData;
use App\Modules\Platform\Hardware\Application\DTOs\PrinterProfileData;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseHardwareRepository implements HardwareRepository
{
    public function __construct(private TablePagination $pagination) {}

    private function profileData(object $row): PrinterProfileData
    {
        return new PrinterProfileData($row->id, $row->name, $row->printer_name, $row->gateway_host,
            json_decode($row->specification, true, flags: JSON_THROW_ON_ERROR), (bool) $row->is_active,
            (int) $row->version, $row->deleted_at);
    }

    public function profiles(bool $deleted = false): array
    {
        $rows = DB::table('hardware_printers')->when(! $deleted, fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('name')->orderBy('id')->limit(201)->get();
        abort_if($rows->count() > 200, 422, 'Maksimal 200 profil; arsipkan profil yang tidak digunakan.');

        return $rows->map(fn ($row) => $this->profileData($row)->toArray())->all();
    }

    public function profile(string $id, bool $lock = false): ?PrinterProfileData
    {
        $row = DB::table('hardware_printers')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first();

        return $row ? $this->profileData($row) : null;
    }

    public function saveProfile(array $data, ?string $id = null): PrinterProfileData
    {
        $spec = array_intersect_key($data, array_flip(['width_mm', 'height_mm', 'dpi', 'orientation']));
        $fields = ['name' => $data['name'], 'printer_name' => $data['printer_name'], 'gateway_host' => $data['gateway_host'],
            'specification' => json_encode($spec, JSON_THROW_ON_ERROR), 'is_active' => $data['is_active'], 'updated_at' => now()->utc()];
        if ($id === null) {
            $id = strtolower((string) Str::ulid());
            DB::table('hardware_printers')->insert(['id' => $id, ...$fields, 'version' => 1, 'created_at' => now()->utc()]);
        } else {
            DB::table('hardware_printers')->where('id', $id)->update([...$fields, 'version' => DB::raw('version + 1')]);
        }

        return $this->profile($id);
    }

    public function changeProfile(string $id, array $changes): PrinterProfileData
    {
        DB::table('hardware_printers')->where('id', $id)->update([...$changes, 'version' => DB::raw('version + 1'), 'updated_at' => now()->utc()]);

        return $this->profile($id);
    }

    public function hasOpenJobs(string $printerId): bool
    {
        return DB::table('hardware_label_jobs')->where('printer_id', $printerId)->whereIn('status', ['queued', 'sending'])->exists();
    }

    private function jobData(object $row): LabelJobData
    {
        $expiresAt = property_exists($row, 'expires_at') ? $row->expires_at : ($this->attempt($row->id)['expires_at'] ?? null);

        return new LabelJobData($row->id, $row->printer_id, (int) $row->created_by, $row->kind, $row->status,
            json_decode($row->snapshot, true, flags: JSON_THROW_ON_ERROR), json_decode($row->printer_snapshot, true, flags: JSON_THROW_ON_ERROR),
            (int) $row->copies, $row->original_job_id, $row->reason, CarbonImmutable::parse($row->created_at, 'UTC')->toIso8601String(),
            $row->request_hash, $expiresAt !== null ? CarbonImmutable::parse($expiresAt, 'UTC')->toIso8601String() : null);
    }

    public function findJobByKey(int $actorId, string $key): ?LabelJobData
    {
        $row = DB::table('hardware_label_jobs')->where('created_by', $actorId)->where('idempotency_key', $key)->first();

        return $row ? $this->jobData($row) : null;
    }

    public function job(string $id, bool $lock = false): ?LabelJobData
    {
        $row = DB::table('hardware_label_jobs')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first();

        return $row ? $this->jobData($row) : null;
    }

    public function createJob(array $data): LabelJobData
    {
        $id = strtolower((string) Str::ulid());
        DB::table('hardware_label_jobs')->insert(['id' => $id, ...$data,
            'snapshot' => json_encode($data['snapshot'], JSON_THROW_ON_ERROR),
            'printer_snapshot' => json_encode($data['printer_snapshot'], JSON_THROW_ON_ERROR),
            'status' => 'queued', 'created_at' => now()->utc(), 'updated_at' => now()->utc()]);

        return $this->job($id);
    }

    public function claim(string $jobId, string $hash, string $expiresAt): string
    {
        $id = strtolower((string) Str::ulid());
        DB::table('hardware_print_attempts')->insert(['id' => $id, 'job_id' => $jobId, 'claim_hash' => $hash,
            'expires_at' => $expiresAt, 'status' => 'sending', 'created_at' => now()->utc(), 'updated_at' => now()->utc()]);
        DB::table('hardware_label_jobs')->where('id', $jobId)->update(['status' => 'sending', 'updated_at' => now()->utc()]);

        return $id;
    }

    public function attempt(string $jobId): ?array
    {
        $row = DB::table('hardware_print_attempts')->where('job_id', $jobId)->first();

        return $row ? (array) $row : null;
    }

    public function complete(string $jobId, string $status, ?string $errorCode): LabelJobData
    {
        DB::table('hardware_label_jobs')->where('id', $jobId)->update(['status' => $status, 'updated_at' => now()->utc()]);
        DB::table('hardware_print_attempts')->where('job_id', $jobId)->update(['status' => $status, 'error_code' => $errorCode, 'updated_at' => now()->utc()]);

        return $this->job($jobId);
    }

    public function history(int $actorId, bool $all, string $search, string $status, int $page): array
    {
        $query = DB::table('hardware_label_jobs')->leftJoin('hardware_print_attempts', 'hardware_print_attempts.job_id', '=', 'hardware_label_jobs.id')
            ->select(['hardware_label_jobs.*', 'hardware_print_attempts.expires_at'])
            ->when(! $all, fn ($q) => $q->where('hardware_label_jobs.created_by', $actorId));
        if ($search !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtolower($search)).'%';
            $query->whereRaw("LOWER(hardware_label_jobs.id) LIKE ? ESCAPE '!'", [$pattern]);
        }
        $paginator = $query->when($status !== '', fn ($q) => $q->where('hardware_label_jobs.status', $status))
            ->orderByDesc('hardware_label_jobs.created_at')->orderByDesc('hardware_label_jobs.id')->paginate($this->pagination->perPage(), page: $page);

        return ['data' => $paginator->getCollection()->map(fn ($r) => $this->jobData($r)->toArray())->all(),
            'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(), 'total' => $paginator->total()]];
    }

    public function audit(string $event, int $actorId, array $metadata): void
    {
        $allowed = array_intersect_key($metadata, array_flip(['printer_id', 'job_id', 'original_job_id', 'status', 'copies', 'reason', 'version', 'changes']));
        $log = activity('platform.hardware')->causedBy($actorId)->event($event)->withProperties($allowed)->log('Aktivitas perangkat POS');
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit perangkat gagal disimpan.');
        }
    }
}
