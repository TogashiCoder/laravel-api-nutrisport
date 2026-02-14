<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cartId = $this->cartId($request);
        $validated = $request->validated();

        try {
            $cart = $this->cartService->addItem(
                $cartId,
                (int) $validated['site_id'],
                (int) $validated['product_id'],
                (int) $validated['quantity']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $response = response()->json([
            'cart' => $this->cartService->toResponse($cart),
            'message' => 'Item added.',
        ], 201);

        return $this->withCartToken($response, $cartId);
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        $cartId = $this->cartId($request);
        $cart = $this->cartService->removeItem($cartId, $productId);

        $response = response()->json([
            'cart' => $this->cartService->toResponse($cart),
            'message' => 'Item removed.',
        ]);

        return $this->withCartToken($response, $cartId);
    }

    public function show(Request $request): JsonResponse
    {
        $cartId = $this->cartId($request);
        $cart = $this->cartService->getCart($cartId);

        $response = response()->json([
            'cart' => $this->cartService->toResponse($cart),
        ]);

        return $this->withCartToken($response, $cartId);
    }

    public function clear(Request $request): JsonResponse
    {
        $cartId = $this->cartId($request);
        $this->cartService->clear($cartId);

        $response = response()->json(['message' => 'Cart cleared.']);
        return $this->withCartToken($response, $cartId);
    }

    private function cartId(Request $request): string
    {
        $token = $request->header('X-Cart-Token', '');
        $id = $this->cartService->getCartId($token);
        if ($token === '' && $id !== '') {
            return $id;
        }
        return $id;
    }

    private function withCartToken(JsonResponse $response, string $cartId): JsonResponse
    {
        $response->header('X-Cart-Token', $cartId);
        return $response;
    }
}
