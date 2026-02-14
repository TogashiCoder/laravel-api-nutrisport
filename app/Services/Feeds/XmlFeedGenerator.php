<?php

namespace App\Services\Feeds;

use App\Contracts\FeedGeneratorInterface;

class XmlFeedGenerator implements FeedGeneratorInterface
{
    public function generate(array $products): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><products/>');
        foreach ($products as $p) {
            $item = $xml->addChild('product');
            $item->addChild('id', (string) $p['id']);
            $item->addChild('name', htmlspecialchars($p['name'], ENT_XML1, 'UTF-8'));
            $item->addChild('in_stock', $p['in_stock'] ? 'true' : 'false');
        }
        return $xml->asXML();
    }

    public function getContentType(): string
    {
        return 'application/xml';
    }
}
