<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicReportController extends Controller
{
    public function create()
    {
        return view('public.report');
    }

    public function store(Request $request, NotificationService $notifications)
    {
        $data = $request->validate([
            'reporter_name' => ['required', 'string', 'max:120'],
            'reporter_phone' => ['required', 'string', 'max:30'],
            'incident_type' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,critical'],
        ]);

        $data['tracking_code'] = $this->trackingCode();
        $data['status'] = Report::STATUS_NEW;
        $data['priority'] = $data['priority'] ?? 'high';

        $report = Report::query()->create($data);
        $notifications->reportCreated($report);

        return redirect()->route('public.tracking', $report->tracking_code)
            ->with('status', 'Laporan berhasil dikirim ke posko TIMSAR.');
    }

    private function trackingCode(): string
    {
        do {
            $code = 'TSR-' . now()->format('ymd') . '-' . Str::upper(Str::random(5));
        } while (Report::query()->where('tracking_code', $code)->exists());

        return $code;
    }
}
