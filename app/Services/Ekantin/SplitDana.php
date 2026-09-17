<?php

namespace App\Services\Ekantin;

class SplitDana
{
    // Bagi fee flat per transaksi secara proporsional ke tiap gerai.
    // Input: [gerai_id => subtotal]. Output: [gerai_id => bersih].
    // Total output selalu = total subtotal - fee (sisa pembulatan ke gerai terakhir).
    public static function bagi(array $subtotalPerGerai, int $feeTotal): array
    {
        $total = array_sum($subtotalPerGerai);
        $bersih = [];
        $sisa = $total - $feeTotal;
        $ids = array_keys($subtotalPerGerai);
        $last = end($ids);
        foreach ($subtotalPerGerai as $id => $sub) {
            if ($id === $last) {
                $bersih[$id] = $sisa;
            } else {
                $bagian = $total > 0 ? intdiv($feeTotal * $sub, $total) : 0;
                $bersih[$id] = $sub - $bagian;
                $sisa -= $bersih[$id];
            }
        }

        return $bersih;
    }
}
