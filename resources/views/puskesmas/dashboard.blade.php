@extends('layouts.app')

@section('title', 'Dashboard Wilayah')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Puskesmas' }}"
        title="Ringkasan wilayah"
        description="Pantau cakupan penimbangan, prevalensi gizi, dan kesiapan tindak lanjut di setiap Posyandu.">
        <x-slot:actions>
            <a href="{{ route('puskesmas.reports.download') }}" class="btn btn-primary"><i class="ti ti-file-download me-1"></i> Unduh laporan CSV</a>
            <a href="{{ route('puskesmas.reports') }}" class="btn btn-light"><i class="ti ti-report me-1"></i> Lihat laporan</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Total sasaran" :value="$summary['total_target']" icon="ti-users" tone="mint" hint="Balita aktif di wilayah" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Cakupan D/S" :value="$summary['coverage_percent'].'%'" icon="ti-scale" tone="sky" :hint="$summary['measured_this_month'].' balita ditimbang bulan ini'" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Prevalensi stunting" :value="$summary['stunting_percent'].'%'" icon="ti-ruler-measure" tone="amber" :hint="$summary['stunting_count'].' balita pendek/sangat pendek'" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-tile label="Prevalensi wasting" :value="$summary['wasting_percent'].'%'" icon="ti-heart-rate-monitor" tone="rose" :hint="$summary['wasting_count'].' balita wasting'" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8" id="posyandu">
            <div class="ng-panel">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div>
                        <h6 class="ng-panel-title mb-0"><i class="ti ti-building-community"></i> Perbandingan Posyandu</h6>
                        <p class="ng-panel-sub mb-0">Cakupan penimbangan bulan ini dan temuan gizi per Posyandu.</p>
                    </div>
                    <span class="badge badge-soft-secondary">{{ $posyandu->count() }} Posyandu</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th style="width: 45px;">No.</th>
                            <th>Posyandu</th>
                            <th class="text-end">Sasaran</th>
                            <th class="text-end">Ditimbang</th>
                            <th>Cakupan</th>
                            <th class="text-end">Pendek</th>
                            <th class="text-end">Wasting</th>
                            <th class="text-end">Perlu ditinjau</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($posyandu as $row)
                            <tr>
                                <td class="ng-small text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold ng-small">{{ $row['name'] }}</div>
                                    <div class="ng-tiny ng-muted">{{ $row['code'] }}</div>
                                </td>
                                <td class="text-end ng-small">{{ $row['total_target'] }}</td>
                                <td class="text-end ng-small">{{ $row['measured'] }}</td>
                                <td style="min-width:130px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:10px; border:2px solid var(--ng-ink); border-radius:999px;">
                                            <div class="progress-bar" style="width: {{ min(100, $row['coverage_percent']) }}%; background: var(--ng-forest);"></div>
                                        </div>
                                        <span class="ng-small fw-bold">{{ $row['coverage_percent'] }}%</span>
                                    </div>
                                </td>
                                <td class="text-end ng-small">{{ $row['stunting'] }}</td>
                                <td class="text-end ng-small">{{ $row['wasting'] }}</td>
                                <td class="text-end"><span class="badge {{ $row['open_alerts'] > 0 ? 'ng-badge-watch' : 'ng-badge-good' }}">{{ $row['open_alerts'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="ng-small ng-muted text-center py-3">Belum ada Posyandu pada wilayah ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="ng-panel ng-panel-mint h-100">
                        <h6 class="ng-panel-title"><i class="ti ti-heart-handshake"></i> Kesiapan tindak lanjut</h6>
                        <div class="ng-indicator-row"><span class="ng-indicator-label">Tanda risiko terbuka</span><strong>{{ $summary['open_alerts'] }}</strong></div>
                        <div class="ng-indicator-row"><span class="ng-indicator-label">Tindak lanjut menunggu</span><strong>{{ $summary['follow_up_pending'] }}</strong></div>
                        <p class="ng-small mb-0 mt-2">Gunakan laporan CSV untuk dibahas dalam rapat bulanan lintas sektor.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ng-panel h-100">
                        <h6 class="ng-panel-title"><i class="ti ti-bulb"></i> Saran pemantauan</h6>
                        <ul class="ng-small ps-3 mb-0">
                            <li class="mb-1">Prioritaskan Posyandu dengan cakupan di bawah 80%.</li>
                            <li class="mb-1">Tindak lanjuti tanda wasting dalam 3 hari kerja.</li>
                            <li>Pastikan setiap balita memiliki minimal satu pengukuran per bulan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="ng-panel mb-3" id="pengguna">
                <h6 class="ng-panel-title"><i class="ti ti-user-cog"></i> Tim layanan</h6>
                <p class="ng-panel-sub">Akun yang bertugas di wilayah ini.</p>
                <div class="ng-list">
                    @foreach ($users as $teamMember)
                        <div class="ng-list-item">
                            <span class="ng-avatar ng-avatar-sm">{{ $teamMember->initials() }}</span>
                            <div class="flex-grow-1">
                                <h6>{{ $teamMember->name }}</h6>
                                <p>{{ $teamMember->roleLabel() }} · {{ $teamMember->facility?->name }}</p>
                            </div>
                            <span class="badge {{ $teamMember->status->value === 'active' ? 'ng-badge-good' : 'ng-badge-neutral' }}">{{ $teamMember->status->label() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ng-panel mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-building-hospital"></i> Fasilitas</h6>
                <div class="ng-list">
                    @foreach ($facilities as $facility)
                        <div class="ng-list-item">
                            <span class="ng-list-icon"><i class="ti {{ $facility->isPuskesmas() ? 'ti-building-hospital' : 'ti-building-community' }}"></i></span>
                            <div>
                                <h6>{{ $facility->name }}</h6>
                                <p>{{ $facility->type->label() }} · {{ $facility->code }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ng-panel" id="pengaturan">
                <h6 class="ng-panel-title"><i class="ti ti-shield-heart"></i> Catatan privasi</h6>
                <p class="ng-small mb-0">
                    Laporan agregat tidak menampilkan identitas anak. Data rinci hanya dapat dibuka oleh petugas yang berwenang sesuai wilayah tugas.
                </p>
            </div>
        </div>
    </div>
@endsection
