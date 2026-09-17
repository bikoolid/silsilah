<?php

namespace App\Services;

use App\Models\Couple;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DashboardService
{
    public function summary(): array
    {
        $total = Person::query()->count();
        $living = Person::query()->whereNull('yod')->count();
        $deceased = Person::query()->whereNotNull('yod')->count();
        $gender = Person::query()
            ->selectRaw("COALESCE(gender, 'unknown') as gender_key, COUNT(*) as total")
            ->groupBy('gender')
            ->pluck('total', 'gender_key');
        $decades = Person::query()
            ->whereNotNull('yob')
            ->selectRaw('FLOOR(yob / 10) * 10 as decade, COUNT(*) as total')
            ->groupBy('decade')
            ->orderBy('decade')
            ->get()
            ->map(fn ($row) => ['label' => "{$row->decade}-".((int) $row->decade + 9), 'value' => (int) $row->total])
            ->values()
            ->all();

        return [
            'metrics' => [
                'totalPeople' => $total,
                'generations' => $this->generationDepth(),
                'couples' => Couple::query()->count(),
                'living' => $living,
                'deceased' => $deceased,
            ],
            'gender' => [
                'male' => (int) ($gender['male'] ?? 0),
                'female' => (int) ($gender['female'] ?? 0),
                'unknown' => (int) ($gender['unknown'] ?? 0),
            ],
            'birthDecades' => $decades,
            'events' => [
                'birthdays' => $this->upcomingEvents('dob'),
                'memorials' => $this->upcomingEvents('dod'),
            ],
            'recentPeople' => Person::query()
                ->latest('updated_at')
                ->limit(6)
                ->get(['id', 'full_name', 'gender', 'yob', 'yod', 'photo_path', 'updated_at'])
                ->map(fn (Person $person) => $this->person($person))
                ->values()
                ->all(),
        ];
    }

    private function generationDepth(): int
    {
        $people = Person::query()->get(['id', 'father_id', 'mother_id'])->keyBy('id');
        $memo = [];
        $visiting = [];
        $depth = function (int $id) use (&$depth, &$memo, &$visiting, $people): int {
            if (isset($memo[$id])) {
                return $memo[$id];
            }

            if (isset($visiting[$id])) {
                return 1;
            }

            $person = $people->get($id);
            if (! $person) {
                return 1;
            }

            $visiting[$id] = true;
            $parents = array_filter([(int) $person->father_id, (int) $person->mother_id]);
            $memo[$id] = empty($parents) ? 1 : 1 + max(array_map($depth, $parents));
            unset($visiting[$id]);

            return $memo[$id];
        };

        return $people->isEmpty() ? 0 : max(array_map($depth, $people->keys()->all()));
    }

    private function upcomingEvents(string $column): array
    {
        $today = Carbon::today();
        $end = $today->copy()->addMonths(2);

        $startKey = $today->month * 100 + $today->day;
        $endKey = $end->month * 100 + $end->day;

        return Person::query()
            ->whereNotNull($column)
            ->where(function ($query) use ($column, $startKey, $endKey): void {
                $expression = "MONTH({$column}) * 100 + DAY({$column})";
                if ($endKey >= $startKey) {
                    $query->whereRaw("{$expression} >= ? AND {$expression} <= ?", [$startKey, $endKey]);
                } else {
                    $query->whereRaw("{$expression} >= ? OR {$expression} <= ?", [$startKey, $endKey]);
                }
            })
            ->orderByRaw("MONTH({$column}), DAY({$column})")
            ->limit(6)
            ->get(['id', 'full_name', 'gender', 'yob', 'yod', 'photo_path', $column])
            ->map(fn (Person $person) => $this->person($person, $column))
            ->values()
            ->all();
    }

    private function person(Person $person, ?string $eventColumn = null): array
    {
        $data = [
            'id' => $person->id,
            'name' => $person->full_name,
            'gender' => $person->gender,
            'birthYear' => $person->yob,
            'deathYear' => $person->yod,
            'deceased' => $person->yod !== null,
            'photo' => $this->photoUrl($person->photo_path),
        ];

        if ($eventColumn) {
            $data['date'] = $person->{$eventColumn}?->format('Y-m-d');
        } else {
            $data['updatedAt'] = $person->updated_at?->toIso8601String();
        }

        return $data;
    }

    private function photoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return file_exists(public_path($path)) ? asset($path) : Storage::disk('public')->url($path);
    }
}
