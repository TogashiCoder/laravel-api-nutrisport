<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CartService
{
    public function getCartId(string $token): string
    {
        return $token !== '' ? $token : $this->generateCartId();
    }

    public function generateCartId(): string
    {
        return 'guest_' . bin2hex(random_bytes(16));
    }

    public function getCart(string $cartId): array
    {
        $key = $this->cacheKey($cartId);
        $data = Cache::get($key);
        if (!is_array($data)) {
            return ['site_id' => null, 'items' => [], 'updated_at' => null];
        }
        return [
            'site_id' => $data['site_id'] ?? null,
            'items' => $data['items'] ?? [],
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }

    public function setCart(string $cartId, array $data): void
    {
        $key = $this->cacheKey($cartId);
        $ttl = config('cart.ttl', 259200);
        $data['updated_at'] = now()->toIso8601String();
        Cache::put($key, $data, $ttl);
    }

    public function addItem(string $cartId, int $siteId, int $productId, int $quantity): array
    {
        $product = Product::query()
            ->with(['sitePrices' => fn ($q) => $q->where('site_id', $siteId)->select('product_id', 'site_id', 'price')])
            ->find($productId);

        if (!$product) {
            throw new \InvalidArgumentException('Product not found.');
        }

        $sitePrice = $product->sitePrices->first();
        if (!$sitePrice) {
            throw new \InvalidArgumentException('Product not available for this site.');
        }

        $available = max(0, $product->stock);
        if ($available < 1) {
            throw new \InvalidArgumentException('Product out of stock.');
        }

        $quantity = min($quantity, $available);
        $price = (string) $sitePrice->price;

        $cart = $this->getCart($cartId);
        $currentSiteId = $cart['site_id'];
        if ($currentSiteId !== null && (int) $currentSiteId !== $siteId) {
            throw new \InvalidArgumentException('Cart is for another site.');
        }

        $items = $cart['items'];
        $found = false;
        foreach ($items as $i => $row) {
            if ((int) $row['product_id'] === $productId) {
                $newQty = $row['quantity'] + $quantity;
                $newQty = min($newQty, $available);
                $items[$i]['quantity'] = $newQty;
                $items[$i]['price'] = $price;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = ['product_id' => $productId, 'quantity' => $quantity, 'price' => $price];
        }

        $cart['site_id'] = $siteId;
        $cart['items'] = $items;
        $this->setCart($cartId, $cart);
        return $this->getCart($cartId);
    }

    public function removeItem(string $cartId, int $productId): array
    {
        $cart = $this->getCart($cartId);
        $cart['items'] = array_values(array_filter($cart['items'], fn ($row) => (int) $row['product_id'] !== $productId));
        $this->setCart($cartId, $cart);
        return $this->getCart($cartId);
    }

    public function clear(string $cartId): void
    {
        $key = $this->cacheKey($cartId);
        Cache::forget($key);
    }

    public function toResponse(array $cart, ?string $productNames = null): array
    {
        $items = $cart['items'];
        $subtotal = 0;
        $itemCount = 0;
        $out = [];
        foreach ($items as $row) {
            $qty = (int) $row['quantity'];
            $price = (float) ($row['price'] ?? 0);
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;
            $itemCount += $qty;
            $out[] = [
                'product_id' => (int) $row['product_id'],
                'quantity' => $qty,
                'price' => $price,
                'line_total' => round($lineTotal, 2),
            ];
        }
        return [
            'items' => $out,
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
            'item_count' => $itemCount,
        ];
    }

    private function cacheKey(string $cartId): string
    {
        $prefix = config('cart.key_prefix', 'cart:');
        return $prefix . $cartId;
    }
}
