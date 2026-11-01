@props(['category', 'action', 'method' => 'POST', 'submitLabel' => 'Save category'])

@php
    $presets = ['#F97316', '#DC2626', '#F59E0B', '#16A34A', '#0EA5E9', '#3B82F6', '#8B5CF6', '#EC4899', '#64748B', '#1E293B'];
@endphp

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-5"
    x-data="{ color: @js(old('color', $category->color ?? '#F97316')) }"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="label">Category name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" class="input @error('name') input-error @enderror" placeholder="e.g. Rice Meals" maxlength="80" required autofocus>
        @error('name') <p class="error-text">{{ $message }}</p> @enderror
        <p class="hint">This is the label cashiers will tap to filter products.</p>
    </div>

    <div>
        <label for="color" class="label">Color</label>
        <div class="flex items-center gap-3">
            <input id="color" name="color" type="color" x-model="color" class="h-11 w-16 cursor-pointer rounded-lg border border-line bg-white p-1">
            <code class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold uppercase text-muted" x-text="color"></code>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($presets as $preset)
                <button type="button" class="h-7 w-7 rounded-full border-2 border-white ring-1 ring-line transition hover:scale-110" style="background-color: {{ $preset }}" @click="color = '{{ $preset }}'" aria-label="Use color {{ $preset }}"></button>
            @endforeach
        </div>
        @error('color') <p class="error-text">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="label">Description <span class="font-normal text-muted">(optional)</span></label>
        <input id="description" name="description" type="text" value="{{ old('description', $category->description) }}" class="input @error('description') input-error @enderror" placeholder="A short note about what belongs here" maxlength="255">
        @error('description') <p class="error-text">{{ $message }}</p> @enderror
    </div>

    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line bg-slate-50/60 p-4">
        <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-line text-orange focus:ring-orange/30" @checked(old('is_active', $category->is_active ?? true))>
        <span>
            <span class="block text-sm font-medium text-ink">Show this category on the POS</span>
            <span class="mt-0.5 block text-xs text-muted">Inactive categories are hidden from the cashier, but their products and history stay intact.</span>
        </span>
    </label>

    <div class="flex flex-col-reverse gap-2 border-t border-line pt-5 sm:flex-row sm:justify-end">
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="save" class="h-4 w-4" />
            {{ $submitLabel }}
        </button>
    </div>
</form>
