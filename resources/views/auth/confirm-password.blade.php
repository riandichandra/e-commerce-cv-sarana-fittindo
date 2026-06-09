<x-guest-layout>
    <div class="mb-8">
        <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8102e]">Konfirmasi Akses</p>
        <h1 class="mt-3 text-3xl font-black tracking-[-.03em] text-[#10233d]">Masukkan kata sandi</h1>
        <p class="mt-3 text-sm leading-6 text-[#657891]">
            Area ini membutuhkan konfirmasi kata sandi sebelum Anda melanjutkan.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="text-sm font-bold text-[#10233d]">Kata sandi</label>
            <div class="mt-2 flex items-center border border-[#f2c8d0] bg-[#fff7f8] px-4 focus-within:border-[#c8102e] focus-within:bg-white">
                <iconify-icon icon="mdi:lock-outline" class="mr-3 text-[#6b7c91]"></iconify-icon>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    class="h-12 w-full border-0 bg-transparent p-0 text-sm text-[#10233d] placeholder:text-[#8ea0b8] focus:ring-0">
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit"
            class="flex h-12 w-full items-center justify-center bg-[#c8102e] text-sm font-black uppercase tracking-[.18em] text-white transition hover:bg-[#9f0d24]">
            Konfirmasi
        </button>
    </form>
</x-guest-layout>
