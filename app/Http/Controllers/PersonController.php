<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdateParentsRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use App\Services\FamilyRelationshipService;
use App\Services\FamilyTreeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PersonController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:50'],
            'life_status' => ['nullable', 'in:living,deceased'],
            'sort' => ['nullable', 'in:name,birth_year'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! Schema::hasTable('people')) {
            return Inertia::render('People/Index', [
                'people' => [
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 12,
                    'total' => 0,
                ],
                'filters' => $filters,
                'error' => 'Data anggota keluarga belum tersedia pada database yang terhubung.',
            ]);
        }

        $query = Person::query()
            ->select(['id', 'full_name', 'gender', 'yob', 'yod', 'photo_path', 'city'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('full_name', 'like', "%{$search}%"))
            ->when($filters['gender'] ?? null, fn (Builder $query, string $gender) => $query->where('gender', $gender))
            ->when(($filters['life_status'] ?? null) === 'living', fn (Builder $query) => $query->whereNull('yod'))
            ->when(($filters['life_status'] ?? null) === 'deceased', fn (Builder $query) => $query->whereNotNull('yod'));

        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';
        $query->orderBy($sort === 'birth_year' ? 'yob' : 'full_name', $direction);

        return Inertia::render('People/Index', [
            'people' => $this->transformPaginator($query->paginate(12)->withQueryString()),
            'filters' => $filters,
            'error' => null,
        ]);
    }

    public function show(int $person): Response
    {
        $personModel = Person::query()
            ->with([
                'father',
                'mother',
                'couplesAsHusband.wife',
                'couplesAsWife.husband',
            ])
            ->find($person);

        if (! $personModel) {
            return Inertia::render('People/Show', [
                'person' => null,
                'notFound' => true,
            ]);
        }
        Gate::authorize('view', $personModel);

        return Inertia::render('People/Show', [
            'person' => $this->personDetailData($personModel),
            'notFound' => false,
            'peopleOptions' => $this->peopleOptions($personModel->id),
            'coupleOptions' => $this->coupleOptions(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Person::class);

        return Inertia::render('People/Form', [
            'person' => null,
            'mode' => 'create',
        ]);
    }

    public function store(StorePersonRequest $request): RedirectResponse
    {
        Gate::authorize('create', Person::class);
        $data = $request->validated();
        unset($data['photo']);
        unset($data['remove_photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->storePhoto($request);
        }

        $person = Person::query()->create($data);

        return Redirect::route('people.show', $person)->with('success', 'Person berhasil ditambahkan.');
    }

    public function edit(Person $person): Response
    {
        Gate::authorize('update', $person);

        return Inertia::render('People/Form', [
            'person' => $this->personDetailData($person),
            'mode' => 'edit',
        ]);
    }

    public function update(UpdatePersonRequest $request, Person $person): RedirectResponse
    {
        Gate::authorize('update', $person);
        $data = $request->validated();
        unset($data['photo']);
        $removePhoto = (bool) ($data['remove_photo'] ?? false);
        unset($data['remove_photo']);

        if ($request->hasFile('photo')) {
            $oldPhoto = $person->photo_path;
            $data['photo_path'] = $this->storePhoto($request);
            $this->removePhoto($oldPhoto);
        } elseif ($removePhoto) {
            $this->removePhoto($person->photo_path);
            $data['photo_path'] = null;
        }

        $person->update($data);

        return Redirect::route('people.show', $person)->with('success', 'Profil Person berhasil diperbarui.');
    }

    public function destroy(Person $person): RedirectResponse
    {
        Gate::authorize('delete', $person);
        if ($person->father()->exists() || $person->mother()->exists()
            || $this->childrenQuery($person)->exists()
            || $person->couplesAsHusband()->exists()
            || $person->couplesAsWife()->exists()) {
            return Redirect::back()->withErrors(['person' => 'Person masih memiliki relasi. Hapus atau ubah relasi tersebut terlebih dahulu.']);
        }

        $person->delete();

        return Redirect::route('people.index')->with('success', 'Person berhasil dihapus.');
    }

    public function updateParents(
        UpdateParentsRequest $request,
        Person $person,
        FamilyRelationshipService $relationshipService
    ): RedirectResponse {
        Gate::authorize('update', $person);
        $data = $request->validated();
        foreach (['father_id', 'mother_id'] as $parentKey) {
            if (! empty($data[$parentKey])) {
                Gate::authorize('update', Person::query()->findOrFail($data[$parentKey]));
            }
        }
        if (! empty($data['parents_couple_id'])) {
            Gate::authorize('update', \App\Models\Couple::query()->findOrFail($data['parents_couple_id']));
        }
        $relationshipService->assignParents(
            $person,
            $data['father_id'] ?? null,
            $data['mother_id'] ?? null,
            $data['parents_couple_id'] ?? null
        );

        return Redirect::back()->with('success', 'Relasi orang tua berhasil diperbarui.');
    }

    public function tree(Request $request, FamilyTreeService $familyTreeService, ?int $person = null): Response
    {
        $filters = $request->validate(['generations' => ['nullable', 'integer', 'between:1,3']]);
        
        // Get all persons for dropdown
        $allPersons = Person::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'yob'])
            ->map(fn (Person $person) => [
                'id' => $person->id,
                'name' => $person->full_name,
                'birthYear' => $person->yob,
            ])
            ->all();

        // Only build tree if person is selected
        $tree = $person ? $familyTreeService->context($person, (int) ($filters['generations'] ?? 1)) : [
            'nodes' => [],
            'links' => [],
            'root' => null,
            'descendantDepth' => 1,
        ];

        if ($person && $tree['root'] !== null) {
            $treePeople = Person::query()->whereIn('id', collect($tree['nodes'])->pluck('id'))->get()->keyBy('id');
            $tree['nodes'] = collect($tree['nodes'])->map(function (array $node) use ($treePeople): array {
                $model = $treePeople->get($node['id']);
                $node['can'] = [
                    'edit' => $model ? Gate::allows('update', $model) : false,
                    'delete' => $model ? Gate::allows('delete', $model) : false,
                    'add_child' => $model ? Gate::allows('update', $model) : false,
                ];
                return $node;
            })->all();
        }

        return Inertia::render('FamilyTree/Index', [
            'tree' => $tree,
            'persons' => $allPersons,
            'notFound' => $person !== null && $tree['root'] === null,
        ]);
    }

    public function cemeteryMap(): Response
    {
        $locations = Person::query()
            ->whereNotNull('yod')
            ->whereNotNull('cemetery_location')
            ->get(['id', 'full_name', 'gender', 'yob', 'yod', 'photo_path', 'cemetery_location'])
            ->map(function (Person $person): ?array {
                $location = $person->cemetery_location;
                $latitude = is_array($location) ? filter_var($location['latitude'] ?? null, FILTER_VALIDATE_FLOAT) : false;
                $longitude = is_array($location) ? filter_var($location['longitude'] ?? null, FILTER_VALIDATE_FLOAT) : false;

                if ($latitude === false || $longitude === false
                    || $latitude < -90 || $latitude > 90
                    || $longitude < -180 || $longitude > 180) {
                    return null;
                }

                return [
                    'id' => $person->id,
                    'name' => $person->full_name,
                    'gender' => $person->gender,
                    'photo' => $person->photo_path,
                    'birthYear' => $person->yob,
                    'deathYear' => $person->yod,
                    'cemetery' => [
                        'name' => $location['name'] ?? null,
                        'address' => $location['address'] ?? null,
                        'latitude' => (float) $latitude,
                        'longitude' => (float) $longitude,
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();

        return Inertia::render('CemeteryMap/Index', ['locations' => $locations]);
    }

    private function transformPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn (Person $person) => $this->personData($person))->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function personData(Person $person): array
    {
        return [
            'id' => $person->id,
            'name' => $person->full_name,
            'gender' => $person->gender,
            'photo' => $this->photoUrl($person->photo_path),
            'birthYear' => $person->yob,
            'deathYear' => $person->yod,
            'city' => $person->city,
            'deceased' => $person->yod !== null,
        ];
    }

    private function storePhoto(StorePersonRequest $request): string
    {
        $file = $request->file('photo');
        $filename = $file->hashName();
        $directory = public_path('images');
        File::ensureDirectoryExists($directory);
        $file->move($directory, $filename);

        return 'images/'.$filename;
    }

    private function removePhoto(?string $path): void
    {
        if ($path && str_starts_with($path, 'images/')) {
            File::delete(public_path($path));
            Storage::disk('public')->delete($path);
        }
    }

    private function photoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return file_exists(public_path($path))
            ? asset($path)
            : Storage::disk('public')->url($path);
    }

    private function peopleOptions(int $exceptId): array
    {
        return Person::query()
            ->where('id', '!=', $exceptId)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'gender'])
            ->map(fn (Person $person) => [
                'id' => $person->id,
                'name' => $person->full_name,
                'gender' => $person->gender,
            ])->all();
    }

    private function coupleOptions(): array
    {
        return \App\Models\Couple::query()
            ->with(['husband:id,full_name', 'wife:id,full_name'])
            ->get()
            ->map(fn ($couple) => [
                'id' => $couple->id,
                'label' => trim(($couple->husband?->full_name ?? 'Person') . ' & ' . ($couple->wife?->full_name ?? 'Person')),
            ])->all();
    }

    private function personDetailData(Person $person): array
    {
        $familyCounts = $this->familyCounts($person);
        $spouses = collect($person->couplesAsHusband
            ->map(fn ($couple) => [
                'id' => $couple->id,
                'person' => $this->personData($couple->wife),
                'marriageDate' => $couple->marriage_date?->format('Y-m-d'),
                'divorceDate' => $couple->divorce_date?->format('Y-m-d'),
                'status' => $couple->status,
                'childrenCount' => Person::query()->where('parents_couple_id', $couple->id)->count(),
            ])
            ->all())
            ->merge($person->couplesAsWife->map(fn ($couple) => [
                'id' => $couple->id,
                'person' => $this->personData($couple->husband),
                'marriageDate' => $couple->marriage_date?->format('Y-m-d'),
                'divorceDate' => $couple->divorce_date?->format('Y-m-d'),
                'status' => $couple->status,
                'childrenCount' => Person::query()->where('parents_couple_id', $couple->id)->count(),
            ]))
            ->values();

        return [
            ...$this->personData($person),
            'birthDate' => $person->dob?->format('Y-m-d'),
            'deathDate' => $person->dod?->format('Y-m-d'),
            'address' => $person->address,
            'phone' => $person->phone,
            'father' => $person->father ? $this->personData($person->father) : null,
            'mother' => $person->mother ? $this->personData($person->mother) : null,
            'parents' => collect([$person->father, $person->mother])
                ->filter()
                ->map(fn (Person $parent) => $this->personData($parent))
                ->values(),
            'spouses' => $spouses,
            'children' => $this->childrenQuery($person)
                ->sortBy('birth_order')
                ->map(fn (Person $child) => $this->personData($child))
                ->values(),
            'siblings' => $this->siblingsFor($person)
                ->sortBy('birth_order')
                ->map(fn (Person $sibling) => $this->personData($sibling))
                ->values(),
            'cemetery' => $person->cemetery_location,
            'familyCounts' => $familyCounts,
            'can' => [
                'edit' => Gate::allows('update', $person),
                'delete' => Gate::allows('delete', $person),
                'add_child' => Gate::allows('update', $person),
            ],
        ];
    }

    private function familyCounts(Person $person): array
    {
        $people = Person::query()
            ->get(['id', 'father_id', 'mother_id'])
            ->keyBy('id');
        $childrenByParent = [];

        foreach ($people as $candidate) {
            foreach ([$candidate->father_id, $candidate->mother_id] as $parentId) {
                if ($parentId) {
                    $childrenByParent[(int) $parentId][] = (int) $candidate->id;
                }
            }
        }

        $directChildren = collect($childrenByParent[$person->id] ?? [])
            ->unique()
            ->values();
        $descendants = collect();
        $frontier = $directChildren->all();

        while ($frontier) {
            $next = collect($frontier)
                ->flatMap(fn (int $parentId) => $childrenByParent[$parentId] ?? [])
                ->map(fn (int $id) => (int) $id)
                ->reject(fn (int $id) => $id === (int) $person->id || $directChildren->contains($id) || $descendants->contains($id))
                ->unique()
                ->values();

            $descendants = $descendants->merge($next)->unique()->values();
            $frontier = $next->all();
        }

        $parentIds = collect([$person->father_id, $person->mother_id])
            ->filter()
            ->map(fn ($id) => (int) $id);
        $siblings = $parentIds
            ->flatMap(fn (int $parentId) => $childrenByParent[$parentId] ?? [])
            ->reject(fn (int $id) => $id === (int) $person->id)
            ->unique()
            ->values();

        return [
            'children' => $directChildren->count(),
            'descendants' => $descendants->count(),
            'siblings' => $siblings->count(),
        ];
    }

    private function siblingsFor(Person $person)
    {
        if (! $person->father_id && ! $person->mother_id) {
            return collect();
        }

        return Person::query()
            ->where('id', '!=', $person->getKey())
            ->where(function (Builder $query) use ($person): void {
                if ($person->father_id) {
                    $query->where('father_id', $person->father_id);
                }

                if ($person->mother_id) {
                    $person->father_id
                        ? $query->orWhere('mother_id', $person->mother_id)
                        : $query->where('mother_id', $person->mother_id);
                }
            })
            ->get();
    }

    private function childrenQuery(Person $person)
    {
        return Person::query()
            ->where(function (Builder $query) use ($person): void {
                $query->where('father_id', $person->id)
                    ->orWhere('mother_id', $person->id);
            })
            ->get();
    }
}
