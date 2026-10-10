<?php

namespace App\Modules\Inventory\Items\Presentation\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends StoreItemRequest
{
    public function rules(): array
    {
        $rules = array_replace(parent::rules(), [
            'sku' => ['required', 'string', 'max:64', Rule::unique('items', 'sku')->ignore($this->route('id'))],
            'is_active' => ['required', 'boolean'],
        ]);
        if (is_array($this->input('units'))) {
            foreach ($this->input('units') as $index => $unit) {
                $unitId = is_array($unit) ? ($unit['unit_id'] ?? null) : null;
                $rules["units.{$index}.barcode"] = ['nullable', 'string', 'max:64', Rule::unique('item_units', 'barcode')
                    ->where(fn (Builder $query) => $query->where(fn (Builder $row) => $row
                        ->where('item_id', '!=', $this->route('id'))->orWhere('unit_id', '!=', $unitId)))];
            }
        }
        $rules['units.*.barcode'] = ['nullable', 'string', 'max:64', 'distinct:ignore_case'];

        return $rules;
    }
}
