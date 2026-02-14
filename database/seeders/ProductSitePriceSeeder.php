<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSitePriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * One price per product per site (FR, IT, BE). Prices vary slightly per site.
     */
    public function run(): void
    {
        $siteIds = DB::table('sites')->orderBy('id')->pluck('id', 'code');
        $productIds = DB::table('products')->pluck('id');

        if ($siteIds->isEmpty() || $productIds->isEmpty()) {
            return;
        }

        $pricesBySite = [
            'fr' => [29.99, 19.99, 24.99, 14.99, 22.99, 34.99, 2.99, 18.99],
            'it' => [31.99, 21.99, 26.99, 15.99, 24.99, 36.99, 3.29, 19.99],
            'be' => [30.99, 20.99, 25.99, 15.49, 23.99, 35.99, 3.19, 19.49],
        ];

        $productIndex = 0;
        foreach ($productIds as $productId) {
            foreach (['fr', 'it', 'be'] as $code) {
                $siteId = $siteIds[$code];
                $priceIndex = min($productIndex, 7);
                $price = $pricesBySite[$code][$priceIndex];

                DB::table('product_site_prices')->updateOrInsert(
                    [
                        'product_id' => $productId,
                        'site_id' => $siteId,
                    ],
                    [
                        'price' => $price,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
            $productIndex++;
        }
    }
}
