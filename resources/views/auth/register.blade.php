<x-guest-layout>
    <div class="mb-8">
        <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8102e]">Create Account</p>
        <h1 class="mt-3 text-3xl font-black tracking-[-.03em] text-[#10233d]">Daftar akun pelanggan</h1>
        <p class="mt-3 text-sm leading-6 text-[#657891]">
            Buat akun untuk mengakses katalog produk CV Sarana Fittindo dan mengelola kebutuhan material Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="text-sm font-bold text-[#10233d]">Name</label>
            <div class="mt-2 flex items-center border border-[#f2c8d0] bg-[#fff7f8] px-4 focus-within:border-[#c8102e] focus-within:bg-white">
                <iconify-icon icon="mdi:account-outline" class="mr-3 text-[#6b7c91]"></iconify-icon>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                    placeholder="Nama lengkap"
                    class="h-12 w-full border-0 bg-transparent p-0 text-sm text-[#10233d] placeholder:text-[#8ea0b8] focus:ring-0">
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="text-sm font-bold text-[#10233d]">Email</label>
            <div class="mt-2 flex items-center border border-[#f2c8d0] bg-[#fff7f8] px-4 focus-within:border-[#c8102e] focus-within:bg-white">
                <iconify-icon icon="mdi:email-outline" class="mr-3 text-[#6b7c91]"></iconify-icon>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                    placeholder="email@example.com"
                    class="h-12 w-full border-0 bg-transparent p-0 text-sm text-[#10233d] placeholder:text-[#8ea0b8] focus:ring-0">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="phone" class="text-sm font-bold text-[#10233d]">Phone</label>
            <div class="mt-2 flex items-center border border-[#f2c8d0] bg-[#fff7f8] px-4 focus-within:border-[#c8102e] focus-within:bg-white">
                <iconify-icon icon="mdi:phone-outline" class="mr-3 text-[#6b7c91]"></iconify-icon>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required autocomplete="tel"
                    placeholder="08xxxxxxxxxx"
                    class="h-12 w-full border-0 bg-transparent p-0 text-sm text-[#10233d] placeholder:text-[#8ea0b8] focus:ring-0">
            </div>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="text-sm font-bold text-[#10233d]">Password</label>
                <div class="mt-2 flex items-center border border-[#f2c8d0] bg-[#fff7f8] px-4 focus-within:border-[#c8102e] focus-within:bg-white">
                    <iconify-icon icon="mdi:lock-outline" class="mr-3 text-[#6b7c91]"></iconify-icon>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        placeholder="Password"
                        class="h-12 w-full border-0 bg-transparent p-0 text-sm text-[#10233d] placeholder:text-[#8ea0b8] focus:ring-0">
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="text-sm font-bold text-[#10233d]">Confirm</label>
                <div class="mt-2 flex items-center border border-[#f2c8d0] bg-[#fff7f8] px-4 focus-within:border-[#c8102e] focus-within:bg-white">
                    <iconify-icon icon="mdi:lock-check-outline" class="mr-3 text-[#6b7c91]"></iconify-icon>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        placeholder="Ulangi password"
                        class="h-12 w-full border-0 bg-transparent p-0 text-sm text-[#10233d] placeholder:text-[#8ea0b8] focus:ring-0">
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <button type="submit"
            class="flex h-12 w-full items-center justify-center bg-[#c8102e] text-sm font-black uppercase tracking-[.18em] text-white transition hover:bg-[#9f0d24]">
            Register
        </button>

        <p class="text-center text-sm text-[#657891]">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="font-black text-[#c8102e] hover:text-[#9f0d24]">Log In</a>
        </p>
    </form>
</x-guest-layout>
