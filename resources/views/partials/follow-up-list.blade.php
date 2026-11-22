@props(['followUps', 'emptyTitle' => 'Belum ada tindak lanjut'])
@if ($followUps->isEmpty())
    <x-empty-state icon="ti-clipboard" :title="$emptyTitle" description="Tindak lanjut yang disepakati akan tampil di sini." />
@else
    <div class="ng-timeline">
        @foreach ($followUps as $followUp)
            <div class="ng-timeline-item">
                <strong>{{ $followUp->category }}</strong>
                <span>
                    @if ($followUp->target_date)
                        Target {{ $followUp->target_date->translatedFormat('d M Y') }} ·
                    @endif
                    {{ $followUp->status->label() }}
                    @if ($followUp->creator) · oleh {{ $followUp->creator->name }} @endif
                </span>
                <p>{{ $followUp->action_plan }}</p>
                @if ($followUp->status->value === 'pending')
                    <form method="POST" action="{{ route('follow-ups.complete', $followUp) }}" class="mt-2">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-soft-lime btn-sm"><i class="ti ti-check me-1"></i> Tandai selesai</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@endif
