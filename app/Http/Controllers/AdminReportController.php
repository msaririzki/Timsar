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
    public function show(Request $request, Report $report, DistanceService $distance)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('admin.report-detail', [
            'report' => $report->load(['assignedMember', 'activeAssignment.member.memberLocation']),
            'nearestMembers' => $distance->nearestMembers($report),
        ]);
    }

    public function assignMember(Request $request, Report $report, AssignmentService $assignments)
    {
        abort_unless($request->user()->isAdmin(), 403);

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

        $report->assignments()->whereNotIn('status', ['completed', 'cancelled'])->update(['status' => 'cancelled']);
        $report->update(['status' => 'cancelled']);

        return redirect()->route('admin.dashboard')->with('status', 'Laporan dibatalkan.');
    }

    public function trail(Request $request, Assignment $assignment, TrailService $trail)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json($trail->trailForAssignment($assignment));
    }
}
