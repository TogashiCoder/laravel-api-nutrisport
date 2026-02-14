<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function index(): JsonResponse
    {
        $user = $this->authService->getUser();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(15);

        $data = $orders->getCollection()->map(fn (Order $order) => [
            'id' => $order->id,
            'total' => (float) $order->total,
            'status' => $order->status,
            'content' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
            ])->all(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $user = $this->authService->getUser();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $order->load('items');

        return response()->json([
            'id' => $order->id,
            'total' => (float) $order->total,
            'status' => $order->status,
            'content' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
            ])->all(),
        ]);
    }
}
