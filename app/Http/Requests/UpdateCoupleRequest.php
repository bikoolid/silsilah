<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoupleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'marriage_date' => ['nullable', 'date'],
            'divorce_date' => ['nullable', 'date', 'after_or_equal:marriage_date'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
