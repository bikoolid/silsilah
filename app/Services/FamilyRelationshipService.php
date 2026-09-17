<?php

namespace App\Services;

use App\Models\Couple;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyRelationshipService
{
    public function connectCouple(Person $person, int $partnerId, array $attributes): Couple
    {
        if ($person->id === $partnerId) {
            throw ValidationException::withMessages(['partner_id' => 'Person tidak dapat menjadi pasangan dirinya sendiri.']);
        }

        $exists = Couple::query()
            ->where(fn ($query) => $query
                ->where('husband_id', $person->id)->where('wife_id', $partnerId))
            ->orWhere(fn ($query) => $query
                ->where('husband_id', $partnerId)->where('wife_id', $person->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['partner_id' => 'Hubungan pasangan tersebut sudah tercatat.']);
        }

        $partner = Person::query()->findOrFail($partnerId);
        $male = $person->gender === 'male' ? $person : ($partner->gender === 'male' ? $partner : null);
        $female = $person->gender === 'female' ? $person : ($partner->gender === 'female' ? $partner : null);

        return DB::transaction(fn () => Couple::query()->create([
            'husband_id' => $male?->id ?? $person->id,
            'wife_id' => $female?->id ?? $partner->id,
            ...$attributes,
        ]));
    }

    public function assignParents(Person $child, ?int $fatherId, ?int $motherId, ?int $coupleId): void
    {
        $parents = Person::query()
            ->whereIn('id', array_values(array_filter([$fatherId, $motherId])))
            ->get()
            ->keyBy('id');

        foreach ([$fatherId, $motherId] as $parentId) {
            if ($parentId === $child->id) {
                throw ValidationException::withMessages(['father_id' => 'Person tidak dapat menjadi orang tuanya sendiri.']);
            }
            if ($parentId && $this->reaches($parentId, $child->id)) {
                throw ValidationException::withMessages(['father_id' => 'Relasi tersebut membentuk siklus keluarga.']);
            }
            if ($parentId && $parents->get($parentId)?->yob !== null && $child->yob !== null
                && $child->yob <= $parents->get($parentId)->yob) {
                throw ValidationException::withMessages([
                    'father_id' => 'Tahun lahir anak harus lebih besar dari tahun lahir orang tua.',
                ]);
            }
        }

        if ($coupleId) {
            $couple = Couple::query()->findOrFail($coupleId);
            if ($fatherId && ! in_array($fatherId, [$couple->husband_id, $couple->wife_id], true)) {
                throw ValidationException::withMessages(['parents_couple_id' => 'Couple tidak sesuai dengan ayah yang dipilih.']);
            }
            if ($motherId && ! in_array($motherId, [$couple->husband_id, $couple->wife_id], true)) {
                throw ValidationException::withMessages(['parents_couple_id' => 'Couple tidak sesuai dengan ibu yang dipilih.']);
            }
        }

        $child->update(['father_id' => $fatherId, 'mother_id' => $motherId, 'parents_couple_id' => $coupleId]);
    }

    public function addChild(Couple $couple, Person $child, ?int $birthOrder): void
    {
        if (($child->father_id && $child->father_id !== $couple->husband_id)
            || ($child->mother_id && $child->mother_id !== $couple->wife_id)) {
            throw ValidationException::withMessages([
                'child_id' => 'Person tersebut sudah memiliki orang tua yang berbeda.',
            ]);
        }

        $this->assignParents($child, $couple->husband_id, $couple->wife_id, $couple->id);
        if ($birthOrder !== null) {
            $child->update(['birth_order' => $birthOrder]);
        }
    }

    private function reaches(int $startId, int $targetId, array $visited = []): bool
    {
        if (in_array($startId, $visited, true)) {
            return false;
        }

        $visited[] = $startId;
        $person = Person::query()->select(['id', 'father_id', 'mother_id'])->find($startId);
        if (! $person) {
            return false;
        }

        foreach ([$person->father_id, $person->mother_id] as $parentId) {
            if ($parentId === $targetId || ($parentId && $this->reaches($parentId, $targetId, $visited))) {
                return true;
            }
        }

        return false;
    }
}
