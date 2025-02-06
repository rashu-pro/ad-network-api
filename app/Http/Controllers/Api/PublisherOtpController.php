<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublisherCampaignStatus;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetZoneResource;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\PublisherAssetResource;
use App\HttpModels\Publisher;
use App\Models\Asset;
use App\Models\Campaign;
use App\Models\CampaignMapping;
use App\Models\PublisherAsset;
use App\Models\Zone;
use App\Repositories\Interfaces\AssetRepositoryInterface;
use App\Repositories\Interfaces\AssetValuationRepositoryInterface;
use App\Repositories\Interfaces\CampaignMappingRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function Symfony\Component\String\s;
use OpenApi\Attributes as OA;
class PublisherOtpController extends Controller
{
    use ApiResponse;
    protected AssetRepositoryInterface $asr;
    protected AssetValuationRepositoryInterface $asrv;
    protected CampaignRepositoryInterface $cmp;

    public function __construct(AssetRepositoryInterface $asr, AssetValuationRepositoryInterface $asrv, CampaignRepositoryInterface $cmp)
    {
        $this->asr = $asr;
        $this->asrv = $asrv;
        $this->cmp = $cmp;
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
        return $this->successResponse('Publisher assets',PublisherAssetResource::collection($user->assets()->get()));
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
            'min_population' => 'required|integer',
            'max_population' => 'nullable|integer',
            'min_duration_in_hour' => 'required|numeric',
            'price_per_hour' => 'required|numeric',
            'url' => 'nullable|string',
        ]);
        $user = Auth::guard('api')->user();
        $data = $request->only(['asset_id','min_duration_in_hour','price_per_hour','url', 'zone_id']);
        $validator = $this->asrv->validateAsset($request->asset_id,$request->min_population,$request->max_population ?? null);
        $asset = $this->asr->find($data['asset_id']);

        if($asset->type == 'online' && (!$request->has('url') || $request->get('url') == null)){
            return $this->errorResponse(message: 'Url is required for online asset',status: 422);
        }
        if(!$validator){
            return $this->errorResponse(message: 'Asset validation not found',status: 404);
        }
        if($validator->max_price_per_hour < $request->price_per_hour){
            return $this->errorResponse('Price is not acceptable for the mentioned population');
        }
        if($validator->min_duration_in_hour > $request->min_duration_in_hour){
            return $this->errorResponse('Duration is not acceptable for the mentioned population');
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
        return $this->successResponse('All campaigns',CampaignResource::collection(Campaign::whereIn('id',$mappings)->where('is_draft',false)->get()));
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
        if($request->status != PublisherCampaignStatus::APPROVE->value){
            //todo:: unlink zone from campaign
            //todo:: change campaign_mapping status
        }
    }
}
