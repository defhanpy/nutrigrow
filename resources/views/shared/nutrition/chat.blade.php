@extends('layouts.app')

@section('title', 'Tanya Asisten Gizi AI (NutriBot)')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0 fw-bold">NutriBot — Asisten Pintar Gizi Balita</h1>
            <p class="ng-tiny text-muted mb-0">Konsultasi cepat seputar menu MPASI lokal, solusi anak GTM, dan kurva pertumbuhan WHO.</p>
        </div>
        <form method="POST" action="{{ route('nutrition.chat.reset') }}" onsubmit="return confirm('Mulai percakapan baru dan bersihkan riwayat chat?');">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-refresh me-1" aria-hidden="true"></i> Percakapan baru
            </button>
        </form>
    </div>

    <div class="row g-3">
        {{-- Chat Box Area (Left on Desktop, Top on Mobile) --}}
        <div class="col-lg-8 order-1">
            <div class="ng-panel p-0 ng-chat-card d-flex flex-column">
                {{-- Chat Header --}}
                <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="background: var(--ng-mint-soft); border-color: var(--ng-border) !important;">
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
                        <div class="badge badge-soft-success d-inline-flex align-items-center gap-1 ng-tiny">
                            <i class="ti ti-baby-carriage"></i> {{ $selectedChild->full_name }} ({{ $selectedChild->ageInMonths() }} bln)
                        </div>
                    @endif
                </div>

                {{-- Quick Prompts Horizontal Scroll Strip (Mobile-first) --}}
                <div class="ng-quick-scroll-bar px-3 py-2 border-bottom">
                    <span class="ng-tiny fw-bold text-muted me-1 flex-shrink-0"><i class="ti ti-bulb text-warning"></i> Tanya:</span>
                    <button type="button" class="ng-quick-pill js-quick-prompt" data-prompt="Ide menu MPASI 8 bulan tinggi zat besi dan protein hewani murah">
                        🥣 MPASI 8 Bulan
                    </button>
                    <button type="button" class="ng-quick-pill js-quick-prompt" data-prompt="Anak saya sedang GTM susah makan sayur dan nasi, bagaimana solusinya?">
                        💡 Solusi Anak GTM
                    </button>
                    <button type="button" class="ng-quick-pill js-quick-prompt" data-prompt="Apa bahan pangan lokal pengganti ikan salmon yang murah tapi kaya omega 3?">
                        🐟 Pengganti Salmon
                    </button>
                    <button type="button" class="ng-quick-pill js-quick-prompt" data-prompt="Bagaimana cara membaca kurva KMS dan arti garis kuning?">
                        📈 Cara Baca KMS
                    </button>
                    <button type="button" class="ng-quick-pill js-quick-prompt" data-prompt="Kapan anak harus segera dibawa ke dokter saat sedang sakit atau diare?">
                        🚨 Tanda Bahaya Darurat
                    </button>
                </div>

                {{-- Message History Stream --}}
                <div class="flex-grow-1 p-3 overflow-y-auto" id="chatStream" style="background: #fafcfb;">
                    {{-- Intro Greeting if empty --}}
                    @if ($messages->isEmpty())
                        <div class="text-center my-3 py-2" id="introGreeting">
                            <div class="ng-bot-avatar mx-auto mb-2" style="width:48px; height:48px; font-size:22px;">
                                <i class="ti ti-sparkles" aria-hidden="true"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Halo, {{ $user->name }}! Saya NutriBot.</h6>
                            <p class="ng-small text-muted mx-auto mb-0" style="max-width: 44ch;">
                                Tanyakan seputar variasi menu MPASI lokal, solusi anak GTM susah makan, atau panduan kenaikan berat badan {{ $selectedChild ? $selectedChild->full_name : 'si kecil' }}.
                            </p>
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
                                <div class="ng-bot-avatar flex-shrink-0" style="width:32px; height:32px; font-size:13px;">
                                    <i class="ti ti-sparkles"></i>
                                </div>
                                <div class="ng-chat-bubble ng-chat-bot">
                                    <div class="ng-chat-text">{!! nl2br(e($msg->message)) !!}</div>
                                    <div class="ng-chat-time">{{ $msg->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Dynamic Typing Indicator: Will always be moved to the very bottom before assistant replies --}}
                    <div id="typingIndicator" class="d-none d-flex gap-2 mb-3">
                        <div class="ng-bot-avatar flex-shrink-0" style="width:32px; height:32px; font-size:13px;">
                            <i class="ti ti-sparkles"></i>
                        </div>
                        <div class="ng-chat-bubble ng-chat-bot ng-thinking-bubble py-2 px-3">
                            <span class="ng-thinking-dot"></span>
                            <span class="ng-thinking-dot"></span>
                            <span class="ng-thinking-dot"></span>
                            <span class="ng-tiny text-muted ms-2 fw-semibold">NutriBot sedang menyusun jawaban...</span>
                        </div>
                    </div>
                </div>

                {{-- Input Form (Sticky at bottom of chat panel) --}}
                <div class="p-2 p-md-3 border-top" style="background: var(--ng-paper); border-color: var(--ng-border) !important;">
                    <form id="chatForm" method="POST" action="{{ route('nutrition.chat.send') }}" class="d-flex gap-2">
                        @csrf
                        <input type="hidden" name="child_id" value="{{ $selectedChild?->id }}">
                        <input type="text" id="chatInput" name="message" class="form-control" placeholder="Ketik pertanyaan seputar gizi &amp; tumbuh kembang..." autocomplete="off" required>
                        <button type="submit" id="chatSendBtn" class="btn btn-primary px-3 flex-shrink-0" aria-label="Kirim pesan">
                            <i class="ti ti-send me-1"></i> <span class="d-none d-sm-inline">Kirim</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Context & Guidance (Right on Desktop, Below on Mobile) --}}
        <div class="col-lg-4 order-2">
            {{-- Child Context Card --}}
            @if ($children->isNotEmpty())
                <div class="ng-panel mb-3">
                    <h6 class="ng-panel-title mb-2"><i class="ti ti-baby-carriage"></i> Balita yang Dikonsultasikan</h6>
                    <form method="GET" action="{{ route('nutrition.chat') }}" id="childFilterForm">
                        <select name="child" id="childSelect" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($children as $c)
                                <option value="{{ $c->id }}" @selected($selectedChild?->id === $c->id)>
                                    {{ $c->full_name }} ({{ $c->ageLabel() }})
                                </option>
                            @endforeach
                        </select>
                    </form>

                    @if ($selectedChild)
                        <div class="mt-3 pt-2 border-top">
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

            {{-- Ethical Rail Disclaimer Card --}}
            <div class="ng-panel ng-panel-mint">
                <div class="d-flex align-items-start gap-2">
                    <i class="ti ti-shield-check fs-20 text-success mt-1"></i>
                    <div>
                        <strong class="ng-small d-block text-dark">Batasan AI Bertanggung Jawab</strong>
                        <p class="ng-tiny text-muted mb-0 mt-1">
                            NutriBot adalah asisten edukasi berbasis bukti standar WHO &amp; panduan gizi nasional. Bukan vonis diagnosis dan tidak menggantikan konsultasi langsung dengan dokter atau bidan Posyandu.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .ng-chat-card {
        height: calc(100vh - 220px);
        min-height: 520px;
        max-height: 750px;
    }
    @media (max-width: 991.98px) {
        .ng-chat-card {
            height: 68vh;
            min-height: 440px;
        }
    }
    .ng-bot-avatar {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        background: var(--ng-forest);
        color: var(--ng-lime);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--ng-ink);
        box-shadow: 2px 2px 0 var(--ng-ink);
        flex-shrink: 0;
    }
    .ng-chat-bubble {
        max-width: 85%;
        border: 2px solid var(--ng-ink);
        border-radius: 12px;
        padding: 9px 13px;
        box-shadow: 2px 2px 0 var(--ng-ink);
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
        font-size: 13px;
        line-height: 1.55;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .ng-chat-time {
        font-size: 10px;
        opacity: 0.65;
        text-align: right;
        margin-top: 3px;
        font-weight: 600;
    }
    /* Horizontal scroll quick prompt pill bar */
    .ng-quick-scroll-bar {
        display: flex;
        align-items: center;
        gap: 6px;
        overflow-x: auto;
        white-space: nowrap;
        background: #f5f8f6;
        -webkit-overflow-scrolling: touch;
    }
    .ng-quick-scroll-bar::-webkit-scrollbar {
        height: 4px;
    }
    .ng-quick-scroll-bar::-webkit-scrollbar-thumb {
        background: #b9cdc2;
        border-radius: 4px;
    }
    .ng-quick-pill {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        font-size: 11.5px;
        font-weight: 700;
        border: 1.5px solid var(--ng-ink);
        border-radius: 999px;
        background: var(--ng-paper);
        color: var(--ng-ink);
        box-shadow: 1.5px 1.5px 0 var(--ng-ink);
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }
    .ng-quick-pill:hover,
    .ng-quick-pill:focus {
        background: var(--ng-mint);
        transform: translate(-1px, -1px);
        box-shadow: 2.5px 2.5px 0 var(--ng-ink);
    }
    /* Thinking animation dots */
    .ng-thinking-bubble {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #fff;
    }
    .ng-thinking-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--ng-forest);
        display: inline-block;
        animation: ngBounce 1.4s infinite ease-in-out both;
    }
    .ng-thinking-dot:nth-child(1) { animation-delay: -0.32s; }
    .ng-thinking-dot:nth-child(2) { animation-delay: -0.16s; }
    @keyframes ngBounce {
        0%, 80%, 100% { transform: scale(0); opacity: 0.3; }
        40% { transform: scale(1); opacity: 1; }
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
        const introGreeting = document.getElementById('introGreeting');

        const scrollToBottom = () => {
            chatStream.scrollTop = chatStream.scrollHeight;
        };
        scrollToBottom();

        // Quick prompts click handler
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

            if (introGreeting) {
                introGreeting.remove();
            }

            const now = new Date();
            const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

            // 1. Append user message before typing indicator
            const userDiv = document.createElement('div');
            userDiv.className = 'd-flex justify-content-end mb-3';
            userDiv.innerHTML = `
                <div class="ng-chat-bubble ng-chat-user">
                    <div class="ng-chat-text">${escapeHtml(message)}</div>
                    <div class="ng-chat-time">${timeStr}</div>
                </div>`;
            chatStream.insertBefore(userDiv, typingIndicator);

            chatInput.value = '';

            // 2. Move typing indicator to bottom & show it
            chatStream.appendChild(typingIndicator);
            typingIndicator.classList.remove('d-none');
            chatSendBtn.disabled = true;
            scrollToBottom();

            // 3. Submit request
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
                // Hide typing indicator
                typingIndicator.classList.add('d-none');
                chatSendBtn.disabled = false;

                if (data.reply) {
                    const formattedReply = escapeHtml(data.reply).replace(/\n/g, '<br>');
                    const botDiv = document.createElement('div');
                    botDiv.className = 'd-flex gap-2 mb-3';
                    botDiv.innerHTML = `
                        <div class="ng-bot-avatar flex-shrink-0" style="width:32px; height:32px; font-size:13px;">
                            <i class="ti ti-sparkles"></i>
                        </div>
                        <div class="ng-chat-bubble ng-chat-bot">
                            <div class="ng-chat-text">${formattedReply}</div>
                            <div class="ng-chat-time">${data.created_at || timeStr}</div>
                        </div>`;
                    chatStream.insertBefore(botDiv, typingIndicator);
                    scrollToBottom();
                }
            })
            .catch(() => {
                typingIndicator.classList.add('d-none');
                chatSendBtn.disabled = false;
                const errDiv = document.createElement('div');
                errDiv.className = 'alert alert-danger py-2 px-3 ng-small mb-3';
                errDiv.textContent = 'Gagal mengirim pesan. Silakan coba lagi.';
                chatStream.insertBefore(errDiv, typingIndicator);
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

