<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChildRequest;
use App\Http\Requests\StoreCoupleRequest;
use App\Http\Requests\UpdateCoupleRequest;
use App\Models\Couple;
use App\Models\Person;
use App\Services\FamilyRelationshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CoupleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $couples = Couple::query()
            ->with([
                'husband:id,full_name,gender,yob,yod,photo_path',
                'wife:id,full_name,gender,yob,yod,photo_path',
            ])
            ->withCount('children')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('husband', fn ($person) => $person->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('wife', fn ($person) => $person->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Couples/Index', [
            'couples' => [
                'data' => $couples->getCollection()->map(fn (Couple $couple) => $this->coupleData($couple))->values(),
                'current_page' => $couples->currentPage(),
                'last_page' => $couples->lastPage(),
                'total' => $couples->total(),
                'from' => $couples->firstItem(),
                'to' => $couples->lastItem(),
            ],
            'filters' => $filters,
            'statuses' => Couple::query()->whereNotNull('status')->where('status', '!=', '')->distinct()->orderBy('status')->pluck('status')->values(),
        ]);
    }

    public function store(StoreCoupleRequest $request, FamilyRelationshipService $relationshipService): RedirectResponse
    {
        $data = $request->validated();
        $person = Person::query()->findOrFail($data['person_id']);
        Gate::authorize('update', $person);
        Gate::authorize('update', Person::query()->findOrFail($data['partner_id']));
        $relationshipService->connectCouple($person, $data['partner_id'], [
            'marriage_date' => $data['marriage_date'] ?? null,
            'divorce_date' => $data['divorce_date'] ?? null,
            'status' => $data['status'] ?? null,
        ]);

        return Redirect::back()->with('success', 'Pasangan berhasil ditambahkan.');
    }

    public function update(UpdateCoupleRequest $request, Couple $couple): RedirectResponse
    {
        Gate::authorize('update', $couple);
        $couple->update($request->validated());

        return Redirect::back()->with('success', 'Informasi pasangan berhasil diperbarui.');
    }

    public function addChild(
        StoreChildRequest $request,
        Couple $couple,
        FamilyRelationshipService $relationshipService
    ): RedirectResponse {
        Gate::authorize('addChild', $couple);
        Gate::authorize('update', Person::query()->findOrFail($data['child_id']));
        $data = $request->validated();
        $relationshipService->addChild(
            $couple,
            Person::query()->findOrFail($data['child_id']),
            $data['birth_order'] ?? null
        );

        return Redirect::back()->with('success', 'Anak berhasil ditambahkan ke Couple.');
    }

    public function destroy(Couple $couple): RedirectResponse
    {
        Gate::authorize('delete', $couple);
        \DB::transaction(function () use ($couple): void {
            Person::query()->where('parents_couple_id', $couple->id)->update(['parents_couple_id' => null]);
            $couple->delete();
        });

        return Redirect::back()->with('success', 'Relasi pasangan berhasil diputus. Data orang tua dan anak tetap dipertahankan.');
    }

    private function coupleData(Couple $couple): array
    {
        return [
            'id' => $couple->id,
            'husband' => $this->personData($couple->husband),
            'wife' => $this->personData($couple->wife),
            'marriageDate' => $couple->marriage_date?->format('Y-m-d'),
            'divorceDate' => $couple->divorce_date?->format('Y-m-d'),
            'status' => $couple->status,
            'childrenCount' => $couple->children_count,
        ];
    }

    private function personData(?Person $person): ?array
    {
        if (! $person) {
            return null;
        }

        return [
            'id' => $person->id,
            'name' => $person->full_name,
            'gender' => $person->gender,
            'birthYear' => $person->yob,
            'deathYear' => $person->yod,
            'deceased' => $person->yod !== null,
            'photo' => $person->photo_path ? asset($person->photo_path) : null,
        ];
    }
}
