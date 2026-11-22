@extends('layouts.app')

@section('title', 'Data Anak')

@section('content')
    <x-page-head eyebrow="Daftar balita" title="Data anak" description="Cari balita berdasarkan nama atau nomor registrasi, lalu buka profil pertumbuhannya.">
        <x-slot:actions>
            <form method="GET" action="{{ route('children.index') }}" class="d-flex gap-2">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau nomor registrasi">
                <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i></button>
            </form>
        </x-slot:actions>
    </x-page-head>

    <div class="ng-panel">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Balita</th>
                    <th>Usia</th>
                    <th>Posyandu</th>
                    <th>Kunjungan</th>
                    <th>Penilaian terakhir</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($children as $child)
                    @php($latest = $child->latestMeasurement?->assessment)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <x-child-avatar :child="$child" />
                                <div>
                                    <div class="fw-bold ng-small">{{ $child->full_name }}</div>
                                    <div class="ng-tiny ng-muted">{{ $child->registration_no }} · {{ $child->sex->label() }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="ng-small">{{ $child->ageLabel() }}</td>
                        <td class="ng-small">{{ $child->facility?->name }}</td>
                        <td class="ng-small">{{ $child->measurements_count }} kali</td>
                        <td><x-status-badge :status="$latest?->overall_status" /></td>
                        <td class="text-end">
                            <a href="{{ route('children.show', $child) }}" class="btn btn-outline-primary btn-sm">
                                <i class="ti ti-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="ti-users" title="Balita tidak ditemukan" description="Coba kata kunci lain atau hubungi kader Posyandu." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $children->links() }}</div>
    </div>
@endsection
