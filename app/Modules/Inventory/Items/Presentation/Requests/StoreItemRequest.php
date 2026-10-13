<?php

namespace App\Modules\Inventory\Items\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['sku', 'name', 'base_unit_id'] as $field) {
            if (is_string($this->input($field))) {
                $value = trim($this->input($field));
                $this->merge([$field => $field === 'base_unit_id' ? strtolower($value) : $value]);
            }
        }

        if (is_array($this->input('units'))) {
            $units = $this->input('units');
            foreach ($units as &$unit) {
                if (! is_array($unit)) {
                    continue;
                }
                if (is_string($unit['unit_id'] ?? null)) {
                    $unit['unit_id'] = strtolower(trim($unit['unit_id']));
                }
                if (is_string($unit['barcode'] ?? null)) {
                    $barcode = trim($unit['barcode']);
                    $unit['barcode'] = $barcode === '' ? null : $barcode;
                }
            }
            unset($unit);
            $this->merge(['units' => $units]);
        }
        if (is_array($this->input('prices'))) {
            $prices = $this->input('prices');
            foreach ($prices as &$price) {
                if (! is_array($price)) {
                    continue;
                }
                foreach (['unit_id', 'customer_level_id'] as $field) {
                    if (is_string($price[$field] ?? null)) {
                        $price[$field] = strtolower(trim($price[$field]));
                    }
                }
            }
            unset($price);
            $this->merge(['prices' => $prices]);
        }
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', Rule::unique('items', 'sku')],
            'name' => ['required', 'string', 'max:150'],
            'base_unit_id' => ['required', 'ulid'],
            'is_active' => ['sometimes', 'boolean'],
            'units' => ['sometimes', 'array', 'list', 'max:100'],
            'units.*' => ['array:unit_id,conversion_ratio,barcode,is_sale_unit,is_purchase_unit'],
            'units.*.unit_id' => ['required', 'ulid', 'distinct'],
            'units.*.conversion_ratio' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D'],
            'units.*.barcode' => ['nullable', 'string', 'max:64', 'distinct:ignore_case', Rule::unique('item_units', 'barcode')],
            'units.*.is_sale_unit' => ['sometimes', 'boolean'],
            'units.*.is_purchase_unit' => ['sometimes', 'boolean'],
            'prices' => ['sometimes', 'array', 'list', 'max:5000'],
            'prices.*' => ['array:unit_id,customer_level_id,amount'],
            'prices.*.unit_id' => ['required', 'ulid'],
            'prices.*.customer_level_id' => ['required', 'ulid'],
            'prices.*.amount' => ['required', 'string', 'regex:/^\d{1,18}(?:\.\d{1,2})?$/D'],
        ];
    }
}
