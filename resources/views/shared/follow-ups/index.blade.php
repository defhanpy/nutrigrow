@extends('layouts.app')

@section('title', 'Tindak Lanjut')

@section('content')
    <x-page-head eyebrow="Pendampingan keluarga" title="Tindak lanjut" description="Pantau rencana yang disepakati bersama keluarga dan tandai yang sudah selesai." />

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="ng-panel">
                @if ($followUps->isEmpty())
                    <x-empty-state icon="ti-clipboard" title="Belum ada tindak lanjut" description="Rencana tindak lanjut dari kunjungan akan tampil di sini." />
                @else
                    <div class="ng-list">
                        @foreach ($followUps as $followUp)
                            <div class="ng-list-item">
                                <span class="ng-list-icon ng-tone-{{ $followUp->status->value === 'pending' ? 'amber' : 'lime' }}">
                                    <i class="ti {{ $followUp->status->value === 'pending' ? 'ti-clock' : 'ti-circle-check' }}"></i>
                                </span>
                                <div class="flex-grow-1">
                                    <h6>{{ $followUp->child->full_name }} · {{ $followUp->category }}</h6>
                                    <p>{{ $followUp->action_plan }}</p>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <span class="badge {{ $followUp->status->value === 'pending' ? 'ng-badge-watch' : 'ng-badge-good' }}">{{ $followUp->status->label() }}</span>
                                        @if ($followUp->target_date)
                                            <span class="badge badge-soft-secondary">Target {{ $followUp->target_date->translatedFormat('d M Y') }}</span>
                                        @endif
                                        @if ($followUp->creator)
                                            <span class="badge badge-soft-secondary">{{ $followUp->creator->name }}</span>
                                        @endif
                                    </div>
                                    @if ($followUp->notes)
                                        <p class="ng-tiny ng-muted mt-1 mb-0">{{ $followUp->notes }}</p>
                                    @endif
                                </div>
                                @if ($followUp->status->value === 'pending' && ! auth()->user()->isOrangTua())
                                    <form method="POST" action="{{ route('follow-ups.complete', $followUp) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-soft-lime btn-sm" aria-label="Tandai selesai tindak lanjut {{ $followUp->child->full_name }}"><i class="ti ti-check" aria-hidden="true"></i></button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">{{ $followUps->links() }}</div>
                @endif
            </div>
        </div>
        <div class="col-xl-4">
            <div class="ng-panel">
                <h6 class="ng-panel-title"><i class="ti ti-info-circle"></i> Panduan singkat</h6>
                <ul class="ng-small ps-3 mb-0">
                    <li class="mb-2">Catat tindak lanjut segera setelah kunjungan agar keluarga ingat kesepakatan.</li>
                    <li class="mb-2">Gunakan bahasa sederhana dan hindari istilah medis.</li>
                    <li>Tandai selesai setelah keluarga mengonfirmasi.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
