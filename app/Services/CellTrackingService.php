<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\CellHandoverEvent;
use App\Models\CellObservation;
use App\Models\CellTower;
use App\Models\LocationLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class CellTrackingService
{
    public function record(
        User $member,
        ?Assignment $assignment,
        LocationLog $locationLog,
        array $cell,
        Carbon $observedAt,
    ): ?CellHandoverEvent {
        if (empty($cell['cell_id']) || empty($cell['radio_type'])) {
            return null;
        }

        $identityKey = $this->identityKey($cell);
        $tower = CellTower::query()->firstOrCreate(
            ['identity_key' => $identityKey],
            [
                ...$this->towerAttributes($cell),
                'estimated_latitude' => $locationLog->latitude,
                'estimated_longitude' => $locationLog->longitude,
                'observation_count' => 0,
                'first_seen_at' => $observedAt,
                'last_seen_at' => $observedAt,
            ],
        );

        $previous = CellObservation::query()
            ->with('cellTower')
            ->where('user_id', $member->id)
            ->when(
                $assignment,
                fn ($query) => $query->where('assignment_id', $assignment->id),
                fn ($query) => $query->whereNull('assignment_id'),
            )
            ->where('is_registered', true)
            ->latest('observed_at')
            ->latest('id')
            ->first();

        CellObservation::query()->create([
            'cell_tower_id' => $tower->id,
            'user_id' => $member->id,
            'assignment_id' => $assignment?->id,
            'location_log_id' => $locationLog->id,
            'latitude' => $locationLog->latitude,
            'longitude' => $locationLog->longitude,
            'accuracy' => $locationLog->accuracy,
            'signal_dbm' => $cell['signal_dbm'] ?? null,
            'rsrp_dbm' => $cell['rsrp_dbm'] ?? null,
            'rsrq_db' => $cell['rsrq_db'] ?? null,
            'sinr_db' => $cell['sinr_db'] ?? null,
            'is_registered' => $cell['is_registered'] ?? true,
            'observed_at' => $observedAt,
        ]);

        $this->updateTowerEstimate($tower, $locationLog, $observedAt, $cell);

        if (($cell['is_registered'] ?? true) !== true || ! $previous || $previous->cell_tower_id === $tower->id) {
            return null;
        }

        return CellHandoverEvent::query()->create([
            'user_id' => $member->id,
            'assignment_id' => $assignment?->id,
            'from_cell_tower_id' => $previous->cell_tower_id,
            'to_cell_tower_id' => $tower->id,
            'latitude' => $locationLog->latitude,
            'longitude' => $locationLog->longitude,
            'observed_at' => $observedAt,
        ]);
    }

    private function identityKey(array $cell): string
    {
        return hash('sha256', implode('|', [
            strtoupper((string) ($cell['radio_type'] ?? 'UNKNOWN')),
            $cell['mcc'] ?? '-',
            $cell['mnc'] ?? '-',
            $cell['tac_or_lac'] ?? '-',
            $cell['cell_id'] ?? '-',
        ]));
    }

    private function towerAttributes(array $cell): array
    {
        return [
            'radio_type' => strtoupper((string) ($cell['radio_type'] ?? 'UNKNOWN')),
            'operator_name' => $cell['operator_name'] ?? $cell['network_operator_name'] ?? null,
            'operator_label' => $cell['operator_label'] ?? $cell['operator_name'] ?? null,
            'network_operator_code' => $cell['network_operator_code'] ?? null,
            'mcc' => $cell['mcc'] ?? null,
            'mnc' => $cell['mnc'] ?? null,
            'cell_id' => (string) $cell['cell_id'],
            'tac_or_lac' => isset($cell['tac_or_lac']) ? (string) $cell['tac_or_lac'] : null,
            'pci_or_psc' => isset($cell['pci_or_psc']) ? (string) $cell['pci_or_psc'] : null,
        ];
    }

    private function updateTowerEstimate(CellTower $tower, LocationLog $locationLog, Carbon $observedAt, array $cell): void
    {
        $count = (int) $tower->observation_count;
        $nextCount = $count + 1;
        $latitude = (($tower->estimated_latitude ?? $locationLog->latitude) * $count + $locationLog->latitude) / $nextCount;
        $longitude = (($tower->estimated_longitude ?? $locationLog->longitude) * $count + $locationLog->longitude) / $nextCount;

        $tower->update([
            ...$this->towerAttributes($cell),
            'estimated_latitude' => $latitude,
            'estimated_longitude' => $longitude,
            'observation_count' => $nextCount,
            'last_seen_at' => $observedAt,
        ]);
    }
}
