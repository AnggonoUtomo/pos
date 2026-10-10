<?php

namespace App\Modules\Inventory\Items\Presentation\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateItemRequest extends StoreItemRequest
{
    public function rules(): array
    {
        $rules = array_replace(parent::rules(), [
            'sku' => ['required', 'string', 'max:64', Rule::unique('items', 'sku')->ignore($this->route('id'))],
            'is_active' => ['required', 'boolean'],
        ]);
        if (is_array($this->input('units')) && array_is_list($this->input('units')) && count($this->input('units')) <= 100) {
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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $units = $this->input('units', []);
            if (! is_array($units) || ! array_is_list($units) || count($units) > 100) {
                return;
            }
            $seen = [];
            foreach ($units as $index => $unit) {
                $barcode = is_array($unit) ? ($unit['barcode'] ?? null) : null;
                if (! is_string($barcode) || $barcode === '') {
                    continue;
                }
                $key = mb_strtolower($barcode);
                if (isset($seen[$key])) {
                    $validator->errors()->add("units.{$seen[$key]}.barcode", 'Barcode tidak boleh berulang.');
                    $validator->errors()->add("units.{$index}.barcode", 'Barcode tidak boleh berulang.');
                }
                $seen[$key] = $index;
            }
        }];
    }
}
