<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicReportController extends Controller
{
    public function create(Request $request)
    {
        if ($request->user()) {
            return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'member.dashboard');
        }

        return view('public.report');
    }

    public function store(Request $request, NotificationService $notifications)
    {
        if ($request->user()) {
            return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'member.dashboard');
        }

        $request->merge([
            'reporter_phone' => $this->normalizePhone((string) $request->input('reporter_phone')),
        ]);

        $data = $request->validate([
            'reporter_name' => ['required', 'string', 'max:120'],
            'reporter_phone' => ['required', 'string', 'regex:/^08[0-9]{8,11}$/'],
            'incident_type' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,critical'],
        ], [
            'reporter_name.required' => 'Nama pelapor wajib diisi.',
            'reporter_phone.required' => 'Nomor HP atau WhatsApp wajib diisi.',
            'reporter_phone.regex' => 'Nomor HP harus berupa nomor Indonesia aktif, misalnya 081234567890 atau +6281234567890.',
            'incident_type.required' => 'Pilih jenis kejadian.',
            'description.required' => 'Keterangan kejadian wajib diisi.',
            'latitude.required' => 'Lokasi laporan belum aktif. Tekan tombol Kunci GPS terlebih dahulu.',
            'longitude.required' => 'Lokasi laporan belum aktif. Tekan tombol Kunci GPS terlebih dahulu.',
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

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';

        if (str_starts_with($phone, '+62')) {
            return '0' . substr($phone, 3);
        }
        if (str_starts_with($phone, '62')) {
            return '0' . substr($phone, 2);
        }
        if (str_starts_with($phone, '8')) {
            return '0' . $phone;
        }

        return $phone;
    }
}
