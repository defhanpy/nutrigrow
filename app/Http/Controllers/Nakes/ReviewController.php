<?php

namespace App\Http\Controllers\Nakes;

use App\Enums\RiskSeverity;
use App\Enums\RiskStatus;
use App\Http\Controllers\Controller;
use App\Models\RiskAssessment;
use App\Services\ChildAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly ChildAccessService $access) {}

    public function __invoke(Request $request): View
    {
        $childIds = $this->access->queryFor($request->user())->pluck('id');
        $severity = $request->string('severity')->toString();
        $status = $request->string('status')->toString();
        $risks = RiskAssessment::query()
            ->with(['child.facility', 'child.latestMeasurement.assessment', 'measurement'])
            ->whereIn('child_id', $childIds)
            ->when(in_array($severity, ['high', 'medium', 'low'], true), fn ($query) => $query->where('severity', $severity))
            ->when(in_array($status, ['open', 'resolved', 'dismissed'], true), fn ($query) => $query->where('status', $status))
            ->orderByRaw("case severity when 'high' then 0 when 'medium' then 1 else 2 end")
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $openRisks = RiskAssessment::query()
            ->whereIn('child_id', $childIds)
            ->where('status', RiskStatus::Open)
            ->get();

        return view('nakes.reviews', [
            'risks' => $risks,
            'stats' => [
                ['label' => 'Antrean terbuka', 'value' => $openRisks->count(), 'icon' => 'ti-alert-triangle', 'tone' => 'amber'],
                ['label' => 'Prioritas tinggi', 'value' => $openRisks->where('severity.value', 'high')->count(), 'icon' => 'ti-heart-rate-monitor', 'tone' => 'rose'],
                ['label' => 'Perlu tindak lanjut', 'value' => $openRisks->where('severity.value', 'medium')->count(), 'icon' => 'ti-clipboard-check', 'tone' => 'sky'],
                ['label' => 'Sudah selesai', 'value' => RiskAssessment::query()->whereIn('child_id', $childIds)->where('status', RiskStatus::Resolved)->count(), 'icon' => 'ti-circle-check', 'tone' => 'mint'],
            ],
            'filters' => [
                'severity' => $severity,
                'status' => $status,
                'severities' => RiskSeverity::cases(),
                'statuses' => RiskStatus::cases(),
            ],
        ]);
    }
}
