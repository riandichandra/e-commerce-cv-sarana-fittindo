<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $pagePath = explode('/', 'MARKETING/USERS');
        $pageName = 'Pengguna Pelanggan';
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $customers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $totalCustomers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))->count();
        $activeCustomers = User::whereHas('roles', fn ($query) => $query->where('name', 'pelanggan'))->where('is_active', true)->count();

        return view('marketing.users.index', compact(
            'pagePath',
            'pageName',
            'customers',
            'search',
            'status',
            'totalCustomers',
            'activeCustomers'
        ));
    }
}
