<?php

namespace App\Http\Controllers\Api\External;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublisherCampaignStatus;
use App\Events\CampaignPublished;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Campaign;
use App\Models\CampaignMapping;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PublisherCampaignController extends Controller
{
    public function storeCampaign(string $slug, Request $request)
    {
        $asset = Asset::where('slug', $slug)->firstOrFail();
        $zone = Zone::where('zone_name', 'digital_banner')->firstOrFail();
        $user = User::firstOrCreate(
            ['secure_api_id' => $request->companyKey],
            [
                'email' => $request->input('companyKey') . '@example.com',
            ]
        );


        // Ensure asset is registered for this user
        $targetAsset = $this->ensurePublisherAssetExists($user, $asset, $zone);

        // Validate request for campaign creation
        $validated = $request->validate([
            'banner' => 'required|file|mimes:jpg,jpeg,png',
            'companyKey' => 'required',
            'campaign_name' => 'required',
            'target_url' => 'nullable|url',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Ensure advertiser exists in ad server
        $publisher_adserver_id = $this->ensureAdvertiserExists($user);

        // Create campaign
        $campaign = $this->createCampaign($user, $validated, $publisher_adserver_id);

        // Map campaign to asset and zone
        $mapping = $this->createCampaignMapping($campaign, $user, $targetAsset, $request);

        // Validate banner size against zone
        $this->validateBannerSize($request, $targetAsset);

        // Store banner in media collection
        $this->storeCampaignBanner($mapping, $request, $campaign);

        // Fire event for campaign publishing
        event(new CampaignPublished($campaign));

        // Response
        return response()->json([
            'success' => true,
            'message' => 'Uploaded successfully',
            'data' => [
                'campaign_id' => $campaign->id,
                'url' => [$mapping->getFirstMediaUrl('banner')],
            ],
        ]);
    }

    private function ensurePublisherAssetExists(User $user, Asset $asset, Zone $zone)
    {
        $existingAsset = $user->assets->where('asset_id', $asset->id)->first();

        if ($existingAsset) {
            return $existingAsset;
        }

        // If asset does not exist for this user, register and create it
        $url = 'https://ads.secure-api.net/digital-display/?org_slug=' . $user->secure_api_key;
        $domain = 'https://ads.secure-api.net';
        $webhookPath = "{$domain}/wp-json/adserver/v1/{$user->secure_api_id}/zone-scripts/{$asset->slug}";

        $payload = [
            'agencyId' => 1,
            'publisherName' => $url,
            'website' => $url,
            'contactName' => 'test',
            'emailAddress' => $user->email,
        ];

        $endpoint = env('AD_SERVER_BASE_URL') . '/pub/new';
        $response = Http::withBasicAuth(
            env('AD_SERVER_SUPER_ADMIN_USERNAME'),
            env('AD_SERVER_SUPER_ADMIN_PASSWORD')
        )->post($endpoint, $payload);

        $publisher_adserver_id = optional($response->object())->publisherId;

        if (!$publisher_adserver_id) {
            throw new \Exception('Failed to register publisher in Ad Server');
        }

        // Create asset for user
        return $user->assets()->create([
            'asset_id' => $asset->id,
            'zone_id' => $zone->id,
            'url' => $url,
            'webhook_path' => $webhookPath,
            'publisher_adserver_id' => $publisher_adserver_id,
            'min_duration_in_hour' => 720,
            'price_per_hour' => 0,
        ]);
    }

    private function ensureAdvertiserExists(User $user)
    {
        if ($user->publisher_advertiser_id) {
            return $user->publisher_advertiser_id;
        }

        $payload = [
            'advertiserName' => $user->name ?? 'test_advertiser_' . $user->id,
            'contactName' => $user->name ?? 'test_advertiser_' . $user->id,
            'emailAddress' => $user->email,
            'username' => $user->email,
        ];

        $endpoint = env('AD_SERVER_BASE_URL') . '/adv/new';
        $response = Http::withBasicAuth(
            env('AD_SERVER_SUPER_ADMIN_USERNAME'),
            env('AD_SERVER_SUPER_ADMIN_PASSWORD')
        )->post($endpoint, $payload);

        $advertiser_id = optional($response->object())->advertiserId;

        if (!$advertiser_id) {
            throw new \Exception('Failed to register advertiser in Ad Server');
        }

        // Save in user record
        $user->publisher_advertiser_id = $advertiser_id;
        $user->save();

        return $advertiser_id;
    }

    private function createCampaign(User $user, array $validated, $advertiser_adserver_id)
    {
        return Campaign::create([
            'campaign_name' => $validated['campaign_name'],
            'target_url' => $validated['target_url'] ?? null,
            'advertiser_id' => $user->id,
            'publisher_id' => $user->id,
            'advertiser_adserver_id' => $advertiser_adserver_id,
            'status' => CampaignStatus::PUBLISH,
            'payment_status' => PaymentStatus::PAID,
            'isDraft' => false,
        ]);
    }

    private function createCampaignMapping(Campaign $campaign, User $user, $asset, Request $request)
    {
        return CampaignMapping::create([
            'campaign_id' => $campaign->id,
            'advertiser_id' => $user->id,
            'publisher_id' => $user->id,
            'publisher_asset_id' => $asset->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'calculated_price' => 0,
            'is_active' => false,
            'publisher_zone_id' => $asset->zone_id,
            'publisher_zone_adserver_id' => $asset->zone_adserver_id,
            'status' => PublisherCampaignStatus::APPROVE,
        ]);
    }

    private function validateBannerSize(Request $request, $asset)
    {
        $zone = Zone::findOrFail($asset->zone_id);

        $validator = Validator::make($request->all(), [
            'banner' => "required|file|mimes:jpg,jpeg,png|dimensions:width={$zone->width},height={$zone->height}",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator, 'Invalid banner dimensions', $validator->errors());
        }
    }

    private function storeCampaignBanner(CampaignMapping $mapping, Request $request, Campaign $campaign)
    {
        if ($mapping->hasMedia('banner')) {
            $mapping->clearMediaCollection('banner');
        }

        $mapping->addMedia($request->file('banner'))
            ->withCustomProperties([
                'publisher_id' => $mapping->publisher_id,
                'publisher_zone_id' => $mapping->publisher_zone_id,
                'publisher_asset_id' => $mapping->publisher_asset_id,
                'campaign_id' => $campaign->id,
            ])
            ->preservingOriginal()
            ->toMediaCollection('banner');
    }
}
