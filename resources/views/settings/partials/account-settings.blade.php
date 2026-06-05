<div class="flex flex-col gap-2">
    <div class="text-xs flex items-center gap-1">
        <p class="tracking-wider">{{ $pagePath[0] }}</p>
        <p>></p>
        <p class="font-bold text-primary tracking-wider">{{ $pagePath[1] ?? $pagePath[0] }}</p>
    </div>

    <div class="w-full flex items-center justify-between mb-7">
        <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="bg-[#FFF1F3] p-5 w-full">
            <h2 class="font-semibold tracking-wider text-texthighlight">PROFILE</h2>

            <div class="mt-4 flex items-center gap-4 border-b border-gray-300 pb-4">
                <div class="flex h-14 w-14 items-center justify-center bg-primary text-xl font-bold text-white">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="font-bold text-texthighlight">{{ $user->name }}</p>
                    <p class="text-sm text-gray-600">{{ $user->email }}</p>
                    <span class="mt-2 inline-flex bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">
                        {{ strtoupper($user->getRoleNames()->first() ?? 'PENGGUNA') }}
                    </span>
                </div>
            </div>

            <dl class="mt-4 flex flex-col gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Telepon</dt>
                    <dd class="font-medium text-texthighlight">{{ $user->phone ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Email Terverifikasi</dt>
                    <dd class="font-medium text-texthighlight">
                        {{ $user->email_verified_at ? $user->email_verified_at->format('d M Y H:i') : 'Belum diverifikasi' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-[#FFF1F3] p-5 w-full xl:col-span-2">
            <h2 class="font-semibold tracking-wider text-texthighlight">UPDATE PROFILE</h2>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-4 flex flex-col gap-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="name" class="text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
                        @error('name')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="email" class="text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
                        @error('email')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="phone" class="text-sm font-medium text-gray-700">Telepon</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
                        @error('phone')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="bg-primary text-white py-2 px-4 hover:bg-primary-dark transition w-fit">
                        SAVE PROFILE
                    </button>
                    @if (session('status') === 'profile-updated')
                        <p class="text-sm text-green-700">Profile berhasil diperbarui.</p>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-[#FFF1F3] p-5 w-full xl:col-span-3">
            <h2 class="font-semibold tracking-wider text-texthighlight">UBAH KATA SANDI</h2>

            <form method="POST" action="{{ route('password.update') }}" class="mt-4 flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="current_password" class="text-sm font-medium text-gray-700">Kata Sandi Saat Ini</label>
                        <input type="password" name="current_password" id="current_password"
                            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
                        @if ($errors->updatePassword->has('current_password'))
                            <p class="text-sm text-red-600">{{ $errors->updatePassword->first('current_password') }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="password" class="text-sm font-medium text-gray-700">Kata Sandi Baru</label>
                        <input type="password" name="password" id="password"
                            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
                        @if ($errors->updatePassword->has('password'))
                            <p class="text-sm text-red-600">{{ $errors->updatePassword->first('password') }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="password_confirmation" class="text-sm font-medium text-gray-700">Konfirmasi Kata Sandi</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
                        @if ($errors->updatePassword->has('password_confirmation'))
                            <p class="text-sm text-red-600">{{ $errors->updatePassword->first('password_confirmation') }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="bg-primary text-white py-2 px-4 hover:bg-primary-dark transition w-fit">
                        PERBARUI KATA SANDI
                    </button>
                    @if (session('status') === 'password-updated')
                        <p class="text-sm text-green-700">Kata Sandi berhasil diperbarui.</p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
