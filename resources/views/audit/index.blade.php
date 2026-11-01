@extends('layouts.app')

@section('title', 'Audit log')
@section('eyebrow', 'Accountability')

@section('content')
    @php
        $hasFilters = $filters['search'] !== ''
            || $filters['action'] !== ''
            || $filters['user'] !== ''
            || $filters['date_from']
            || $filters['date_to'];
        $badgeFor = function (string $action): string {
            return match (true) {
                str_starts_with($action, 'user.') => 'badge-orange',
                str_starts_with($action, 'sale.') => 'badge-success',
                str_starts_with($action, 'inventory.') => 'badge-warning',
                str_starts_with($action, 'settings.') => 'badge-warning',
                default => 'badge-neutral',
            };
        };
        $labelFor = fn (string $action): string => \Illuminate\Support\Str::headline(str_replace(['.', '_'], ' ', $action));
    @endphp

    <div class="mb-6">
        <h1 class="page-title">Audit log</h1>
        <p class="page-subtitle">An append-only record of staff, product, stock, settings, and sales activity.</p>
    </div>

    <form method="GET" action="{{ route('audit.index') }}" class="card p-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div class="relative xl:col-span-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input type="search" name="search" value="{{ $filters['search'] }}" class="input pl-9" placeholder="Search action, note, or IP" aria-label="Search audit log">
            </div>

            <select name="action" class="input" aria-label="Filter by action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $labelFor($action) }}</option>
                @endforeach
            </select>

            <select name="user" class="input" aria-label="Filter by staff member">
                <option value="">All staff</option>
                @foreach ($staff as $member)
                    <option value="{{ $member->id }}" @selected($filters['user'] === (string) $member->id)>
                        {{ $member->name }}{{ $member->is_active ? '' : ' (inactive)' }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input" aria-label="From date">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input" aria-label="To date">
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11px] text-muted">Entries cannot be edited or deleted.</p>
            <div class="flex flex-wrap gap-2">
                @if ($hasFilters)
                    <a href="{{ route('audit.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                @endif
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="list-filter" class="h-3.5 w-3.5" /> Apply filters
                </button>
            </div>
        </div>
    </form>

    @if ($logs->isEmpty())
        <x-empty-state
            icon="history"
            title="{{ $hasFilters ? 'No audit entries match those filters' : 'No activity recorded yet' }}"
            message="{{ $hasFilters ? 'Try a wider date range or clear the action and staff filters.' : 'Changes made by administrators and cashiers will appear here.' }}"
        />
    @else
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-muted">
            <p>Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries</p>
            <p>Newest activity first</p>
        </div>

        <div class="card mt-2 overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Staff</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <span class="text-sm text-ink">{{ $log->created_at->format('M j, Y') }}</span>
                                    <p class="mt-0.5 text-[11px] text-muted">{{ $log->created_at->format('g:i A') }}</p>
                                </td>
                                <td>
                                    <span class="text-sm font-medium text-ink">{{ $log->user?->name ?? 'System' }}</span>
                                    @if ($log->user)
                                        <p class="mt-0.5 text-[11px] capitalize text-muted">{{ $log->user->role }}</p>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $badgeFor($log->action) }}">{{ $labelFor($log->action) }}</span>
                                </td>
                                <td>
                                    <p class="max-w-md text-sm text-ink">{{ $log->description }}</p>
                                    @if ($log->ip_address)
                                        <p class="num mt-0.5 text-[10px] text-muted">IP {{ $log->ip_address }}</p>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->old_values || $log->new_values)
                                        <details class="group">
                                            <summary class="cursor-pointer list-none text-xs font-semibold text-orange-dark hover:text-orange">View changes</summary>
                                            <div class="mt-2 max-w-md space-y-2">
                                                @if ($log->old_values)
                                                    <div>
                                                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Before</p>
                                                        <pre class="mt-1 max-h-36 overflow-auto rounded-lg bg-slate-50 p-2 text-[10px] leading-relaxed text-ink scrollbar-thin">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                @endif
                                                @if ($log->new_values)
                                                    <div>
                                                        <p class="text-[10px] font-bold uppercase tracking-wide text-muted">After</p>
                                                        <pre class="mt-1 max-h-36 overflow-auto rounded-lg bg-slate-50 p-2 text-[10px] leading-relaxed text-ink scrollbar-thin">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-xs text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
@endsection
