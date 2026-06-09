<x-guest-layout>
    <div class="mb-8">
        <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8102e]">Lupa Kata Sandi</p>
        <h1 class="mt-3 text-3xl font-black tracking-[-.03em] text-[#10233d]">Atur ulang akses akun</h1>
        <p class="mt-3 text-sm leading-6 text-[#657891]">
            Masukkan alamat email yang terdaftar. Kami akan mengirim tautan untuk membuat kata sandi baru.
        </p>
    </div>

    <x-auth-session-status class="mb-5 border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

        <button type="submit"
            class="flex h-12 w-full items-center justify-center bg-[#c8102e] text-sm font-black uppercase tracking-[.18em] text-white transition hover:bg-[#9f0d24]">
            Kirim tautan
        </button>

        <p class="text-center text-sm text-[#657891]">
            Ingat kata sandi Anda?
            <a href="{{ route('login') }}" class="font-black text-[#c8102e] hover:text-[#9f0d24]">Masuk</a>
        </p>
    </form>
</x-guest-layout>
