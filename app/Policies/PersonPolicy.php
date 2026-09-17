<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PersonPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdministrator() ? true : null;
    }

    public function view(User $user, Person $person): bool
    {
        return $this->inScope($user, $person);
    }

    public function update(User $user, Person $person): bool
    {
        return $this->inScope($user, $person);
    }

    public function delete(User $user, Person $person): bool
    {
        return $this->inScope($user, $person);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function inScope(User $user, Person $target): bool
    {
        if (! $user->person_id) {
            return false;
        }

        $scope = collect([(int) $user->person_id]);
        $frontier = $scope->all();

        while ($frontier) {
            $ids = array_values(array_unique(array_map('intval', $frontier)));
            $frontier = [];

            $related = Person::query()
                ->where(function ($query) use ($ids): void {
                    $query->whereIn('id', $ids)
                        ->orWhereIn('father_id', $ids)
                        ->orWhereIn('mother_id', $ids)
                        ->orWhereIn('father_id', $ids)
                        ->orWhereIn('mother_id', $ids);
                })
                ->get(['id', 'father_id', 'mother_id']);

            $parentIds = $related->flatMap(fn (Person $person) => [$person->father_id, $person->mother_id])->filter();
            $childIds = $related->filter(fn (Person $person) => in_array((int) $person->father_id, $ids, true)
                || in_array((int) $person->mother_id, $ids, true))->pluck('id');

            $coupleIds = DB::table('couples')
                ->whereIn('husband_id', $ids)
                ->orWhereIn('wife_id', $ids)
                ->pluck('id');
            $spouseIds = DB::table('couples')
                ->whereIn('id', $coupleIds)
                ->get(['husband_id', 'wife_id'])
                ->flatMap(fn ($couple) => [$couple->husband_id, $couple->wife_id]);

            $next = $parentIds->merge($childIds)->merge($spouseIds)
                ->map(fn ($id) => (int) $id)
                ->reject(fn (int $id) => $scope->contains($id))
                ->unique()
                ->values();
            $scope = $scope->merge($next);
            $frontier = $next->all();
        }

        return $scope->contains((int) $target->id);
    }
}
