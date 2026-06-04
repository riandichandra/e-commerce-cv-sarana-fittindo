<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrderSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:menunggu_konfirmasi_ongkir,belum_dibayar,menunggu_verifikasi_pembayaran,pembayaran_dikonfirmasi,diproses,dikirim,selesai,dibatalkan'],
        ];
    }
}
