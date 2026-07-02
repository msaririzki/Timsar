<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Report;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\DistanceService;
use App\Services\TrailService;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $filters = $request->validate([
            'status' => ['nullable', 'in:all,completed,cancelled'],
            'q' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $status = $filters['status'] ?? 'all';
        $search = trim($filters['q'] ?? '');

        $query = Report::query()
            ->with(['assignedMember', 'activeAssignment.member', 'closedBy'])
            ->whereIn('status', [Report::STATUS_COMPLETED, Report::STATUS_CANCELLED]);

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('tracking_code', 'like', "%{$search}%")
                    ->orWhere('reporter_name', 'like', "%{$search}%")
                    ->orWhere('reporter_phone', 'like', "%{$search}%")
                    ->orWhere('incident_type', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('closed_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('closed_at', '<=', $filters['date_to']);
        }

        return view('admin.reports-index', [
            'reports' => $query->orderByDesc('closed_at')->orderByDesc('updated_at')->paginate(15)->withQueryString(),
            'filters' => [
                'status' => $status,
                'q' => $search,
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'stats' => [
                'total' => Report::query()->whereIn('status', [Report::STATUS_COMPLETED, Report::STATUS_CANCELLED])->count(),
                'completed' => Report::query()->where('status', Report::STATUS_COMPLETED)->count(),
                'cancelled' => Report::query()->where('status', Report::STATUS_CANCELLED)->count(),
                'this_month' => Report::query()->whereIn('status', [Report::STATUS_COMPLETED, Report::STATUS_CANCELLED])->whereBetween('closed_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
        ]);
    }

    public function show(Request $request, Report $report, DistanceService $distance)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('admin.report-detail', [
            'report' => $report->load(['assignedMember', 'activeAssignment.member.memberLocation', 'closedBy']),
            'nearestMembers' => $distance->nearestMembers($report),
        ]);
    }

    public function assignMember(Request $request, Report $report, AssignmentService $assignments)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if(in_array($report->status, [Report::STATUS_COMPLETED, Report::STATUS_CANCELLED], true), 409, 'Laporan ini sudah ditutup.');

        $data = $request->validate([
            'member_id' => ['required', 'exists:users,id'],
        ]);

        $member = User::query()->where('role', 'member')->findOrFail($data['member_id']);
        $assignments->assignMember($report, $member, $request->user());

        return back()->with('status', "Tugas dikirim ke {$member->name}.");
    }

    public function cancel(Request $request, Report $report)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if(in_array($report->status, [Report::STATUS_COMPLETED, Report::STATUS_CANCELLED], true), 409, 'Laporan ini sudah ditutup.');

        $data = $request->validate([
            'closure_notes' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'closure_notes.required' => 'Alasan pembatalan wajib diisi.',
            'closure_notes.min' => 'Alasan pembatalan minimal 10 karakter.',
            'closure_notes.max' => 'Alasan pembatalan maksimal 500 karakter.',
        ]);

        $report->assignments()->whereNotIn('status', ['completed', 'cancelled'])->update(['status' => 'cancelled']);
        $report->update([
            'status' => Report::STATUS_CANCELLED,
            'closed_at' => now(),
            'closure_notes' => $data['closure_notes'],
            'closed_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.reports.index', ['status' => 'cancelled'])->with('status', 'Laporan dibatalkan dan dipindahkan ke riwayat.');
    }

    public function trail(Request $request, Assignment $assignment, TrailService $trail)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json($trail->trailForAssignment($assignment));
    }
}
