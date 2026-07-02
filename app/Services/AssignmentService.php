<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function __construct(
        private readonly RoutingService $routing,
        private readonly NotificationService $notifications,
    ) {}

    public function assignMember(Report $report, User $member, User $admin): Assignment
    {
        return DB::transaction(function () use ($report, $member, $admin): Assignment {
            $report->assignments()
                ->whereNotIn('status', [Assignment::STATUS_COMPLETED, Assignment::STATUS_CANCELLED])
                ->update(['status' => Assignment::STATUS_CANCELLED]);

            $route = null;
            if ($member->memberLocation) {
                $route = $this->routing->route(
                    (float) $member->memberLocation->latitude,
                    (float) $member->memberLocation->longitude,
                    (float) $report->latitude,
                    (float) $report->longitude,
                );
            }

            $assignment = Assignment::query()->create([
                'report_id' => $report->id,
                'assigned_member_id' => $member->id,
                'assigned_by' => $admin->id,
                'assignment_type' => 'individual',
                'status' => Assignment::STATUS_ASSIGNED,
                'assigned_at' => now(),
                'distance_meters' => $route['distance_meters'] ?? null,
                'duration_seconds' => $route['duration_seconds'] ?? null,
                'route_geometry_json' => $route['geometry'] ?? null,
                'route_steps_json' => $route['steps'] ?? [],
            ]);

            $report->update([
                'status' => Report::STATUS_ASSIGNED,
                'assigned_member_id' => $member->id,
            ]);

            $this->notifications->assignmentCreated($report, $member);

            return $assignment->load(['report', 'member.memberLocation']);
        });
    }

    public function updateStatus(Assignment $assignment, string $status): Assignment
    {
        $updates = ['status' => $status];
        $reportStatus = match ($status) {
            Assignment::STATUS_ON_THE_WAY => Report::STATUS_ON_THE_WAY,
            Assignment::STATUS_ARRIVED => Report::STATUS_ARRIVED,
            Assignment::STATUS_HANDLING => Report::STATUS_HANDLING,
            Assignment::STATUS_COMPLETED => Report::STATUS_COMPLETED,
            default => $assignment->report->status,
        };

        if ($status === Assignment::STATUS_ACCEPTED) {
            $updates['accepted_at'] = now();
        }
        if ($status === Assignment::STATUS_ON_THE_WAY) {
            $updates['started_at'] = now();
        }
        if ($status === Assignment::STATUS_ARRIVED) {
            $updates['arrived_at'] = now();
        }
        if ($status === Assignment::STATUS_COMPLETED) {
            $updates['completed_at'] = now();
        }

        $assignment->update($updates);

        $reportUpdates = ['status' => $reportStatus];
        if ($status === Assignment::STATUS_COMPLETED) {
            $reportUpdates['closed_at'] = $updates['completed_at'];
            $reportUpdates['closed_by'] = $assignment->assigned_member_id;
        }
        $assignment->report()->update($reportUpdates);

        return $assignment->refresh()->load(['report', 'member.memberLocation']);
    }

    public function cancelActiveAssignment(Report $report): void
    {
        DB::transaction(function () use ($report): void {
            $report->assignments()
                ->whereNotIn('status', [Assignment::STATUS_COMPLETED, Assignment::STATUS_CANCELLED])
                ->update(['status' => Assignment::STATUS_CANCELLED]);

            $report->update([
                'status' => Report::STATUS_NEW,
                'assigned_member_id' => null,
                'assigned_team_id' => null,
            ]);
        });
    }
}
