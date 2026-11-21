<?php

namespace App\Modules\Platform\Hardware\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ResolveScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:64', 'regex:/^[\x20-\x7E]+$/D']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['code']) !== []) {
                $validator->errors()->add('code', 'Uji scanner hanya menerima kode barang.');
            }
        }];
    }

    public function messages(): array
    {
        return ['code.required' => 'Masukkan barcode atau SKU satuan jual.',
            'code.string' => 'Kode harus berupa teks.', 'code.max' => 'Kode maksimal 64 karakter.',
            'code.regex' => 'Kode hanya boleh berisi karakter teks ASCII yang dapat dicetak.'];
    }
}
