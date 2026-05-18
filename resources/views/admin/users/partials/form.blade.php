<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex flex-col gap-1">
        <label for="name" class="text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $user?->name) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('name')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="email" class="text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $user?->email) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('email')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="phone" class="text-sm font-medium text-gray-700">Phone</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $user?->phone) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('phone')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="role_id" class="text-sm font-medium text-gray-700">Role</label>
        <select name="role_id" id="role_id"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
            <option value="">Choose role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $user?->role_id) === (string) $role->id)>
                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                </option>
            @endforeach
        </select>
        @error('role_id')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="password" class="text-sm font-medium text-gray-700">Password</label>
        <input type="password" name="password" id="password"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('password')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="password_confirmation" class="text-sm font-medium text-gray-700">Confirm Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
    </div>
</div>

<label class="inline-flex items-center gap-2 text-sm text-gray-700">
    <input type="checkbox" name="is_active" value="1" class="text-primary" @checked(old('is_active', $user?->is_active ?? true))>
    Active
</label>
