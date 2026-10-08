@include('supplier_terms._styles')
    <div class="modal fade" id="supplierTermsAcceptModal" tabindex="-1" role="dialog"
        data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="supplierTermsAcceptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierTermsAcceptModalLabel">{{ $supplierTermsGate['title'] }}</h5>
                </div>
                <div class="modal-body px-3 px-md-4">
                    @include('supplier_terms._document', [
                        'terms' => null,
                        'termsTitle' => $supplierTermsGate['title'],
                        'bodyHtml' => $supplierTermsGate['bodyHtml'],
                        'embedded' => true,
                    ])
                </div>
                <div class="modal-footer flex-column flex-md-row align-items-stretch align-items-md-center">
                    <form method="POST" action="{{ route('supplier.terms.accept') }}" class="w-100" id="supplierTermsAcceptForm">
                        @csrf
                        <div class="form-check mb-3 text-start">
                            <input type="checkbox" class="form-check-input" id="accept_terms" name="accept_terms" value="1" required>
                            <label class="form-check-label" for="accept_terms">
                                I have read and agree to the terms and conditions above.
                            </label>
                        </div>
                        @error('accept_terms')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <a href="{{ route('supplier.terms.pdf') }}" class="btn btn-outline-secondary mb-2 mb-md-0" target="_blank">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </a>
                            <button type="submit" class="btn btn-primary" id="supplierTermsAcceptBtn" disabled>
                                Accept and continue
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<script>
(function () {
    document.body.classList.add('supplier-terms-dashboard-blocked');
    var modalEl = document.getElementById('supplierTermsAcceptModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: 'static',
            keyboard: false
        }).show();
    }
    var cb = document.getElementById('accept_terms');
    var btn = document.getElementById('supplierTermsAcceptBtn');
    if (cb && btn) {
        cb.addEventListener('change', function () {
            btn.disabled = !cb.checked;
        });
    }
})();
</script>
