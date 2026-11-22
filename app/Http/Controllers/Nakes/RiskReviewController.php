<?php

namespace App\Http\Controllers\Nakes;

use App\Http\Controllers\Controller;
use App\Models\RiskAssessment;
use App\Services\ChildAccessService;
use App\Services\GrowthMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RiskReviewController extends Controller
{
    public function __construct(
        private readonly ChildAccessService $access,
        private readonly GrowthMonitoringService $monitoring,
    ) {}

    public function resolve(Request $request, RiskAssessment $risk): RedirectResponse
    {
        abort_unless($this->access->canAccess($request->user(), $risk->child), 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
            'action_plan' => ['nullable', 'string', 'max:500'],
        ], [
            'note.required' => 'Catatan peninjauan wajib diisi.',
        ]);

        $this->monitoring->resolveRisk($risk, $request->user(), $validated['note']);

        if (! empty($validated['action_plan'])) {
            $this->monitoring->createFollowUp(
                $risk->child,
                $request->user(),
                'Arahan tenaga kesehatan',
                $validated['action_plan'],
                now()->addDays(14)->toDateString(),
                $validated['note'],
            );
        }

        return redirect()
            ->route('nakes.dashboard')
            ->with('status', 'Peninjauan tersimpan. Catatan Anda menjadi arahan tindak lanjut.');
    }

    public function dismiss(Request $request, RiskAssessment $risk): RedirectResponse
    {
        abort_unless($this->access->canAccess($request->user(), $risk->child), 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $risk->update(['status' => 'dismissed']);

        $this->monitoring->createFollowUp(
            $risk->child,
            $request->user(),
            'Peninjauan tenaga kesehatan',
            'Tanda risiko ditutup setelah peninjauan.',
            null,
            $validated['note'],
        );

        return redirect()->route('nakes.dashboard')->with('status', 'Tanda risiko ditutup dengan catatan peninjauan.');
    }
}
