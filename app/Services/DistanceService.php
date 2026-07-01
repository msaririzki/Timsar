<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;

class DistanceService
{
    private const ONLINE_WINDOW_SECONDS = 90;

    public function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public function nearestMembers(Report $report): Collection
    {
        return User::query()
            ->where('role', 'member')
            ->with('memberLocation')
            ->get()
            ->filter(fn (User $member): bool => $member->memberLocation !== null)
            ->map(function (User $member) use ($report): User {
                $location = $member->memberLocation;
                $member->setAttribute('distance_meters', $this->haversineMeters(
                    (float) $location->latitude,
                    (float) $location->longitude,
                    (float) $report->latitude,
                    (float) $report->longitude,
                ));
                $member->setAttribute('network_type', $location->network_type);
                $member->setAttribute('last_seen_at', $location->last_seen_at);
                $member->setAttribute('is_online', $location->is_online && $location->last_seen_at?->gt(now()->subSeconds(self::ONLINE_WINDOW_SECONDS)));

                return $member;
            })
            ->sortBy('distance_meters')
            ->values();
    }

    public function roughDurationSeconds(float $distanceMeters): int
    {
        $metersPerSecond = 25_000 / 3600;

        return max(60, (int) ceil($distanceMeters / $metersPerSecond));
    }
}
