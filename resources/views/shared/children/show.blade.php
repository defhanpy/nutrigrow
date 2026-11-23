@extends('layouts.app')

@section('title', $child->full_name)

@section('content')
    <x-page-head
        :eyebrow="'Profil pertumbuhan · '.$child->facility?->name"
        :title="$child->full_name"
        :description="$child->ageLabel().' · '.$child->sex->label().' · registrasi '.$child->registration_no">
        <x-slot:actions>
            <a href="{{ route('children.index') }}" class="btn btn-light"><i class="ti ti-arrow-left me-1"></i> Kembali</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="ng-panel mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-chart-line"></i> Kurva berat badan menurut usia</h6>
                <p class="ng-panel-sub">Posisi anak dibandingkan rentang pertumbuhan yang diharapkan.</p>
                <x-growth-chart :chart="$chart" id="childGrowthChart" />
            </div>

            @if ($trajectory)
                @include('partials.trajectory', ['trajectory' => $trajectory])
            @endif

            <div class="ng-panel mt-3">
                <h6 class="ng-panel-title"><i class="ti ti-history"></i> Riwayat pengukuran</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th style="width: 45px;">No.</th>
                            <th>Tanggal</th>
                            <th>Usia</th>
                            <th>Berat</th>
                            <th>Tinggi</th>
                            <th>LILA</th>
                            <th>Posisi</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($measurements as $measurement)
                            <tr>
                                <td class="ng-small text-muted">{{ $loop->iteration }}</td>
                                <td class="ng-small ng-nowrap">{{ $measurement->measured_at->translatedFormat('d M Y') }}</td>
                                <td class="ng-small">{{ $measurement->assessment?->age_in_days ? floor($measurement->assessment->age_in_days / 30.4375).' bln' : '—' }}</td>
                                <td class="ng-small">{{ $measurement->weight_kg }} kg</td>
                                <td class="ng-small">{{ $measurement->height_cm }} cm</td>
                                <td class="ng-small">{{ $measurement->lila_cm ? $measurement->lila_cm.' cm' : '—' }}</td>
                                <td class="ng-small">{{ $measurement->method->label() }}</td>
                                <td><x-status-badge :status="$measurement->assessment?->overall_status" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="ng-panel mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-user-heart"></i> Data anak</h6>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Tanggal lahir</span><strong>{{ $child->date_of_birth->translatedFormat('d F Y') }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Berat lahir</span><strong>{{ $child->birth_weight ? $child->birth_weight.' kg' : '—' }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Panjang lahir</span><strong>{{ $child->birth_height ? $child->birth_height.' cm' : '—' }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Posyandu</span><strong>{{ $child->facility?->name }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Kunjungan</span><strong>{{ $measurements->count() }} kali</strong></div>
            </div>

            @if ($assessment)
                <div class="ng-panel mb-3">
                    <h6 class="ng-panel-title"><i class="ti ti-clipboard-data"></i> Penilaian terakhir</h6>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Berat menurut usia</span><x-status-badge :status="$assessment->wfa_status" /></div>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Tinggi menurut usia</span><x-status-badge :status="$assessment->lhfa_status" /></div>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Berat menurut tinggi</span><x-status-badge :status="$assessment->wfh_status" /></div>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">IMT menurut usia</span><x-status-badge :status="$assessment->bfa_status" /></div>
                    <p class="ng-tiny ng-muted mt-2 mb-0">Penilaian mengikuti klasifikasi gizi balita yang berlaku. Bukan diagnosis medis.</p>
                </div>
            @endif

            <div class="ng-panel mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-users"></i> Keluarga</h6>
                @forelse ($child->guardians as $guardian)
                    <div class="ng-indicator-row">
                        <span class="ng-indicator-label">{{ $guardian->relationship->label() }}</span>
                        <strong class="ng-small">{{ $guardian->user?->name }}</strong>
                    </div>
                @empty
                    <p class="ng-small ng-muted mb-0">Belum ada data keluarga.</p>
                @endforelse
            </div>

            @include('partials.follow-up-form', ['child' => $child])

            <div class="ng-panel mt-3">
                <h6 class="ng-panel-title"><i class="ti ti-clipboard-check"></i> Tindak lanjut</h6>
                @include('partials.follow-up-list', ['followUps' => $child->followUps->sortByDesc('created_at')])
            </div>
        </div>
    </div>

    <div class="mt-3">
        @include('partials.nutrition-cards', ['recommendation' => $nutrition])
    </div>
@endsection
