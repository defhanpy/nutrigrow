@extends('layouts.app')

@section('title', 'Dashboard Kader')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Posyandu' }}"
        title="Selamat bertugas di Posyandu"
        description="Catat hasil penimbangan hari ini, pantau balita yang perlu ditinjau, dan sepakati tindak lanjut bersama keluarga.">
        <x-slot:actions>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#measurementModal">
                <i class="ti ti-scale me-1"></i> Pengukuran baru
            </button>
            <a href="{{ route('children.index') }}" class="btn btn-light"><i class="ti ti-users me-1"></i> Data anak</a>
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
        <div class="col-xl-7">
            <div class="ng-panel mb-3" id="pengukuran">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                    <div>
                        <h6 class="ng-panel-title mb-0"><i class="ti ti-chart-line"></i> Grafik pertumbuhan (KMS)</h6>
                        <p class="ng-panel-sub mb-0">
                            {{ $selectedChild?->full_name ?? 'Belum ada balita' }}
                            @if ($selectedChild)
                                · {{ $selectedChild->ageLabel() }} · {{ $selectedChild->sex->label() }}
                            @endif
                        </p>
                    </div>
                    @if ($children->count() > 1)
                        <form method="GET" action="{{ route('kader.dashboard') }}" class="d-flex gap-2">
                            <select name="child" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach ($children as $child)
                                    <option value="{{ $child->id }}" @selected($selectedChild && $selectedChild->id === $child->id)>
                                        {{ $child->full_name }} · {{ $child->ageLabel() }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
                @if ($chart)
                    <x-growth-chart :chart="$chart" id="kaderGrowthChart" />
                @else
                    <x-empty-state icon="ti-chart-line" title="Grafik belum tersedia" description="Tambahkan data balita dan pengukuran pertama." />
                @endif
            </div>

            @if ($selectedChild)
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        @php
                            $latest = $selectedChild->latestMeasurement?->assessment;
                            $bannerTone = match (true) {
                                $latest === null => '',
                                in_array($latest->overall_status, ['Gizi Baik'], true) => '',
                                in_array($latest->overall_status, ['Berisiko Gizi Lebih', 'Gizi Lebih'], true) => 'is-watch',
                                default => 'is-alert',
                            };
                        @endphp
                        <div class="ng-status-banner {{ $bannerTone }} h-100">
                            <div class="ng-status-label">Penilaian terakhir</div>
                            <div class="ng-status-value">{{ $latest?->overall_status ?? 'Belum ada penilaian' }}</div>
                            <div class="ng-small ng-muted mt-1">
                                @if ($selectedChild->latestMeasurement)
                                    Diukur {{ $selectedChild->latestMeasurement->measured_at->translatedFormat('d F Y') }}
                                @else
                                    Belum ada pengukuran tercatat
                                @endif
                            </div>
                            <div class="ng-divider"></div>
                            @if ($latest)
                                <div class="ng-indicator-row"><span class="ng-indicator-label">Berat menurut usia</span><x-status-badge :status="$latest->wfa_status" /></div>
                                <div class="ng-indicator-row"><span class="ng-indicator-label">Tinggi menurut usia</span><x-status-badge :status="$latest->lhfa_status" /></div>
                                <div class="ng-indicator-row"><span class="ng-indicator-label">Berat menurut tinggi</span><x-status-badge :status="$latest->wfh_status" /></div>
                            @else
                                <p class="ng-small ng-muted mb-0">Lakukan pengukuran pertama untuk melihat penilaian gizi.</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="ng-panel h-100">
                            <h6 class="ng-panel-title"><i class="ti ti-user-heart"></i> Ringkasan balita</h6>
                            <div class="ng-indicator-row"><span class="ng-indicator-label">Nomor registrasi</span><strong>{{ $selectedChild->registration_no }}</strong></div>
                            <div class="ng-indicator-row"><span class="ng-indicator-label">Tanggal lahir</span><strong>{{ $selectedChild->date_of_birth->translatedFormat('d F Y') }}</strong></div>
                            <div class="ng-indicator-row"><span class="ng-indicator-label">Berat lahir</span><strong>{{ $selectedChild->birth_weight ? $selectedChild->birth_weight.' kg' : '—' }}</strong></div>
                            <div class="ng-indicator-row"><span class="ng-indicator-label">Panjang lahir</span><strong>{{ $selectedChild->birth_height ? $selectedChild->birth_height.' cm' : '—' }}</strong></div>
                            <div class="ng-indicator-row"><span class="ng-indicator-label">Jumlah kunjungan</span><strong>{{ $selectedChild->measurements_count }} kali</strong></div>
                            <a href="{{ route('children.show', $selectedChild) }}" class="btn btn-outline-primary btn-sm mt-2">
                                <i class="ti ti-arrow-right me-1"></i> Buka profil lengkap
                            </a>
                        </div>
                    </div>
                </div>

                @if ($trajectory)
                    @include('partials.trajectory', ['trajectory' => $trajectory])
                @endif

                @if ($nutrition)
                    <div class="mt-3">
                        @include('partials.nutrition-cards', ['recommendation' => $nutrition])
                    </div>
                @endif
            @endif
        </div>

        <div class="col-xl-5">
            <div class="ng-panel mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="ng-panel-title mb-0"><i class="ti ti-users"></i> Daftar balita</h6>
                    <span class="badge badge-soft-secondary">{{ $children->count() }} anak</span>
                </div>
                <div class="ng-list">
                    @forelse ($children as $child)
                        @php($latest = $child->latestMeasurement?->assessment)
                        <a href="{{ route('kader.dashboard', ['child' => $child->id]) }}" class="ng-list-item ng-link-plain">
                            <x-child-avatar :child="$child" />
                            <div class="flex-grow-1">
                                <h6>{{ $child->full_name }}</h6>
                                <p>{{ $child->ageLabel() }} · {{ $child->sex->label() }} · {{ $child->facility?->name }}</p>
                            </div>
                            <x-status-badge :status="$latest?->overall_status" />
                        </a>
                    @empty
                        <x-empty-state icon="ti-users" title="Belum ada balita" description="Data balita pada Posyandu ini akan tampil di sini." />
                    @endforelse
                </div>
            </div>

            @if ($selectedChild)
                @include('partials.follow-up-form', ['child' => $selectedChild])

                <div class="ng-panel mt-3">
                    <h6 class="ng-panel-title"><i class="ti ti-history"></i> Tindak lanjut terakhir</h6>
                    @include('partials.follow-up-list', ['followUps' => $followUps])
                </div>
            @endif

            <div class="ng-panel mt-3">
                <h6 class="ng-panel-title"><i class="ti ti-list-check"></i> Pengukuran terbaru</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Balita</th><th>Tanggal</th><th>Berat</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse ($recentMeasurements as $measurement)
                            <tr>
                                <td class="ng-small fw-bold">{{ $measurement->child->full_name }}</td>
                                <td class="ng-small ng-nowrap">{{ $measurement->measured_at->translatedFormat('d M') }}</td>
                                <td class="ng-small">{{ $measurement->weight_kg }} kg</td>
                                <td><x-status-badge :status="$measurement->assessment?->overall_status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="ng-small ng-muted text-center py-3">Belum ada pengukuran.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('partials.measurement-modal')
@endsection
