<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'dob' => ['nullable', 'date'],
            'yob' => ['nullable', 'integer', 'between:1,3000'],
            'dod' => ['nullable', 'date', 'after_or_equal:dob'],
            'yod' => ['nullable', 'integer', 'between:1,3000', 'gte:yob'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'photo_path' => ['nullable', 'string', 'max:1000'],
            'cemetery_location' => ['nullable', 'array'],
            'cemetery_location.name' => ['nullable', 'string', 'max:255'],
            'cemetery_location.address' => ['nullable', 'string', 'max:1000'],
            'cemetery_location.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'cemetery_location.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $person = $this->route('person');
            if (! $person instanceof Person || ! $this->filled('yob')) {
                return;
            }

            $birthYear = (int) $this->input('yob');
            $parents = [$person->father, $person->mother];
            foreach ($parents as $parent) {
                if ($parent?->yob !== null && $birthYear <= $parent->yob) {
                    $validator->errors()->add('yob', 'Tahun lahir Person harus lebih besar dari tahun lahir orang tua.');
                    break;
                }
            }

            $hasOlderChild = Person::query()
                ->where(function ($query) use ($person): void {
                    $query->where('father_id', $person->id)
                        ->orWhere('mother_id', $person->id);
                })
                ->whereNotNull('yob')
                ->where('yob', '<=', $birthYear)
                ->exists();

            if ($hasOlderChild) {
                $validator->errors()->add('yob', 'Tahun lahir orang tua harus lebih kecil dari tahun lahir anak.');
            }
        });
    }
}
