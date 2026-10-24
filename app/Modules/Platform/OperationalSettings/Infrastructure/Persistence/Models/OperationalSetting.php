<?php

namespace App\Modules\Platform\OperationalSettings\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['receipt_paper', 'backup_enabled', 'backup_time'];

    protected function casts(): array
    {
        return ['backup_enabled' => 'boolean', 'version' => 'integer'];
    }
}
