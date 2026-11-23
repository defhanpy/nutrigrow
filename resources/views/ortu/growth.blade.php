@extends('layouts.app')

@section('title', 'Pertumbuhan Anak')

@section('content')
    <x-page-head
        eyebrow="Ruang keluarga"
        title="{{ $selectedChild ? 'Pertumbuhan '.$selectedChild->full_name : 'Pertumbuhan anak' }}"
        description="Grafik kartu menuju sehat, riwayat penimbangan, dan tonggak usia dalam bahasa yang mudah dipahami.">
        <x-slot:actions>
            @if ($children->count() > 1)
                <form method="GET" action="{{ route('ortu.growth') }}">
                    <select name="child" class="form-select" onchange="this.form.submit()">
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}" @selected($selectedChild && $selectedChild->id === $child->id)>{{ $child->full_name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            <a href="{{ route('ortu.schedule') }}" class="btn btn-light"><i class="ti ti-calendar-event me-1"></i> Jadwal Posyandu</a>
        </x-slot:actions>
    </x-page-head>

    @if ($selectedChild)
        @php
            $status = $latestAssessment?->overall_status;
            $tone = match (true) {
                $status === null => '',
                $status === 'Gizi Baik' => '',
                in_array($status, ['Berisiko Gizi Lebih', 'Gizi Lebih'], true) => 'is-watch',
                default => 'is-alert',
            };
            $friendly = match (true) {
                $status === null => 'Belum ada penilaian',
                in_array($status, ['Gizi Baik', 'Normal'], true) => 'Tumbuh dengan baik',
                in_array($status, ['Berisiko Gizi Lebih', 'Gizi Lebih'], true) => 'Perlu dijaga keseimbangannya',
                default => 'Perlu perhatian keluarga',
            };
            $lastMeasurement = $selectedChild->latestMeasurement;
        @endphp

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="ng-status-banner {{ $tone }} h-100">
                    <div class="ng-status-label">Ringkasan pertumbuhan terakhir</div>
                    <div class="ng-status-value">{{ $friendly }}</div>
                    <p class="ng-small mb-0 mt-2" style="max-width: 60ch;">
                        @if ($lastMeasurement)
                            Pada kunjungan {{ $lastMeasurement->measured_at->translatedFormat('d F Y') }}, {{ $selectedChild->full_name }}
                            berusia {{ $selectedChild->ageLabel($lastMeasurement->measured_at) }} dengan berat {{ $lastMeasurement->weight_kg }} kg
                            dan tinggi {{ $lastMeasurement->height_cm }} cm.
                        @else
                            Belum ada catatan pengukuran. Ajak {{ $selectedChild->full_name }} ke Posyandu terdekat untuk pengukuran pertama.
                        @endif
                    </p>
                    @if ($latestAssessment)
                        <div class="row g-2 mt-3">
                            <div class="col-sm-4"><div class="ng-soft-box"><div class="ng-tiny ng-muted">Berat menurut usia</div><x-status-badge :status="$latestAssessment->wfa_status" class="mt-1" /></div></div>
                            <div class="col-sm-4"><div class="ng-soft-box"><div class="ng-tiny ng-muted">Tinggi menurut usia</div><x-status-badge :status="$latestAssessment->lhfa_status" class="mt-1" /></div></div>
                            <div class="col-sm-4"><div class="ng-soft-box"><div class="ng-tiny ng-muted">Berat menurut tinggi</div><x-status-badge :status="$latestAssessment->wfh_status" class="mt-1" /></div></div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <div class="ng-panel ng-panel-lime h-100">
                    <h6 class="ng-panel-title"><i class="ti ti-id-badge"></i> Profil singkat</h6>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <x-child-avatar :child="$selectedChild" size="lg" />
                        <div>
                            <div class="fw-bold">{{ $selectedChild->full_name }}</div>
                            <div class="ng-small ng-muted">{{ $selectedChild->registration_no }}</div>
                        </div>
                    </div>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Usia</span><strong>{{ $selectedChild->ageLabel() }}</strong></div>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Jenis kelamin</span><strong>{{ $selectedChild->sex->label() }}</strong></div>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Posyandu</span><strong>{{ $selectedChild->facility?->name }}</strong></div>
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Kunjungan</span><strong>{{ $measurements->count() }} kali</strong></div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="ng-panel mb-3">
                    <h6 class="ng-panel-title"><i class="ti ti-chart-line"></i> Kurva pertumbuhan (KMS)</h6>
                    <p class="ng-panel-sub mb-0">Garis hijau menunjukkan rentang pertumbuhan yang diharapkan untuk anak seusianya.</p>
                    @if ($chart)
                        <x-growth-chart :chart="$chart" id="ortuGrowthDetailChart" />
                    @else
                        <x-empty-state icon="ti-chart-line" title="Grafik belum tersedia" description="Grafik muncul setelah ada catatan pengukuran." />
                    @endif
                </div>

                @if ($trajectory)
                    @include('partials.trajectory', ['trajectory' => $trajectory])
                @endif

                <div class="ng-panel mt-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <h6 class="ng-panel-title mb-0"><i class="ti ti-history"></i> Riwayat penimbangan</h6>
                        <span class="badge badge-soft-secondary">{{ $measurements->count() }} kunjungan</span>
                    </div>
                    @if ($measurements->isEmpty())
                        <x-empty-state icon="ti-scale" title="Belum ada pengukuran" description="Riwayat kunjungan akan tampil di sini." />
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Usia</th>
                                    <th>Berat</th>
                                    <th>Tinggi</th>
                                    <th>Penilaian</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($measurements as $measurement)
                                    <tr>
                                        <td class="ng-small ng-nowrap">{{ $measurement->measured_at->translatedFormat('d M Y') }}</td>
                                        <td class="ng-small">{{ $selectedChild->ageLabel($measurement->measured_at) }}</td>
                                        <td class="ng-small">{{ $measurement->weight_kg }} kg</td>
                                        <td class="ng-small">{{ $measurement->height_cm }} cm</td>
                                        <td><x-status-badge :status="$measurement->assessment?->overall_status" /></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xl-4">
                <div class="ng-panel">
                    <h6 class="ng-panel-title"><i class="ti ti-milestone"></i> Tonggak usia</h6>
                    <p class="ng-panel-sub">Panduan sederhana tahapan makan dan pemantauan sesuai usia.</p>
                    <div class="ng-milestones">
                        @foreach ($milestones as $milestone)
                            <div class="ng-milestone {{ $milestone['reached'] ? 'is-reached' : '' }}">
                                <span class="ng-milestone-dot" aria-hidden="true"></span>
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <strong class="ng-small">{{ $milestone['title'] }}</strong>
                                        <span class="badge {{ $milestone['reached'] ? 'ng-badge-good' : 'ng-badge-neutral' }}">{{ $milestone['age'] }}</span>
                                    </div>
                                    <p class="ng-small mb-0">{{ $milestone['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="ng-divider"></div>
                    <p class="ng-small ng-muted mb-0">
                        <i class="ti ti-heart-handshake"></i> Bila ada yang ingin ditanyakan, sampaikan saat kunjungan Posyandu berikutnya.
                    </p>
                </div>
            </div>
        </div>
    @else
        <x-empty-state icon="ti-users" title="Belum ada data anak" description="Hubungi kader Posyandu untuk menghubungkan akun Anda dengan data balita." />
    @endif
@endsection
