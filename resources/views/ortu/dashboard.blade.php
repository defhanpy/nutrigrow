@extends('layouts.app')

@section('title', 'Beranda Keluarga')

@section('content')
    <x-page-head
        eyebrow="Ruang keluarga"
        title="{{ $selectedChild ? 'Perkembangan '.$selectedChild->full_name : 'Selamat datang di NUTRIGROW' }}"
        description="Ringkasan pertumbuhan, ide menu lokal, dan jadwal kunjungan Posyandu untuk keluarga Anda.">
        <x-slot:actions>
            @if ($children->count() > 1)
                <form method="GET" action="{{ route('ortu.dashboard') }}">
                    <select name="child" class="form-select" onchange="this.form.submit()">
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}" @selected($selectedChild && $selectedChild->id === $child->id)>{{ $child->full_name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </x-slot:actions>
    </x-page-head>

    @if ($selectedChild)
        @php
            $latest = $assessment;
            $status = $latest?->overall_status;
            $tone = match (true) {
                $status === null => '',
                $status === 'Gizi Baik' => '',
                in_array($status, ['Berisiko Gizi Lebih', 'Gizi Lebih'], true) => 'is-watch',
                default => 'is-alert',
            };
            $friendly = match (true) {
                $status === null => 'Belum ada penilaian',
                $status === 'Gizi Baik' => 'Tumbuh dengan baik',
                $status === 'Normal' => 'Tumbuh dengan baik',
                in_array($status, ['Berisiko Gizi Lebih', 'Gizi Lebih'], true) => 'Perlu dijaga keseimbangannya',
                in_array($status, ['Gizi Kurang', 'Pendek', 'Gizi Kurang (Wasting)'], true) => 'Perlu perhatian keluarga',
                default => 'Perlu segera ditinjau',
            };
        @endphp

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="ng-status-banner {{ $tone }} h-100">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <div class="ng-status-label">Ringkasan pertumbuhan terakhir</div>
                            <div class="ng-status-value">{{ $friendly }}</div>
                            <p class="ng-small mb-0 mt-2" style="max-width: 52ch;">
                                @if ($latest)
                                    Pada kunjungan {{ $selectedChild->latestMeasurement->measured_at->translatedFormat('d F Y') }}, {{ $selectedChild->full_name }} berusia {{ $selectedChild->ageLabel($selectedChild->latestMeasurement->measured_at) }} dengan berat {{ $selectedChild->latestMeasurement->weight_kg }} kg dan tinggi {{ $selectedChild->latestMeasurement->height_cm }} cm.
                                @else
                                    Belum ada catatan pengukuran. Ajak {{ $selectedChild->full_name }} ke Posyandu terdekat untuk pengukuran pertama.
                                @endif
                            </p>
                        </div>
                        <span class="ng-avatar ng-avatar-lg">{{ mb_strtoupper(mb_substr($selectedChild->full_name, 0, 1)) }}</span>
                    </div>

                    @if ($latest)
                        <div class="row g-2 mt-3">
                            <div class="col-sm-4">
                                <div class="ng-soft-box">
                                    <div class="ng-tiny ng-muted">Berat menurut usia</div>
                                    <x-status-badge :status="$latest->wfa_status" class="mt-1" />
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="ng-soft-box">
                                    <div class="ng-tiny ng-muted">Tinggi menurut usia</div>
                                    <x-status-badge :status="$latest->lhfa_status" class="mt-1" />
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="ng-soft-box">
                                    <div class="ng-tiny ng-muted">Berat menurut tinggi</div>
                                    <x-status-badge :status="$latest->wfh_status" class="mt-1" />
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($status && ! in_array($status, ['Gizi Baik', 'Normal'], true))
                        <p class="ng-small mt-3 mb-0"><i class="ti ti-heart-handshake"></i> Tenaga kesehatan akan mendampingi. Bila ada yang ingin ditanyakan, sampaikan saat kunjungan berikutnya.</p>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <div class="ng-panel ng-panel-lime h-100" id="jadwal">
                    <h6 class="ng-panel-title"><i class="ti ti-calendar-event"></i> Jadwal Posyandu</h6>
                    <p class="ng-panel-sub">Kunjungan berikutnya</p>
                    <div class="ng-status-value">{{ $nextVisit->translatedFormat('d F Y') }}</div>
                    <div class="ng-small ng-muted mt-1">{{ $nextVisit->translatedFormat('l') }} · 08.00–11.00</div>
                    <div class="ng-divider"></div>
                    <ul class="ng-small ps-3 mb-0">
                        <li class="mb-1">Bawa Buku KIA dan kartu Posyandu.</li>
                        <li class="mb-1">Catat pertanyaan untuk kader atau bidan.</li>
                        <li>Datang lebih awal agar tidak menunggu lama.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="ng-panel mb-3" id="pertumbuhan">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <h6 class="ng-panel-title mb-0"><i class="ti ti-chart-line"></i> Kurva pertumbuhan {{ $selectedChild->full_name }}</h6>
                            <p class="ng-panel-sub mb-0">Garis putus-putus menunjukkan rentang pertumbuhan yang diharapkan.</p>
                        </div>
                    </div>
                    @if ($chart)
                        <x-growth-chart :chart="$chart" id="ortuGrowthChart" />
                    @endif
                </div>

                @if ($trajectory)
                    @include('partials.trajectory', ['trajectory' => $trajectory])
                @endif
            </div>

            <div class="col-xl-4">
                <div class="ng-panel mb-3" id="profil">
                    <h6 class="ng-panel-title"><i class="ti ti-id-badge"></i> Profil anak</h6>
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
                    <div class="ng-indicator-row"><span class="ng-indicator-label">Kunjungan tercatat</span><strong>{{ $selectedChild->measurements()->count() }} kali</strong></div>
                </div>

                <div class="ng-panel mb-3">
                    <h6 class="ng-panel-title"><i class="ti ti-history"></i> Riwayat terakhir</h6>
                    @if ($measurements->isEmpty())
                        <x-empty-state icon="ti-scale" title="Belum ada pengukuran" description="Riwayat kunjungan akan tampil di sini." />
                    @else
                        <div class="ng-timeline">
                            @foreach ($measurements as $measurement)
                                <div class="ng-timeline-item">
                                    <strong>{{ $measurement->measured_at->translatedFormat('d F Y') }}</strong>
                                    <span>{{ $measurement->weight_kg }} kg · {{ $measurement->height_cm }} cm</span>
                                    <p>{{ $measurement->assessment?->overall_status ?? 'Menunggu penilaian' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="ng-panel">
                    <h6 class="ng-panel-title"><i class="ti ti-clipboard-check"></i> Rencana bersama</h6>
                    @include('partials.follow-up-list', ['followUps' => $followUps, 'emptyTitle' => 'Belum ada rencana tindak lanjut'])
                </div>
            </div>
        </div>

        <div class="mt-3">
            @include('partials.nutrition-cards', ['recommendation' => $nutrition])
        </div>

        @if ($nutrition['knowledge']->isNotEmpty())
            <div class="row g-3 mt-1">
                @foreach ($nutrition['knowledge'] as $knowledge)
                    <div class="col-md-6 col-xl-4">
                        <div class="ng-panel h-100">
                            <span class="badge badge-soft-info mb-2">{{ $nutrition['age_label'] }}</span>
                            <h6 class="ng-panel-title">{{ $knowledge->title }}</h6>
                            <p class="ng-small mb-2">{{ $knowledge->content }}</p>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($knowledge->key_nutrients ?? [] as $nutrient)
                                    <span class="ng-chip">{{ $nutrient }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <x-empty-state icon="ti-users" title="Belum ada data anak" description="Hubungi kader Posyandu untuk menghubungkan akun Anda dengan data balita." />
    @endif
@endsection
