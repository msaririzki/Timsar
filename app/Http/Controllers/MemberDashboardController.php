<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\MemberLocation;
use App\Models\Report;
use App\Services\TrackingService;
use Illuminate\Http\Request;

class MemberDashboardController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isMember(), 403);

        return view('member.dashboard', [
            'activeAssignment' => $this->activeAssignment($request),
            'reports' => Report::query()->whereNotIn('status', ['completed', 'cancelled'])->latest()->limit(10)->get(),
        ]);
    }

    public function updateLocation(Request $request, TrackingService $tracking)
    {
        abort_unless($request->user()->isMember(), 403);

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'network_type' => ['nullable', 'string', 'max:40'],
            'recorded_at' => ['nullable', 'date'],
            'cell' => ['nullable', 'array'],
            'cell.radio_type' => ['nullable', 'string', 'in:LTE,NR,WCDMA,GSM,UNKNOWN'],
            'cell.operator_name' => ['nullable', 'string', 'max:100'],
            'cell.network_operator_name' => ['nullable', 'string', 'max:100'],
            'cell.operator_label' => ['nullable', 'string', 'max:100'],
            'cell.network_operator_code' => ['nullable', 'string', 'max:20'],
            'cell.mcc' => ['nullable', 'string', 'max:10'],
            'cell.mnc' => ['nullable', 'string', 'max:10'],
            'cell.cell_id' => ['nullable', 'string', 'max:64'],
            'cell.tac_or_lac' => ['nullable', 'string', 'max:64'],
            'cell.pci_or_psc' => ['nullable', 'string', 'max:64'],
            'cell.signal_dbm' => ['nullable', 'integer', 'between:-200,0'],
            'cell.rsrp_dbm' => ['nullable', 'integer', 'between:-200,0'],
            'cell.rsrq_db' => ['nullable', 'numeric', 'between:-100,100'],
            'cell.sinr_db' => ['nullable', 'numeric', 'between:-100,100'],
            'cell.is_registered' => ['nullable', 'boolean'],
        ]);

        $location = $tracking->updateMemberLocation($request->user(), $data);

        return response()->json([
            'message' => 'Lokasi terkirim.',
            'data' => [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'accuracy' => $location->accuracy,
                'network_type' => $location->network_type,
                'last_seen_at' => $location->last_seen_at?->toISOString(),
                'accepted_for_routing' => (bool) $location->getAttribute('accepted_for_routing'),
                'cell_recorded' => (bool) $location->getAttribute('cell_recorded'),
                'handover_detected' => (bool) $location->getAttribute('handover_detected'),
            ],
        ]);
    }

    public function heartbeat(Request $request)
    {
        abort_unless($request->user()->isMember(), 403);

        $data = $request->validate([
            'network_type' => ['nullable', 'string', 'max:40'],
        ]);

        $request->user()->update(['status' => 'online']);

        MemberLocation::query()
            ->where('user_id', $request->user()->id)
            ->update([
                'network_type' => $data['network_type'] ?? 'unknown',
                'is_online' => true,
                'last_seen_at' => now(),
            ]);

        return response()->json([
            'message' => 'Status online terkirim.',
            'last_seen_at' => now()->toISOString(),
        ]);
    }

    public function activeAssignmentData(Request $request)
    {
        abort_unless($request->user()->isMember(), 403);

        $assignment = $this->activeAssignment($request);

        return response()->json([
            'assignment' => $assignment ? [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'status_label' => PublicTrackingController::assignmentLabel($assignment->status),
                'distance_meters' => $assignment->distance_meters,
                'duration_seconds' => $assignment->duration_seconds,
                'route_geometry' => $assignment->route_geometry_json,
                'report' => [
                    'tracking_code' => $assignment->report->tracking_code,
                    'incident_type' => $assignment->report->incident_type,
                    'description' => $assignment->report->description,
                    'latitude' => (float) $assignment->report->latitude,
                    'longitude' => (float) $assignment->report->longitude,
                    'status' => $assignment->report->status,
                ],
            ] : null,
            'member_location' => $request->user()->memberLocation,
        ]);
    }

    private function activeAssignment(Request $request): ?Assignment
    {
        return Assignment::query()
            ->with(['report', 'member.memberLocation'])
            ->where('assigned_member_id', $request->user()->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest('assigned_at')
            ->first();
    }
}
