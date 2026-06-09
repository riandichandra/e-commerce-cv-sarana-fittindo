<x-guest-layout>
    <div class="mb-8">
        <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8102e]">Selamat Datang Kembali</p>
        <h1 class="mt-3 text-3xl font-black tracking-[-.03em] text-[#10233d]">Masuk ke akun Anda</h1>
        <p class="mt-3 text-sm leading-6 text-[#657891]">
            Masuk untuk melihat produk material, mengelola profil, dan melanjutkan kebutuhan proyek Anda.
        </p>
    </div>

    <x-auth-session-status class="mb-5 border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="text-sm font-bold text-[#10233d]">Email</label>
            <div class="mt-2 flex items-center border border-[#f2c8d0] bg-[#fff7f8] px-4 focus-within:border-[#c8102e] focus-within:bg-white">
                <iconify-icon icon="mdi:email-outline" class="mr-3 text-[#6b7c91]"></iconify-icon>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    placeholder="email@example.com"
                    class="h-12 w-full border-0 bg-transparent p-0 text-sm text-[#10233d] placeholder:text-[#8ea0b8] focus:ring-0">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

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

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="border-[#ef9aaa] text-[#c8102e] focus:ring-[#c8102e]" name="remember">
                <span class="ms-2 text-sm font-medium text-[#657891]">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-bold text-[#c8102e] hover:text-[#9f0d24]" href="{{ route('password.request') }}">
                    Lupa kata sandi?
                </a>
            @endif
        </div>

        <button type="submit"
            class="flex h-12 w-full items-center justify-center bg-[#c8102e] text-sm font-black uppercase tracking-[.18em] text-white transition hover:bg-[#9f0d24]">
            Masuk
        </button>

        <p class="text-center text-sm text-[#657891]">
            Belum memiliki akun?
            <a href="{{ route('register') }}" class="font-black text-[#c8102e] hover:text-[#9f0d24]">Daftar</a>
        </p>
    </form>
</x-guest-layout>
