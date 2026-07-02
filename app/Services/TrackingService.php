<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\LocationLog;
use App\Models\MemberLocation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TrackingService
{
    private const MAX_ACCEPTED_ACCURACY_METERS = 120;
    private const MAX_REASONABLE_SPEED_KMH = 180;
    private const MIN_ROUTE_RECALCULATION_DISTANCE_METERS = 20;

    public function __construct(
        private readonly RoutingService $routing,
        private readonly CellTrackingService $cells,
    ) {}

    public function updateMemberLocation(User $member, array $data): MemberLocation
    {
        return DB::transaction(function () use ($member, $data): MemberLocation {
            $recordedAt = isset($data['recorded_at']) ? Carbon::parse($data['recorded_at']) : now();
            $seenAt = now();
            $previousLocation = MemberLocation::query()->where('user_id', $member->id)->first();
            $acceptedForRouting = $this->shouldAcceptPoint($previousLocation, $data, $seenAt);

            if ($acceptedForRouting || ! $previousLocation) {
                $location = MemberLocation::query()->updateOrCreate(
                    ['user_id' => $member->id],
                    [
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'accuracy' => $data['accuracy'] ?? null,
                        'speed' => $data['speed'] ?? null,
                        'network_type' => $data['network_type'] ?? 'unknown',
                        'is_online' => true,
                        'last_seen_at' => $seenAt,
                    ],
                );
            } else {
                $previousLocation->update([
                    'network_type' => $data['network_type'] ?? 'unknown',
                    'is_online' => true,
                    'last_seen_at' => $seenAt,
                ]);
                $location = $previousLocation->refresh();
            }

            $member->update(['status' => 'online']);

            $assignment = Assignment::query()
                ->with('report')
                ->where('assigned_member_id', $member->id)
                ->whereNotIn('status', [Assignment::STATUS_COMPLETED, Assignment::STATUS_CANCELLED])
                ->latest('assigned_at')
                ->first();

            $locationLog = LocationLog::query()->create([
                'user_id' => $member->id,
                'assignment_id' => $assignment?->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'speed' => $data['speed'] ?? null,
                'network_type' => $data['network_type'] ?? 'unknown',
                'recorded_at' => $recordedAt,
            ]);

            $handover = null;
            if (! empty($data['cell'])) {
                $handover = $this->cells->record($member, $assignment, $locationLog, $data['cell'], $recordedAt);
            }

            if ($acceptedForRouting && $assignment && $assignment->report && $this->shouldRecalculateRoute($assignment, $previousLocation, $data)) {
                $route = $this->routing->route(
                    (float) $data['latitude'],
                    (float) $data['longitude'],
                    (float) $assignment->report->latitude,
                    (float) $assignment->report->longitude,
                );

                $assignment->update([
                    'distance_meters' => $route['distance_meters'],
                    'duration_seconds' => $route['duration_seconds'],
                    'route_geometry_json' => $route['geometry'],
                    'route_steps_json' => $route['steps'],
                ]);
            }

            return $location->refresh()
                ->setAttribute('accepted_for_routing', $acceptedForRouting)
                ->setAttribute('cell_recorded', ! empty($data['cell']['cell_id']))
                ->setAttribute('handover_detected', $handover !== null);
        });
    }

    private function shouldAcceptPoint(?MemberLocation $previousLocation, array $data, Carbon $seenAt): bool
    {
        if (! $previousLocation) {
            return true;
        }

        $accuracy = isset($data['accuracy']) ? (float) $data['accuracy'] : null;
        $previousAccuracy = $previousLocation->accuracy !== null ? (float) $previousLocation->accuracy : null;

        if (
            $accuracy !== null &&
            $accuracy > self::MAX_ACCEPTED_ACCURACY_METERS &&
            ($previousAccuracy === null || $accuracy > $previousAccuracy)
        ) {
            return false;
        }

        $seconds = max(1, $seenAt->diffInSeconds($previousLocation->last_seen_at ?? $seenAt));
        $distanceMeters = $this->haversineMeters(
            (float) $previousLocation->latitude,
            (float) $previousLocation->longitude,
            (float) $data['latitude'],
            (float) $data['longitude'],
        );
        $speedKmh = ($distanceMeters / $seconds) * 3.6;
        $accuracyBuffer = max(60, $accuracy ?? 0, $previousAccuracy ?? 0);

        if ($distanceMeters > $accuracyBuffer && $speedKmh > self::MAX_REASONABLE_SPEED_KMH) {
            return false;
        }

        return true;
    }

    private function shouldRecalculateRoute(Assignment $assignment, ?MemberLocation $previousLocation, array $data): bool
    {
        if (! $assignment->route_geometry_json || ! $previousLocation) {
            return true;
        }

        $distanceMeters = $this->haversineMeters(
            (float) $previousLocation->latitude,
            (float) $previousLocation->longitude,
            (float) $data['latitude'],
            (float) $data['longitude'],
        );

        return $distanceMeters >= self::MIN_ROUTE_RECALCULATION_DISTANCE_METERS;
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
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
}
