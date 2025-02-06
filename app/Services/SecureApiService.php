<?php

namespace App\Services;

use App\Exceptions\SecureApiException;
use App\HttpModels\SecureApiUser;
use Illuminate\Support\Facades\Http;

class SecureApiService
{
    private string $base_url;
    public function __construct(string $base_url)
    {
        $this->base_url = $base_url;
    }

    /**
     * @throws \Exception
     */
    public function createUser(array $data): \App\HttpModels\SecureApiUser
    {
        $userData = new SecureApiUser($data);

        $response = Http::post("{$this->base_url}/ad-network/register", $userData->toArray());
        if ($response->ok()) {
            return SecureApiUser::fromApiResponse($response->json());
        }

        throw new SecureApiException("Unable to create advertiser", $response->status(),$response->body());
    }

    /**
     * @throws SecureApiException
     */
    public function getUser(string $id): array
    {
        $response = Http::get("{$this->base_url}/ad-network/advertiser/{$id}");
        if ($response->ok()) {
            return SecureApiUser::fromApiResponse($response->json())->toArray();
        }
        throw new SecureApiException("Failed to fetch user", $response->status(),$response->body());
    }

    /**
     * @throws SecureApiException
     */
    public function login(array $data)
    {
        $response = Http::asForm()->post("https://alpha.secure-api.dev/api/v1/auth/token", [
            'grant_type' => 'password',
            'username' => $data['username'],
            'password' => $data['password'],
        ]);
        if ($response->ok()) {
            return $response->json();
        }

        throw new SecureApiException("Failed to fetch user", $response->status(),$response->body());
    }

}
