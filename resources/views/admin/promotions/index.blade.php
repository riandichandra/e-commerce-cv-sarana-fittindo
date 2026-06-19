<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">{{ $pagePath[0] }}</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">{{ $pagePath[1] }}</p>
        </div>

        <div class="mb-7 flex w-full flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.16em] text-primary">Daftar Promosi</p>
                <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            </div>
            <div class="rounded-md border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm">
                <span class="font-bold text-texthighlight">{{ $promotions->total() }}</span> promosi terdaftar
            </div>
        </div>

        <div class="w-full overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                <div>
                    <h2 class="font-bold tracking-wider text-texthighlight">DAFTAR PROMOSI</h2>
                    <p class="mt-1 text-sm text-gray-500">Admin hanya dapat melihat data promosi yang dibuat marketing.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[.08em] text-gray-500">
                            <th class="w-16 px-5 py-3">No.</th>
                            <th class="px-5 py-3">Promosi</th>
                            <th class="px-5 py-3">Tipe & Nilai</th>
                            <th class="px-5 py-3">Periode</th>
                            <th class="px-5 py-3">Syarat</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Dibuat Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse ($promotions as $promotion)
                            @php
                                $today = today();
                                $startsAt = $promotion->start_date;
                                $endsAt = $promotion->end_date;
                                $isRunning = $promotion->is_active && $startsAt->lte($today) && $endsAt->gte($today);
                                $isUpcoming = $promotion->is_active && $startsAt->gt($today);
                                $isEnded = $promotion->is_active && $endsAt->lt($today);
                                $statusClass = $isRunning
                                    ? 'bg-green-100 text-green-700'
                                    : ($isUpcoming
                                        ? 'bg-blue-100 text-blue-700'
                                        : ($isEnded
                                            ? 'bg-yellow-100 text-yellow-700'
                                            : 'bg-gray-200 text-gray-700'));
                                $statusText = $isRunning
                                    ? 'Berjalan'
                                    : ($isUpcoming
                                        ? 'Terjadwal'
                                        : ($isEnded
                                            ? 'Berakhir'
                                            : 'Nonaktif'));
                            @endphp
                            <tr class="align-top transition hover:bg-gray-50">
                                <td class="px-5 py-4 font-semibold text-gray-500">
                                    {{ $promotions->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-texthighlight">{{ $promotion->name }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        <span class="rounded bg-gray-100 px-2 py-1 font-semibold text-gray-700">
                                            {{ $promotion->code ?: 'Tanpa kode' }}
                                        </span>
                                        @if ($promotion->banner_image)
                                            <a class="font-semibold text-primary hover:text-primary-dark"
                                                href="{{ asset('storage/' . $promotion->banner_image) }}"
                                                target="_blank">
                                                Lihat banner
                                            </a>
                                        @endif
                                    </div>
                                    @if ($promotion->description)
                                        <p class="mt-2 max-w-md text-sm leading-5 text-gray-500">
                                            {{ $promotion->description }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-texthighlight">
                                        {{ $promotion->type === 'percent' ? 'Persen' : 'Nominal' }}</div>
                                    <div class="mt-1 text-gray-600">
                                        @if ($promotion->type === 'percent')
                                            {{ rtrim(rtrim(number_format($promotion->value, 2, ',', '.'), '0'), ',') }}%
                                        @else
                                            Rp {{ number_format($promotion->value, 0, ',', '.') }}
                                        @endif
                                    </div>
                                    @if ($promotion->max_discount)
                                        <div class="mt-1 text-xs text-gray-500">Maks. Rp
                                            {{ number_format($promotion->max_discount, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    <div>{{ $startsAt->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-400">sampai</div>
                                    <div>{{ $endsAt->format('d M Y') }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    @if ($promotion->min_purchase)
                                        Min. Rp {{ number_format($promotion->min_purchase, 0, ',', '.') }}
                                    @else
                                        Tanpa minimum
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $promotion->createdBy?->name ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center gap-2 text-gray-500">
                                        <iconify-icon icon="mdi:loudspeaker-off-outline"
                                            class="text-4xl text-gray-300"></iconify-icon>
                                        <p class="font-semibold text-texthighlight">Belum ada promosi</p>
                                        <p class="text-sm">Data promosi akan tampil di sini setelah dibuat oleh
                                            marketing.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($promotions->hasPages())
                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $promotions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
