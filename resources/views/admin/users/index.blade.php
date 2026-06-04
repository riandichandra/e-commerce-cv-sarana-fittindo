<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">PENGGUNAS</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                href="{{ route('admin.users.create') }}">
                ADD PENGGUNA
            </x-button>
        </div>

        <div class="flex flex-col gap-5">
            @foreach ($roles as $role)
                <div class="bg-[#FFF1F3] p-5 w-full">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold tracking-wider text-texthighlight">
                            {{ strtoupper(str_replace('_', ' ', $role->name)) }}
                        </h2>
                        <span class="bg-white px-3 py-1 text-xs font-semibold text-primary">
                            {{ $role->users->count() }} PENGGUNAS
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="mt-3 w-full">
                            <thead>
                                <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                                    <th class="py-3 px-3">#</th>
                                    <th class="py-3 px-3">Nama</th>
                                    <th class="py-3 px-3">Email</th>
                                    <th class="py-3 px-3">Telepon</th>
                                    <th class="py-3 px-3">Status</th>
                                    <th class="py-3 px-3">Dibuat</th>
                                    <th class="py-3 px-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($role->users as $user)
                                    <tr class="border-b border-gray-200 text-sm">
                                        <td class="py-3 px-3">{{ $loop->iteration }}</td>
                                        <td class="py-3 px-3 font-medium text-texthighlight">{{ $user->name }}</td>
                                        <td class="py-3 px-3">{{ $user->email }}</td>
                                        <td class="py-3 px-3">{{ $user->phone ?? '-' }}</td>
                                        <td class="py-3 px-3">
                                            <span class="px-2 py-1 text-xs {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">{{ $user->created_at->format('d M Y') }}</td>
                                        <td class="py-3 px-3">
                                            <a class="inline-flex items-center gap-1.5 bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition"
                                                href="{{ route('admin.users.edit', $user) }}">
                                                <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                                EDIT
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-6 px-3 text-center text-sm text-gray-500">
                                            Belum ada user untuk role ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-admin-layout>
