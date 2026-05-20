<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex flex-col gap-1">
        <label for="name" class="text-sm font-medium text-gray-700">Method Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $paymentMethod?->name) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('name')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="bank_name" class="text-sm font-medium text-gray-700">Bank Name</label>
        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $paymentMethod?->bank_name) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('bank_name')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="account_number" class="text-sm font-medium text-gray-700">Account Number</label>
        <input type="text" name="account_number" id="account_number"
            value="{{ old('account_number', $paymentMethod?->account_number) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('account_number')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="account_name" class="text-sm font-medium text-gray-700">Account Name</label>
        <input type="text" name="account_name" id="account_name"
            value="{{ old('account_name', $paymentMethod?->account_name) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('account_name')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="sort_order" class="text-sm font-medium text-gray-700">Sort Order</label>
        <input type="number" name="sort_order" id="sort_order" min="0"
            value="{{ old('sort_order', $paymentMethod?->sort_order ?? 0) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('sort_order')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

</div>

<div class="flex flex-col gap-1">
    <label for="instructions" class="text-sm font-medium text-gray-700">Payment Instructions</label>
    <textarea name="instructions" id="instructions" rows="4"
        class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">{{ old('instructions', $paymentMethod?->instructions) }}</textarea>
    @error('instructions')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<label class="inline-flex items-center gap-2 text-sm text-gray-700">
    <input type="checkbox" name="is_active" value="1" class="text-primary" @checked(old('is_active', $paymentMethod?->is_active ?? true))>
    Active
</label>
