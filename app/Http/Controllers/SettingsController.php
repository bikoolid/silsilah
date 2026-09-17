<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SettingsController extends Controller
{
    public function index(): InertiaResponse
    {
        abort_unless(auth()->check(), 403);
        return Inertia::render('Settings/Index', [
            'user' => auth()->user()?->only('id', 'name', 'email'),
            'users' => auth()->check()
                ? User::query()->orderBy('name')->get(['id', 'name', 'email', 'person_id', 'role', 'created_at'])
                : [],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('success', 'Profil akun berhasil diperbarui.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        User::query()->create($data);

        return back()->with('success', 'Pengelola berhasil ditambahkan.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        abort_if($request->user()->is($user), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        $user->delete();

        return back()->with('success', 'Pengelola berhasil dihapus.');
    }

    public function downloadBackup(BackupService $backupService)
    {
        abort_unless(auth()->user()->isAdministrator(), 403);

        return Response::streamDownload(function () use ($backupService): void {
            echo json_encode($backupService->export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'karuhun-backup-'.now()->format('Y-m-d').'.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function inspectBackup(Request $request, BackupService $backupService): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);

        $request->validate(['backup' => ['required', 'file', 'mimes:json', 'max:10240']]);
        $summary = $backupService->inspect($request->file('backup'));

        return back()->with('backup_preview', $summary);
    }

    public function restoreBackup(Request $request, BackupService $backupService): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);

        $request->validate([
            'backup' => ['required', 'file', 'mimes:json', 'max:10240'],
            'confirmation' => ['required', 'accepted'],
        ]);
        $summary = $backupService->restore($request->file('backup'));

        return back()->with('success', "Restore selesai: {$summary['people']} Person dan {$summary['couples']} Couple.");
    }
}
