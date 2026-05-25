<x-marketing-layout>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">{{ $pagePath[0] }}</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">{{ $pagePath[1] }}</p>
        </div>

        <div class="mb-7 flex w-full items-center justify-between">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                href="{{ route('marketing.promotions.create') }}">
                ADD PROMOTION
            </x-button>
        </div>

        <div class="w-full bg-[#FFF1F3] p-5">
            <h2 class="font-semibold tracking-wider text-texthighlight">PROMOTION LISTS</h2>
            <div class="overflow-x-auto">
                <table class="mt-3 w-full">
                    <thead>
                        <tr class="border-b border-gray-300 text-left text-sm font-medium text-gray-600">
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3">Name</th>
                            <th class="px-3 py-3">Type</th>
                            <th class="px-3 py-3">Value</th>
                            <th class="px-3 py-3">Period</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($promotions as $promotion)
                            @php
                                $isRunning = $promotion->is_active && $promotion->start_date->isPast() && $promotion->end_date->isFuture();
                            @endphp
                            <tr class="border-b border-gray-200 text-sm">
                                <td class="px-3 py-3">{{ $promotions->firstItem() + $loop->index }}</td>
                                <td class="px-3 py-3 font-medium text-texthighlight">{{ $promotion->name }}</td>
                                <td class="px-3 py-3">{{ ucfirst($promotion->type) }}</td>
                                <td class="px-3 py-3">
                                    @if ($promotion->type === 'percent')
                                        {{ rtrim(rtrim(number_format($promotion->value, 2, ',', '.'), '0'), ',') }}%
                                    @else
                                        Rp {{ number_format($promotion->value, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    {{ $promotion->start_date->format('d M Y') }} - {{ $promotion->end_date->format('d M Y') }}
                                </td>
                                <td class="px-3 py-3">
                                    <span class="px-2 py-1 text-xs {{ $isRunning ? 'bg-green-100 text-green-700' : ($promotion->is_active ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700') }}">
                                        {{ $isRunning ? 'Running' : ($promotion->is_active ? 'Scheduled/Ended' : 'Inactive') }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <a class="inline-flex items-center gap-1.5 bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-dark"
                                            href="{{ route('marketing.promotions.edit', $promotion) }}">
                                            <iconify-icon icon="mdi:pencil" class="fs-6"></iconify-icon>
                                            EDIT
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-sm text-gray-500">Belum ada promosi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $promotions->links() }}
            </div>
        </div>
    </div>
</x-marketing-layout>
