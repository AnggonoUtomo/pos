<?php

namespace App\Modules\Platform\AuditLog\Infrastructure\Persistence\Repositories;

use App\Models\User;
use App\Modules\Platform\AuditLog\Application\Contracts\AuditLogReader;
use App\Modules\Platform\AuditLog\Application\Services\AuditLogProjection;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseAuditLogReader implements AuditLogReader
{
    public function __construct(private TablePagination $pagination, private AuditLogProjection $projection) {}

    private function query(): Builder
    {
        $morph = (new User)->getMorphClass();

        return DB::table('activity_log as a')->leftJoin('users as actor', function ($join) use ($morph): void {
            $join->on('actor.id', '=', 'a.causer_id')->where('a.causer_type', $morph);
        });
    }

    private function columns(): array
    {
        return ['a.id', 'a.log_name', 'a.event', 'a.subject_id', 'a.causer_id', 'a.created_at',
            'a.properties', 'a.attribute_changes', 'actor.name as actor_name'];
    }

    private function row(object $row, bool $detail = false): array
    {
        $changes = $this->projection->changes($row->attribute_changes, $row->properties);
        $data = array_replace($changes['before'] ?? [], $changes['after'] ?? []);
        $module = AuditLogProjection::key($row->log_name);
        $event = AuditLogProjection::key($row->event);
        $subject = is_string($row->subject_id) && preg_match('/^[a-zA-Z0-9-]{1,64}$/D', $row->subject_id) ? $row->subject_id : null;
        $reference = $data['number'] ?? $data['sku'] ?? $data['code'] ?? $subject;
        $result = ['id' => (string) $row->id, 'occurred_at' => $row->created_at === null ? null : Carbon::parse($row->created_at, config('app.timezone'))->utc()->toISOString(),
            'actor_name' => $row->causer_id === null ? 'Sistem' : mb_substr($row->actor_name ?? 'Pengguna tidak tersedia', 0, 150),
            'module' => $module, 'module_name' => AuditLogProjection::MODULES[$module] ?? 'Module lain',
            'event' => $event, 'event_name' => AuditLogProjection::eventName($event), 'subject_id' => $subject,
            'reference' => is_scalar($reference) ? (string) $reference : $subject];

        return $detail ? $result + $changes + ['field_labels' => array_intersect_key(AuditLogProjection::FIELDS, $data)] : $result;
    }

    public function paginate(int $page, array $filters): array
    {
        $query = $this->query();
        foreach (['module' => 'a.log_name', 'event' => 'a.event'] as $key => $column) {
            if ($filters[$key] !== null) {
                $query->where($column, $filters[$key]);
            }
        }
        if ($filters['actor_id'] === 'system') {
            $query->whereNull('a.causer_id');
        } elseif ($filters['actor_id'] !== null) {
            $query->where('a.causer_type', (new User)->getMorphClass())->where('a.causer_id', $filters['actor_id']);
        }
        foreach (['from' => '>=', 'until' => '<'] as $key => $operator) {
            if ($filters[$key] !== null) {
                $query->where('a.created_at', $operator, $filters[$key]);
            }
        }
        if ($filters['search'] !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']).'%';
            $query->where(function (Builder $search) use ($like): void {
                $search->whereRaw("actor.name LIKE ? ESCAPE '!'", [$like])->orWhereRaw("a.subject_id LIKE ? ESCAPE '!'", [$like]);
                foreach (['a.properties', 'a.attribute_changes'] as $document) {
                    foreach (['', 'attributes->', 'old->', 'before->', 'after->'] as $section) {
                        foreach (['number', 'code', 'sku'] as $field) {
                            $column = $document.'->'.$section.$field;
                            $search->orWhereRaw($search->getGrammar()->wrap($column)." LIKE ? ESCAPE '!'", [$like]);
                        }
                    }
                }
            });
        }
        $result = $query->select($this->columns())->orderByDesc('a.created_at')->orderByDesc('a.id')->paginate($this->pagination->perPage(), page: $page);

        return ['data' => $result->getCollection()->map(fn ($row) => $this->row($row))->all(),
            'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => $result->perPage(), 'total' => $result->total()]];
    }

    public function detail(string $id): array
    {
        $row = $this->query()->select($this->columns())->where('a.id', $id)->first();
        abort_if($row === null, 404);

        return $this->row($row, true);
    }

    public function options(): array
    {
        $modules = DB::table('activity_log')->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name');
        $events = DB::table('activity_log')->whereNotNull('event')->distinct()->orderBy('event')->pluck('event');

        return ['modules' => $modules->filter(fn ($key) => AuditLogProjection::key($key) !== null)->map(fn ($key) => ['id' => $key, 'name' => AuditLogProjection::MODULES[$key] ?? 'Module lain'])->values()->all(),
            'events' => $events->filter(fn ($key) => AuditLogProjection::key($key) !== null)->map(fn ($key) => ['id' => $key, 'name' => AuditLogProjection::eventName($key).' ('.$key.')'])->values()->all(),
            'actors' => $this->query()->whereNotNull('actor.id')->select('actor.id', 'actor.name')->distinct()->orderBy('actor.name')->orderBy('actor.id')
                ->get()->map(fn ($row) => ['id' => (string) $row->id, 'name' => mb_substr($row->name, 0, 150)])->all()];
    }
}
