<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Services\AgentAuthService;
use Illuminate\Http\JsonResponse;

class AgentAuthController extends Controller
{
    public function __construct(
        private AgentAuthService $agentAuthService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        if (! $this->agentAuthService->attemptLogin($request->validated())) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        $agent = $this->agentAuthService->getAgent();
        if (! $agent) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        return response()->json($this->agentAuthService->tokenResponse($agent));
    }

    public function logout(): JsonResponse
    {
        $this->agentAuthService->logout();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }
}
