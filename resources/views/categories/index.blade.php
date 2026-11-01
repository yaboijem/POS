@extends('layouts.app')

@section('title', 'Categories')
@section('eyebrow', 'Products')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Categories</h1>
            <p class="page-subtitle">The filters cashiers see on the POS. Reorder them to match your flow.</p>
        </div>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Add category
        </a>
    </div>

    @if ($categories->isEmpty())
        <x-empty-state
            icon="tags"
            title="No categories yet"
            message="Add a category such as Rice Meals, Ulam, or Drinks to start grouping products."
            :action="route('categories.create')"
            action-label="Add category"
        />
    @else
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-16">Order</th>
                            <th>Category</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr class="{{ $category->is_active ? '' : 'bg-slate-50/60' }}">
                                <td>
                                    <div class="flex items-center gap-1">
                                        <span class="num text-xs font-semibold text-muted">{{ $category->sort_order }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="h-9 w-9 shrink-0 rounded-lg border border-black/5" style="background-color: {{ $category->color }}"></span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-ink">{{ $category->name }}</p>
                                            @if ($category->description)
                                                <p class="truncate text-xs text-muted">{{ $category->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($category->products_count > 0)
                                        <a href="{{ route('products.index', ['category' => $category->id]) }}" class="badge badge-neutral hover:bg-slate-200">{{ $category->products_count }} {{ \Illuminate\Support\Str::plural('product', $category->products_count) }}</a>
                                    @else
                                        <span class="text-xs text-muted">No products</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($category->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-neutral">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <form method="POST" action="{{ route('categories.move', [$category, 'up']) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm" title="Move up" @disabled($loop->first)>
                                                <x-icon name="arrow-up" class="h-4 w-4" />
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('categories.move', [$category, 'down']) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm" title="Move down" @disabled($loop->last)>
                                                <x-icon name="arrow-down" class="h-4 w-4" />
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('categories.toggle', $category) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="btn btn-ghost btn-sm"
                                                title="{{ $category->is_active ? 'Deactivate' : 'Activate' }}"
                                                @if ($category->is_active)
                                                    onclick="event.preventDefault(); window.confirmAction({ title: 'Deactivate category?', message: @js('“'.$category->name.'” will be hidden from the POS. Existing products stay safe.'), confirmLabel: 'Deactivate', onConfirm: () => this.closest('form').requestSubmit() })"
                                                @endif
                                            >
                                                <x-icon :name="$category->is_active ? 'ban' : 'circle-check'" class="h-4 w-4" />
                                            </button>
                                        </form>

                                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-ghost btn-sm" title="Edit">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-sm text-danger hover:bg-red-50 hover:text-danger"
                                            title="Delete"
                                            onclick="window.confirmAction({ title: 'Delete category?', message: @js('“'.$category->name.'” will be removed. Categories with products cannot be deleted.'), confirmLabel: 'Delete category', onConfirm: () => document.getElementById('delete-category-{{ $category->id }}').requestSubmit() })"
                                        >
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                        </button>
                                        <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('categories.destroy', $category) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <p class="mt-3 text-xs text-muted">Categories with products cannot be deleted. Deactivate them or move the products first.</p>
    @endif
@endsection
