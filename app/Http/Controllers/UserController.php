<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\ResetUserPasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $role = in_array($request->query('role'), [User::ROLE_ADMIN, User::ROLE_CASHIER], true)
            ? $request->query('role')
            : '';
        $status = in_array($request->query('status'), ['active', 'inactive'], true)
            ? $request->query('status')
            : '';

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($role !== '', fn ($query) => $query->where('role', $role))
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $activeAdmins = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->count();

        return view('users.index', [
            'users' => $users,
            'filters' => compact('search', 'role', 'status'),
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'activeAdmins' => $activeAdmins,
            ],
            'activeAdminCount' => $activeAdmins,
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'user' => new User(['role' => User::ROLE_CASHIER, 'is_active' => true]),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $user = User::query()->create($validated);

        AuditLog::record(
            $request->user(),
            'user.created',
            'Created staff account '.$user->name.' as '.$user->role.'.',
            $user,
            [],
            $user->only(['name', 'username', 'email', 'role', 'is_active'])
        );

        return redirect()
            ->route('users.index')
            ->with('success', $user->name.' can now sign in.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        return DB::transaction(function () use ($request, $user) {
            $this->lockStaff($user);
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active');

            if ($this->wouldRemoveLastActiveAdmin($user, $validated['role'], $validated['is_active'])) {
                return back()
                    ->withErrors(['role' => 'This is the last active administrator. Promote or activate another administrator first.'])
                    ->withInput();
            }

            if ($user->id === $request->user()->id && ! $validated['is_active']) {
                return back()
                    ->withErrors(['is_active' => 'You cannot deactivate your own account while signed in.'])
                    ->withInput();
            }

            $oldValues = $user->only(['name', 'username', 'email', 'role', 'is_active']);

            $user->update($validated);

            AuditLog::record(
                $request->user(),
                'user.updated',
                'Updated staff account '.$user->name.'.',
                $user,
                $oldValues,
                $user->only(['name', 'username', 'email', 'role', 'is_active'])
            );

            return redirect()
                ->route('users.index')
                ->with('success', $user->name.'’s account was updated.');
        }, 3);
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        return DB::transaction(function () use ($request, $user) {
            $this->lockStaff($user);
            $active = ! $user->is_active;

            if (! $active && $user->id === $request->user()->id) {
                return back()->with('error', 'You cannot deactivate your own account while signed in.');
            }

            if (! $active && $this->isLastActiveAdmin($user)) {
                return back()->with('error', 'This is the last active administrator. Activate another administrator first.');
            }

            $old = $user->is_active;
            $user->update(['is_active' => $active]);

            AuditLog::record(
                $request->user(),
                $active ? 'user.activated' : 'user.deactivated',
                ($active ? 'Activated' : 'Deactivated').' staff account '.$user->name.'.',
                $user,
                ['is_active' => $old],
                ['is_active' => $active]
            );

            return back()->with('success', $user->name.'’s account is now '.($active ? 'active' : 'inactive').'.');
        }, 3);
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => $request->validated('password')]);

        AuditLog::record(
            $request->user(),
            'user.password_reset',
            'Reset the password for staff account '.$user->name.'.',
            $user
        );

        return back()->with('success', 'The password for '.$user->name.' was reset.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        return DB::transaction(function () use ($request, $user) {
            $this->lockStaff($user);
            if ($user->id === $request->user()->id) {
                return back()->with('error', 'You cannot delete your own account while signed in.');
            }

            if ($this->isLastActiveAdmin($user)) {
                return back()->with('error', 'This is the last active administrator and cannot be deleted.');
            }

            $name = $user->name;
            $user->delete();

            AuditLog::record(
                $request->user(),
                'user.deleted',
                'Deleted staff account '.$name.'.',
                $user
            );

            return redirect()
                ->route('users.index')
                ->with('success', $name.'’s account was removed.');
        }, 3);
    }

    protected function lockStaff(User $user): void
    {
        // All admin-removal paths take the same locks in the same order.
        User::query()->orderBy('id')->lockForUpdate()->get();
        $user->refresh();
    }

    protected function isLastActiveAdmin(User $user): bool
    {
        if ($user->role !== User::ROLE_ADMIN || ! $user->is_active) {
            return false;
        }

        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->lockForUpdate()->get()->count() <= 1;
    }

    protected function wouldRemoveLastActiveAdmin(User $user, string $role, bool $active): bool
    {
        if (! $this->isLastActiveAdmin($user)) {
            return false;
        }

        return $role !== User::ROLE_ADMIN || ! $active;
    }
}
