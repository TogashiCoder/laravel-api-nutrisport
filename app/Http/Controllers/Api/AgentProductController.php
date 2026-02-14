<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateAgentProductRequest;
use App\Models\Product;
use App\Models\ProductSitePrice;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgentProductController extends Controller
{
    public function store(CreateAgentProductRequest $request): JsonResponse
    {
        $agent = Auth::guard('agent')->user();
        if (!$agent || $agent->id !== 1) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validated();
        $sites = Site::query()->whereIn('code', ['fr', 'it', 'be'])->get()->keyBy('code');

        $product = DB::transaction(function () use ($validated, $sites) {
            $product = Product::query()->create([
                'name' => $validated['name'],
                'stock' => (int) $validated['stock'],
                'description' => null,
                'is_available' => true,
            ]);

            foreach (['fr', 'it', 'be'] as $code) {
                $site = $sites->get($code);
                if ($site) {
                    ProductSitePrice::query()->create([
                        'product_id' => $product->id,
                        'site_id' => $site->id,
                        'price' => (float) $validated['prices'][$code],
                    ]);
                }
            }

            return $product->load(['sitePrices.site']);
        });

        $pricesBySite = $product->sitePrices->mapWithKeys(fn ($sp) => [
            $sp->site->code => (float) $sp->price,
        ])->all();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'prices' => $pricesBySite,
            ],
            'message' => 'Produit créé.',
        ], 201);
    }
}
