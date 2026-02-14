<?php

namespace App\Contracts;

interface FeedGeneratorInterface
{
    /**
     * @param array<int, array{id: int, name: string, in_stock: bool}> $products
     */
    public function generate(array $products): string;

    public function getContentType(): string;
}
