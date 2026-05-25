<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table>
        <tr>
            <th colspan="16">Laporan GM CV Sarana Fittindo</th>
        </tr>
        <tr>
            <td colspan="16">Generated: {{ $generatedAt->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td colspan="16">
                Periode:
                {{ $filters['start_date'] ?: '-' }}
                sampai
                {{ $filters['end_date'] ?: '-' }}
            </td>
        </tr>
    </table>

    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Order Number</th>
                <th>Order Date</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Shipping City</th>
                <th>Products</th>
                <th>Qty</th>
                <th>Subtotal</th>
                <th>Discount</th>
                <th>Shipping Cost</th>
                <th>Total</th>
                <th>Payment Method</th>
                <th>Payment Status</th>
                <th>Order Status</th>
                <th>Verified At</th>
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
</body>
</html>
