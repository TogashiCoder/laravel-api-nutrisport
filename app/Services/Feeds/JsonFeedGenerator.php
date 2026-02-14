<?php

namespace App\Services\Feeds;

use App\Contracts\FeedGeneratorInterface;

class JsonFeedGenerator implements FeedGeneratorInterface
{
    public function generate(array $products): string
    {
        return json_encode(['products' => array_values($products)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function getContentType(): string
    {
        return 'application/json';
    }
}
