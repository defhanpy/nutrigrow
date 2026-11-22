@extends('layouts.app')

@section('title', 'Dashboard Tenaga Kesehatan')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Puskesmas' }}"
        title="Peninjauan tumbuh kembang"
        description="Tinjau tanda yang perlu perhatian, lengkapi konteks klinis, dan tetapkan arahan tindak lanjut bersama kader.">
        <x-slot:actions>
            <a href="{{ route('children.index') }}" class="btn btn-light"><i class="ti ti-users me-1"></i> Semua balita</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        @foreach ($stats as $stat)
            <div class="col-6 col-xl-3">
                <x-stat-tile :label="$stat['label']" :value="$stat['value']" :icon="$stat['icon']" :tone="$stat['tone']" />
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-5" id="antrean">
            <div class="ng-panel">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h6 class="ng-panel-title mb-0"><i class="ti ti-alert-triangle"></i> Antrean peninjauan</h6>
                        <p class="ng-panel-sub mb-0">Diurutkan dari prioritas tertinggi.</p>
                    </div>
                    <span class="badge badge-soft-warning">{{ $risks->count() }} tanda · {{ $queue->count() }} balita</span>
                </div>

                @if ($queue->isEmpty())
                    <x-empty-state icon="ti-circle-check" title="Tidak ada peninjauan terbuka" description="Semua tanda risiko sudah ditindaklanjuti." />
                @else
                    <div class="ng-list">
                        @foreach ($queue as $entry)
                            @php($risk = $entry['worst'])
                            <a href="{{ route('nakes.dashboard', ['risk' => $risk->id]) }}"
                               class="ng-list-item ng-link-plain {{ $selectedRisk && $selectedRisk->child_id === $risk->child_id ? 'ng-tone-mint' : '' }}">
                                <span class="ng-list-icon ng-tone-{{ $risk->severity->value === 'high' ? 'rose' : ($risk->severity->value === 'medium' ? 'amber' : 'sky') }}">
                                    <i class="ti {{ $risk->severity->value === 'high' ? 'ti-alert-circle' : 'ti-info-circle' }}"></i>
                                </span>
                                <div class="flex-grow-1">
                                    <h6>{{ $entry['child']->full_name }}</h6>
                                    <p>{{ $risk->reason }}</p>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <x-severity-badge :severity="$risk->severity" />
                                        @if ($entry['flags']->count() > 1)
                                            <span class="badge badge-soft-secondary">{{ $entry['flags']->count() }} tanda</span>
                                        @endif
                                        <span class="badge badge-soft-secondary">{{ $entry['child']->facility?->name }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-7">
            @if ($selectedChild)
                <div class="ng-panel mb-3">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <x-child-avatar :child="$selectedChild" size="lg" />
                            <div>
                                <h6 class="ng-panel-title mb-0">{{ $selectedChild->full_name }}</h6>
                                <p class="ng-panel-sub mb-0">
                                    {{ $selectedChild->ageLabel() }} · {{ $selectedChild->sex->label() }} · {{ $selectedChild->facility?->name }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('children.show', $selectedChild) }}" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-file-search me-1"></i> Profil longitudinal
                        </a>
                    </div>

                    @php($latest = $selectedChild->latestMeasurement?->assessment)
                    <div class="row g-2 mt-2">
                        <div class="col-sm-4"><div class="ng-soft-box"><div class="ng-tiny ng-muted">Berat menurut usia</div><x-status-badge :status="$latest?->wfa_status" class="mt-1" /><div class="ng-tiny ng-muted mt-1">z = {{ $latest?->wfa_z ?? '—' }}</div></div></div>
                        <div class="col-sm-4"><div class="ng-soft-box"><div class="ng-tiny ng-muted">Tinggi menurut usia</div><x-status-badge :status="$latest?->lhfa_status" class="mt-1" /><div class="ng-tiny ng-muted mt-1">z = {{ $latest?->lhfa_z ?? '—' }}</div></div></div>
                        <div class="col-sm-4"><div class="ng-soft-box"><div class="ng-tiny ng-muted">Berat menurut tinggi</div><x-status-badge :status="$latest?->wfh_status" class="mt-1" /><div class="ng-tiny ng-muted mt-1">z = {{ $latest?->wfh_z ?? '—' }}</div></div></div>
                    </div>
                </div>

                @if ($selectedRisk)
                    <div class="ng-panel ng-panel-amber mb-3">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                            <div>
                                <h6 class="ng-panel-title mb-1"><i class="ti ti-stethoscope"></i> Catatan peninjauan</h6>
                                <p class="ng-panel-sub mb-0">{{ $selectedRisk->reason }}</p>
                            </div>
                            <x-severity-badge :severity="$selectedRisk->severity" />
                        </div>

                        <form method="POST" action="{{ route('nakes.risks.resolve', $selectedRisk) }}" class="d-grid gap-2 mt-2">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="form-label" for="note">Hasil peninjauan <span class="ng-tiny ng-muted">wajib</span></label>
                                <textarea id="note" name="note" class="form-control" rows="3" maxlength="500" placeholder="contoh: anak dalam pemulihan batuk, berat badan turun sementara. Jadwalkan kontrol 2 minggu." required></textarea>
                            </div>
                            <div>
                                <label class="form-label" for="action_plan">Arahan tindak lanjut <span class="ng-tiny ng-muted">opsional</span></label>
                                <input type="text" id="action_plan" name="action_plan" class="form-control" maxlength="500" placeholder="contoh: rujuk ke poli gizi dan dampingi menu protein hewani">
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-circle-check me-1"></i> Simpan peninjauan</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('nakes.risks.dismiss', $selectedRisk) }}" class="mt-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="note" value="Ditutup setelah peninjauan tenaga kesehatan.">
                            <button type="submit" class="btn btn-light btn-sm"><i class="ti ti-x me-1"></i> Tutup tanda ini</button>
                        </form>
                    </div>
                @endif

                @if ($trajectory)
                    @include('partials.trajectory', ['trajectory' => $trajectory])
                @endif
            @else
                <x-empty-state icon="ti-users" title="Belum ada balita terpantau" description="Data balita di wilayah Puskesmas akan tampil di sini." />
            @endif
        </div>
    </div>
@endsection
