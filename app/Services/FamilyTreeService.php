<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Support\Collection;

class FamilyTreeService
{
    public function context(?int $rootId = null, int $descendantDepth = 1): array
    {
        $descendantDepth = max(1, min($descendantDepth, 3));
        $root = $rootId
            ? Person::query()->find($rootId)
            : Person::query()->orderBy('full_name')->first();

        if (! $root) {
            return ['root' => null, 'nodes' => [], 'links' => []];
        }

        $parentIds = collect([$root->father_id, $root->mother_id])->filter()->unique()->values();
        $people = collect([$root])->merge(
            $parentIds->isEmpty() ? collect() : Person::query()->whereIn('id', $parentIds)->get()
        )->unique('id');
        $frontier = [$root];
        for ($level = 1; $level <= $descendantDepth; $level++) {
            $frontierIds = collect($frontier)->pluck('id');
            $next = $frontierIds->isEmpty() ? collect() : Person::query()
                ->where(fn ($query) => $query
                    ->whereIn('father_id', $frontierIds)
                    ->orWhereIn('mother_id', $frontierIds))
                ->get();
            $people = $people->merge($next)->unique('id');
            $frontier = $next->all();
        }

        $includedIds = $people->pluck('id');
        $rootCouples = \App\Models\Couple::query()
            ->where(fn ($query) => $query
                ->where('husband_id', $root->id)
                ->orWhere('wife_id', $root->id))
            ->get();
        $rootSpouseIds = $rootCouples->flatMap(fn ($couple) => [$couple->husband_id, $couple->wife_id])
            ->filter()
            ->unique()
            ->diff($includedIds);
        if ($rootSpouseIds->isNotEmpty()) {
            $people = $people->merge(Person::query()->whereIn('id', $rootSpouseIds)->get())->unique('id');
        }

        return [
            'root' => $root->id,
            'nodes' => $people->map(fn (Person $person) => $this->node($person, $root->id))->values()->all(),
            'descendantDepth' => $descendantDepth,
            'links' => $this->links($root, $people, $rootCouples)->values()->all(),
        ];
    }

    private function node(Person $person, int $rootId): array
    {
        return [
            'id' => $person->id,
            'name' => $person->full_name,
            'gender' => $person->gender,
            'photo' => $person->photo_path
                ? (str_starts_with($person->photo_path, 'http') || str_starts_with($person->photo_path, '/')
                    ? $person->photo_path
                    : asset($person->photo_path))
                : null,
            'birthYear' => $person->yob,
            'deathYear' => $person->yod,
            'deceased' => $person->yod !== null,
            'isRoot' => $person->id === $rootId,
        ];
    }

    private function links(Person $root, Collection $people, Collection $couples): Collection
    {
        $ids = $people->pluck('id');
        $links = collect();

        foreach ([$root->father_id, $root->mother_id] as $parentId) {
            if ($parentId && $ids->contains($parentId)) {
                $links->push(['source' => $parentId, 'target' => $root->id, 'type' => 'parent-child']);
            }
        }

        foreach ($people as $child) {
            foreach (['father_id', 'mother_id'] as $parentKey) {
                $parentId = $child->{$parentKey};
                if ($parentId && $ids->contains($parentId) && $ids->contains($child->id)) {
                    $links->push(['source' => $parentId, 'target' => $child->id, 'type' => 'parent-child']);
                }
            }
        }

        foreach ($couples as $couple) {
            if ($ids->contains($couple->husband_id) && $ids->contains($couple->wife_id)) {
                $links->push(['source' => $couple->husband_id, 'target' => $couple->wife_id, 'type' => 'spouse']);
            }
        }

        return $links->unique(fn (array $link) => "{$link['type']}:{$link['source']}:{$link['target']}");
    }
}
