<?php

namespace App\Services;

use App\Models\Couple;
use App\Models\Person;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BackupService
{
    public function export(): array
    {
        return [
            'format' => 'karuhun-json',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'people' => Person::query()->orderBy('id')->get()->map(fn (Person $person) => $person->only([
                'id', 'full_name', 'gender', 'father_id', 'mother_id', 'parents_couple_id',
                'dob', 'yob', 'dod', 'yod', 'birth_order', 'photo_path', 'address', 'phone',
                'city', 'cemetery_location', 'created_at', 'updated_at',
            ]))->all(),
            'couples' => Couple::query()->orderBy('id')->get()->map(fn (Couple $couple) => $couple->only([
                'id', 'husband_id', 'wife_id', 'marriage_date', 'divorce_date', 'status',
                'created_at', 'updated_at',
            ]))->all(),
        ];
    }

    public function inspect(UploadedFile $file): array
    {
        $payload = $this->decode($file);

        return [
            'people' => count($payload['people']),
            'couples' => count($payload['couples']),
            'version' => $payload['version'],
        ];
    }

    public function restore(UploadedFile $file): array
    {
        $payload = $this->decode($file);

        DB::transaction(function () use ($payload): void {
            Couple::query()->delete();
            Person::query()->update([
                'father_id' => null,
                'mother_id' => null,
                'parents_couple_id' => null,
            ]);
            Person::query()->delete();

            foreach ($payload['people'] as $person) {
                $person['father_id'] = null;
                $person['mother_id'] = null;
                $person['parents_couple_id'] = null;
                Person::query()->create($person);
            }

            foreach ($payload['couples'] as $couple) {
                Couple::query()->create($couple);
            }

            foreach ($payload['people'] as $person) {
                Person::query()->whereKey($person['id'])->update([
                    'father_id' => $person['father_id'] ?? null,
                    'mother_id' => $person['mother_id'] ?? null,
                    'parents_couple_id' => $person['parents_couple_id'] ?? null,
                ]);
            }
        });

        return [
            'people' => count($payload['people']),
            'couples' => count($payload['couples']),
        ];
    }

    private function decode(UploadedFile $file): array
    {
        $payload = json_decode($file->get(), true);

        if (! is_array($payload)
            || (($payload['format'] ?? null) !== 'karuhun-json')
            || (($payload['version'] ?? null) !== 1)
            || ! is_array($payload['people'] ?? null)
            || ! is_array($payload['couples'] ?? null)
            || count($payload['people']) > 100000
            || count($payload['couples']) > 100000) {
            throw ValidationException::withMessages([
                'backup' => 'File backup tidak valid atau bukan format Karuhun.',
            ]);
        }

        $people = $this->sanitizeRecords($payload['people'], [
            'id', 'full_name', 'gender', 'father_id', 'mother_id', 'parents_couple_id',
            'dob', 'yob', 'dod', 'yod', 'birth_order', 'photo_path', 'address', 'phone',
            'city', 'cemetery_location', 'created_at', 'updated_at',
        ]);
        $couples = $this->sanitizeRecords($payload['couples'], [
            'id', 'husband_id', 'wife_id', 'marriage_date', 'divorce_date', 'status',
            'created_at', 'updated_at',
        ]);
        $personIds = array_column($people, 'id');
        $coupleIds = array_column($couples, 'id');

        if (count($personIds) !== count(array_unique($personIds))
            || count($coupleIds) !== count(array_unique($coupleIds))
            || array_diff(array_filter(array_merge(
                array_column($people, 'father_id'),
                array_column($people, 'mother_id'),
            )), $personIds)
            || array_diff(array_filter(array_merge(
                array_column($people, 'parents_couple_id'),
            )), $coupleIds)
            || array_diff(array_filter(array_merge(
                array_column($couples, 'husband_id'),
                array_column($couples, 'wife_id'),
            )), $personIds)) {
            throw ValidationException::withMessages([
                'backup' => 'Referensi Person atau Couple pada file backup tidak konsisten.',
            ]);
        }

        $payload['people'] = $people;
        $payload['couples'] = $couples;

        return $payload;
    }

    private function sanitizeRecords(array $records, array $allowed): array
    {
        return array_map(function ($record) use ($allowed): array {
            if (! is_array($record) || ! isset($record['id']) || ! is_int($record['id']) || $record['id'] < 1) {
                throw ValidationException::withMessages([
                    'backup' => 'Format record backup tidak valid.',
                ]);
            }

            return array_intersect_key($record, array_flip($allowed));
        }, $records);
    }
}
