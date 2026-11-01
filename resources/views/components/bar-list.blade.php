@props(['items', 'labelKey' => 'name', 'valueKey' => 'total', 'colorKey' => 'color', 'valueFormatter' => null])

@php
    $max = collect($items)->max(fn ($item) => (float) data_get($item, $valueKey)) ?: 1;
@endphp

<div class="space-y-3.5">
    @foreach ($items as $item)
        @php
            $value = (float) data_get($item, $valueKey);
            $label = data_get($item, $labelKey);
            $color = data_get($item, $colorKey) ?: '#F97316';
            $width = max(2, ($value / $max) * 100);
        @endphp
        <div>
            <div class="mb-1.5 flex items-baseline justify-between gap-3">
                <span class="truncate text-sm font-medium text-ink">{{ $label }}</span>
                <span class="num shrink-0 text-sm font-semibold text-ink">{{ $valueFormatter ? $valueFormatter($value) : peso($value) }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full" style="width: {{ $width }}%; background-color: {{ $color }}"></div>
            </div>
        </div>
    @endforeach
</div>
