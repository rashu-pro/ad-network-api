<?php

namespace App\Services;

use App\Models\CampaignMapping;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdServerService
{
    private $base_url;
    private $adserver_username;
    private $adserver_password;
    private $httpClient;

    public function __construct($url, $username, $password)
    {
        $this->base_url = rtrim($url,'/');
        $this->adserver_username = $username;
        $this->adserver_password = $password;
        $this->httpClient = Http::withBasicAuth($this->adserver_username, $this->adserver_password);
    }
    public function getUrl(string $path)
    {
        return "{$this->base_url}/{$path}";
    }

    /**
     *
     * @throws ConnectionException
     */
    public function getCampaignEmbedsByAdZone($adZoneId)
    {
        $endpoint = $this->getUrl('zon/'.$adZoneId.'/ic');
        $payload = [
            'code_type' => 'adjs'
        ];
        try {
            Log::info('endpoint for '.$adZoneId.' '.$endpoint,$payload);
            $response = $this->httpClient->post($endpoint, $payload);

            if (!$response->successful()) {
                throw new HttpException(
                    $response->status(),
                    "Error from API: " . $response->body()
                );
            }

            return $response->object()->invocation_code;
        } catch (ConnectionException $e) {
            throw new ConnectionException("Connection error: " . $e->getMessage(), 0, $e);
        }
    }

    public function createZone(
        int $publisher_id,
        string $zone_name,
        int $width,
        int $height
    ){
        $payload = [
            'publisherId' => $publisher_id,
            'zoneName' => $zone_name,
            'type' => 0,
            'width' => $width,
            'height' => $height,
        ];
        // Send GET request with Basic Auth
        $endpoint = env('AD_SERVER_BASE_URL').'/zon/new';
        $response = $this->httpClient->post($endpoint, $payload);
        $zone_adserver_id = $response->object()->zoneId;
        return $zone_adserver_id;
    }
}
