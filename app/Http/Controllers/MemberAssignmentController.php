<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

class MemberAssignmentController extends Controller
{
    public function show(Request $request, Assignment $assignment)
    {
        $this->authorizeMember($request, $assignment);

        return view('member.assignment-detail', [
            'assignment' => $assignment->load(['report', 'member.memberLocation']),
        ]);
    }

    public function accept(Request $request, Assignment $assignment, AssignmentService $assignments)
    {
        $this->authorizeMember($request, $assignment);
        $assignments->updateStatus($assignment, Assignment::STATUS_ACCEPTED);

        return back()->with('status', 'Tugas diterima.');
    }

    public function start(Request $request, Assignment $assignment, AssignmentService $assignments)
    {
        $this->authorizeMember($request, $assignment);
        $assignments->updateStatus($assignment, Assignment::STATUS_ON_THE_WAY);

        return back()->with('status', 'Status diubah menjadi menuju lokasi.');
    }

    public function arrive(Request $request, Assignment $assignment, AssignmentService $assignments)
    {
        $this->authorizeMember($request, $assignment);
        $assignments->updateStatus($assignment, Assignment::STATUS_ARRIVED);

        return back()->with('status', 'Status sampai lokasi dikirim.');
    }

    public function handling(Request $request, Assignment $assignment, AssignmentService $assignments)
    {
        $this->authorizeMember($request, $assignment);
        $assignments->updateStatus($assignment, Assignment::STATUS_HANDLING);

        return back()->with('status', 'Status penanganan aktif.');
    }

    public function complete(Request $request, Assignment $assignment, AssignmentService $assignments)
    {
        $this->authorizeMember($request, $assignment);
        $assignments->updateStatus($assignment, Assignment::STATUS_COMPLETED);

        return redirect()->route('member.dashboard')->with('status', 'Tugas selesai.');
    }

    private function authorizeMember(Request $request, Assignment $assignment): void
    {
        abort_unless($request->user()->isMember(), 403);
        abort_unless((int) $assignment->assigned_member_id === (int) $request->user()->id, 403);
    }
}
