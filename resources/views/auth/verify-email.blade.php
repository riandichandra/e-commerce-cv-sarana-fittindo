<x-guest-layout>
    <div class="mb-8">
        <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8102e]">Verifikasi Email</p>
        <h1 class="mt-3 text-3xl font-black tracking-[-.03em] text-[#10233d]">Cek email Anda</h1>
        <p class="mt-3 text-sm leading-6 text-[#657891]">
            Terima kasih sudah mendaftar. Verifikasi alamat email Anda melalui tautan yang baru saja kami kirim. Jika belum menerima email, kirim ulang tautannya dari halaman ini.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            Tautan verifikasi baru telah dikirim ke alamat email Anda.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <button type="submit"
                    class="inline-flex h-12 items-center justify-center bg-[#c8102e] px-5 text-sm font-black uppercase tracking-[.18em] text-white transition hover:bg-[#9f0d24]">
                    Kirim ulang
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm font-bold text-[#657891] hover:text-[#10233d]">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
