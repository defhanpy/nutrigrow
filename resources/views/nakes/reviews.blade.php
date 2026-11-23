@extends('layouts.app')

@section('title', 'Daftar Peninjauan')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Puskesmas' }}"
        title="Daftar peninjauan"
        description="Antrean balita yang perlu ditinjau karena pertumbuhan melambat, wasting, atau stunting. Saring berdasarkan keparahan dan status penyelesaian.">
        <x-slot:actions>
            <a href="{{ route('nakes.dashboard') }}" class="btn btn-light"><i class="ti ti-layout-dashboard me-1"></i> Dashboard review</a>
            <a href="{{ route('nakes.analytics') }}" class="btn btn-primary"><i class="ti ti-activity me-1"></i> Hasil analisis</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        @foreach ($stats as $stat)
            <div class="col-6 col-xl-3">
                <x-stat-tile :label="$stat['label']" :value="$stat['value']" :icon="$stat['icon']" :tone="$stat['tone']" />
            </div>
        @endforeach
    </div>

    <div class="ng-panel mb-3">
        <form method="GET" action="{{ route('nakes.reviews') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="severity">Keparahan</label>
                <select id="severity" name="severity" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua keparahan</option>
                    @foreach ($filters['severities'] as $option)
                        <option value="{{ $option->value }}" @selected($filters['severity'] === $option->value)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status penyelesaian</label>
                <select id="status" name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach ($filters['statuses'] as $option)
                        <option value="{{ $option->value }}" @selected($filters['status'] === $option->value)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                @if ($filters['severity'] !== '' || $filters['status'] !== '')
                    <a href="{{ route('nakes.reviews') }}" class="btn btn-light w-100"><i class="ti ti-x me-1"></i> Bersihkan filter</a>
                @else
                    <span class="ng-small ng-muted">Menampilkan seluruh antrean peninjauan wilayah kerja.</span>
                @endif
            </div>
        </form>
    </div>

    <div class="ng-panel">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div>
                <h6 class="ng-panel-title mb-0"><i class="ti ti-alert-triangle"></i> Antrean peninjauan</h6>
                <p class="ng-panel-sub mb-0">Diurutkan dari prioritas tertinggi.</p>
            </div>
            <span class="badge badge-soft-secondary">{{ $risks->total() }} tanda</span>
        </div>

        @if ($risks->isEmpty())
            <x-empty-state icon="ti-circle-check" title="Tidak ada peninjauan pada filter ini" description="Ubah filter atau kembali ke seluruh antrean." />
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th style="width: 50px;">No.</th>
                        <th>Balita</th>
                        <th>Posyandu</th>
                        <th>Temuan</th>
                        <th>Keparahan</th>
                        <th>Status</th>
                        <th>Diperbarui</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($risks as $risk)
                        <tr>
                            <td class="ng-small text-muted">{{ $risks->firstItem() ? $risks->firstItem() + $loop->index : $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <x-child-avatar :child="$risk->child" />
                                    <div>
                                        <div class="fw-bold ng-small">{{ $risk->child->full_name }}</div>
                                        <div class="ng-tiny ng-muted">{{ $risk->child->ageLabel() }} · {{ $risk->child->sex->label() }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="ng-small">{{ $risk->child->facility?->name }}</td>
                            <td class="ng-small" style="max-width: 32ch;">{{ $risk->reason }}</td>
                            <td><x-severity-badge :severity="$risk->severity" /></td>
                            <td>
                                <span class="badge {{ $risk->status->value === 'open' ? 'ng-badge-watch' : ($risk->status->value === 'resolved' ? 'ng-badge-good' : 'ng-badge-neutral') }}">
                                    {{ $risk->status->label() }}
                                </span>
                            </td>
                            <td class="ng-small ng-nowrap">{{ $risk->updated_at->translatedFormat('d M Y') }}</td>
                            <td class="text-end">
                                @if ($risk->status->value === 'open')
                                    <a href="{{ route('nakes.dashboard', ['risk' => $risk->id]) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="ti ti-stethoscope me-1"></i> Tinjau
                                    </a>
                                @else
                                    <a href="{{ route('children.show', $risk->child) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $risks->links() }}</div>
        @endif
    </div>
@endsection
