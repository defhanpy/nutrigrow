<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\FollowUpRecord;
use App\Services\ChildAccessService;
use App\Services\GrowthMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly GrowthMonitoringService $monitoring,
    ) {}

    public function index(Request $request): View
    {
        $children = $this->access->queryFor($request->user())->orderBy('full_name')->get();

        return view('shared.follow-ups.index', [
            'children' => $children,
            'followUps' => FollowUpRecord::query()
                ->with(['child', 'creator'])
                ->whereIn('child_id', $children->pluck('id'))
                ->orderByRaw("case status when 'pending' then 0 else 1 end")
                ->orderBy('target_date')
                ->paginate(15),
        ]);
    }

    public function store(Request $request, Child $child): RedirectResponse
    {
        abort_unless($this->access->canAccess($request->user(), $child), 403);

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:60'],
            'action_plan' => ['required', 'string', 'max:500'],
            'target_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'category.required' => 'Jenis tindak lanjut wajib dipilih.',
            'action_plan.required' => 'Rencana tindakan wajib diisi.',
            'target_date.after_or_equal' => 'Tanggal target tidak boleh sebelum hari ini.',
        ]);

        $this->monitoring->createFollowUp(
            $child,
            $request->user(),
            $validated['category'],
            $validated['action_plan'],
            $validated['target_date'] ?? null,
            $validated['notes'] ?? null,
        );

        return back()->with('status', 'Tindak lanjut untuk '.$child->full_name.' berhasil dicatat.');
    }

    public function complete(Request $request, FollowUpRecord $followUp): RedirectResponse
    {
        abort_unless($this->access->canAccess($request->user(), $followUp->child), 403);

        $followUp->update([
            'status' => 'completed',
            'notes' => trim(($followUp->notes ? $followUp->notes."\n" : '').($request->string('note')->trim()->toString() ?: 'Ditandai selesai oleh '.$request->user()->name.'.')),
        ]);

        return back()->with('status', 'Tindak lanjut ditandai selesai.');
    }
}
