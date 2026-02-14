<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AgentOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $agent = Auth::guard('agent')->user();
        if (!$agent || $agent->id !== 1) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $orders = Order::query()
            ->where('created_at', '>=', now()->subDays(5))
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        $data = $orders->getCollection()->map(fn (Order $order) => [
            'id' => $order->id,
            'customer_name' => $order->user?->name ?? '',
            'total' => (float) $order->total,
            'status' => $order->status,
            'remaining_amount' => (float) $order->remaining_amount,
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
}
