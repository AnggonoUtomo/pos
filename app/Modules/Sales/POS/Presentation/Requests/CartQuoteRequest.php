<?php

namespace App\Modules\Sales\POS\Presentation\Requests;

use Illuminate\Validation\Validator;

class CartQuoteRequest extends CatalogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $lines = $this->input('lines');
        if (is_array($lines)) {
            foreach ($lines as &$line) {
                if (! is_array($line)) {
                    continue;
                }
                foreach (['item_id', 'unit_id'] as $key) {
                    if (is_string($line[$key] ?? null)) {
                        $line[$key] = strtolower(trim($line[$key]));
                    }
                }
            }
            unset($line);
            $this->merge(['lines' => $lines]);
        }
    }

    public function rules(): array
    {
        return ['pricing' => ['sometimes', 'array:discount_type,discount_value,tax_rate'],
            'pricing.discount_type' => ['sometimes', 'in:fixed,percent'], 'pricing.discount_value' => ['sometimes', 'string'], 'pricing.tax_rate' => ['sometimes', 'string'],
            'warehouse_id' => ['required', 'ulid'], 'customer_id' => ['required', 'ulid'],
            'lines' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'lines.*' => ['array:item_id,unit_id,quantity'], 'lines.*.item_id' => ['required', 'ulid'],
            'lines.*.unit_id' => ['required', 'ulid'], 'lines.*.quantity' => ['required', 'string', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/D']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['warehouse_id', 'customer_id', 'lines', 'pricing', '_token']) !== []) {
                $validator->errors()->add('cart', 'Hanya konteks, barang, satuan dan kuantitas yang boleh dikirim.');
            }
        }];
    }
}
