@props(['label', 'value', 'icon' => 'package', 'hint' => null, 'delta' => null, 'tone' => 'orange'])

@php
    $toneClasses = match ($tone) {
        'success' => 'bg-green-50 text-green-700',
        'warning' => 'bg-amber-50 text-amber-700',
        'danger' => 'bg-red-50 text-red-700',
        default => 'bg-orange-soft text-orange-dark',
    };
@endphp

<div class="card card-pad stat-card">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-muted">{{ $label }}</p>
            <p class="stat-value mt-2 truncate">{{ $value }}</p>
        </div>
        <div class="stat-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $toneClasses }}">
            <x-icon :name="$icon" class="h-4 w-4" />
        </div>
    </div>
    @if ($delta !== null)
        <div class="mt-3 flex items-center gap-1.5 text-xs">
            <span class="inline-flex items-center gap-1 font-semibold {{ $delta['positive'] ? 'text-success' : 'text-danger' }}">
                <x-icon :name="$delta['positive'] ? 'trending-up' : 'trending-down'" class="h-3.5 w-3.5" />
                {{ $delta['value'] }}
            </span>
            <span class="text-muted">vs. yesterday</span>
        </div>
    @endif
    @if ($hint)
        <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif

</div>
