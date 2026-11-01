@php($isEdit = $user->exists)

<form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}" class="space-y-4" x-data="{ showPassword: false }">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-danger">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-pad">
        <h2 class="section-title">Staff details</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="input @error('name') input-error @enderror" required autofocus autocomplete="name" placeholder="e.g. Maria Santos">
                @error('name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="username" class="label">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" class="input @error('username') input-error @enderror" required autocomplete="username" pattern="[A-Za-z0-9_-]+" maxlength="50" placeholder="e.g. maria">
                <p class="mt-1 text-[11px] text-muted">Letters, numbers, hyphens, and underscores only.</p>
                @error('username') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="input @error('email') input-error @enderror" required autocomplete="email" placeholder="name@example.com">
                @error('email') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="label">Role</label>
                <select id="role" name="role" class="input @error('role') input-error @enderror" required>
                    <option value="cashier" @selected(old('role', $user->role) === 'cashier')>Cashier</option>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrator</option>
                </select>
                <p class="mt-1 text-[11px] text-muted">Administrators can manage products, staff, settings, and all sales.</p>
                @error('role') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="label">Account status</span>
                <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-line bg-white px-3.5 py-2.5">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-line text-orange focus:ring-orange" @checked(old('is_active', $user->is_active ?? true))>
                    <span>
                        <span class="block text-sm font-medium text-ink">Active account</span>
                        <span class="block text-[11px] text-muted">Inactive staff cannot sign in.</span>
                    </span>
                </label>
                @error('is_active') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            @unless ($isEdit)
                <div class="sm:col-span-2 border-t border-line pt-4">
                    <h3 class="text-sm font-semibold text-ink">Set a password</h3>
                    <p class="mt-0.5 text-xs text-muted">Share it with the staff member and ask them to keep it private.</p>
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <div class="relative">
                        <input id="password" name="password" :type="showPassword ? 'text' : 'password'" class="input pr-12 @error('password') input-error @enderror" required autocomplete="new-password" minlength="8">
                        <button type="button" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-muted hover:text-ink" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                            <span x-show="!showPassword"><x-icon name="eye" class="h-4 w-4" /></span>
                            <span x-show="showPassword" x-cloak><x-icon name="eye-off" class="h-4 w-4" /></span>
                        </button>
                    </div>
                    <p class="mt-1 text-[11px] text-muted">At least 8 characters.</p>
                    @error('password') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" :type="showPassword ? 'text' : 'password'" class="input" required autocomplete="new-password" minlength="8">
                </div>
            @endunless
        </div>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check" class="h-4 w-4" />
            {{ $isEdit ? 'Save changes' : 'Add staff member' }}
        </button>
    </div>
</form>
