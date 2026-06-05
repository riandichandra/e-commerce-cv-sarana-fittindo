<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/PAYMENTS/PAYMENT METHODS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Metode Pembayaran';
        $paymentMethods = PaymentMethod::orderBy('sort_order')->orderBy('bank_name')->paginate(10)->withQueryString();

        return view('admin.payment-methods.index', compact('pagePath', 'pageName', 'paymentMethods'));
    }

    public function create()
    {
        $pagePath = 'ADMIN/PAYMENTS/PAYMENT METHODS/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Tambah Metode Pembayaran';
        $paymentMethod = new PaymentMethod([
            'account_name' => 'CV Sarana Fittindo',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return view('admin.payment-methods.create', compact('pagePath', 'pageName', 'paymentMethod'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePaymentMethod($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['code'] = $this->generateUniqueCode($validated['bank_name'], $validated['account_number']);
        $validated['icon'] = null;

        PaymentMethod::create($validated);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        $pagePath = 'ADMIN/PAYMENTS/PAYMENT METHODS/EDIT';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Edit Metode Pembayaran';

        return view('admin.payment-methods.edit', compact('pagePath', 'pageName', 'paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $this->validatePaymentMethod($request, $paymentMethod);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['code'] = $paymentMethod->code
            ?: $this->generateUniqueCode($validated['bank_name'], $validated['account_number'], $paymentMethod);
        $validated['icon'] = null;

        $paymentMethod->update($validated);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Rekening bank berhasil diperbarui.');
    }

    private function validatePaymentMethod(Request $request, ?PaymentMethod $paymentMethod = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function generateUniqueCode(string $bankName, string $accountNumber, ?PaymentMethod $paymentMethod = null): string
    {
        $baseCode = Str::limit(Str::slug($bankName . '-' . $accountNumber, '_'), 44, '');
        $baseCode = $baseCode ?: 'rekening_bank';
        $code = $baseCode;
        $counter = 1;

        while (
            PaymentMethod::where('code', $code)
                ->when($paymentMethod, fn ($query) => $query->whereKeyNot($paymentMethod->id))
                ->exists()
        ) {
            $code = Str::limit($baseCode, 44, '') . '_' . $counter;
            $counter++;
        }

        return $code;
    }
}
