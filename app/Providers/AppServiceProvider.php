<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin.navigation', function ($view) {
            $paidUnprocessedOrderCount = Order::nonDummy()->where('status', 'pembayaran_dikonfirmasi')->count();
            $pendingShippingCount = Order::nonDummy()->where(function ($q) {
                $q->where('status', 'menunggu_konfirmasi_ongkir')
                    ->orWhere('shipping_cost_status', 'waiting_admin');
            })->count();

            $view->with([
                'paidUnprocessedOrderCount' => $paidUnprocessedOrderCount,
                'pendingShippingCount' => $pendingShippingCount,
            ]);
        });
    }
}
