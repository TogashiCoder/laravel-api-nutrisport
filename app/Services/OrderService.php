<?php

namespace App\Services;

use App\Events\OrderCreated;
use App\Mail\OrderConfirmationClient;
use App\Mail\OrderConfirmationAdmin;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    public function __construct(
        private CartService $cartService
    ) {
    }

    public function createOrder(User $user, string $cartId, array $input): Order
    {
        $cart = $this->cartService->getCart($cartId);
        if (empty($cart['items'])) {
            throw new \InvalidArgumentException('Cart is empty.');
        }

        $siteId = (int) $user->site_id;
        if ((int) ($cart['site_id'] ?? 0) !== $siteId) {
            throw new \InvalidArgumentException('Cart is for another site.');
        }

        $address = $this->resolveAddress($user, $input);
        $items = $cart['items'];

        $productIds = array_unique(array_column($items, 'product_id'));
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $row) {
            $pid = (int) $row['product_id'];
            $qty = (int) $row['quantity'];
            $product = $products->get($pid);
            if (!$product || $product->stock < $qty) {
                throw new \InvalidArgumentException('Insufficient stock for product ID ' . $pid . '.');
            }
        }

        return DB::transaction(function () use ($user, $address, $items, $products, $cartId) {
            $order = new Order();
            $order->user_id = $user->id;
            $order->site_id = $user->site_id;
            $order->address_id = $address->id;
            $order->payment_method = 'bank_transfer';
            $order->status = 'pending';
            $order->remaining_amount = 0;
            $order->total = 0;
            $order->save();

            $total = 0.0;
            foreach ($items as $row) {
                $product = $products->get((int) $row['product_id']);
                $qty = (int) $row['quantity'];
                $price = (float) ($row['price'] ?? 0);
                $subtotal = round($price * $qty, 2);
                $total += $subtotal;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $price,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $qty);
            }

            $order->total = round($total, 2);
            $order->remaining_amount = $order->total;
            $order->save();

            $this->cartService->clear($cartId);

            Mail::to($user->email)->send(new OrderConfirmationClient($order->load('items', 'address')));
            $adminEmail = config('mail.admin_email');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new OrderConfirmationAdmin($order->load('items', 'address', 'user'), $user));
            }

            event(new OrderCreated($order->fresh(['user', 'address']), $user));

            return $order->fresh('items');
        });
    }

    private function resolveAddress(User $user, array $input): Address
    {
        if (!empty($input['address_id'])) {
            $address = Address::query()
                ->where('id', $input['address_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
            return $address;
        }

        $a = $input['address'];
        return Address::query()->create([
            'user_id' => $user->id,
            'full_name' => $a['full_name'],
            'address_line' => $a['address_line'],
            'city' => $a['city'],
            'country' => $a['country'],
            'is_default' => false,
        ]);
    }
}
