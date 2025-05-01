<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublisherCampaignStatus;
use App\Enums\RolesEnum;
use App\Events\PublishCampaignMappingToAdServer;
use App\Events\SendCampaignCodesToPublishers;
use App\Exceptions\SecureApiException;
use App\Facades\AdServer;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetZoneResource;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\PublisherAssetResource;
use App\Http\Resources\PublisherEarningDetailsResource;
use App\HttpModels\Publisher;
use App\Models\Asset;
use App\Models\Campaign;
use App\Models\CampaignMapping;
use App\Models\PublisherAsset;
use App\Models\User;
use App\Models\Zone;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use App\Repositories\Interfaces\AssetValuationRepositoryInterface;
use App\Repositories\Interfaces\CampaignMappingRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Services\AdvertiserService;
use App\Services\BillingService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function Laravel\Prompts\error;
use function Symfony\Component\String\s;
use OpenApi\Attributes as OA;
class PublisherOtpController extends Controller
{
    use ApiResponse;
    protected AssetRepositoryInterface $asr;
    protected AssetValuationRepositoryInterface $asrv;
    protected CampaignRepositoryInterface $cmp;
    protected AdvertiserService $advertiserService;

    public function __construct(
        AssetRepositoryInterface $asr,
        AssetValuationRepositoryInterface $asrv,
        CampaignRepositoryInterface $cmp,
        AdvertiserService $advertiserService
    )
    {
        $this->asr = $asr;
        $this->asrv = $asrv;
        $this->cmp = $cmp;
        $this->advertiserService = $advertiserService;
    }

    #[OA\Get(
        path: "/api/publisher/assets",
        summary: "Get all assets for the authenticated publisher",
        security: [
            ["bearerAuth" => []] // Protected route requiring Publisher's Bearer token
        ],
        tags: ["Publisher"],
        responses: [
            new OA\Response(response: 200, description: "Publisher assets retrieved successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Publisher assets"),
                            new OA\Property(
                                property: "data",
                                description: "List of publisher assets",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "asset_id", type: "integer", example: 2),
                                        new OA\Property(property: "asset_name", type: "string", example: "Billboard Asset"),
                                        new OA\Property(property: "min_duration_in_hour", type: "number", format: "float", example: 1.5),
                                        new OA\Property(property: "price_per_hour", type: "number", format: "float", example: 50.00),
                                        new OA\Property(property: "url", type: "string", format: "url", example: "https://example.com/asset", nullable: true),
                                        new OA\Property(
                                            property: "zone",
                                            description: "Zone details associated with the asset",
                                            properties: [
                                                new OA\Property(property: "id", type: "integer", example: 5),
                                                new OA\Property(property: "name", type: "string", example: "Zone A"),
                                                new OA\Property(property: "width", type: "integer", example: 1920),
                                                new OA\Property(property: "height", type: "integer", example: 1080)
                                            ],
                                            type: "object"
                                        )
                                    ],
                                    type: "object"
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Unauthorized"),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "An unexpected error occurred"),
                        ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function assets()
    {
        $user = Auth::guard('api')->user();
        return $this->successResponse('Publisher assets',PublisherAssetResource::collection($user->assets()->latest()->get()));
    }

