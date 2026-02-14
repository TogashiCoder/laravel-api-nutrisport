<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $siteId = (int) $validated['site_id'];
        $perPage = (int) ($validated['per_page'] ?? 15);

        $products = Product::query()
            ->whereHas('sitePrices', fn ($q) => $q->where('site_id', $siteId))
            ->with(['sitePrices' => fn ($q) => $q->where('site_id', $siteId)->select('product_id', 'site_id', 'price')])
            ->orderBy('id')
            ->paginate($perPage);

        $items = $products->getCollection()->map(function (Product $product) {
            $price = $product->sitePrices->first();
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $price ? (float) $price->price : null,
                'in_stock' => $product->stock > 0,
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'integer', 'exists:sites,id'],
        ]);

        $siteId = (int) $validated['site_id'];

        $product = Product::query()
            ->with(['sitePrices' => fn ($q) => $q->where('site_id', $siteId)->select('product_id', 'site_id', 'price')])
            ->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $price = $product->sitePrices->first();
        if (!$price) {
            return response()->json(['message' => 'Product not found for this site.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $price->price,
                'in_stock' => $product->stock > 0,
                'description' => $product->description,
            ],
        ]);
    }
}
