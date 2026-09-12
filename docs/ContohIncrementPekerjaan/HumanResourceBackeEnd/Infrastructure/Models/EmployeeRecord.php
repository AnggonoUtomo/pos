<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Infrastructure\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $primary_unit_id
 * @property string $employee_no
 * @property string $name
 * @property string|null $preferred_name
 * @property string $employment_type
 * @property string|null $position
 * @property string $status
 * @property Carbon|null $joined_on
 * @property Carbon|null $left_on
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, EmployeeUnitAssignmentRecord> $assignments
 */
final class EmployeeRecord extends Model
{
    use HasUlids;

    protected $table = 'employees';

    protected $guarded = [];

    /** @return HasMany<EmployeeUnitAssignmentRecord, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeUnitAssignmentRecord::class, 'employee_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'joined_on' => 'immutable_date',
            'left_on' => 'immutable_date',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
