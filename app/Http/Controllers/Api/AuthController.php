<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdatePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json($this->authService->tokenResponse($user), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! $this->authService->attemptLogin($request->validated())) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        $user = $this->authService->getUser();
        if (! $user) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        return response()->json($this->authService->tokenResponse($user));
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function user(): JsonResponse
    {
        $user = $this->authService->getUser();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'site_id']),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->getUser();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $this->authService->updateProfile($user, $request->validated());
        $user->refresh();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'site_id']),
            'message' => 'Profil mis à jour.',
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $this->authService->getUser();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $this->authService->updatePassword($user, $request->validated('password'));

        return response()->json(['message' => 'Mot de passe mis à jour.']);
    }
}
