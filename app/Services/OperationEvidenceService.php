<?php

namespace App\Services;

use App\Http\Controllers\PublicTrackingController;
use App\Models\Assignment;
use App\Models\CellHandoverEvent;
use App\Models\CellObservation;
use App\Models\LocationLog;
use App\Models\Report;
use Illuminate\Support\Collection;

class OperationEvidenceService
{
    public function __construct(
        private readonly TrailService $trail,
    ) {}

    public function forReport(Report $report, ?int $limit = 80): array
    {
        $report->loadMissing(['assignedMember', 'activeAssignment.member', 'closedBy']);
        $assignment = $report->activeAssignment;

        if (! $assignment) {
            return [
                'summary' => $this->emptySummary($report),
                'timeline' => $this->timeline($report, null),
                'logs' => collect(),
                'trail' => null,
            ];
        }

        $assignment->loadMissing(['member', 'report']);
        $trail = $this->trail->trailForAssignment($assignment, true);

        return [
            'summary' => $this->summary($report, $assignment, $trail),
            'timeline' => $this->timeline($report, $assignment),
            'logs' => $this->mobileLogs($assignment, $limit),
            'trail' => $trail,
        ];
    }

    public function mobileLogPayload(Assignment $assignment, int $limit = 80): array
    {
        $assignment->loadMissing(['report']);
        $evidence = $this->forReport($assignment->report, $limit);

        return [
            'summary' => $evidence['summary'],
            'logs' => $evidence['logs']->values()->all(),
        ];
    }

    private function summary(Report $report, Assignment $assignment, array $trail): array
    {
        $locationQuery = LocationLog::query()->where('assignment_id', $assignment->id);
        $cellQuery = CellObservation::query()->where('assignment_id', $assignment->id);

        $firstLocation = (clone $locationQuery)->oldest('recorded_at')->first();
        $latestLocation = (clone $locationQuery)->latest('recorded_at')->first();
        $firstCell = CellObservation::query()
            ->with('cellTower')
            ->where('assignment_id', $assignment->id)
            ->where('is_registered', true)
            ->oldest('observed_at')
            ->first();
        $latestCell = CellObservation::query()
            ->with('cellTower')
            ->where('assignment_id', $assignment->id)
            ->where('is_registered', true)
            ->latest('observed_at')
            ->first();

        return [
            'tracking_code' => $report->tracking_code,
            'report_status' => PublicTrackingController::statusLabel($report->status),
            'assignment_status' => PublicTrackingController::assignmentLabel($assignment->status),
            'member_name' => $assignment->member?->name ?? '-',
            'gps_points' => (clone $locationQuery)->count(),
            'cell_observations' => (clone $cellQuery)->count(),
            'cell_points' => $trail['summary']['cell_point_count'] ?? 0,
            'handovers' => CellHandoverEvent::query()->where('assignment_id', $assignment->id)->count(),
            'network_changes' => $this->networkChanges($assignment),
            'distance_meters' => $trail['summary']['distance_meters'] ?? 0,
            'started_at' => $firstLocation?->recorded_at,
            'last_at' => $latestLocation?->recorded_at,
            'first_cell' => $this->cellSummary($firstCell),
            'latest_cell' => $this->cellSummary($latestCell),
        ];
    }

    private function emptySummary(Report $report): array
    {
        return [
            'tracking_code' => $report->tracking_code,
            'report_status' => PublicTrackingController::statusLabel($report->status),
            'assignment_status' => 'Belum ditugaskan',
            'member_name' => '-',
            'gps_points' => 0,
            'cell_observations' => 0,
            'cell_points' => 0,
            'handovers' => 0,
            'network_changes' => 0,
            'distance_meters' => 0,
            'started_at' => null,
            'last_at' => null,
            'first_cell' => null,
            'latest_cell' => null,
        ];
    }

