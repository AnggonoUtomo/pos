<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StockIssue extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['source_type', 'source_id', 'source_number', 'request_hash', 'warehouse_id', 'warehouse_code', 'warehouse_name', 'posted_at', 'posted_by'];
}
