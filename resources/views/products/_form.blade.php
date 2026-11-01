@props(['product', 'categories', 'action', 'method' => 'POST', 'submitLabel' => 'Save product'])

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    x-data="{
        imagePreview: @js($product->image_url),
        selling: {{ (float) old('selling_price', $product->selling_price ?? 0) }},
        cost: {{ (float) old('cost_price', $product->cost_price ?? 0) }},
        get margin() { return this.selling > 0 && this.cost > 0 ? (((this.selling - this.cost) / this.selling) * 100) : null; }
    }"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-pad lg:col-span-2">
            <h2 class="section-title">Product details</h2>
            <p class="mt-0.5 text-xs text-muted">The name and price are what cashiers see on the POS.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="label">Product name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" class="input @error('name') input-error @enderror" placeholder="e.g. Adobo Rice Meal" maxlength="120" required autofocus>
                    @error('name') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sku" class="label">Product code / SKU <span class="font-normal text-muted">(optional)</span></label>
                    <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}" class="input num @error('sku') input-error @enderror" placeholder="Leave blank to generate" maxlength="50">
                    @error('sku') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="category_id" class="label">Category</label>
                    <select id="category_id" name="category_id" class="input @error('category_id') input-error @enderror" required>
                        <option value="">Choose a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="label">Description <span class="font-normal text-muted">(optional)</span></label>
                    <textarea id="description" name="description" rows="3" class="input @error('description') input-error @enderror" placeholder="Short description shown to you and other admins" maxlength="2000">{{ old('description', $product->description) }}</textarea>
                    @error('description') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="section-title">Photo</h2>
            <p class="mt-0.5 text-xs text-muted">JPG, PNG, or WebP up to 2 MB.</p>

            <div class="mt-4 overflow-hidden rounded-xl border border-dashed border-line bg-slate-50">
                <template x-if="imagePreview">
                    <img :src="imagePreview" alt="Product photo preview" class="h-44 w-full object-cover">
                </template>
                <template x-if="!imagePreview">
                    <div class="flex h-44 flex-col items-center justify-center text-muted">
                        <x-icon name="image" class="h-9 w-9 text-slate-300" />
                        <p class="mt-2 text-xs">No photo yet</p>
                    </div>
                </template>
            </div>

            <label for="image" class="btn btn-secondary mt-3 w-full cursor-pointer">
                <x-icon name="upload" class="h-4 w-4" />
                Choose photo
                <input
                    id="image"
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="sr-only"
                    @change="const file = $event.target.files[0]; if (file) imagePreview = URL.createObjectURL(file)"
                >
            </label>
            @error('image') <p class="error-text">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card card-pad">
            <h2 class="section-title">Pricing</h2>
            <p class="mt-0.5 text-xs text-muted">All prices are in Philippine pesos. Price changes are logged.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="selling_price" class="label">Selling price</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-muted">₱</span>
                        <input id="selling_price" name="selling_price" type="number" min="0" step="0.01" x-model.number="selling" value="{{ old('selling_price', $product->selling_price) }}" class="input num pl-8 @error('selling_price') input-error @enderror" placeholder="0.00" required>
                    </div>
                    @error('selling_price') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cost_price" class="label">Cost price <span class="font-normal text-muted">(optional)</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-muted">₱</span>
                        <input id="cost_price" name="cost_price" type="number" min="0" step="0.01" x-model.number="cost" value="{{ old('cost_price', $product->cost_price) }}" class="input num pl-8 @error('cost_price') input-error @enderror" placeholder="0.00">
                    </div>
                    @error('cost_price') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-lg bg-orange-soft px-4 py-3">
                <span class="text-xs font-medium text-orange-dark">Estimated profit per unit</span>
                <span class="num text-sm font-bold text-orange-dark" x-text="margin === null ? 'Add a cost' : (new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(selling - cost) + ' · ' + margin.toFixed(0) + '%')"></span>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="section-title">Stock and unit</h2>
            <p class="mt-0.5 text-xs text-muted">Turn off tracking for meals cooked without a fixed count.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="unit" class="label">Unit of measurement</label>
                    <select id="unit" name="unit" class="input @error('unit') input-error @enderror" required>
                        @foreach (\App\Models\Product::UNITS as $unit)
                            <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>{{ ucfirst($unit) }}</option>
                        @endforeach
                    </select>
                    @error('unit') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="stock_quantity" class="label">Current stock</label>
                    @if ($product->exists)
                        <p class="num text-sm font-semibold">{{ quantity($product->stock_quantity) }} {{ $product->unit }}</p>
                        <a href="{{ route('inventory.index', ['search' => $product->name]) }}" class="text-xs font-semibold text-orange-dark">Change stock in Inventory</a>
                    @else
                        <input id="stock_quantity" name="stock_quantity" type="number" min="0" step="0.001" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" class="input num @error('stock_quantity') input-error @enderror" required>
                    @endif
                    @error('stock_quantity') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="low_stock_threshold" class="label">Low-stock alert level <span class="font-normal text-muted">(optional)</span></label>
                    <input id="low_stock_threshold" name="low_stock_threshold" type="number" min="0" step="0.001" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" class="input num @error('low_stock_threshold') input-error @enderror" placeholder="Uses the default {{ quantity(setting('low_stock_threshold', 5)) }} when blank">
                    @error('low_stock_threshold') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-lg border border-line bg-slate-50/60 p-3.5">
                <input type="checkbox" name="track_inventory" value="1" class="mt-0.5 h-4 w-4 rounded border-line text-orange focus:ring-orange/30" @checked(old('track_inventory', $product->track_inventory ?? false))>
                <span>
                    <span class="block text-sm font-medium text-ink">Track stock for this product</span>
                    <span class="mt-0.5 block text-xs text-muted">When on, checkout checks and deducts stock. When off, stock is ignored.</span>
                </span>
            </label>
        </div>
    </div>

    <div class="card card-pad mt-6">
        <label class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" name="is_available" value="1" class="mt-0.5 h-4 w-4 rounded border-line text-orange focus:ring-orange/30" @checked(old('is_available', $product->is_available ?? true))>
            <span>
                <span class="block text-sm font-medium text-ink">Available on the POS</span>
                <span class="mt-0.5 block text-xs text-muted">Turn this off to keep the product in your catalogue without selling it.</span>
            </span>
        </label>
    </div>

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="save" class="h-4 w-4" />
            {{ $submitLabel }}
        </button>
    </div>
</form>
