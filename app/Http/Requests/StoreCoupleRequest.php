<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoupleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'integer', 'different:person_id', 'exists:people,id'],
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'marriage_date' => ['nullable', 'date'],
            'divorce_date' => ['nullable', 'date', 'after_or_equal:marriage_date'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
