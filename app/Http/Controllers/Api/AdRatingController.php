<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdRating;
use App\Models\CampaignMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdRatingController extends Controller
{
    public function show(CampaignMapping $mapping)
    {
        $rating = $mapping->rating;
        if (!$rating) return response()->json(['message' => 'Not rated yet'], 404);
        return response()->json($rating);
    }

    public function store(Request $request, CampaignMapping $mapping)
    {
        $this->authorizeRating($request, $mapping);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $existing = AdRating::where('campaign_mapping_id', $mapping->id)->first();
        if ($existing) {
            return response()->json(['message' => 'Rating already exists. Use PUT to update.'], 400);
        }

        $rating = AdRating::create([
            'campaign_mapping_id' => $mapping->id,
            'advertiser_id' => $request->user()->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($rating, 201);
    }

    public function update(Request $request, CampaignMapping $mapping)
    {
        $this->authorizeRating($request, $mapping);

        $rating = AdRating::where('campaign_mapping_id', $mapping->id)->firstOrFail();

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $rating->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($rating);
    }

    public function destroy(Request $request, CampaignMapping $mapping)
    {
        $this->authorizeRating($request, $mapping);

        $rating = AdRating::where('campaign_mapping_id', $mapping->id)->firstOrFail();
        $rating->delete();

        return response()->json(['message' => 'Rating deleted']);
    }

    protected function authorizeRating(Request $request, CampaignMapping $mapping)
    {
        if (Auth::guard('api')->id !== $mapping->campaign->advertiser_id) {
            abort(403, 'Unauthorized');
        }

        if ($mapping->status !== 'COMPLETED') {
            abort(400, 'Can only rate completed mappings.');
        }
    }
}
