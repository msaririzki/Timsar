<?php

namespace App\Services;

use App\Models\Report;
use App\Models\TimsarNotification;
use App\Models\User;

class NotificationService
{
    public function reportCreated(Report $report): void
    {
        User::query()->where('role', 'admin')->each(function (User $admin) use ($report): void {
            TimsarNotification::query()->create([
                'user_id' => $admin->id,
                'report_id' => $report->id,
                'title' => 'Laporan darurat baru',
                'message' => "{$report->incident_type} dari {$report->reporter_name}",
                'type' => 'report_created',
            ]);
        });
    }

    public function assignmentCreated(Report $report, User $member): void
    {
        TimsarNotification::query()->create([
            'user_id' => $member->id,
            'report_id' => $report->id,
            'title' => 'Tugas TIMSAR baru',
            'message' => "Anda ditugaskan menangani {$report->incident_type}.",
            'type' => 'assignment_created',
        ]);
    }
}