    private function mobileLogs(Assignment $assignment, ?int $limit): Collection
    {
        $query = LocationLog::query()
            ->with(['cellObservation.cellTower'])
            ->where('assignment_id', $assignment->id)
            ->latest('recorded_at')
            ->latest('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (LocationLog $log) => $this->serializeLog($log));
    }

    private function serializeLog(LocationLog $log): array
    {
        $observation = $log->cellObservation;
        $tower = $observation?->cellTower;

        return [
            'id' => $log->id,
            'recorded_at' => $log->recorded_at,
            'recorded_at_iso' => $log->recorded_at?->toISOString(),
            'latitude' => (float) $log->latitude,
            'longitude' => (float) $log->longitude,
            'accuracy' => $log->accuracy !== null ? (float) $log->accuracy : null,
            'speed' => $log->speed !== null ? (float) $log->speed : null,
            'network_type' => $log->network_type ?? 'unknown',
            'cell_available' => $observation !== null && $tower !== null,
            'cell' => $tower ? [
                'operator' => $tower->operator_label ?? $tower->operator_name ?? 'Operator tidak diketahui',
                'radio_type' => $tower->radio_type,
                'mcc' => $tower->mcc,
                'mnc' => $tower->mnc,
                'cell_id' => $tower->cell_id,
                'tac_or_lac' => $tower->tac_or_lac,
                'pci_or_psc' => $tower->pci_or_psc,
            ] : null,
            'signal' => $observation ? [
                'signal_dbm' => $observation->signal_dbm,
                'rsrp_dbm' => $observation->rsrp_dbm,
                'rsrq_db' => $observation->rsrq_db !== null ? (float) $observation->rsrq_db : null,
                'sinr_db' => $observation->sinr_db !== null ? (float) $observation->sinr_db : null,
            ] : null,
        ];
    }

    private function timeline(Report $report, ?Assignment $assignment): Collection
    {
        return collect([
            ['event' => 'Laporan masuk', 'time' => $report->created_at, 'actor' => $report->reporter_name, 'note' => $report->incident_type],
            ['event' => 'Petugas ditugaskan', 'time' => $assignment?->assigned_at, 'actor' => $assignment?->member?->name, 'note' => 'Admin posko menetapkan anggota lapangan'],
            ['event' => 'Tugas diterima', 'time' => $assignment?->accepted_at, 'actor' => $assignment?->member?->name, 'note' => 'Anggota membuka tugas'],
            ['event' => 'Menuju lokasi', 'time' => $assignment?->started_at, 'actor' => $assignment?->member?->name, 'note' => 'GPS dan BTS mulai menjadi bukti perjalanan'],
            ['event' => 'Tiba di lokasi', 'time' => $assignment?->arrived_at, 'actor' => $assignment?->member?->name, 'note' => 'Anggota sampai di area laporan'],
            ['event' => 'Penanganan lapangan', 'time' => $report->status === Report::STATUS_HANDLING ? $report->updated_at : null, 'actor' => $assignment?->member?->name, 'note' => 'Operasi penanganan sedang berjalan'],
            ['event' => 'Laporan selesai', 'time' => $assignment?->completed_at, 'actor' => $assignment?->member?->name, 'note' => $report->closure_notes],
            ['event' => 'Laporan dibatalkan', 'time' => $report->status === Report::STATUS_CANCELLED ? ($report->closed_at ?? $report->updated_at) : null, 'actor' => $report->closedBy?->name ?? 'Admin posko', 'note' => $report->closure_notes],
        ])->filter(fn (array $item) => $item['time'])->values();
    }

    private function networkChanges(Assignment $assignment): int
    {
        $logs = LocationLog::query()
            ->where('assignment_id', $assignment->id)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->pluck('network_type');

        $changes = 0;
        $previous = null;

        foreach ($logs as $networkType) {
            $current = $networkType ?: 'unknown';
            if ($previous !== null && $current !== $previous) {
                $changes++;
            }
            $previous = $current;
        }

        return $changes;
    }

    private function cellSummary(?CellObservation $observation): ?array
    {
        $tower = $observation?->cellTower;
        if (! $observation || ! $tower) {
            return null;
        }

        return [
            'operator' => $tower->operator_label ?? $tower->operator_name ?? 'Operator tidak diketahui',
            'radio_type' => $tower->radio_type,
            'cell_id' => $tower->cell_id,
            'tac_or_lac' => $tower->tac_or_lac,
            'pci_or_psc' => $tower->pci_or_psc,
            'rsrp_dbm' => $observation->rsrp_dbm,
            'observed_at' => $observation->observed_at,
        ];
    }
}
