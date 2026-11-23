@extends('layouts.app')

@section('title', 'Tanya Asisten Gizi AI (NutriBot)')

@section('content')
    <x-page-head
        title="NutriBot — Asisten Pintar Gizi Balita"
        subtitle="Konsultasi cepat seputar menu MPASI pangan lokal, tips anak susah makan (GTM), dan panduan kurva pertumbuhan standar WHO."
    >
        <x-slot:actions>
            <form method="POST" action="{{ route('nutrition.chat.reset') }}" onsubmit="return confirm('Mulai percakapan baru dan bersihkan riwayat chat?');">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-refresh me-1" aria-hidden="true"></i> Percakapan baru
                </button>
            </form>
        </x-slot:actions>
    </x-page-head>

    <div class="row g-3">
        {{-- Chat Box Area (Left) --}}
        <div class="col-lg-8">
            <div class="ng-panel p-0 d-flex flex-column" style="min-height: 600px; height: calc(100vh - 240px); max-height: 750px;">
                {{-- Chat Header --}}
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between" style="background: var(--ng-mint-soft); border-color: var(--ng-border) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ng-bot-avatar">
                            <i class="ti ti-sparkles" aria-hidden="true"></i>
                        </div>
                        <div>
                            <div class="fw-bold ng-small text-dark">NutriBot AI</div>
                            <div class="ng-tiny text-success d-flex align-items-center gap-1">
                                <span class="ng-live-pulse" style="width:6px; height:6px;"></span> Aktif · Berbasis Panduan Gizi WHO
                            </div>
                        </div>
                    </div>
                    @if ($selectedChild)
                        <div class="badge badge-soft-success d-none d-sm-inline-flex align-items-center gap-1">
                            <i class="ti ti-baby-carriage"></i> Konteks: {{ $selectedChild->full_name }} ({{ $selectedChild->ageInMonths() }} bln)
                        </div>
                    @endif
                </div>

                {{-- Message History Stream --}}
                <div class="flex-grow-1 p-3 overflow-y-auto" id="chatStream" style="background: #fafcfb;">
                    {{-- Intro Greeting if empty --}}
                    @if ($messages->isEmpty())
                        <div class="text-center my-4 py-3">
                            <div class="ng-bot-avatar mx-auto mb-3" style="width:54px; height:54px; font-size:24px;">
                                <i class="ti ti-sparkles" aria-hidden="true"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Halo, {{ $user->name }}! Saya NutriBot.</h6>
                            <p class="ng-small text-muted mx-auto" style="max-width: 42ch;">
                                Ada yang ingin ditanyakan seputar variasi menu MPASI lokal, solusi anak susah makan, atau panduan kenaikan berat badan {{ $selectedChild ? $selectedChild->full_name : 'si kecil' }}?
                            </p>
                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                <button type="button" class="btn btn-sm btn-outline-primary js-quick-prompt" data-prompt="Ide menu MPASI 8 bulan tinggi zat besi dan protein hewani murah">
                                    🥣 Menu MPASI 8 Bulan
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary js-quick-prompt" data-prompt="Anak saya sedang GTM susah makan sayur dan nasi, bagaimana solusinya?">
                                    💡 Solusi Anak GTM
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary js-quick-prompt" data-prompt="Apa bahan pangan lokal pengganti ikan salmon yang murah tapi kaya omega 3?">
                                    🐟 Pangan Pengganti Salmon
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary js-quick-prompt" data-prompt="Bagaimana cara membaca kurva KMS dan arti garis kuning?">
                                    📈 Cara Baca Kurva KMS
                                </button>
                            </div>
                        </div>
                    @endif

                    @foreach ($messages as $msg)
                        @if ($msg->role === 'user')
                            <div class="d-flex justify-content-end mb-3">
                                <div class="ng-chat-bubble ng-chat-user">
                                    <div class="ng-chat-text">{{ $msg->message }}</div>
                                    <div class="ng-chat-time">{{ $msg->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                        @else
                            <div class="d-flex gap-2 mb-3">
                                <div class="ng-bot-avatar flex-shrink-0" style="width:34px; height:34px; font-size:14px;">
                                    <i class="ti ti-sparkles"></i>
                                </div>
                                <div class="ng-chat-bubble ng-chat-bot">
                                    <div class="ng-chat-text">{!! nl2br(e($msg->message)) !!}</div>
                                    <div class="ng-chat-time">{{ $msg->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <div id="typingIndicator" class="d-none d-flex gap-2 mb-3">
                        <div class="ng-bot-avatar flex-shrink-0" style="width:34px; height:34px; font-size:14px;">
                            <i class="ti ti-sparkles"></i>
                        </div>
                        <div class="ng-chat-bubble ng-chat-bot py-2 px-3">
                            <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                            <span class="ng-small text-muted ms-1">NutriBot sedang menyusun jawaban...</span>
                        </div>
                    </div>
                </div>

                {{-- Input Form --}}
                <div class="p-3 border-top" style="background: var(--ng-paper); border-color: var(--ng-border) !important;">
                    <form id="chatForm" method="POST" action="{{ route('nutrition.chat.send') }}" class="d-flex gap-2">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $selectedChild?->id }}">
                        <input type="text" id="chatInput" name="message" class="form-control" placeholder="Tanyakan seputar gizi, menu, atau tumbuh kembang..." autocomplete="off" required>
                        <button type="submit" id="chatSendBtn" class="btn btn-primary px-3 flex-shrink-0">
                            <i class="ti ti-send me-1"></i> Kirim
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Context & Guidance (Right) --}}
        <div class="col-lg-4">
            {{-- Child Context Card --}}
            @if ($children->isNotEmpty())
                <div class="ng-panel mb-3">
                    <h6 class="ng-panel-title mb-2"><i class="ti ti-baby-carriage"></i> Pilih Balita</h6>
                    <form method="GET" action="{{ route('nutrition.chat') }}" id="childFilterForm">
                        <label class="form-label ng-small text-muted" for="childSelect">Sesuaikan saran gizi dengan profil anak:</label>
                        <select name="child" id="childSelect" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($children as $c)
                                <option value="{{ $c->id }}" @selected($selectedChild?->id === $c->id)>
                                    {{ $c->full_name }} ({{ $c->ageLabel() }})
                                </option>
                            @endforeach
                        </select>
                    </form>

                    @if ($selectedChild)
                        <div class="mt-3 pt-3 border-top">
                            <div class="ng-indicator-row">
                                <span class="ng-indicator-label">Usia saat ini</span>
                                <strong>{{ $selectedChild->ageLabel() }}</strong>
                            </div>
                            <div class="ng-indicator-row">
                                <span class="ng-indicator-label">Status terakhir</span>
                                <x-status-badge :status="$selectedChild->latestMeasurement?->assessment?->overall_status" />
                            </div>
                            <div class="ng-indicator-row">
                                <span class="ng-indicator-label">Posyandu</span>
                                <strong class="ng-small">{{ $selectedChild->facility?->name }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Quick Prompts Guide --}}
            <div class="ng-panel mb-3">
                <h6 class="ng-panel-title mb-2"><i class="ti ti-bulb"></i> Contoh Pertanyaan</h6>
                <div class="d-flex flex-column gap-2 mt-2">
                    <a href="javascript:void(0)" class="ng-prompt-card js-quick-prompt" data-prompt="Apa ide menu MPASI lengkap untuk balita usia 9-11 bulan dari bahan lokal?">
                        <i class="ti ti-arrow-right text-success"></i>
                        <span>Menu MPASI lengkap 9–11 bulan</span>
                    </a>
                    <a href="javascript:void(0)" class="ng-prompt-card js-quick-prompt" data-prompt="Bagaimana cara meningkatkan berat badan anak yang berada di bawah garis median WHO?">
                        <i class="ti ti-arrow-right text-success"></i>
                        <span>Menaikkan BB di bawah median</span>
                    </a>
                    <a href="javascript:void(0)" class="ng-prompt-card js-quick-prompt" data-prompt="Sebutkan manfaat hati ayam dan daun kelor untuk pencegahan stunting pada balita.">
                        <i class="ti ti-arrow-right text-success"></i>
                        <span>Khasiat hati ayam &amp; daun kelor</span>
                    </a>
                    <a href="javascript:void(0)" class="ng-prompt-card js-quick-prompt" data-prompt="Kapan anak harus segera dibawa ke dokter saat sedang sakit atau diare?">
                        <i class="ti ti-arrow-right text-success"></i>
                        <span>Tanda bahaya medis darurat</span>
                    </a>
                </div>
            </div>

            {{-- Ethical Rail Disclaimer Card --}}
            <div class="ng-panel ng-panel-mint">
                <div class="d-flex align-items-start gap-2">
                    <i class="ti ti-shield-check fs-20 text-success mt-1"></i>
                    <div>
                        <strong class="ng-small d-block text-dark">Batasan AI Bertanggung Jawab</strong>
                        <p class="ng-tiny text-muted mb-0 mt-1">
                            NutriBot dirancang sebagai asisten edukasi berbasis bukti standar WHO &amp; Kemenkes. Jawaban bukan vonis diagnosis dan tidak menggantikan konsultasi langsung dengan dokter atau bidan Posyandu.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .ng-bot-avatar {
        width: 36px;
        height: 36px;
        border-radius: 999px;
        background: var(--ng-forest);
        color: var(--ng-lime);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--ng-ink);
        box-shadow: 2px 2px 0 var(--ng-ink);
    }
    .ng-chat-bubble {
        max-width: 82%;
        border: 2px solid var(--ng-ink);
        border-radius: 12px;
        padding: 10px 14px;
        box-shadow: 2.5px 2.5px 0 var(--ng-ink);
    }
    .ng-chat-user {
        background: var(--ng-lime);
        color: var(--ng-ink);
        border-bottom-right-radius: 2px;
    }
    .ng-chat-bot {
        background: #fff;
        color: var(--ng-ink);
        border-bottom-left-radius: 2px;
    }
    .ng-chat-text {
        font-size: 13.5px;
        line-height: 1.55;
        white-space: pre-wrap;
    }
    .ng-chat-time {
        font-size: 10px;
        opacity: 0.65;
        text-align: right;
        margin-top: 4px;
        font-weight: 600;
    }
    .ng-prompt-card {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        background: var(--ng-paper);
        border: 1.5px solid var(--ng-ink);
        border-radius: 8px;
        text-decoration: none;
        color: var(--ng-ink);
        font-size: 12px;
        font-weight: 700;
        box-shadow: 2px 2px 0 var(--ng-ink);
        transition: all 0.15s ease;
    }
    .ng-prompt-card:hover {
        background: var(--ng-mint);
        color: var(--ng-ink);
        transform: translate(-1px, -1px);
        box-shadow: 3px 3px 0 var(--ng-ink);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chatStream = document.getElementById('chatStream');
        const chatForm = document.getElementById('chatForm');
        const chatInput = document.getElementById('chatInput');
        const chatSendBtn = document.getElementById('chatSendBtn');
        const typingIndicator = document.getElementById('typingIndicator');

        const scrollToBottom = () => {
            chatStream.scrollTop = chatStream.scrollHeight;
        };
        scrollToBottom();

        // Quick prompts handler
        document.querySelectorAll('.js-quick-prompt').forEach(btn => {
            btn.addEventListener('click', function () {
                const prompt = this.dataset.prompt;
                if (!prompt) return;
                chatInput.value = prompt;
                chatForm.dispatchEvent(new Event('submit', { cancelable: true }));
            });
        });

        // AJAX Chat Submission
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const message = chatInput.value.trim();
            if (!message) return;

            // Append user message immediately to stream
            const now = new Date();
            const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
            const userHtml = `
                <div class="d-flex justify-content-end mb-3">
                    <div class="ng-chat-bubble ng-chat-user">
                        <div class="ng-chat-text">${escapeHtml(message)}</div>
                        <div class="ng-chat-time">${timeStr}</div>
                    </div>
                </div>`;
            chatStream.insertAdjacentHTML('beforeend', userHtml);
            chatInput.value = '';
            scrollToBottom();

            // Show typing indicator
            typingIndicator.classList.remove('d-none');
            chatSendBtn.disabled = true;
            scrollToBottom();

            // Submit via fetch
            fetch("{{ route('nutrition.chat.send') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: message,
                    child_id: document.querySelector('input[name="child_id"]')?.value || null
                })
            })
            .then(res => res.json())
            .then(data => {
                typingIndicator.classList.add('d-none');
                chatSendBtn.disabled = false;
                if (data.reply) {
                    const formattedReply = data.reply.replace(/\n/g, '<br>');
                    const botHtml = `
                        <div class="d-flex gap-2 mb-3">
                            <div class="ng-bot-avatar flex-shrink-0" style="width:34px; height:34px; font-size:14px;">
                                <i class="ti ti-sparkles"></i>
                            </div>
                            <div class="ng-chat-bubble ng-chat-bot">
                                <div class="ng-chat-text">${formattedReply}</div>
                                <div class="ng-chat-time">${data.created_at || timeStr}</div>
                            </div>
                        </div>`;
                    chatStream.insertAdjacentHTML('beforeend', botHtml);
                    scrollToBottom();
                }
            })
            .catch(err => {
                typingIndicator.classList.add('d-none');
                chatSendBtn.disabled = false;
                const errHtml = `
                    <div class="alert alert-danger py-2 px-3 ng-small mb-3">
                        Gagal mengirim pesan. Silakan coba lagi.
                    </div>`;
                chatStream.insertAdjacentHTML('beforeend', errHtml);
                scrollToBottom();
            });
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
</script>
@endpush
