@php
    $modalChildren = $children ?? collect();
@endphp
<div class="modal fade" id="measurementModal" tabindex="-1" aria-labelledby="measurementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border:3px solid var(--ng-ink); border-radius:12px; box-shadow:8px 8px 0 var(--ng-ink);">
            <form method="POST" id="measurementForm" action="{{ $selectedChild ? route('measurements.store', $selectedChild) : '#' }}">
                @csrf
                <div class="modal-header" style="background:var(--ng-mint); border-bottom:2px solid var(--ng-ink);">
                    <div>
                        <div class="ng-eyebrow mb-1">Catat kunjungan</div>
                        <h5 class="modal-title" id="measurementModalLabel">Pengukuran baru</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="measurement_child">Balita</label>
                            <select id="measurement_child" class="form-select" required>
                                @foreach ($modalChildren as $child)
                                    <option value="{{ $child->id }}" data-url="{{ route('measurements.store', $child) }}" data-preview="{{ route('measurements.preview', $child) }}" @selected($selectedChild && $selectedChild->id === $child->id)>
                                        {{ $child->full_name }} · {{ $child->ageLabel() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="measured_at">Tanggal pengukuran</label>
                            <input type="date" id="measured_at" name="measured_at" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="weight_kg">Berat badan (kg)</label>
                            <input type="number" id="weight_kg" name="weight_kg" class="form-control" step="0.01" min="1" max="40" placeholder="contoh: 9.45" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="height_cm">Tinggi / panjang (cm)</label>
                            <input type="number" id="height_cm" name="height_cm" class="form-control" step="0.1" min="30" max="130" placeholder="contoh: 78.2" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="lila_cm">Lingkar lengan (cm) <span class="ng-tiny ng-muted">opsional</span></label>
                            <input type="number" id="lila_cm" name="lila_cm" class="form-control" step="0.1" min="5" max="30" placeholder="contoh: 14.5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="method">Posisi pengukuran</label>
                            <select id="method" name="method" class="form-select" required>
                                <option value="terlentang">Terlentang (berbaring)</option>
                                <option value="berdiri">Berdiri</option>
                            </select>
                            <div class="form-text">Untuk anak di bawah 2 tahun, gunakan posisi terlentang.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="notes">Catatan kunjungan <span class="ng-tiny ng-muted">opsional</span></label>
                            <input type="text" id="notes" name="notes" class="form-control" maxlength="500" placeholder="contoh: nafsu makan menurun">
                        </div>
                    </div>

                    <div class="ng-soft-box mt-3" id="previewBox">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="ng-small"><i class="ti ti-sparkles"></i> Pratinjau penilaian</strong>
                            <span class="badge badge-soft-secondary" id="previewAge">—</span>
                        </div>
                        <div id="previewBody" class="ng-small ng-muted">
                            Isi berat dan tinggi badan untuk melihat penilaian awal secara langsung.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:2px solid var(--ng-ink);">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Simpan pengukuran</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('measurementForm');
        const childSelect = document.getElementById('measurement_child');
        const weight = document.getElementById('weight_kg');
        const height = document.getElementById('height_cm');
        const measuredAt = document.getElementById('measured_at');
        const method = document.getElementById('method');
        const previewBody = document.getElementById('previewBody');
        const previewAge = document.getElementById('previewAge');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let timer = null;

        if (!form || !childSelect) return;

        const updateTarget = () => {
            const option = childSelect.options[childSelect.selectedIndex];
            if (option) form.action = option.dataset.url;
        };

        const toneFor = (status) => {
            if (!status) return 'neutral';
            if (['Gizi Baik', 'Normal', 'Tinggi'].includes(status)) return 'good';
            if (['Berisiko Gizi Lebih', 'Gizi Lebih'].includes(status)) return 'watch';
            return 'alert';
        };

        const renderPreview = () => {
            if (!weight.value || !height.value) {
                previewBody.innerHTML = 'Isi berat dan tinggi badan untuk melihat penilaian awal secara langsung.';
                previewAge.textContent = '—';
                return;
            }
            const option = childSelect.options[childSelect.selectedIndex];
            if (!option) return;

            fetch(option.dataset.preview, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    measured_at: measuredAt.value,
                    weight_kg: weight.value,
                    height_cm: height.value,
                    method: method.value,
                }),
            })
                .then((res) => res.ok ? res.json() : Promise.reject(res))
                .then((data) => {
                    previewAge.textContent = data.age_label;
                    const rows = data.indicators.map((indicator) => `
                        <div class="ng-indicator-row">
                            <span class="ng-indicator-label">${indicator.label}</span>
                            <span class="badge ng-badge-${toneFor(indicator.status)}">${indicator.status ?? 'Belum dapat dinilai'}</span>
                        </div>`).join('');
                    previewBody.innerHTML = rows + `
                        <div class="mt-2 ng-tiny ng-muted">Penilaian awal bersifat informatif. Tenaga kesehatan tetap menjadi rujukan utama.</div>`;
                })
                .catch(() => {
                    previewBody.innerHTML = '<span class="text-danger">Pratinjau belum tersedia. Periksa kembali angka yang dimasukkan.</span>';
                });
        };

        const schedule = () => {
            clearTimeout(timer);
            timer = setTimeout(renderPreview, 450);
        };

        childSelect.addEventListener('change', () => { updateTarget(); schedule(); });
        [weight, height, measuredAt, method].forEach((input) => {
            input.addEventListener('input', schedule);
            input.addEventListener('change', schedule);
        });

        updateTarget();
    })();
</script>
@endpush
