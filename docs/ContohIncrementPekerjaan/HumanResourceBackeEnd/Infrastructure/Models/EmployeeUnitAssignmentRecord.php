<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $employee_id
 * @property string $organization_unit_id
 * @property string $role
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property bool $is_primary
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read EmployeeRecord $employee
 */
final class EmployeeUnitAssignmentRecord extends Model
{
    use HasUlids;

    protected $table = 'employee_unit_assignments';

    protected $guarded = [];

    /** @return BelongsTo<EmployeeRecord, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeRecord::class, 'employee_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'is_primary' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
