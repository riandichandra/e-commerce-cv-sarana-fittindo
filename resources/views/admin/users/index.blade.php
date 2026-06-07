<x-admin-layout>
    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">PENGGUNA</p>
        </div>

        <div class="flex w-full flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary">Manajemen akses</p>
                <h1 class="mt-1 text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            </div>
            <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                href="{{ route('admin.users.create') }}">
                TAMBAH PENGGUNA
            </x-button>
        </div>

        <div class="w-full overflow-hidden border border-[#f2c8d0] bg-white shadow-sm">
            <div
                class="flex flex-col gap-3 border-b border-[#f2c8d0] bg-[#fff7f8] p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-wide text-texthighlight">Daftar Pengguna</h2>
                    <p class="mt-1 text-sm text-gray-600">Kelola akun, role, dan status akses pengguna.</p>
                </div>
                <form method="GET" action="{{ route('admin.users.index') }}">
                    <select name="role_id" onchange="this.form.submit()"
                        class="border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary focus:outline-none">
                        <option value="">Semua Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) $role->id === $selectedRoleId)>
                                {{ strtoupper(str_replace('_', ' ', $role->name)) }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-white text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <th class="w-16 px-5 py-4">No.</th>
                            <th class="px-5 py-4">Pengguna</th>
                            <th class="px-5 py-4">Kontak</th>
                            <th class="px-5 py-4">Role</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Dibuat</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr class="text-sm transition hover:bg-[#fff7f8]">
                                <td class="px-5 py-4 align-top font-semibold text-gray-500">
                                    {{ $users->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-texthighlight">{{ $user->name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">ID: {{ $user->id }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-800">{{ $user->email }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $user->phone ?? 'Tidak ada telepon' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex bg-[#fff1f3] px-2.5 py-1 text-xs font-bold text-primary">
                                        {{ strtoupper($user->getRoleNames()->first() ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="px-2.5 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-800">{{ $user->created_at->format('d M Y') }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $user->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a class="inline-flex h-9 items-center justify-center gap-1.5 bg-primary px-3 text-xs font-bold text-white transition hover:bg-red-700"
                                        href="{{ route('admin.users.edit', $user) }}">
                                        <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                        EDIT
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-md flex-col items-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center bg-[#fff1f3] text-primary">
                                            <iconify-icon icon="mdi:account-group-outline"
                                                class="fs-3"></iconify-icon>
                                        </div>
                                        <p class="mt-4 text-base font-bold text-texthighlight">Belum ada pengguna.</p>
                                        <p class="mt-2 text-sm text-gray-500">Tambahkan akun untuk mulai mengatur akses
                                            dashboard.</p>
                                        <a href="{{ route('admin.users.create') }}"
                                            class="mt-5 inline-flex h-10 items-center justify-center gap-2 bg-primary px-4 text-sm font-bold text-white transition hover:bg-red-700">
                                            <iconify-icon icon="mdi:plus" class="fs-6"></iconify-icon>
                                            Tambah Pengguna
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
