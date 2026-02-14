<?php

namespace App\Services;

use App\Contracts\FeedGeneratorInterface;
use App\Models\Product;
use App\Services\Feeds\JsonFeedGenerator;
use App\Services\Feeds\XmlFeedGenerator;

class FeedService
{
    private const GENERATORS = [
        'json' => JsonFeedGenerator::class,
        'xml' => XmlFeedGenerator::class,
    ];

    public function getProductsForFeed(): array
    {
        return Product::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'in_stock' => $p->stock > 0,
            ])
            ->all();
    }

    public function generate(string $type): ?array
    {
        $generator = $this->resolveGenerator($type);
        if (!$generator) {
            return null;
        }
        $products = $this->getProductsForFeed();
        $body = $generator->generate($products);
        return [
            'content_type' => $generator->getContentType(),
            'body' => $body,
        ];
    }

    private function resolveGenerator(string $type): ?FeedGeneratorInterface
    {
        $class = self::GENERATORS[$type] ?? null;
        return $class ? app($class) : null;
    }
}
