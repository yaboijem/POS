@props(['message' => null, 'title' => null, 'action' => null, 'actionLabel' => 'Add one', 'icon' => 'package'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-line bg-white px-6 py-14 text-center']) }}>
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-orange-soft text-orange-dark">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    @if ($title)
        <h3 class="font-display text-base font-semibold text-ink">{{ $title }}</h3>
    @endif
    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $message }}</p>
    @endif
    @if ($action)
        <a href="{{ $action }}" class="btn btn-primary mt-5">
            <x-icon name="plus" class="h-4 w-4" />
            {{ $actionLabel }}
        </a>
    @endif
</div>
