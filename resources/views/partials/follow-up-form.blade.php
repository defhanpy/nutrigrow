@props(['child'])
<div class="ng-panel">
    <h6 class="ng-panel-title"><i class="ti ti-clipboard-plus"></i> Catat tindak lanjut</h6>
    <p class="ng-panel-sub">Tuliskan rencana yang disepakati bersama keluarga.</p>
    <form method="POST" action="{{ route('follow-ups.store', $child) }}" class="d-grid gap-2">
        @csrf
        <div>
            <label class="form-label" for="category">Jenis tindak lanjut</label>
            <select id="category" name="category" class="form-select" required>
                <option value="Pendampingan gizi">Pendampingan gizi</option>
                <option value="Kunjungan rumah">Kunjungan rumah</option>
                <option value="Rujukan ke Puskesmas">Rujukan ke Puskesmas</option>
                <option value="Penimbangan ulang">Penimbangan ulang</option>
                <option value="Edukasi keluarga">Edukasi keluarga</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="action_plan">Rencana tindakan</label>
            <textarea id="action_plan" name="action_plan" class="form-control" rows="2" maxlength="500" placeholder="contoh: dampingi ibu menyusun menu protein hewani" required></textarea>
        </div>
        <div>
            <label class="form-label" for="target_date">Target tanggal</label>
            <input type="date" id="target_date" name="target_date" class="form-control" min="{{ now()->toDateString() }}">
        </div>
        <div>
            <label class="form-label" for="notes">Catatan <span class="ng-tiny ng-muted">opsional</span></label>
            <input type="text" id="notes" name="notes" class="form-control" maxlength="500" placeholder="Kesepakatan dengan keluarga">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i> Simpan tindak lanjut</button>
    </form>
</div>
