@extends('layouts.app')

@section('title', 'Jadwal Posyandu')

@section('content')
    <x-page-head
        eyebrow="Ruang keluarga"
        title="Jadwal Posyandu"
        description="Catat tanggal kunjungan, imunisasi wajib, vitamin A, dan obat cacing agar tidak ada yang terlewat.">
        <x-slot:actions>
            @if ($children->count() > 1)
                <form method="GET" action="{{ route('ortu.schedule') }}">
                    <select name="child" class="form-select" onchange="this.form.submit()">
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}" @selected($selectedChild && $selectedChild->id === $child->id)>{{ $child->full_name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            <a href="{{ route('ortu.growth') }}" class="btn btn-light"><i class="ti ti-chart-line me-1"></i> Pertumbuhan anak</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="ng-panel ng-panel-lime h-100">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="ng-eyebrow mb-1">Kunjungan berikutnya</div>
                        <div class="ng-status-value">{{ $nextVisit->translatedFormat('d F Y') }}</div>
                        <div class="ng-small ng-muted mt-1">{{ $nextVisit->translatedFormat('l') }} · 08.00–11.00</div>
                    </div>
                    <span class="ng-list-icon ng-tone-paper"><i class="ti ti-calendar-heart"></i></span>
                </div>
                <div class="ng-divider"></div>
                <ul class="ng-small ps-3 mb-0">
                    <li class="mb-1">Bawa Buku KIA dan kartu Posyandu.</li>
                    <li class="mb-1">Catat pertanyaan untuk kader atau bidan.</li>
                    <li>Datang lebih awal agar tidak menunggu lama.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="ng-panel h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="ng-panel-title mb-0"><i class="ti ti-calendar-month"></i> Kalender {{ $nextVisit->translatedFormat('F Y') }}</h6>
                    <span class="badge badge-soft-info">Hari kunjungan ditandai</span>
                </div>
                <div class="ng-calendar" role="grid" aria-label="Kalender jadwal Posyandu {{ $nextVisit->translatedFormat('F Y') }}">
                    @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayLabel)
                        <div class="ng-calendar-head" role="columnheader">{{ $dayLabel }}</div>
                    @endforeach
                    @foreach ($calendar as $week)
                        @foreach ($week as $cell)
                            <div class="ng-calendar-cell {{ $cell['is_visit'] ? 'is-visit' : '' }} {{ $cell['is_today'] ? 'is-today' : '' }}" role="gridcell">
                                @if ($cell['day'])
                                    <span>{{ $cell['day'] }}</span>
                                    @if ($cell['is_visit'])
                                        <span class="ng-calendar-dot" aria-label="Jadwal Posyandu"></span>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="ng-panel h-100">
                <h6 class="ng-panel-title"><i class="ti ti-calendar-event"></i> Jadwal kunjungan berikutnya</h6>
                <p class="ng-panel-sub">Tiga kunjungan terdekat untuk keluarga Anda.</p>
                <div class="ng-list">
                    @foreach ($visits as $visit)
                        <div class="ng-list-item ng-tone-{{ $visit['tone'] }}">
                            <span class="ng-list-icon ng-tone-paper"><i class="ti ti-calendar-check"></i></span>
                            <div>
                                <h6>{{ $visit['date']->translatedFormat('l, d F Y') }}</h6>
                                <p>{{ $visit['title'] }}</p>
                                <p class="ng-tiny ng-muted mt-1 mb-0">{{ $visit['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="ng-panel mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-vaccine"></i> Imunisasi wajib {{ $selectedChild?->full_name }}</h6>
                <p class="ng-panel-sub">Jadwal imunisasi dasar sesuai usia anak.</p>
                @if (empty($immunisations))
                    <x-empty-state icon="ti-vaccine" title="Belum ada data anak" description="Jadwal imunisasi muncul setelah data anak terhubung." />
                @else
                    <div class="ng-timeline">
                        @foreach ($immunisations as $immunisation)
                            <div class="ng-timeline-item">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <strong>{{ $immunisation['name'] }}</strong>
                                    @if ($immunisation['done'])
                                        <span class="badge ng-badge-good">Sudah</span>
                                    @elseif ($immunisation['due'])
                                        <span class="badge ng-badge-watch">Segera</span>
                                    @else
                                        <span class="badge ng-badge-neutral">{{ $immunisation['age'] }}</span>
                                    @endif
                                </div>
                                <span>{{ $immunisation['age'] }}</span>
                                <p>{{ $immunisation['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="ng-panel">
                <h6 class="ng-panel-title"><i class="ti ti-pill"></i> Vitamin A dan obat cacing</h6>
                <p class="ng-panel-sub">Diberikan rutin di Posyandu setiap Februari dan Agustus.</p>
                <div class="ng-list">
                    @foreach ($supplements as $supplement)
                        <div class="ng-list-item ng-tone-{{ $supplement['tone'] }}">
                            <span class="ng-list-icon ng-tone-paper"><i class="ti ti-pill"></i></span>
                            <div>
                                <h6>{{ $supplement['name'] }}</h6>
                                <p>{{ $supplement['schedule'] }}</p>
                                <p class="ng-tiny ng-muted mt-1 mb-0">{{ $supplement['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
