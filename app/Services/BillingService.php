<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignMapping;
use Carbon\Carbon;

class BillingService
{
    public function calculateCampaignMappingBill(CampaignMapping $mapping): float
    {
        return $this->calculateMappingBillBetween(
            $mapping,
            Carbon::parse($mapping->start_date),
            Carbon::parse($mapping->end_date)
        );
    }

    public function calculateCampaignBillTillNow(Campaign $campaign): float
    {
        return $campaign->mappings->sum(function ($mapping) {
            $start = Carbon::parse($mapping->start_date);
            $end = Carbon::parse($mapping->end_date);
            $now = Carbon::now();

            // Skip future mappings
            if ($now->lessThan($start)) {
                return 0;
            }

            // Limit end to now if still running
            $upperBound = $now->lessThan($end) ? $now : $end;

            return $this->calculateMappingBillBetween($mapping, $start, $upperBound);
        });
    }

    public function calculateTotalCampaignBill(Campaign $campaign): float
    {
        return $campaign->mappings->sum(function ($mapping) {
            return $this->calculateCampaignMappingBill($mapping);
        });
    }

    public function calculateCampaignMappingHours(CampaignMapping $mapping): float
    {
        $start = Carbon::parse($mapping->start_date);
        $end = Carbon::parse($mapping->end_date);

        if (!$start || !$end) {
            return 0;
        }

        $totalMinutes = $start->diffInMinutes($end);

        $pausedMinutes = $mapping->pauseHistories->reduce(function ($carry, $pause) use ($start, $end) {
            $pausedAt = Carbon::parse($pause->paused_at);
            $resumedAt = $pause->resumed_at ? Carbon::parse($pause->resumed_at) : now();

            $pauseStart = $pausedAt->lessThan($start) ? $start : $pausedAt;
            $pauseEnd = $resumedAt->greaterThan($end) ? $end : $resumedAt;

            if ($pauseEnd->lessThanOrEqualTo($pauseStart)) {
                return $carry;
            }

            return $carry + $pauseStart->diffInMinutes($pauseEnd);
        }, 0);

        $activeMinutes = max($totalMinutes - $pausedMinutes, 0);
        return round($activeMinutes / 60, 2); // return hours
    }

    public function calculateMappingBillBetween(CampaignMapping $mapping, Carbon $start, Carbon $end): float
    {
        if (!$start || !$end || !$mapping->publisherAsset) {
            return 0;
        }

        $totalMinutes = $start->diffInMinutes($end);

        $pausedMinutes = $mapping->pauseHistories->reduce(function ($carry, $pause) use ($start, $end) {
            $pausedAt = Carbon::parse($pause->paused_at);
            $resumedAt = $pause->resumed_at ? Carbon::parse($pause->resumed_at) : now();

            $pauseStart = $pausedAt->lessThan($start) ? $start : $pausedAt;
            $pauseEnd = $resumedAt->greaterThan($end) ? $end : $resumedAt;

            if ($pauseEnd->lessThanOrEqualTo($pauseStart)) {
                return $carry;
            }

            return $carry + $pauseStart->diffInMinutes($pauseEnd);
        }, 0);

        $activeMinutes = max($totalMinutes - $pausedMinutes, 0);
        $activeHours = $activeMinutes / 60;
        $rate = $mapping->publisherAsset->price_per_hour ?? 0;

        return round($activeHours * $rate, 2);
    }
}
