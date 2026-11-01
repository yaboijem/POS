@extends('layouts.app')

@section('title', 'Users')
@section('eyebrow', 'Staff')

@section('content')
    @php
        $hasFilters = $filters['search'] !== '' || $filters['role'] !== '' || $filters['status'] !== '';
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Users</h1>
            <p class="page-subtitle">Cashier and administrator accounts. Only administrators can manage staff access.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Add staff member
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Staff accounts" :value="number_format($stats['total'])" icon="users" hint="Not deleted" />
        <x-stat-card label="Active accounts" :value="number_format($stats['active'])" icon="badge-check" hint="Can sign in" />
        <x-stat-card label="Active administrators" :value="number_format($stats['activeAdmins'])" icon="shield-check" hint="At least one is required" />
    </div>

    <form method="GET" action="{{ route('users.index') }}" class="card mt-4 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative sm:col-span-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input type="search" name="search" value="{{ $filters['search'] }}" class="input pl-9" placeholder="Search name, username, or email" aria-label="Search staff">
            </div>

            <select name="role" class="input" aria-label="Filter by role">
                <option value="">All roles</option>
                <option value="admin" @selected($filters['role'] === 'admin')>Administrators</option>
                <option value="cashier" @selected($filters['role'] === 'cashier')>Cashiers</option>
            </select>

            <select name="status" class="input" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="active" @selected($filters['status'] === 'active')>Active</option>
                <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
            </select>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
            @if ($hasFilters)
                <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
            <button type="submit" class="btn btn-secondary btn-sm">
                <x-icon name="list-filter" class="h-3.5 w-3.5" /> Apply filters
            </button>
        </div>
    </form>

    @if ($users->isEmpty())
        <x-empty-state
            icon="users"
            title="{{ $hasFilters ? 'No staff match those filters' : 'No staff accounts yet' }}"
            message="{{ $hasFilters ? 'Try a different search or clear the role and status filters.' : 'Add a cashier account so your team can start using the POS.' }}"
            :action="route('users.create')"
            action-label="Add staff member"
        />
    @else
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-muted">
            <p>Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} staff accounts</p>
            <p>{{ $stats['active'] }} active {{ \Illuminate\Support\Str::plural('account', $stats['active']) }}</p>
        </div>

        <div class="card mt-2 overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Staff member</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last sign-in</th>
                            <th>Added</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $staff)
                            @php
                                $isSelf = $staff->id === auth()->id();
                                $isLastAdmin = $staff->role === 'admin'
                                    && $staff->is_active
                                    && $activeAdminCount <= 1;
                                $canToggle = ! $isSelf && ! $isLastAdmin;
                                $initials = str($staff->name)->explode(' ')->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
                            @endphp
                            <tr class="{{ $staff->is_active ? '' : 'bg-slate-50/60' }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $staff->is_active ? 'bg-orange-soft text-orange-dark' : 'bg-slate-100 text-muted' }} text-xs font-bold">
                                            {{ $initials }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="flex flex-wrap items-center gap-1.5 font-semibold text-ink">
                                                {{ $staff->name }}
                                                @if ($isSelf)
                                                    <span class="badge badge-orange">You</span>
                                                @endif
                                            </p>
                                            <p class="mt-0.5 truncate text-xs text-muted">{{ '@'.$staff->username }} · {{ $staff->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $staff->role === 'admin' ? 'badge-orange' : 'badge-neutral' }}">
                                        {{ $staff->role === 'admin' ? 'Administrator' : 'Cashier' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($staff->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-neutral">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($staff->last_login_at)
                                        <span class="text-sm text-ink">{{ $staff->last_login_at->diffForHumans() }}</span>
                                        <p class="mt-0.5 text-[11px] text-muted">{{ $staff->last_login_at->format('M j, Y g:i A') }}</p>
                                    @else
                                        <span class="text-sm text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-sm text-ink">{{ $staff->created_at->format('M j, Y') }}</span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <a href="{{ route('users.edit', $staff) }}" class="btn btn-ghost btn-sm">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" /> Edit
                                        </a>
                                        <a href="{{ route('users.edit', $staff) }}#reset-password" class="btn btn-ghost btn-sm" title="Reset password">
                                            <x-icon name="key-round" class="h-3.5 w-3.5" /> Password
                                        </a>

                                        @if ($canToggle)
                                            <form
                                                method="POST"
                                                action="{{ route('users.status', $staff) }}"
                                                class="inline"
                                                x-data="{
                                                    confirmToggle() {
                                                        window.confirmAction({
                                                            title: @js($staff->is_active ? 'Deactivate '.$staff->name.'?' : 'Activate '.$staff->name.'?'),
                                                            message: @js($staff->is_active
                                                                ? 'They will be signed out and will not be able to sign in until the account is activated again.'
                                                                : 'They will be able to sign in to the POS again.'),
                                                            confirmLabel: @js($staff->is_active ? 'Deactivate account' : 'Activate account'),
                                                            tone: @js($staff->is_active ? 'danger' : 'primary'),
                                                            onConfirm: () => this.$el.submit(),
                                                        });
                                                    }
                                                }"
                                                @submit.prevent="confirmToggle()"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm {{ $staff->is_active ? 'text-danger hover:bg-red-50 hover:text-danger' : 'text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800' }}">
                                                    <x-icon name="{{ $staff->is_active ? 'x' : 'check' }}" class="h-3.5 w-3.5" />
                                                    {{ $staff->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        @else
                                            <button
                                                type="button"
                                                class="btn btn-ghost btn-sm cursor-not-allowed opacity-50"
                                                disabled
                                                title="{{ $isSelf ? 'You cannot deactivate your own account.' : 'This is the last active administrator.' }}"
                                            >
                                                <x-icon name="{{ $staff->is_active ? 'x' : 'check' }}" class="h-3.5 w-3.5" />
                                                {{ $staff->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    @endif
@endsection
