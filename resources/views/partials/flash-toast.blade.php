<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="ngToast" class="toast ng-toast" role="status" aria-live="polite" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="ngToastBody"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup pemberitahuan"></button>
        </div>
    </div>
</div>

<script>
    window.NG = {
        showToast(message) {
            const body = document.getElementById('ngToastBody');
            const el = document.getElementById('ngToast');
            if (!body || !el || !window.bootstrap) return;
            body.textContent = message;
            window.bootstrap.Toast.getOrCreateInstance(el, { delay: 4500 }).show();
        }
    };
    document.addEventListener('DOMContentLoaded', () => {
        @if (session('status'))
            window.NG.showToast(@json(session('status')));
        @endif
        @if ($errors->any())
            window.NG.showToast(@json($errors->first()));
        @endif
    });
</script>
