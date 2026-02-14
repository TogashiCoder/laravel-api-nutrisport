<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function register(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'site_id' => $data['site_id'],
        ]);
    }

    public function attemptLogin(array $credentials): bool
    {
        return Auth::guard('api')->attempt($credentials);
    }

    public function getUser(): ?User
    {
        $user = Auth::guard('api')->user();

        return $user instanceof User ? $user : null;
    }

    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    public function updateProfile(User $user, array $data): void
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update(['password' => $newPassword]);
    }

    /**
     * @return array{user: array, token: string, expires_in: int}
     */
    public function tokenResponse(User $user): array
    {
        $token = Auth::guard('api')->login($user);
        $ttl = (int) config('jwt.ttl', 360);

        return [
            'user' => $user->only(['id', 'name', 'email', 'site_id']),
            'token' => $token,
            'expires_in' => $ttl * 60,
        ];
    }
}
