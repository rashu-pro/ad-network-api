<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdServerService
{
    private $base_url;
    private $adserver_username;
    private $adserver_password;
    private $httpClient;

    public function __construct(string $url, string $username, string $password)
    {
        $this->base_url = rtrim($url, '/');
        $this->adserver_username = $username;
        $this->adserver_password = $password;
        $this->httpClient = Http::withBasicAuth($this->adserver_username, $this->adserver_password);
    }

    private function getUrl(string $path): string
    {
        return "{$this->base_url}/{$path}";
    }

    /**
     * Create a new zone on Adserver
     */
    public function createZone(int $publisherId, string $zoneName, int $width, int $height): int
    {
        $payload = [
            'publisherId' => $publisherId,
            'zoneName' => $zoneName,
            'type' => 0,
            'width' => $width,
            'height' => $height,
        ];
        $endpoint = $this->getUrl('zon/new');
        $response = $this->safePost($endpoint, $payload);

        return $response->zoneId;
    }

    /**
     * Delete a zone from the AdServer
     *
     * @throws ConnectionException
     */
    public function deleteZone(int $zoneAdServerId): bool
    {
        $endpoint = $this->getUrl("zon/{$zoneAdServerId}");

        try {
            $response = $this->httpClient->delete($endpoint);

            if (!$response->successful()) {
                Log::error("Failed to delete zone: {$zoneAdServerId}, Status: {$response->status()}, Body: {$response->body()}");
                throw new \Exception("Unable to process delete zone from AdServer.");
            }

            return true;
        } catch (ConnectionException $e) {
            throw new ConnectionException("Connection error during zone delete: " . $e->getMessage(), 0, $e);
        }
    }


    /**
     * Create a new campaign on Adserver
     */
    public function createCampaign(int $advertiserId, string $campaignName, string $startDate, string $endDate): int
    {
        $payload = [
            'advertiserId' => $advertiserId,
            'campaignName' => $campaignName,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'impressions' => 10000,
            'revenueType' => 1,
            'revenue' => 12.50,
            'weight' => 1
        ];
        $endpoint = $this->getUrl('cam/new');
        $response = $this->safePost($endpoint, $payload);

        return $response->campaignId;
    }

    /**
     * Update an existing campaign on the AdServer
     *
     * @throws \Exception
     */
    public function updateCampaign(
        int $campaignId,
        string $campaignName,
        string $startDate,
        string $endDate
    ): bool {
        $payload = [
            'campaignName' => $campaignName,
            'weight'       => 2,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
        ];

        $endpoint = $this->getUrl("cam/{$campaignId}");
        $response = $this->httpClient
            ->withHeaders(['Content-Type' => 'text/javascript'])
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to update campaign: ' . $response->body());
        }

        return true;
    }


    /**
     * Upload a banner for a campaign
     */
    public function uploadBanner(int $campaignId, string $bannerName, string $imageUrl, string $targetUrl, int $width, int $height): int
    {
        $payload = [
            'campaignId' => $campaignId,
            'bannerName' => $bannerName,
            'storageType' => "url",
            'imageURL' => $imageUrl,
            'url' => $targetUrl,
            'width' => $width,
            'height' => $height
        ];
        $endpoint = $this->getUrl('bnn/new');
        $response = $this->safePost($endpoint, $payload);

        return $response->bannerId;
    }

    /**
     * Link a zone to a campaign
     */
    public function linkZoneToCampaign($zoneId, $campaignId): bool
    {
        $endpoint = $this->getUrl("zon/{$zoneId}/cam/{$campaignId}");
        $response = $this->httpClient->post($endpoint);
        return $response->body() === '{"OK"}';
    }

    /**
     * Get Campaign Embeds (Invocation Code) by Ad Zone ID
     *
     * @throws ConnectionException
     */
    public function getCampaignEmbedsByAdZone(int $adZoneId): string
    {
        $endpoint = $this->getUrl("zon/{$adZoneId}/ic");
        $payload = [
            'code_type' => 'adjs'
        ];

//        Log::info('Fetching campaign embeds for AdZone ID: ' . $adZoneId, [
//            'endpoint' => $endpoint,
//            'payload' => $payload
//        ]);

        $response = $this->safePost($endpoint, $payload);

        return $response->invocation_code;
    }

    /**
     * Delete an existing campaign
     *
     * @throws ConnectionException
     */
    public function deleteCampaign(int $campaignId): bool
    {
        $endpoint = $this->getUrl("cam/{$campaignId}");

        try {
            $response = $this->httpClient->delete($endpoint);

            if (!$response->successful()) {
                throw new HttpException(
                    $response->status(),
                    "Error deleting campaign: " . $response->body()
                );
            }

            return true;
        } catch (ConnectionException $e) {
            throw new ConnectionException("Connection error during delete: " . $e->getMessage(), 0, $e);
        }
    }



    /**
     * Helper method to make safe POST requests
     */
    private function safePost(string $endpoint, array $payload = [])
    {
        try {
            $response = $this->httpClient->post($endpoint, $payload);

            if (!$response->successful()) {
                throw new HttpException(
                    $response->status(),
                    "Error from API: " . $response->body()
                );
            }

            return $response->object();
        } catch (ConnectionException $e) {
            throw new ConnectionException("Connection error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new advertiser in AdServer
     *
     * @throws \Exception
     */
    public function createAdvertiser(string $advertiserName, string $contactName, string $email): int
    {
        $payload = [
            'advertiserName' => $advertiserName,
            'contactName' => $contactName,
            'emailAddress' => $email,
            'username' => $email,
        ];

        $endpoint = $this->getUrl('adv/new');
        $response = $this->httpClient->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to create advertiser on AdServer: ' . $response->body());
        }

        return $response->object()->advertiserId;
    }

}
