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
    public function __construct(private readonly RoutingService $routing) {}

    public function updateMemberLocation(User $member, array $data): MemberLocation
    {
        return DB::transaction(function () use ($member, $data): MemberLocation {
            $recordedAt = isset($data['recorded_at']) ? Carbon::parse($data['recorded_at']) : now();

            $location = MemberLocation::query()->updateOrCreate(
                ['user_id' => $member->id],
                [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy' => $data['accuracy'] ?? null,
                    'speed' => $data['speed'] ?? null,
                    'network_type' => $data['network_type'] ?? 'unknown',
                    'is_online' => true,
                    'last_seen_at' => $recordedAt,
                ],
            );

            $member->update(['status' => 'online']);

            $assignment = Assignment::query()
                ->with('report')
                ->where('assigned_member_id', $member->id)
                ->whereNotIn('status', [Assignment::STATUS_COMPLETED, Assignment::STATUS_CANCELLED])
                ->latest('assigned_at')
                ->first();

            LocationLog::query()->create([
                'user_id' => $member->id,
                'assignment_id' => $assignment?->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'speed' => $data['speed'] ?? null,
                'network_type' => $data['network_type'] ?? 'unknown',
                'recorded_at' => $recordedAt,
            ]);

            if ($assignment && $assignment->report) {
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

            return $location->refresh();
        });
    }
}
