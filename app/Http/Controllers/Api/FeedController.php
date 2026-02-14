<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeedService;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __construct(
        private FeedService $feedService
    ) {
    }

    public function json(): Response
    {
        $result = $this->feedService->generate('json');
        if (!$result) {
            abort(404);
        }
        return response($result['body'], 200, [
            'Content-Type' => $result['content_type'],
        ]);
    }

    public function xml(): Response
    {
        $result = $this->feedService->generate('xml');
        if (!$result) {
            abort(404);
        }
        return response($result['body'], 200, [
            'Content-Type' => $result['content_type'],
        ]);
    }
}
