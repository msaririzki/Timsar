<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('admin.dashboard', [
            'reports' => Report::query()->with('assignedMember')->latest()->limit(20)->get(),
            'members' => User::query()->where('role', 'member')->with('memberLocation')->orderBy('name')->get(),
            'stats' => [
                'new' => Report::query()->where('status', 'new')->count(),
                'active' => Report::query()->whereIn('status', ['assigned', 'on_the_way', 'arrived', 'handling'])->count(),
                'members_online' => User::query()->where('role', 'member')->whereHas('memberLocation', fn ($q) => $q->where('last_seen_at', '>=', now()->subSeconds(30)))->count(),
                'completed_today' => Report::query()->where('status', 'completed')->whereDate('updated_at', today())->count(),
            ],
        ]);
    }

    public function mapData(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json([
            'reports' => Report::query()
                ->with('assignedMember')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->latest()
                ->get()
                ->map(fn (Report $report): array => [
                    'id' => $report->id,
                    'tracking_code' => $report->tracking_code,
                    'incident_type' => $report->incident_type,
                    'status' => $report->status,
                    'status_label' => PublicTrackingController::statusLabel($report->status),
                    'priority' => $report->priority,
                    'latitude' => (float) $report->latitude,
                    'longitude' => (float) $report->longitude,
                    'assigned_member' => $report->assignedMember?->name,
                    'url' => route('admin.reports.show', $report),
                ]),
            'members' => User::query()
                ->where('role', 'member')
                ->with('memberLocation')
                ->get()
                ->filter(fn (User $member): bool => $member->memberLocation !== null)
                ->values()
                ->map(fn (User $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'phone' => $member->phone,
                    'latitude' => (float) $member->memberLocation->latitude,
                    'longitude' => (float) $member->memberLocation->longitude,
                    'network_type' => $member->memberLocation->network_type,
                    'last_seen_at' => $member->memberLocation->last_seen_at?->toISOString(),
                    'is_online' => $member->memberLocation->last_seen_at?->gt(now()->subSeconds(30)),
                ]),
        ]);
    }
}
