<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'father_id' => ['nullable', 'integer', 'exists:people,id'],
            'mother_id' => ['nullable', 'integer', 'exists:people,id'],
            'parents_couple_id' => ['nullable', 'integer', 'exists:couples,id'],
        ];
    }
}
