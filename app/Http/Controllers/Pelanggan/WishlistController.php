<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request, Product $product)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();

            return back()->with('success', 'Produk dihapus dari wishlist.');
        }

        if (! $product->isVisibleToCustomers()) {
            return back()->with('error', 'Produk sedang tidak tersedia.');
        }

        Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return back()->with('success', 'Produk ditambahkan ke wishlist.');
    }
}
