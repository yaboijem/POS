<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::query()
                ->withCount(['products' => fn ($query) => $query->withTrashed()])
                ->ordered()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new Category(['color' => '#F97316', 'is_active' => true])]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create([
            'name' => $request->validated('name'),
            'slug' => Category::uniqueSlug($request->validated('name')),
            'color' => $request->validated('color'),
            'description' => $request->validated('description'),
            'sort_order' => (int) Category::query()->max('sort_order') + 1,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLog::record($request->user(), 'category.created', 'Created category '.$category->name, $category, [], $category->only(['name', 'color', 'is_active']));

        return redirect()->route('categories.index')->with('success', 'Category "'.$category->name.'" was added.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $old = $category->only(['name', 'color', 'description', 'is_active']);

        $category->fill([
            'name' => $request->validated('name'),
            'color' => $request->validated('color'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($category->isDirty('name')) {
            $category->slug = Category::uniqueSlug($category->name, $category->id);
        }

        $category->save();

        AuditLog::record($request->user(), 'category.updated', 'Updated category '.$category->name, $category, $old, $category->only(['name', 'color', 'description', 'is_active']));

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if (Product::query()->withTrashed()->where('category_id', $category->id)->exists()) {
            return back()->with('error', 'This category still has products. Move or archive those products first.');
        }

        AuditLog::record($request->user(), 'category.deleted', 'Deleted category '.$category->name, $category, $category->only(['name', 'color']));
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    public function toggle(Request $request, Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        AuditLog::record($request->user(), 'category.toggled', ($category->is_active ? 'Activated' : 'Deactivated').' category '.$category->name, $category, ['is_active' => ! $category->is_active], ['is_active' => $category->is_active]);

        return back()->with('success', $category->is_active ? 'Category activated.' : 'Category deactivated. Its products are hidden from the POS.');
    }

    public function move(Request $request, Category $category, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        DB::transaction(function () use ($category, $direction) {
            $ids = Category::query()->ordered()->pluck('id')->all();
            $index = array_search($category->id, $ids, true);

            if ($index === false) {
                return;
            }

            $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

            if ($swapWith < 0 || $swapWith >= count($ids)) {
                return;
            }

            [$ids[$index], $ids[$swapWith]] = [$ids[$swapWith], $ids[$index]];

            foreach ($ids as $position => $id) {
                Category::query()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return back()->with('success', 'Category order updated.');
    }
}
