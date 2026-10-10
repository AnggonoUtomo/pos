<?php

namespace App\Modules\Inventory\Units\Application\Actions;

use App\Modules\Inventory\Units\Application\Contracts\UnitRepository;
use App\Modules\Inventory\Units\Application\DTOs\CreateUnitData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateUnit
{
    public function __construct(private UnitRepository $units) {}

    public function handle(CreateUnitData $data): string
    {
        $values = Validator::make([
            'code' => trim($data->code),
            'name' => trim($data->name),
            'symbol' => trim($data->symbol),
        ], [
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:150'],
            'symbol' => ['required', 'string', 'max:16'],
        ])->validate();
        $normalized = new CreateUnitData($values['code'], $values['name'], $values['symbol'], $data->isActive);

        return DB::transaction(fn (): string => $this->units->create($normalized));
    }
}
