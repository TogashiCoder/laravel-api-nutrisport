<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AgentAuthService
{
    /**
     * Agent JWT TTL: 8 hours (480 minutes).
     */
    private const AGENT_TTL_MINUTES = 480;

    public function attemptLogin(array $credentials): bool
    {
        Config::set('jwt.ttl', self::AGENT_TTL_MINUTES);

        return Auth::guard('agent')->attempt($credentials);
    }

    public function getAgent(): ?Agent
    {
        $agent = Auth::guard('agent')->user();

        return $agent instanceof Agent ? $agent : null;
    }

    public function logout(): void
    {
        Auth::guard('agent')->logout();
    }

    /**
     * @return array{agent: array, token: string, expires_in: int}
     */
    public function tokenResponse(Agent $agent): array
    {
        Config::set('jwt.ttl', self::AGENT_TTL_MINUTES);
        $token = Auth::guard('agent')->login($agent);

        return [
            'agent' => $agent->only(['id', 'name', 'email']),
            'token' => $token,
            'expires_in' => self::AGENT_TTL_MINUTES * 60, // seconds
        ];
    }
}
