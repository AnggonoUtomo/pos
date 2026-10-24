<?php

namespace App\Modules\Platform\CompanySettings\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['version' => 'integer'];
    }
}