    #[OA\Get(
        path: "/api/publishers/available-zones/{asset_id}",
        summary: "Get available zones for a given asset",
        security: [
            ["bearerAuth" => []] // Protected route requiring Publisher's Bearer token
        ],
        tags: ["Publisher"],
        parameters: [
            new OA\Parameter(
                name: "asset_id",
                description: "ID of the asset for which zones are retrieved",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Available zones retrieved successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Available zones"),
                            new OA\Property(
                                property: "data",
                                description: "List of available zones",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "asset_id", type: "integer", example: 1),
                                        new OA\Property(property: "asset_name", type: "string", example: "Billboard Asset"),
                                        new OA\Property(property: "width", type: "integer", example: 1920),
                                        new OA\Property(property: "height", type: "integer", example: 1080),
                                        new OA\Property(property: "type_id", type: "integer", example: 2),
                                    ],
                                    type: "object"
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Unauthorized"),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 404, description: "Asset not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Asset not found"),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "An unexpected error occurred"),
                        ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function availableZones(Asset $asset)
    {
        $zones = $asset->zones()->get();
        return $this->successResponse('Available zones', AssetZoneResource::collection($zones));
    }

    #[OA\Post(
        path: "/api/publisher/set-asset",
        summary: "Set an asset for the logged in publisher",
        security: [
            ["bearerAuth" => []] // Protected route requiring Publisher's Bearer token
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["asset_id", "zone_id", "min_population", "min_duration_in_hour", "price_per_hour"],
                    properties: [
                        new OA\Property(
                            property: "asset_id",
                            description: "ID of the asset",
                            type: "integer",
                            example: 1
                        ),
                        new OA\Property(
                            property: "zone_id",
                            description: "ID of the zone where the asset belongs",
                            type: "integer",
                            example: 5
                        ),
                        new OA\Property(
                            property: "min_population",
                            description: "Minimum population capacity for the asset",
                            type: "integer",
                            example: 100
                        ),
                        new OA\Property(
                            property: "max_population",
                            description: "Maximum population capacity for the asset",
                            type: "integer",
                            example: 1000,
                            nullable: true
                        ),
                        new OA\Property(
                            property: "min_duration_in_hour",
                            description: "Minimum duration in hours for using the asset",
                            type: "number",
                            format: "float",
                            example: 1.5
                        ),
                        new OA\Property(
                            property: "price_per_hour",
                            description: "Price per hour for using the asset",
                            type: "number",
                            format: "float",
                            example: 50.00
                        ),
                        new OA\Property(
                            property: "url",
                            description: "URL for the asset (required if the asset type is 'online')",
                            type: "string",
                            example: "https://example.com/asset",
                            nullable: true
                        )
                    ],
                    type: "object"
                )
            )
        ),
        tags: ["Publisher"],
        responses: [
            new OA\Response(response: 200, description: "Asset added to the publisher",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Asset added to the publisher"),
                            new OA\Property(
                                property: "data",
                                description: "Details of the publisher asset",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(
                                        property: "publisher",
                                        description: "Details of the publisher",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 2),
                                            new OA\Property(property: "name", type: "string", example: "Publisher A")
                                        ],
                                        type: "object"
                                    ),
                                    new OA\Property(
                                        property: "asset",
                                        description: "Details of the asset",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Online Banner"),
                                            new OA\Property(property: "type", type: "string", example: "online")
                                        ],
                                        type: "object"
                                    ),
                                    new OA\Property(
                                        property: "zone",
                                        description: "Details of the zone",
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 5),
                                            new OA\Property(property: "name", type: "string", example: "Zone A")
                                        ],
                                        type: "object"
                                    ),
                                    new OA\Property(property: "min_duration_in_hour", type: "number", format: "float", example: 1.5),
                                    new OA\Property(property: "price_per_hour", type: "number", format: "float", example: 50.00),
                                    new OA\Property(property: "url", type: "string", example: "https://example.com/asset", nullable: true)
                                ],
                                type: "object"
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Validation Error"),
                            new OA\Property(property: "errors", type: "object"),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 404, description: "Asset validation not found or URL is required",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Asset validation not found or URL is required"),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "An unexpected error occurred"),
                        ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function setAsset(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|integer|exists:assets,id',
            'zone_id' => 'required|integer|exists:zones,id',
            'min_population' => 'nullable|integer',
            'max_population' => 'nullable|integer',
            'min_duration_in_hour' => 'required|numeric',
            'price_per_hour' => 'required|numeric',
            'url' => 'nullable|string',
        ]);
        $user = Auth::guard('api')->user();
        $asset = Asset::findOrFail($request->asset_id);
        $data = $request->only(['asset_id','min_duration_in_hour','price_per_hour', 'url', 'zone_id']);
        $secureApiUser = SecureApi::getUser($user->secure_api_id,$user->email);
