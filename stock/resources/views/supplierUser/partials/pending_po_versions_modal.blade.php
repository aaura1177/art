{{-- Supplier popup: pending furniture PO versions to accept --}}
@if (!empty($supplierPendingPoVersions) && count($supplierPendingPoVersions) > 0)
<div class="modal fade" id="supplierPoVersionsModal" tabindex="-1" aria-labelledby="supplierPoVersionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="supplierPoVersionsModalLabel">
                    <i class="fas fa-history me-1"></i> PO version update
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    One or more purchase orders were updated. Please review and accept the new version(s)
                    before raising an invoice.
                </p>
                <ul class="list-group mb-0">
                    @foreach ($supplierPendingPoVersions as $row)
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <strong>{{ $row['pono'] }}</strong>
                                <span class="badge bg-secondary ms-1">v{{ $row['latest'] }}</span>
                                @if ($row['pending'] > 1)
                                    <span class="badge bg-warning text-dark">{{ $row['pending'] }} pending</span>
                                @else
                                    <span class="badge bg-warning text-dark">Accept needed</span>
                                @endif
                            </div>
                            <a class="btn btn-sm btn-primary js-po-version-popup-nav" href="{{ $row['versions_url'] }}">Review &amp; accept</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="modal-footer">
                <a class="btn btn-outline-secondary js-po-version-popup-nav" href="{{ url('/supplier-dashboard/accepted-purchase-orders') }}">Accepted POs</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="supplierPoVersionsDismissBtn">Later</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var fingerprint = @json(
        collect($supplierPendingPoVersions)
            ->map(fn ($r) => ($r['purchase_order_id'] ?? '').':'.($r['latest'] ?? '').':'.($r['pending'] ?? ''))
            ->sort()
            ->values()
            ->implode('|')
    );
    var storageKey = 'po_version_popup_dismissed_' + fingerprint;
    var markDismissed = function () {
        try {
            sessionStorage.setItem(storageKey, '1');
        } catch (e) {}
    };

    // Don't re-open while supplier is already on a version review page
    if (/\/supplier-dashboard\/purchase-orders\/\d+\/versions\/?$/.test(window.location.pathname)) {
        markDismissed();
        return;
    }

    try {
        if (sessionStorage.getItem(storageKey) === '1') {
            return;
        }
    } catch (e) {}

    var el = document.getElementById('supplierPoVersionsModal');
    if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    // Navigating via modal links must dismiss before the next page loads
    el.querySelectorAll('.js-po-version-popup-nav').forEach(function (link) {
        link.addEventListener('click', markDismissed);
    });

    var modal = bootstrap.Modal.getOrCreateInstance(el);
    modal.show();
    el.addEventListener('hidden.bs.modal', markDismissed);
})();
</script>
@endif
