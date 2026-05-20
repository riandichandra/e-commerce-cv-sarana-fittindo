<x-pelanggan-layout>
    <section class="bg-[#071d33] px-8 py-14 text-white">
        <div class="mx-auto max-w-[1290px]">
            <p class="text-xs font-black uppercase tracking-[.24em] text-[#c8d8ee]">Payment Confirmation</p>
            <h1 class="mt-4 text-4xl font-black uppercase tracking-[-.03em]">Upload Bukti Pembayaran</h1>
        </div>
    </section>

    <section class="bg-[#f7faff] px-8 py-14">
        <div class="mx-auto max-w-[1290px]">
            @if ($errors->any())
                <div class="mb-6 border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    Periksa kembali data pembayaran Anda.
                </div>
            @endif

            <form action="{{ route('pelanggan.orders.payment-proof.store', $order) }}" method="POST" enctype="multipart/form-data"
                class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_420px]">
                @csrf

                <div class="space-y-6">
                    <div class="bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-2 border-b border-[#e8eef7] pb-5 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-xl font-black uppercase text-[#10233d]">{{ $order->order_number }}</h2>
                                <p class="mt-1 text-sm text-[#657891]">{{ $order->created_at->format('d M Y H:i') }}</p>
                            </div>
                            <p class="text-2xl font-black text-[#c8102e]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                        </div>

                        <div class="mt-5 grid gap-4 text-sm md:grid-cols-2">
                            <div>
                                <p class="text-[#657891]">Metode pembayaran</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[#657891]">Bank</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->bank_name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[#657891]">Nomor rekening</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->account_number ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[#657891]">Atas nama</p>
                                <p class="mt-1 font-black text-[#10233d]">{{ $order->paymentMethod?->account_name ?? '-' }}</p>
                            </div>
                        </div>

                        @if ($order->paymentMethod?->instructions)
                            <div class="mt-5 bg-[#fff1f3] p-4 text-sm font-semibold leading-6 text-[#657891]">
                                {{ $order->paymentMethod->instructions }}
                            </div>
                        @endif
                    </div>

                    <div class="bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black uppercase text-[#10233d]">Data Pembayaran</h2>

                        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label for="sender_name" class="text-sm font-bold text-[#10233d]">Nama pengirim</label>
                                <input id="sender_name" name="sender_name" type="text" value="{{ old('sender_name', $order->payment?->sender_name) }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('sender_name')" class="mt-2" />
                            </div>

                            <div>
                                <label for="transfer_date" class="text-sm font-bold text-[#10233d]">Tanggal transfer</label>
                                <input id="transfer_date" name="transfer_date" type="date" value="{{ old('transfer_date', optional($order->payment?->transfer_date)->format('Y-m-d')) }}"
                                    max="{{ now()->format('Y-m-d') }}"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <x-input-error :messages="$errors->get('transfer_date')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <label for="proof_image" class="text-sm font-bold text-[#10233d]">Bukti pembayaran</label>
                                <input id="proof_image" name="proof_image" type="file" accept="image/png,image/jpeg,image/webp"
                                    class="mt-2 w-full border border-[#d8e2f0] bg-white p-3 text-sm file:mr-4 file:border-0 file:bg-[#c8102e] file:px-4 file:py-2 file:text-xs file:font-black file:uppercase file:tracking-[.12em] file:text-white focus:border-[#c8102e] focus:ring-[#c8102e]">
                                <p class="mt-2 text-xs font-semibold text-[#657891]">Format JPG, PNG, atau WEBP. Maksimal 2MB.</p>
                                <x-input-error :messages="$errors->get('proof_image')" class="mt-2" />
                            </div>

                            @if ($order->payment?->proof_image)
                                <div class="md:col-span-2">
                                    <p class="text-sm font-bold text-[#10233d]">Bukti pembayaran saat ini</p>
                                    <a href="{{ asset('storage/' . $order->payment->proof_image) }}" target="_blank"
                                        class="mt-2 inline-flex h-10 items-center gap-2 bg-[#10233d] px-4 text-xs font-black uppercase tracking-[.12em] text-white hover:bg-[#244263]">
                                        <iconify-icon icon="mdi:image-outline"></iconify-icon>
                                        Lihat Bukti
                                    </a>
                                </div>
                            @endif

                            <div class="md:col-span-2">
                                <label for="notes" class="text-sm font-bold text-[#10233d]">Catatan</label>
                                <textarea id="notes" name="notes" rows="4"
                                    class="mt-2 w-full border-[#d8e2f0] text-sm focus:border-[#c8102e] focus:ring-[#c8102e]">{{ old('notes', $order->payment?->notes) }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="h-fit bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black uppercase text-[#10233d]">Produk Dipesan</h2>

                    <div class="mt-6 space-y-4">
                        @foreach ($order->items as $item)
                            <div class="border-b border-[#e8eef7] pb-4">
                                <div class="flex items-start justify-between gap-4 text-sm">
                                    <div>
                                        <p class="font-bold text-[#10233d]">{{ $item->product_name }}</p>
                                        <p class="mt-1 text-[#657891]">{{ $item->quantity }} x Rp {{ number_format($item->product_price, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="shrink-0 font-black text-[#c8102e]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-between border-t border-[#f2c8d0] pt-4 text-sm">
                        <span class="text-[#657891]">Total pembayaran</span>
                        <span class="font-black text-[#c8102e]">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>

                    <button type="submit" class="mt-7 flex h-11 w-full items-center justify-center bg-[#c8102e] text-xs font-black uppercase tracking-[.16em] text-white hover:bg-[#9f0d24]">
                        Upload Bukti
                    </button>
                    <a href="{{ route('pelanggan.orders.index') }}" class="mt-3 flex h-11 w-full items-center justify-center border border-[#d8e2f0] text-xs font-black uppercase tracking-[.16em] text-[#10233d] hover:border-[#c8102e] hover:text-[#c8102e]">
                        Kembali
                    </a>
                </aside>
            </form>
        </div>
    </section>
</x-pelanggan-layout>
