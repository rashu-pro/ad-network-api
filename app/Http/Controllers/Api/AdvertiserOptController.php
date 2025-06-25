<?php

namespace App\Http\Controllers\Api;

use App\Enums\PublisherCampaignStatus;
use App\Enums\RolesEnum;
use App\Events\PublishCampaignMappingToAdServer;
use App\Events\SendCampaignCodesToPublishers;
use App\Exceptions\SecureApiException;
use App\Facades\AdServer;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdvertiserPaymentResource;
use App\Http\Resources\CampaignPaymentResource;
use App\Http\Resources\CampaignResource;
use App\Listeners\GenerateCampaignCodes;
use App\Listeners\PublishCampaignToAdserver;
use App\Models\Campaign;
use App\Models\CampaignMapping;
use App\Models\CampaignPayment;
use App\Models\PublisherAsset;
use App\Models\User;
use App\Models\Zone;
use App\Services\AdvertiserService;
use App\Services\BillingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AdvertiserOptController extends Controller
{
    use ApiResponse;
    protected $advertiserService;
    public function __construct(AdvertiserService $advertiserService)
    {
        $this->advertiserService = $advertiserService;
    }

    #[OA\Get(
        path: "/api/advertiser/campaigns",
        summary: "Get all campaigns for the authenticated advertiser",
        security: [
            ["bearerAuth" => []] // Protected route requiring Bearer token
        ],
        tags: ["Advertiser"],
        responses: [
            new OA\Response(response: 200, description: "All campaigns retrieved successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "All campaigns"),
                            new OA\Property(
                                property: "data",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(
                                            property: "campaign",
                                            properties: [
                                                new OA\Property(property: "id", type: "integer", example: 1),
                                                new OA\Property(property: "name", type: "string", example: "Summer Sale Campaign"),
                                                new OA\Property(property: "advertiser_adserver_id", type: "integer", example: 101),
                                                new OA\Property(property: "status", type: "string", enum: ["draft", "publish"], example: "draft"),
                                                new OA\Property(property: "is_draft", type: "boolean", example: false),
                                            ],
                                            type: "object"
                                        ),
                                        new OA\Property(
                                            property: "publishers",
                                            type: "array",
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: "publisher_id", type: "integer", example: 10),
                                                    new OA\Property(property: "publisher_name", type: "string", example: "Publisher A"),
                                                    new OA\Property(
                                                        property: "assets",
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
                                                                new OA\Property(property: "banner", type: "string", format: "url", example: "https://example.com/banner.jpg"),
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
                        ],
                        type: "object"
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
        try{
            $user = Auth::guard('api')->user();
            $campaigns = CampaignResource::collection($user->campaigns()->latest()->get());
            return $this->successResponse(message: 'All campaigns',data: $campaigns);
        }catch (\Exception $e){
            return $this->errorResponse($e->getMessage());
        }
    }


    #[OA\Post(
        path: "/api/advertiser/create-campaign",
        summary: "Create a new campaign",
        security: [
            ["bearerAuth" => []] // Protected route requiring Bearer token
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["publisher_ids", "campaign_name", "target_url", "start_date", "end_date"],
                    properties: [
                        new OA\Property(
                            property: "publisher_ids",
                            description: "List of publisher IDs for the campaign",
                            type: "array",
                            items: new OA\Items(type: "integer"),
                            example: [1, 2, 3]
                        ),
                        new OA\Property(
                            property: "campaign_name",
                            description: "Name of the campaign",
                            type: "string",
                            example: "Winter Sale Campaign"
                        ),
                        new OA\Property(
                            property: "target_url",
                            description: "Target URL for the campaign",
                            type: "string",
                            format: "url",
                            example: "https://example.com/winter-sale"
                        ),
                        new OA\Property(
                            property: "is_draft",
                            description: "Indicates if the campaign is a draft",
                            type: "boolean",
                            example: false
                        ),
                        new OA\Property(
                            property: "start_date",
                            description: "Start date of the campaign",
                            type: "string",
                            format: "date",
                            example: "2024-01-01"
                        ),
                        new OA\Property(
                            property: "end_date",
                            description: "End date of the campaign",
                            type: "string",
                            format: "date",
                            example: "2024-01-31"
                        )
                    ],
                    type: "object"
                )
            )
        ),
        tags: ["Advertiser"],
        responses: [
            new OA\Response(response: 201, description: "Campaign created successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "created successfully"),
                            new OA\Property(
                                property: "data",
                                description: "Details of the created campaign",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "Winter Sale Campaign"),
                                    new OA\Property(property: "advertiser_adserver_id", type: "integer", example: 101),
                                    new OA\Property(property: "status", type: "string", enum: ["draft", "publish"], example: "draft"),
                                    new OA\Property(property: "is_draft", type: "boolean", example: true),
                                    new OA\Property(
                                        property: "publishers",
                                        type: "array",
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: "publisher_id", type: "integer", example: 1),
                                                new OA\Property(property: "publisher_name", type: "string", example: "Publisher A"),
                                                new OA\Property(
                                                    property: "assets",
                                                    type: "array",
                                                    items: new OA\Items(
                                                        properties: [
                                                            new OA\Property(property: "id", type: "integer", example: 10),
                                                            new OA\Property(property: "name", type: "string", example: "Billboard Asset"),
                                                            new OA\Property(property: "price_per_hour", type: "number", format: "float", example: 50.00),
                                                            new OA\Property(property: "calculated_price", type: "number", format: "float", example: 1200.00),
                                                            new OA\Property(property: "start_date", type: "string", format: "date", example: "2024-01-01"),
                                                            new OA\Property(property: "end_date", type: "string", format: "date", example: "2024-01-31"),
                                                            new OA\Property(property: "zone_id", type: "integer", example: 20),
                                                            new OA\Property(property: "is_active", type: "boolean", example: false),
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
    public function createCampaign(Request $request)
    {
        $request->validate([
            'publisher_ids' => 'required|array',
            'publisher_asset_ids' => 'required|array',
        ]);
        $user = Auth::guard('api')->user();

        $campaignData = $request->only([
            'campaign_name', 'target_url', 'start_date', 'end_date',
        ]);
        $campaignData['advertiser_id'] = $user->id;
        $campaignData['advertiser_adserver_id'] = $user->adserver_id;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $days = $startDate->diffInDays($endDate) + 1;
        $minDuration = DB::table('publisher_assets')->whereIn('publisher_id',$request->publisher_ids)->min('min_duration_in_hour') / 24;
        if(($days) < $minDuration){
            return $this->errorResponse('You have to run ad for at least '.$minDuration.' Days');
        }
        $campaign = $this->advertiserService->createCampaign($campaignData);

        // Fetch only the selected assets
        $publisherAssets = DB::table('publisher_assets')
            ->whereIn('id', $request->publisher_asset_ids)
            ->whereIn('publisher_id', $request->publisher_ids)
            ->get();

        $publishersData = $publisherAssets->map(function ($asset) use ($request, $user, $days) {
            $calculatedPrice = $asset->price_per_hour * 24 * $days;

            return [
                'advertiser_id' => $user->id,
                'publisher_id' => $asset->publisher_id,
                'publisher_asset_id' => $asset->id,
                'publisher_zone_id' => $asset->zone_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'calculated_price' => $calculatedPrice,
                'is_active' => false,
            ];
        })->toArray();
        $this->advertiserService->selectPublishers($campaign->id,$publishersData);

        return $this->successResponse(message: 'created successfully', data: new CampaignResource($campaign));
    }

    public function getUniqueZones(Campaign $campaign)
    {
        $user = Auth::guard('api')->user();
        $zones = $campaign->mappings()
            ->select('publisher_zone_id', 'publisher_id')
            ->distinct() // Ensure unique rows
            ->get()
            ->groupBy('publisher_zone_id')
            ->map(function ($group) {
                $zoneId =  $group->first()->publisher_zone_id;
                $z = Zone::find($zoneId);
                return [
                    'id' => $zoneId,
                    'asset_id' => $z?->asset_id,
                    'asset_name' => $z?->asset->name,
                    'type_id' => $z?->type_id,
                    'width' => $z?->width,
                    'height' => $z?->height,
                    'publisher_ids' => $group->pluck('publisher_id')->unique()->values()->toArray()
                ];
            })
            ->values()
            ->toArray();

        return $this->successResponse('Zones for banner',$zones);
    }

    #[OA\Post(
        path: "/api/advertiser/upload-campaign-banner/{id}",
        summary: "Upload a banner for a campaign",
        security: [
            ["bearerAuth" => []] // Protected route requiring Bearer token
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["banner", "publisher_id", "zone_id"],
                    properties: [
                        new OA\Property(
                            property: "banner",
                            description: "The banner file to upload. Only jpg, jpeg, png are allowed",
                            type: "string",
                            format: "binary"
                        ),
                        new OA\Property(
                            property: "publisher_id",
                            description: "ID of the publisher associated with the campaign",
                            type: "integer",
                            example: 2
                        ),
                        new OA\Property(
                            property: "zone_id",
                            description: "Zone ID of the publisher asset",
                            type: "integer",
                            example: 5
                        )
                    ],
                    type: "object"
                )
            )
        ),
        tags: ["Advertiser"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID of the campaign",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Banner uploaded successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "uploaded successfully"),
                            new OA\Property(
                                property: "data",
                                properties: [
                                    new OA\Property(
                                        property: "url",
                                        description: "URLs of the uploaded banners",
                                        type: "array",
                                        items: new OA\Items(type: "string", format: "url"),
                                        example: [
                                            "https://example.com/banner1.jpg",
                                            "https://example.com/banner2.jpg"
                                        ]
                                    )
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
            new OA\Response(response: 404, description: "Campaign not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Campaign not found"),
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

    public function uploadCampaign(Campaign $campaign, Request $request)
    {
        $request->validate([
            'banner' => 'required|file|mimes:jpg,jpeg,png',
            'publisher_ids' => 'required|array|min:1',
            'publisher_ids.*' => 'required|integer|exists:users,id',
            'zone_id' => 'nullable|integer|exists:publisher_assets,zone_id', // zone_id is now nullable
        ]);

        $mappings = $campaign->mappings()
            ->whereIn('publisher_id', $request->publisher_ids)
            ->when($request->zone_id, function ($query) use ($request) {
                $query->where('publisher_zone_id', $request->zone_id);
            })
            ->get();
//        dd($mappings);

        if ($mappings->isEmpty()) {
            return $this->errorResponse('Provided zone or publisher is not associated with this campaign');
        }

        $zone = null;
        if ($request->zone_id) {
            $zone = Zone::find($request->zone_id); // Don't fail if not found
        }

        $rules = [
            'banner' => 'required|file|mimes:jpg,jpeg,png',
        ];

        if ($zone && $zone->width && $zone->height) {
            $rules['banner'] .= '|dimensions:width=' . $zone->width . ',height=' . $zone->height;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator, 'Invalid data', $validator->errors());
        }

        $tempPath = $request->file('banner')->store('temp');
        $bannerPath = storage_path('app/private/' . $tempPath);

        foreach ($mappings as $mapping) {
            if ($mapping->hasMedia('banner')) {
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

        Storage::delete('app/private/' . $tempPath);

        return $this->successResponse(message: 'uploaded successfully', data: [
            'url' => $mappings->filter(function ($mapping) {
                return $mapping->hasMedia('banner');
            })->map(function ($mapping) {
                return $mapping->getFirstMedia('banner')->getUrl();
            })->toArray()
        ]);
    }


    #[OA\Post(
        path: "/api/advertiser/update-campaign/{id}",
        summary: "Update an advertiser's campaign",
        security: [
            ["bearerAuth" => []] // Protected route requiring Bearer token
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: [],
                    properties: [
                        new OA\Property(
                            property: "campaign_name",
                            description: "Name of the campaign",
                            type: "string",
                            example: "Spring Sale Campaign",
                            nullable: true
                        ),
                        new OA\Property(
                            property: "target_url",
                            description: "Target URL for the campaign",
                            type: "string",
                            format: "url",
                            example: "https://example.com/spring-sale",
                            nullable: true
                        ),
                        new OA\Property(
                            property: "is_draft",
                            description: "Indicates if the campaign is a draft",
                            type: "boolean",
                            example: false,
                            nullable: true
                        ),
                        new OA\Property(
                            property: "status",
                            description: "Status of the campaign",
                            type: "string",
                            enum: ["draft", "publish"],
                            example: "draft", // Enum values for the status field
                            nullable: true
                        )
                    ],
                    type: "object"
                )
            )
        ),
        tags: ["Advertiser"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID of the campaign to update",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Campaign updated successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "updated successfully"),
                            new OA\Property(
                                property: "data",
                                description: "Details of the updated campaign",
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "Spring Sale Campaign"),
                                    new OA\Property(property: "advertiser_adserver_id", type: "integer", example: 101),
                                    new OA\Property(property: "status", type: "string", enum: ["draft", "publish"], example: "draft"),
                                    new OA\Property(property: "is_draft", type: "boolean", example: false),
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
            new OA\Response(response: 404, description: "Campaign not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Campaign not found"),
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
    public function updateCampaign(Campaign $campaign, Request $request)
    {
        $campaign->update($request->only(
            'campaign_name', 'target_url', 'is_draft', 'status'
        ));
        try {
            foreach ($campaign->mappings as $mapping) {
                $mapping->update([
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date
                ]);

                if (!empty($mapping->campaign_adserver_id)) {
                    AdServer::updateCampaign(
                        $mapping->campaign_adserver_id,
                        $campaign->campaign_name,
                        $mapping->start_date,
                        $mapping->end_date
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to update campaign on AdServer', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Failed to update campaign in AdServer: ' . $e->getMessage());
        }
        return $this->successResponse(message: 'updated successfully', data: new CampaignResource($campaign));
    }

    public function availablePublishers()
    {
        $data = User::whereHas('roles', function ($query) {
                $query->where('name', RolesEnum::PUBLISHER->value);
            })
            ->whereHas('assets')
            ->with(['assets.asset'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($publisher) {
                try{
                    $securePublisher = SecureApi::getUser($publisher->secure_api_id, $publisher->email);

                    return [
                        'id' => $publisher->id,
                        'company_name' => $securePublisher['businessName'],
                        'publisher_address' => $securePublisher['businessInfo']['address'],
                        'logo' => $securePublisher['businessInfo']['logoUrl'],
                        'email' => $publisher->email,
                        'assets' => $publisher->assets->map(function ($publisherAsset) {
                            return [
                                'id' => $publisherAsset->asset->id ?? null,
                                'publisher_asset_id' => $publisherAsset->id,
                                'name' => $publisherAsset->asset->name ?? null,
                                'slug' => $publisherAsset->asset->slug ?? null,
                                'type' => $publisherAsset->asset->type ?? null,
                                'url' => $publisherAsset->url ?? null,
                            ];
                        }),
                    ];
                }catch (SecureApiException $apiException){
                    return null;
                }
            })
            ->filter()
            ->values();

        return $this->successResponse('All available publishers',$data);
    }

    public function getCampaign(Campaign $campaign)
    {
        return $this->successResponse('All campaign', new CampaignResource($campaign));
    }

    public function reuploadToAsset(CampaignMapping $campaignMapping, Request $request)
    {
        $request->validate([
            'banner' => 'required|file|mimes:jpg,jpeg,png',
        ]);
        if($campaignMapping->hasMedia('banner')){
            $campaignMapping->clearMediaCollection('banner');
        }
        $campaignMapping->addMedia($request->file('banner'))
            ->withCustomProperties([
                'publisher_id' => $campaignMapping->publisher_id,
                'publisher_zone_id' => $campaignMapping->publisher_zone_id,
                'publisher_asset_id' => $campaignMapping->publisher_asset_id,
                'campaign_id' => $campaignMapping->id,
            ])
            ->preservingOriginal()
            ->toMediaCollection('banner');


        return $this->successResponse(message: 'uploaded successfully', data: [
            'url' => $campaignMapping->getFirstMedia('banner')->getUrl()
        ]);
    }
    public function publishInAsset(CampaignMapping $campaignMapping)
    {
        if($campaignMapping->is_active){
            return $this->errorResponse(message: 'Campaign already published', status: 409);
        }
        if($campaignMapping->status != PublisherCampaignStatus::APPROVE){
            $campaignMapping->update(['status' => PublisherCampaignStatus::APPROVE->value,  'notes' => null]);
            $campaignMapping->refresh();
        }

        if(Carbon::parse($campaignMapping->start_date)->lte(Carbon::today())){
            event(new PublishCampaignMappingToAdServer($campaignMapping));
        }
        return $this->successResponse(message: 'Campaign published');
    }

    #[OA\Delete(
        path: "/api/advertiser/delete-campaign/{id}",
        summary: "Delete a campaign",
        security: [
            ["bearerAuth" => []] // Protected route requiring Bearer token
        ],
        tags: ["Advertiser"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID of the campaign to delete",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Campaign deleted successfully",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(property: "message", type: "string", example: "Campaign deleted successfully"),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 404, description: "Campaign not found",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: false),
                            new OA\Property(property: "message", type: "string", example: "Campaign not found"),
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
    public function deleteCampaign(Campaign $campaign)
    {
        try {
            $user = Auth::guard('api')->user();

            if ($campaign->advertiser_id !== $user->id) {
                return $this->errorResponse('Unauthorized to delete this campaign', 403);
            }


            foreach ($campaign->mappings as $mapping){
                if (!empty($mapping->campaign_adserver_id)) {
                    AdServer::deleteCampaign($mapping->campaign_adserver_id);
                    $mapping->update([
                        'code' => null
                    ]);
                    $mapping->refresh();
                }
            }
            event(new SendCampaignCodesToPublishers($campaign->mappings()->get()));


            // Delete the campaign and its related mappings, banners if any
            $campaign->mappings()->delete();
            $campaign->delete();

            return $this->successResponse('Campaign deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function listAdvertiserPayments(Request $request)
    {
        $user = Auth::guard('api')->user(); // advertiser
        return CampaignPaymentResource::collection(
            CampaignPayment::where('advertiser_id', $user->id)->latest()->get()
        );
    }
    public function showAdvertiserPayment(Request $request, CampaignPayment $payment)
    {
        // Ensure advertiser owns this payment
        if ($payment->advertiser_id !== Auth::guard('api')->user()->id) {
            abort(403, 'Unauthorized');
        }

        return new AdvertiserPaymentResource($payment);
    }
}
