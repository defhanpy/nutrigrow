@extends('layouts.app')

@section('title', 'Pengaturan Puskesmas')

@section('content')
    <x-page-head
        eyebrow="{{ auth()->user()->facility?->name ?? 'Puskesmas' }}"
        title="Pengaturan wilayah"
        description="Profil fasilitas, target cakupan penimbangan, dan kontak rujukan untuk tim Posyandu.">
        <x-slot:actions>
            <a href="{{ route('puskesmas.posyandu') }}" class="btn btn-light"><i class="ti ti-building-community me-1"></i> Posyandu</a>
            <a href="{{ route('puskesmas.users') }}" class="btn btn-primary"><i class="ti ti-user-cog me-1"></i> Pengguna</a>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="ng-panel ng-panel-mint mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-building-hospital"></i> Profil faskes</h6>
                <p class="ng-panel-sub">Identitas fasilitas yang terhubung dengan akun Anda.</p>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Nama fasilitas</span><strong>{{ $facility?->name ?? 'Puskesmas Sukamaju' }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Jenis</span><strong>{{ $facility?->type->label() ?? 'Puskesmas' }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Kode fasilitas</span><strong>{{ $facility?->code ?? '—' }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Alamat</span><strong class="ng-small">{{ $facility?->address ?? '—' }}</strong></div>
                <div class="ng-indicator-row"><span class="ng-indicator-label">Posyandu binaan</span><strong>{{ $posyandu->count() }}</strong></div>
            </div>

            <div class="ng-panel">
                <h6 class="ng-panel-title"><i class="ti ti-target-arrow"></i> Target pemantauan</h6>
                <p class="ng-panel-sub">Acuan bersama untuk menilai cakupan dan tindak lanjut.</p>
                @foreach ($targets as $target)
                    <div class="ng-soft-box mb-2">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <strong class="ng-small">{{ $target['label'] }}</strong>
                            <span class="badge badge-soft-success">{{ $target['value'] }}</span>
                        </div>
                        <p class="ng-tiny ng-muted mb-0 mt-1">{{ $target['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-xl-7">
            <div class="ng-panel mb-3">
                <h6 class="ng-panel-title"><i class="ti ti-phone-call"></i> Kontak darurat rujukan</h6>
                <p class="ng-panel-sub">Nomor dan layanan yang dapat dihubungi saat diperlukan.</p>
                <div class="ng-list">
                    @foreach ($contacts as $contact)
                        <div class="ng-list-item">
                            <span class="ng-list-icon ng-tone-{{ $loop->first ? 'mint' : ($loop->last ? 'rose' : 'sky') }}"><i class="ti {{ $loop->last ? 'ti-ambulance' : 'ti-phone' }}"></i></span>
                            <div>
                                <h6>{{ $contact['name'] }}</h6>
                                <p>{{ $contact['label'] }} · {{ $contact['detail'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ng-panel">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <h6 class="ng-panel-title mb-0"><i class="ti ti-building-community"></i> Posyandu dan penanggung jawab</h6>
                    <span class="badge badge-soft-secondary">{{ $posyandu->count() }} Posyandu</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th style="width: 45px;">No.</th>
                            <th>Posyandu</th>
                            <th>Kode</th>
                            <th>Alamat</th>
                            <th>Petugas</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($posyandu as $entry)
                            <tr>
                                <td class="ng-small text-muted">{{ $loop->iteration }}</td>
                                <td class="ng-small fw-bold">{{ $entry->name }}</td>
                                <td class="ng-small">{{ $entry->code }}</td>
                                <td class="ng-small">{{ $entry->address }}</td>
                                <td class="ng-small">
                                    {{ $team->where('facility_id', $entry->id)->count() }} orang
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="ng-small ng-muted text-center py-3">Belum ada Posyandu pada wilayah ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="ng-divider"></div>
                <p class="ng-small ng-muted mb-0">
                    <i class="ti ti-shield-heart"></i> Perubahan profil fasilitas dan target wilayah dikelola bersama tim Puskesmas agar data tetap akurat.
                </p>
            </div>
        </div>
    </div>
@endsection
