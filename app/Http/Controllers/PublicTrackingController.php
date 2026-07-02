<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\TrailService;

class PublicTrackingController extends Controller
{
    private const ONLINE_WINDOW_SECONDS = 90;

    public function show(string $trackingCode)
    {
        $report = Report::query()->where('tracking_code', $trackingCode)->firstOrFail();

        return view('public.tracking', compact('report'));
    }

    public function data(string $trackingCode)
    {
        $report = Report::query()
            ->with(['assignedMember.memberLocation', 'activeAssignment.member.memberLocation'])
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();

        $assignment = $report->activeAssignment;
        $member = $assignment?->member ?? $report->assignedMember;
        $location = $member?->memberLocation;

        return response()->json([
            'report' => [
                'tracking_code' => $report->tracking_code,
                'incident_type' => $report->incident_type,
                'description' => $report->description,
                'status' => $report->status,
                'status_label' => self::statusLabel($report->status),
                'priority' => $report->priority,
                'latitude' => (float) $report->latitude,
                'longitude' => (float) $report->longitude,
                'created_at' => $report->created_at?->toISOString(),
                'closed_at' => $report->closed_at?->toISOString(),
                'closure_notes' => $report->closure_notes,
            ],
            'assignment' => $assignment ? [
                'status' => $assignment->status,
                'status_label' => self::assignmentLabel($assignment->status),
                'distance_meters' => $assignment->distance_meters,
                'duration_seconds' => $assignment->duration_seconds,
                'route_geometry' => $assignment->route_geometry_json,
            ] : null,
            'member' => $member ? [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'latitude' => $location?->latitude,
                'longitude' => $location?->longitude,
                'accuracy' => $location?->accuracy,
                'network_type' => $location?->network_type ?? 'unknown',
                'last_seen_at' => $location?->last_seen_at?->toISOString(),
                'is_online' => $location ? $location->last_seen_at?->gt(now()->subSeconds(self::ONLINE_WINDOW_SECONDS)) : false,
            ] : null,
        ]);
    }

    public function trail(string $trackingCode, TrailService $trail)
    {
        $report = Report::query()
            ->with('activeAssignment')
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();

        if (! $report->activeAssignment) {
            return response()->json([
                'assignment_id' => null,
                'summary' => [
                    'point_count' => 0,
                    'distance_meters' => 0,
                    'started_at' => null,
                    'last_at' => null,
                    'network_changes' => 0,
                ],
                'segments' => [],
            ]);
        }

        return response()->json($trail->trailForAssignment($report->activeAssignment));
    }

    public static function statusLabel(string $status): string
    {
        return [
            'new' => 'Laporan baru',
            'assigned' => 'Petugas ditugaskan',
            'on_the_way' => 'Petugas menuju lokasi',
            'arrived' => 'Petugas sampai lokasi',
            'handling' => 'Kejadian ditangani',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ][$status] ?? $status;
    }

    public static function assignmentLabel(string $status): string
    {
        return [
            'assigned' => 'Menunggu konfirmasi petugas',
            'accepted' => 'Tugas diterima',
            'on_the_way' => 'Menuju lokasi',
            'arrived' => 'Sampai lokasi',
            'handling' => 'Menangani kejadian',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ][$status] ?? $status;
    }
}
