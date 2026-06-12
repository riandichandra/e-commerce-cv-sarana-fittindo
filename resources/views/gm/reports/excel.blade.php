<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table>
        <tr>
            <th colspan="16" style="font-size: 18px; font-weight: bold; text-align: center;">
                {{ $header['title'] }}
            </th>
        </tr>
        <tr>
            <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
                Cabang {{ $header['branch'] }}
            </th>
        </tr>
        <tr>
            <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
                Periode: {{ $header['period'] }}
            </th>
        </tr>
        <tr>
            <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
                Status Pesanan : {{ $header['order_status'] }}
            </th>
        </tr>
        <tr>
            <th colspan="16" style="font-size: 12px; font-weight: bold; text-align: center;">
                Status Pembayaran : {{ $header['payment_status'] }}
            </th>
        </tr>
        <tr>
            <td colspan="16" style="font-size: 10px; text-align: right;">
                Dicetak: {{ $generatedAt->format('d M Y H:i') }}
            </td>
        </tr>
        <tr>
            <td colspan="16">&nbsp;</td>
        </tr>
    </table>

    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Pesanan</th>
                <th>Pesanan Date</th>
                <th>Pelanggan</th>
                <th>Telepon</th>
                <th>Kota Pengiriman</th>
                <th>Produk</th>
                <th>Qty</th>
                <th>Subtotal</th>
                <th>Discount</th>
                <th>Biaya Pengiriman</th>
                <th>Total</th>
                <th>Pembayaran Metode</th>
                <th>Status Pembayaran</th>
                <th>Status Pesanan</th>
                <th>Diverifikasi Pada</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $order->user?->name ?? $order->shipping_name }}</td>
                    <td>{{ $order->shipping_phone }}</td>
                    <td>{{ $order->shipping_city }}</td>
                    <td>{{ $order->items->map(fn ($item) => $item->product_name . ' (' . $item->quantity . ')')->implode(', ') }}</td>
                    <td>{{ $order->items->sum('quantity') }}</td>
                    <td>{{ $order->subtotal }}</td>
                    <td>{{ $order->discount_amount }}</td>
                    <td>{{ $order->shipping_cost }}</td>
                    <td>{{ $order->total_amount }}</td>
                    <td>{{ $order->payment?->paymentMethod?->name ?? '-' }}</td>
                    <td>{{ $order->payment?->status ?? '-' }}</td>
                    <td>{{ $order->status }}</td>
                    <td>{{ optional($order->payment?->verified_at)->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16">Tidak ada data laporan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <br>

    <table>
        <tr>
            <th colspan="8" style="font-size: 14px; font-weight: bold; text-align: left;">
                Detail Barang Terjual
            </th>
        </tr>
    </table>

    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Nomor Pesanan</th>
                <th>Tanggal Pesanan</th>
                <th>Pelanggan</th>
                <th>Nama Barang</th>
                <th>Harga Satuan</th>
                <th>Qty</th>
                <th>Subtotal Barang</th>
            </tr>
        </thead>
        <tbody>
            @php
                $itemNumber = 1;
                $hasItems = false;
            @endphp

            @foreach ($orders as $order)
                @foreach ($order->items as $item)
                    @php
                        $hasItems = true;
                    @endphp
                    <tr>
                        <td>{{ $itemNumber++ }}</td>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $order->user?->name ?? $order->shipping_name }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->product_price }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->subtotal }}</td>
                    </tr>
                @endforeach
            @endforeach

            @if (! $hasItems)
                <tr>
                    <td colspan="8">Tidak ada data barang terjual.</td>
                </tr>
            @endif
        </tbody>
        @if ($hasItems)
            <tfoot>
                <tr>
                    <th colspan="6">Total</th>
                    <th>{{ $orders->sum(fn ($order) => $order->items->sum('quantity')) }}</th>
                    <th>{{ $orders->sum(fn ($order) => $order->items->sum('subtotal')) }}</th>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
