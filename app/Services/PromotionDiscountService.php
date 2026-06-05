<?php

namespace App\Services;

use App\Models\Promotion;

class PromotionDiscountService
{
    public function bestForSubtotal(float $subtotal): array
    {
        $bestPromotion = null;
        $bestDiscount = 0.0;

        Promotion::activeNow()
            ->orderBy('id')
            ->get()
            ->each(function (Promotion $promotion) use ($subtotal, &$bestPromotion, &$bestDiscount) {
                $discount = $promotion->calculateDiscount($subtotal);

                if ($discount > $bestDiscount) {
                    $bestPromotion = $promotion;
                    $bestDiscount = $discount;
                }
            });

        return [
            'promotion' => $bestPromotion,
            'discount_amount' => round($bestDiscount, 2),
            'subtotal_after_discount' => round(max($subtotal - $bestDiscount, 0), 2),
        ];
    }
}
