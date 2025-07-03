<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Exceptions\SecureApiException;
use App\HttpModels\SecureApiUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    public function createExistingSecureApiUser(string $companySlug, array $data): \App\HttpModels\SecureApiUser
    {
        $payload = [
            'companyKey'      => $data['companyKey'] ?? null,
            'companySlug'     => $companySlug,
            'isAdPublisher'   => $data['isAdPublisher'] ?? false,
            'isAdvertiser'    => $data['isAdvertiser'] ?? false,
            'addressLatitude' => $data['addressLatitude'] ?? 0,
            'addressLongitude'=> $data['addressLongitude'] ?? 0,
            'contactInfo'     => [
                'name'  => $data['contactInfo']['name'] ?? '',
                'email' => $data['contactInfo']['email'] ?? '',
                'phone' => $data['contactInfo']['phone'] ?? ''
            ]
        ];

        $response = Http::post("{$this->base_url}/ad-network/register-existing-company", $payload);

        if ($response->ok()) {
            return new SecureApiUser($response->json());
        }

        throw new SecureApiException("Unable to create advertiser", $response->status(), $response->body());
    }


    /**
     * @throws SecureApiException
     */
    public function getUser(string $id, string $email): array
    {
        return Cache::remember("user_{$id}", now()->addDay(), function () use ($id,$email) {
            $user = User::where('secure_api_id', $id)->where('email',$email)->firstOrFail();

            // Determine URL based on user role
            $url = $user->hasRole(RolesEnum::PUBLISHER->value)
                ? "{$this->base_url}/ad-network/ad-publisher/{$id}"
                : "{$this->base_url}/ad-network/advertiser/{$id}";
            Log::info('url '.$url);
            // Fetch user data from external API
            $response = Http::get($url);

            if ($response->ok()) {
                return SecureApiUser::fromApiResponse($response->json())->toArray();
            }

            throw new SecureApiException("Failed to fetch user", $response->status(), $response->body());
        });
    }

    /**
     * @throws SecureApiException
     */
    public function login(array $data)
    {
        $response = Http::asForm()->withHeaders([
            'User-Agent' => 'MyCustomUserAgent/1.0',
            'Accept' => 'application/json',
            'Referer' => url()->current(),
        ])->post(env('SECURE_API_AUTHENTICATION_URL')."/api/v1/auth/token", [
            'grant_type' => 'password',
            'username' => $data['username'],
            'password' => $data['password'],
        ]);
        if ($response->ok()) {
            return $response->json();
        }

        throw new SecureApiException("Failed to fetch user", $response->status(),$response->body());
    }

    /**
     * Send a single email using the Secure API email template service.
     *
     * @param string $companyKey
     * @param string $templateIdentifier
     * @param string $recipient
     * @param array $placeholders
     * @param string|null $cc
     * @return array
     * @throws SecureApiException
     */
    public function sendSingleEmail(
        string $templateIdentifier,
        string $recipient,
        array $placeholders,
        ?string $cc = null
    ): mixed {
        $placeholders['Description'] = $placeholders['Description'] ?? ' ';

        $payload = [
            "CompanyKey" => env('EMAIL_TEMPLATE_COMPANY_KEY','D6A763AD-659E-403C-BE52-0429CCCA6457'),
            "EmailTemplateIdentifierName" => $templateIdentifier,
            "Recipient" => $recipient,
            "Placeholders" => $placeholders,
        ];
        Log::info('payload', $payload);
        if ($cc) {
            $payload["Cc"] = $cc;
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->post(env('SECURE_API_AUTHENTICATION_URL') . "/api/v1/send-single-email", $payload);

        if ($response->ok()) {
            return $response->json();
        }

        throw new SecureApiException("Failed to send email", $response->status(), $response->body());
    }


}