//        $data['url'] = $request->url ? "{$request->url}?org_slug={$secureApiUser['companyKey']}": '';
        $url = rtrim($request->url, '/');
        $data['webhook_path'] = $url ? "{$url}/wp-json/adserver/v1/zone-scripts/web": '';
        if($asset->slug != 'website' && $asset->type == 'online'){
            $domain = $this->getDomainOnly($request->url);
            $data['webhook_path'] = $domain ? "{$domain}/wp-json/adserver/v1/{$secureApiUser['secure_api_id']}/zone-scripts/{$asset->slug}": '';
        }
        $validator = $this->asrv->validateAsset($request->asset_id,$request->min_population,$request->max_population ?? null);
        $asset = $this->asr->find($data['asset_id']);

        if($asset->type == 'online' && (!$request->has('url') || $request->get('url') == null)){
            return $this->errorResponse(message: 'Url is required for online asset',status: 422);
        }
        if($validator){
            if($validator->max_price_per_hour < $request->price_per_hour){
                return $this->errorResponse('Price is not acceptable for the mentioned population');
            }
            if($validator->min_duration_in_hour > $request->min_duration_in_hour){
                return $this->errorResponse('Duration is not acceptable for the mentioned population');
            }
        }


//        $securePublisher = SecureApi::getUser($user->secure_api_id);

        // Payload data
        $payload = [
            'agencyId' => 1,
            'publisherName' => $data['url'],
            'website' => $data['url'],
            'contactName' => 'test',
            'emailAddress' => $user->email,
        ];

        // Send GET request with Basic Auth
        $endpoint = env('AD_SERVER_BASE_URL').'/pub/new';
        $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
        $publisher_adserver_id = $response->object()->publisherId;
        $data['publisher_adserver_id'] = $publisher_adserver_id;

        $publisherAsset = $user->assets()->create($data);
        return $this->successResponse(message: "Asset added to the publisher",data: new PublisherAssetResource($publisherAsset));
    }

    function getDomainOnly($url) {     $parsedUrl = parse_url($url);     return isset($parsedUrl['scheme'], $parsedUrl['host'])         ? "{$parsedUrl['scheme']}://{$parsedUrl['host']}" : null; }


    #[OA\Get(
        path: "/api/publisher/campaigns",
        summary: "Get all active campaigns for the authenticated publisher",
        security: [
            ["bearerAuth" => []] // Protected route requiring Publisher's Bearer token
        ],
        tags: ["Publisher"],
        responses: [
            new OA\Response(response: 200, description: "All active campaigns retrieved successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "All campaigns"),
                            new OA\Property(
                                property: "data",
                                description: "List of active campaigns",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(
                                            property: "campaign",
                                            description: "Campaign details",
                                            properties: [
                                                new OA\Property(property: "id", type: "integer", example: 1),
                                                new OA\Property(property: "name", type: "string", example: "Winter Sale Campaign"),
                                                new OA\Property(property: "advertiser_adserver_id", type: "integer", example: 101),
                                                new OA\Property(property: "status", type: "string", example: "active"),
                                                new OA\Property(property: "is_draft", type: "boolean", example: false)
                                            ],
                                            type: "object"
                                        ),
                                        new OA\Property(
                                            property: "publishers",
                                            description: "Details of publishers and their assets",
                                            type: "array",
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: "publisher_id", type: "integer", example: 10),
                                                    new OA\Property(property: "publisher_name", type: "string", example: "Publisher A"),
                                                    new OA\Property(
                                                        property: "assets",
                                                        description: "Assets associated with the publisher",
                                                        type: "array",
                                                        items: new OA\Items(
                                                            properties: [
                                                                new OA\Property(property: "id", type: "integer", example: 1),
                                                                new OA\Property(property: "name", type: "string", example: "Billboard Asset"),
                                                                new OA\Property(property: "price_per_hour", type: "number", format: "float", example: 50.00),
                                                                new OA\Property(property: "calculated_price", type: "number", format: "float", example: 500.00),
                                                                new OA\Property(property: "start_date", type: "string", format: "date", example: "2024-01-01"),
                                                                new OA\Property(property: "end_date", type: "string", format: "date", example: "2024-12-31"),
                                                                new OA\Property(property: "zone_id", type: "integer", example: 20),
                                                                new OA\Property(property: "zone_adserver_id", type: "integer", example: 2001),
                                                                new OA\Property(property: "campaign_adserver_id", type: "integer", example: 3001),
                                                                new OA\Property(property: "url", type: "string", format: "url", example: "https://example.com/asset"),
                                                                new OA\Property(property: "target_url", type: "string", format: "url", example: "https://example.com/target"),
                                                                new OA\Property(property: "is_active", type: "boolean", example: true),
                                                                new OA\Property(property: "banner", type: "string", format: "url", example: "https://example.com/banner.jpg")
                                                            ],
                                                            type: "object"
                                                        )
                                                    )
                                                ],
                                                type: "object"
                                            )
                                        )
                                    ],
                                    type: "object"
                                )
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Unauthorized"),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "An unexpected error occurred"),
                        ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function allCampaigns()
    {
        $user = Auth::guard('api')->user();
        $mappings = CampaignMapping::where('publisher_id',$user->id)->pluck('campaign_id')->toArray();
        return $this->successResponse('All campaigns',CampaignResource::collection(Campaign::whereIn('id',$mappings)->where('is_draft',false)->latest()->get()));
    }

    #[OA\Post(
        path: "/api/publishers/campaign-approval/{campaignId}",
        summary: "Publish a campaign for a specific zone",
        security: [
            ["bearerAuth" => []] // Protected route requiring Publisher's Bearer token
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["zone_id", "status"],
                    properties: [
                        new OA\Property(
                            property: "zone_id",
                            description: "ID of the zone associated with the campaign",
                            type: "integer",
                            example: 5
                        ),
                        new OA\Property(
                            property: "note",
                            description: "Optional note for the campaign mapping",
                            type: "string",
                            example: "This is a high-priority zone.",
                            nullable: true
                        ),
                        new OA\Property(
                            property: "status",
                            description: "Status of the campaign mapping",
                            type: "string",
                            enum: ["approve", "reject"], // Replace with PublisherCampaignStatus::values() if dynamic enum values are possible
                            example: "approve"
                        )
                    ],
                    type: "object"
                )
            )
        ),
        tags: ["Publisher"],
        parameters: [
            new OA\Parameter(
                name: "campaignId",
                description: "ID of the campaign to publish",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Campaign status updated successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Campaign status is updated")
                        ]
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error or invalid campaign state",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Campaign or its banner does not exist"),
                            new OA\Property(property: "errors", type: "object")
                        ]
                    )
                )
            ),
            new OA\Response(response: 404, description: "Campaign not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Campaign not found")
                        ]
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "An unexpected error occurred")
                        ]
                    )
                )
            )
        ]
    )]
    public function publishCampaign($campaignId, Request $request)
    {
        $request->validate([
            'zone_id' => 'required|integer|exists:campaign_mappings,publisher_zone_id',
            'note' => 'nullable',
            'status' => 'required|string|in:'.implode(',',PublisherCampaignStatus::values()),
        ]);
        $campaign = Campaign::findOrFail($campaignId);
        if(
            $campaign->status != CampaignStatus::PUBLISH || $campaign->payment_status != PaymentStatus::PAID

        ){
            return $this->errorResponse(message: 'Campaign is not published or paid',errors: [
                'campaign' => 'Campaign status is not published',
            ],status:422);
        }
        $user = Auth::guard('api')->user();
        $campaignMappings = CampaignMapping::where('publisher_id',$user->id)->where('campaign_id',$campaignId)
            ->where('publisher_zone_id',$request->zone_id)
            ->get();
        foreach($campaignMappings as $campaignMapping){
            if($campaignMapping->is_active){
                continue;
            }
            $zone = Zone::findOrFail($campaignMapping->publisher_zone_id);
            $campaignMapping->update([
                'status' => $request->status,
                'note' => $request->note
            ]);
            $campaignMapping->refresh();
            if($campaignMapping->status == PublisherCampaignStatus::APPROVE){
                // Payload data
                $payload = [
                    'publisherId' => (int)$campaignMapping->publisherAsset->publisher_adserver_id,
                    'zoneName' => $zone->zone_name.'_'.now(),
                    'type' => 0,
                    'width' => $zone->width,
                    'height' => $zone->height,
                ];
                // Send GET request with Basic Auth
                $endpoint = env('AD_SERVER_BASE_URL').'/zon/new';
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
                $zone_adserver_id = $response->object()->zoneId;

                $campaignMapping->update([
                    'publisher_zone_adserver_id' => $zone_adserver_id,
                ]);
                $campaignMapping->refresh();
                // Payload data
                $payload = [
                    'advertiserId' => (int)$campaign->advertiser->adserver_id,
                    'campaignName' => $campaign->campaign_name.'_'.now(),
                    'startDate' => $campaign->start_date,
                    'endDate' => $campaign->end_date,
                    'impressions' => 10000,
                    'revenueType' => 1,
                    'revenue' => 12.50,
                    'weight' => 1
                ];

                // Send GET request with Basic Auth
                $endpoint = env('AD_SERVER_BASE_URL').'/cam/new';
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);

                $campaign_adserver_id = $response->object()->campaignId;
                $campaignMapping->update([
                    'campaign_adserver_id' => $campaign_adserver_id,
                ]);
                $campaignMapping->refresh();


                if(!$campaignMapping->hasMedia('banner')){
                    return $this->errorResponse(message: 'Campaign or its banner does not exists',errors: [
                        'campaign' => 'Campaign or its banner does not exists',
                    ],status:422);
                }
                $banner = $campaignMapping->getFirstMedia('banner');

                // Payload data
                $payload = [
                    'campaignId' => (int)$campaignMapping->campaign_adserver_id,
                    'bannerName' => $banner->name,
                    'storageType' => "url",
                    'imageURL' => $banner->getUrl(),
                    'url' => $campaign->target_url,
                    'width' => $campaignMapping->publisherZone->width,
                    'height' => $campaignMapping->publisherZone->height
                ];

                // Send GET request with Basic Auth
                $endpoint = env('AD_SERVER_BASE_URL').'/bnn/new';
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);

                $banner_adserver_id = $response->object()->bannerId;
                $campaignMapping->update([
                    'banner_adserver_id' => $banner_adserver_id,
                ]);

                $campaignMapping->refresh();

                $endpoint = env('AD_SERVER_BASE_URL').'/zon/'.$campaignMapping->publisher_zone_adserver_id.'/cam/'.$campaignMapping->campaign_adserver_id;
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint);
                $isActive = $response->body() === '{"OK"}';
                $campaignMapping->update([
                    'is_active' => $isActive,
                ]);
                $campaignMapping->refresh();
                if(!$campaignMapping->is_active){
                    throw new HttpException("Campaign was unable to publish. Try again later.");
                }
            }
        }

      return $this->successResponse(message: 'Campaign status is updated');
    }

    public function updateCampaignMappingStatus(CampaignMapping $campaignMapping, Request $request){
        $request->validate([
            'status' => 'required',
            'notes' => 'nullable'
        ]);
        if (now()->greaterThan(Carbon::parse($campaignMapping->end_date))) {
            return $this->errorResponse('Campaign mapping has already expired. Cannot update status.');
        }

        if($campaignMapping->campaign->status == CampaignStatus::PUBLISH){

            $campaignMapping->status  = $request->status;
            $campaignMapping->notes  = $request->notes;
            $campaignMapping->save();
            $campaignMapping->refresh();

            if($request->status != PublisherCampaignStatus::APPROVE->value){
                $campaignMapping->pauseHistories()->create([
                    'paused_at' => now(),
                ]);

                try {
                    AdServer::deleteZone($campaignMapping->publisher_zone_adserver_id);
                } catch (\Throwable $e) {
                    Log::error('Failed to delete zone from AdServer: '.$e->getMessage(), [
                        'zone_adserver_id' => $campaignMapping->publisher_zone_adserver_id
                    ]);
                    return $this->errorResponse('Failed to delete zone from AdServer: '.$e->getMessage());
                }
                $campaignMapping->is_active = false;
                $campaignMapping->code = null;
                $campaignMapping->save();
                $campaignMapping->refresh();
//                Log::info('mapping: ', $campaignMapping);
                $mappings = $campaignMapping->campaign->mappings;
                Log::info('valid mapping: ', $mappings->toArray());
                event(new SendCampaignCodesToPublishers($mappings));
            }else{
                if($campaignMapping->is_active == false){
                    event(new PublishCampaignMappingToAdServer($campaignMapping));
                }
            }
            return $this->successResponse('Campaign status is updated for '.$campaignMapping->publisherAsset->asset->name);
        }
        return $this->errorResponse('Campaign is not valid');
    }

    public function getCampaignScript(CampaignMapping $campaignMapping)
    {
        $adZoneId = $campaignMapping->publisher_zone_adserver_id;
        $payload = [
            'code_type' => 'adjs'
        ];
        if($adZoneId){
            $endpoint = env('AD_SERVER_BASE_URL').'/zon/'.$adZoneId.'/ic';
            try{
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint,$payload);
                $code = $response->object()->invocation_code;
                return $this->successResponse('Script generated',$code);
            }catch (SecureApiException $err){
                return $this->errorResponse($err->getMessage());
            }
        }
        return $this->errorResponse('No zone AdServer id found');
    }

    public function updateCampaign(Request $request, int $id = null){
        $campaign = Campaign::find($id);
        if(!$campaign){
            $request->validate([
                'zoneId' => 'required|integer|exists:publisher_assets,zone_id',
                'banner' => 'required|file|mimes:jpg,jpeg,png',
                'companyKey' => 'required'
            ]);
            $user = User::where('secure_api_id',$request->companyKey)->firstOrFail();

            $campaignData = $request->only([
                'campaign_name', 'target_url', 'start_date', 'end_date',
            ]);
            $campaignData['advertiser_id'] = $user->id;
            $campaignData['publisher_id'] = $user->id;
            $publisher_adserver_id = $user->publisher_advertiser_id;

            //get publisher_advertiser_id
            if(!$publisher_adserver_id){
                // Payload data
                $payload = [
                    'advertiserName' => $user->name ?? 'test_advertiser_'.$user->id,
                    'contactName'    => $user->name ?? 'test_advertiser_'.$user->id,
                    'emailAddress'   => $user->email,
                    'username'       => $user->email,
                ];

                // Send GET request with Basic Auth
                $endpoint = env('AD_SERVER_BASE_URL').'/adv/new';
                $response = Http::withBasicAuth(env('AD_SERVER_SUPER_ADMIN_USERNAME'), env('AD_SERVER_SUPER_ADMIN_PASSWORD'))->post($endpoint, $payload);
                $advertiser_id = $response->object()->advertiserId;

                //After the successful response add the advertiser_id into database
                $user->publisher_adserver_id = $advertiser_id;
                $publisher_adserver_id = $advertiser_id;
                $user->save();
                $user->refresh();
            }

            $campaignData['advertiser_adserver_id'] = $publisher_adserver_id;
            $campaignData['status'] = CampaignStatus::PUBLISH;
            //campaign create
            $campaign = $this->advertiserService->createCampaign($campaignData);

            //mapping create
            $publishersData = User::whereHas('roles', function ($query) {
                $query->where('name', RolesEnum::PUBLISHER->value);
            })->where('id', $request->publisher_id)
                ->has('assets')
                ->with('assets')
                ->get()
                ->map(function ($publisher) use ($request,$user){
                    return $publisher->assets->map(function ($asset) use ($publisher,$request,$user) {
                        $startDate = Carbon::parse($request->start_date);
                        $endDate = Carbon::parse($request->end_date);
                        $days = $startDate->diffInDays($endDate) + 1; // Include the start day
                        // Calculate the total price
                        $calculatedPrice = $asset->price_per_hour * 24 * $days;

                        return [
                            'advertiser_id' => $user->id,
                            'publisher_id' => $publisher->id,
                            'publisher_asset_id' => $asset->id,
                            'publisher_zone_id' => $asset->zone_id,
                            'start_date' => $request->start_date,
                            'end_date' => $request->end_date,
                            'status' => PublisherCampaignStatus::APPROVE,
                            'calculated_price' => 0,
                            'is_active' => true,
                        ];
                    })->toArray(); // Convert collection to array
                })
                ->flatten(1) // Flatten nested arrays
                ->toArray(); // Convert to a plain array
            $this->advertiserService->selectPublishers($campaign->id,$publishersData);
        }

        //updating banner
        $mappings = $campaign->mappings()->where('publisher_id',$request->publisher_id)
            ->where('publisher_zone_id',$request->zone_id)
            ->get();

        if($mappings->count() <= 0){
            return $this->errorResponse('Provided zone or publisher is not associated with this campaign');
        }

        $zone = Zone::findOrFail($request->zone_id);
        $validator = Validator::make($request->all(), [
            'banner' => 'required|file|mimes:jpg,jpeg,png|dimensions:width=' . $zone->width . ',height=' . $zone->height,
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator,'Invalid data',$validator->errors());
        }

        $tempPath = $request->file('banner')->store('temp');
        $bannerPath = storage_path('app/private/' . $tempPath);

        foreach ($mappings as $mapping) {
            if($mapping->hasMedia('banner')){
                $mapping->clearMediaCollection('banner');
            }
            $mapping->addMedia($bannerPath)
                ->withCustomProperties([
                    'publisher_id' => $mapping->publisher_id,
                    'publisher_zone_id' => $mapping->publisher_zone_id,
                    'publisher_asset_id' => $mapping->publisher_asset_id,
                    'campaign_id' => $campaign->id,
                ])
                ->preservingOriginal()
                ->toMediaCollection('banner');
        }
        Storage::delete('app/private/'.$tempPath);
        return $this->successResponse(message: 'uploaded successfully', data: [
            'campaign_id' => $campaign->id,
            'url' => $mappings->filter(function ($mapping){
                return $mapping->hasMedia('banner');
            })->map(function ($mapping) {
                return  $mapping->getFirstMedia('banner')->getUrl();
            })->toArray()
        ]);
    }

    public function bills(Request $request)
    {
        $publisherId = $request->user()->id;

        $mappings = CampaignMapping::where('publisher_id', $publisherId)
            ->with('campaign', 'publisherAsset', 'pauseHistories')
            ->get();

        $billing = new BillingService();

        return response()->json($mappings->map(function ($mapping) use ($billing) {
            return [
                'campaign_name' => $mapping->campaign->campaign_name,
                'asset_name' => $mapping->publisherAsset->asset->name ?? 'N/A',
                'price_per_hour' => $mapping->publisherAsset->price_per_hour,
                'billable_hours' => $billing->calculateCampaignMappingHours($mapping),
                'amount_earned' => $billing->calculateCampaignMappingBill($mapping),
            ];
        }));
    }

    public function showPublisherEarning(Request $request, CampaignMapping $mapping)
    {
        if ($mapping->publisher_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return new PublisherEarningDetailsResource($mapping);
    }

}
