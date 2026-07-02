<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\CellHandoverEvent;
use App\Models\CellObservation;
use App\Models\LocationLog;
use Illuminate\Support\Collection;

class TrailService
{
    private const MAX_ACCURACY_METERS = 120;
    private const MAX_SPEED_KMH = 180;
    private const GAP_SECONDS = 60;
    private const MAX_POINTS = 500;

    public function trailForAssignment(Assignment $assignment, bool $includeCellDetails = false): array
    {
        $logs = LocationLog::query()
            ->where('assignment_id', $assignment->id)
            ->when($assignment->assigned_member_id, fn ($query) => $query->where('user_id', $assignment->assigned_member_id))
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $accepted = $this->acceptedPoints($logs);
        $accepted = $this->downsample($accepted);

        $summary = $this->summary($accepted);
        $handovers = collect();
        if ($includeCellDetails) {
            $handovers = CellHandoverEvent::query()
                ->with(['fromCellTower', 'toCellTower'])
                ->where('assignment_id', $assignment->id)
                ->orderBy('observed_at')
                ->get();
            $summary['cell_observation_count'] = CellObservation::query()->where('assignment_id', $assignment->id)->count();
            $summary['handover_count'] = $handovers->count();
        }

        return [
            'assignment_id' => $assignment->id,
            'summary' => $summary,
            'segments' => $this->segments($accepted),
            'handovers' => $includeCellDetails ? $handovers->map(fn (CellHandoverEvent $event) => [
                'id' => $event->id,
                'latitude' => (float) $event->latitude,
                'longitude' => (float) $event->longitude,
                'observed_at' => $event->observed_at?->toISOString(),
                'from' => $this->serializeTower($event->fromCellTower),
                'to' => $this->serializeTower($event->toCellTower),
            ])->values()->all() : [],
        ];
    }

    private function serializeTower($tower): ?array
    {
        if (! $tower) {
            return null;
        }

        return [
            'radio_type' => $tower->radio_type,
            'operator' => $tower->operator_label ?? $tower->operator_name ?? 'Operator tidak diketahui',
            'mcc' => $tower->mcc,
            'mnc' => $tower->mnc,
            'cell_id' => $tower->cell_id,
            'tac_or_lac' => $tower->tac_or_lac,
            'pci_or_psc' => $tower->pci_or_psc,
        ];
    }

    private function acceptedPoints(Collection $logs): Collection
    {
        $points = collect();
        $last = null;

        foreach ($logs as $log) {
            if (! $this->hasValidCoordinate($log)) {
                continue;
            }

            if ($log->accuracy !== null && $log->accuracy > self::MAX_ACCURACY_METERS) {
                continue;
            }

            $point = $this->pointFromLog($log);

            if ($last) {
                $distance = $this->distanceMeters($last['latitude'], $last['longitude'], $point['latitude'], $point['longitude']);
                $seconds = max(1, $last['recorded_at']->diffInSeconds($point['recorded_at']));
                $speedKmh = ($distance / $seconds) * 3.6;

                if ($speedKmh > self::MAX_SPEED_KMH) {
                    continue;
                }

                if ($seconds <= self::GAP_SECONDS && $distance < $this->stationaryThreshold($last, $point)) {
                    continue;
                }
            }

            $points->push($point);
            $last = $point;
        }

        return $points;
    }

    private function downsample(Collection $points): Collection
    {
        if ($points->count() <= self::MAX_POINTS) {
            return $points->values();
        }

        $step = (int) ceil($points->count() / self::MAX_POINTS);

        return $points
            ->filter(fn ($point, $index) => $index === 0 || $index === $points->count() - 1 || $index % $step === 0)
            ->values();
    }

    private function segments(Collection $points): array
    {
        $segments = [];
        $current = [];
        $previous = null;

        foreach ($points as $point) {
            if ($previous && $previous['recorded_at']->diffInSeconds($point['recorded_at']) > self::GAP_SECONDS) {
                if (count($current) > 0) {
                    $segments[] = ['points' => $this->serializePoints($current)];
                }

                $current = [];
            }

            $current[] = $point;
            $previous = $point;
        }

        if (count($current) > 0) {
            $segments[] = ['points' => $this->serializePoints($current)];
        }

        return $segments;
    }

    private function summary(Collection $points): array
    {
        if ($points->isEmpty()) {
            return [
                'point_count' => 0,
                'distance_meters' => 0,
                'started_at' => null,
                'last_at' => null,
                'network_changes' => 0,
            ];
        }

        $distance = 0.0;
        $networkChanges = 0;
        $previous = null;

        foreach ($points as $point) {
            if ($previous) {
                if ($previous['recorded_at']->diffInSeconds($point['recorded_at']) <= self::GAP_SECONDS) {
                    $distance += $this->distanceMeters($previous['latitude'], $previous['longitude'], $point['latitude'], $point['longitude']);
                }

                if (($previous['network_type'] ?? 'unknown') !== ($point['network_type'] ?? 'unknown')) {
                    $networkChanges++;
                }
            }

            $previous = $point;
        }

        return [
            'point_count' => $points->count(),
            'distance_meters' => round($distance, 2),
            'started_at' => $points->first()['recorded_at']?->toISOString(),
            'last_at' => $points->last()['recorded_at']?->toISOString(),
            'network_changes' => $networkChanges,
        ];
    }

    private function serializePoints(array $points): array
    {
        return array_map(fn ($point) => [
            'id' => $point['id'],
            'latitude' => $point['latitude'],
            'longitude' => $point['longitude'],
            'accuracy' => $point['accuracy'],
            'speed' => $point['speed'],
            'network_type' => $point['network_type'],
            'recorded_at' => $point['recorded_at']?->toISOString(),
        ], $points);
    }

    private function pointFromLog(LocationLog $log): array
    {
        return [
            'id' => $log->id,
            'latitude' => (float) $log->latitude,
            'longitude' => (float) $log->longitude,
            'accuracy' => $log->accuracy !== null ? (float) $log->accuracy : null,
            'speed' => $log->speed !== null ? (float) $log->speed : null,
            'network_type' => $log->network_type ?? 'unknown',
            'recorded_at' => $log->recorded_at,
        ];
    }

    private function hasValidCoordinate(LocationLog $log): bool
    {
        return $log->latitude !== null
            && $log->longitude !== null
            && abs((float) $log->latitude) <= 90
            && abs((float) $log->longitude) <= 180;
    }

    private function stationaryThreshold(array $previous, array $point): float
    {
        $accuracy = max((float) ($previous['accuracy'] ?? 0), (float) ($point['accuracy'] ?? 0));

        return max(8, min(30, $accuracy * 0.35));
    }

    private function distanceMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371000;
        $fromLatRad = deg2rad($fromLat);
        $toLatRad = deg2rad($toLat);
        $deltaLat = deg2rad($toLat - $fromLat);
        $deltaLng = deg2rad($toLng - $fromLng);

        $haversine = sin($deltaLat / 2) ** 2
            + cos($fromLatRad) * cos($toLatRad) * sin($deltaLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    }
}
