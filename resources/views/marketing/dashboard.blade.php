<x-marketing-layout>
    @php
        $summaryCards = [
            [
                'label' => 'Total Pelanggan',
                'value' => $totalCustomers,
                'icon' => 'mdi:account-group',
                'tone' => 'bg-rose-50 text-primary',
                'meta' => $activeCustomers . ' pelanggan aktif',
            ],
            [
                'label' => 'Promosi Aktif',
                'value' => $activePromotions . ' / ' . $totalPromotions,
                'icon' => 'mdi:loudspeaker',
                'tone' => 'bg-emerald-50 text-emerald-700',
                'meta' => $upcomingPromotions . ' promosi terjadwal',
            ],
            [
                'label' => 'Selesai Pesanan',
                'value' => $selesaiOrders,
                'icon' => 'mdi:shopping-outline',
                'tone' => 'bg-blue-50 text-blue-700',
                'meta' => 'Pesanan selesai',
            ],
            [
                'label' => 'Discount Given',
                'value' => 'Rp ' . number_format($totalDiscountGiven, 0, ',', '.'),
                'icon' => 'mdi:ticket-percent',
                'tone' => 'bg-yellow-50 text-yellow-700',
                'meta' => 'Total diskon dari order',
            ],
        ];
    @endphp

    <div class="space-y-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex items-center gap-1 text-xs uppercase tracking-[.18em] text-gray-500">
                    <span>{{ $pagePath[0] }}</span>
                    <span>/</span>
                    <span class="font-black text-primary">{{ $pagePath[1] }}</span>
                </div>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-texthighlight">{{ $pageName }}</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium text-gray-600">
                    Ringkasan pelanggan, performa promosi, dan pertumbuhan customer terbaru.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm lg:min-w-[360px]">
                <a href="{{ route('marketing.promotions.create') }}"
                    class="flex items-center justify-between bg-white px-4 py-3 font-bold text-texthighlight shadow-sm hover:text-primary">
                    <span>Promo Terbaru</span>
                    <iconify-icon icon="mdi:plus"></iconify-icon>
                </a>
                <a href="{{ route('marketing.users.index') }}"
                    class="flex items-center justify-between bg-white px-4 py-3 font-bold text-texthighlight shadow-sm hover:text-primary">
                    <span>Pelanggan</span>
                    <iconify-icon icon="mdi:arrow-right"></iconify-icon>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                <div class="bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.16em] text-gray-500">{{ $card['label'] }}
                            </p>
                            <p class="mt-3 text-3xl font-black text-texthighlight">{{ $card['value'] }}</p>
                            <p class="mt-2 text-sm font-semibold text-gray-500">{{ $card['meta'] }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center {{ $card['tone'] }}">
                            <iconify-icon icon="{{ $card['icon'] }}" class="fs-4"></iconify-icon>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_380px]">
            <section class="bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Pelanggan Growth</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Pelanggan baru dalam 6 bulan terakhir.</p>
                    </div>
                    <p class="text-sm font-black text-primary">{{ $totalCustomers }} pelanggan</p>
                </div>

                <div class="mt-8 flex h-64 items-end gap-4 border-b border-gray-200">
                    @foreach ($monthlyCustomers as $month)
                        @php
                            $height = max(8, ((int) $month['count'] / $maxMonthlyCustomers) * 100);
                        @endphp
                        <div class="flex h-full flex-1 flex-col justify-end gap-3">
                            <div class="relative flex flex-1 items-end">
                                <div class="w-full bg-primary" style="height: {{ $height }}%"></div>
                            </div>
                            <div class="pb-3 text-center">
                                <p class="text-xs font-black uppercase text-gray-500">{{ $month['label'] }}</p>
                                <p class="mt-1 text-xs font-semibold text-texthighlight">{{ $month['count'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="bg-[#10233d] p-6 text-white shadow-sm">
                <h2 class="text-xl font-black uppercase">Kondisi Promosi</h2>
                <p class="mt-1 text-sm font-medium text-blue-100">Status kampanye marketing saat ini.</p>

                <div class="mt-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-white/15 pb-4">
                        <span class="text-sm text-blue-100">Total promotions</span>
                        <span class="text-lg font-black">{{ $totalPromotions }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-white/15 pb-4">
                        <span class="text-sm text-blue-100">Aktif now</span>
                        <span class="text-lg font-black">{{ $activePromotions }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-blue-100">Upcoming</span>
                        <span class="text-lg font-black">{{ $upcomingPromotions }}</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Promosi Terbaru</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Promosi terbaru yang dibuat.</p>
                    </div>
                    <a href="{{ route('marketing.promotions.index') }}"
                        class="text-xs font-black uppercase tracking-[.14em] text-primary hover:text-primary-dark">Lihat
                        Semua</a>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($recentPromotions as $promotion)
                        @php
                            $today = today();
                            $isRunning =
                                $promotion->is_active &&
                                $promotion->start_date->lte($today) &&
                                $promotion->end_date->gte($today);
                            $isUpcoming = $promotion->is_active && $promotion->start_date->gt($today);
                            $isEnded = $promotion->is_active && $promotion->end_date->lt($today);
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
                        <div class="flex items-start justify-between gap-4 px-5 py-4 transition hover:bg-gray-50">
                            <div>
                                <p class="font-black text-texthighlight">{{ $promotion->name }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">
                                    {{ $promotion->code ?: 'Tanpa kode' }} ·
                                    {{ $promotion->start_date->format('d M Y') }} -
                                    {{ $promotion->end_date->format('d M Y') }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <iconify-icon icon="mdi:loudspeaker-off-outline"
                                class="text-4xl text-gray-300"></iconify-icon>
                            <p class="mt-2 text-sm font-semibold text-gray-500">Belum ada promosi.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 bg-[#FFF7F8] px-5 py-4">
                    <div>
                        <h2 class="text-xl font-black uppercase text-texthighlight">Pelanggan Terbaru</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Pelanggan terbaru dari role pelanggan.</p>
                    </div>
                    <a href="{{ route('marketing.users.index') }}"
                        class="text-xs font-black uppercase tracking-[.14em] text-primary hover:text-primary-dark">Lihat
                        Semua</a>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($recentCustomers as $customer)
                        <div class="flex items-start justify-between gap-4 px-5 py-4 transition hover:bg-gray-50">
                            <div>
                                <p class="font-black text-texthighlight">{{ $customer->name }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-600">{{ $customer->email }}</p>
                                <p class="text-xs text-gray-500">{{ $customer->phone ?? 'Tanpa telepon' }} ·
                                    {{ $customer->created_at->format('d M Y') }}</p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $customer->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                {{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <iconify-icon icon="mdi:account-search-outline"
                                class="text-4xl text-gray-300"></iconify-icon>
                            <p class="mt-2 text-sm font-semibold text-gray-500">Belum ada pelanggan.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-marketing-layout>
