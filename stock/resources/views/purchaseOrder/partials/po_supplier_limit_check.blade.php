{{-- PO monthly limit AJAX (furniture + consumable PO only; carton exempt) --}}
<style>
    #po-limit-alerts-fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        padding: 0.5rem 1rem 0;
        max-width: 100%;
        pointer-events: none;
    }
    #po-limit-alerts-fixed .alert {
        pointer-events: auto;
        margin: 0 auto 0.5rem;
        max-width: 960px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
    }
</style>

<div id="po-limit-alerts-fixed" aria-live="polite">
    <div id="poLimitStatusBanner" class="alert alert-warning mb-0" style="display:none;" role="alert"></div>
    <div id="poLimitStatusBlocked" class="alert alert-danger mb-0" style="display:none;" role="alert"></div>
    <div id="poLimitStatusError" class="alert alert-secondary mb-0" style="display:none;" role="alert"></div>
</div>

<div class="modal fade" id="poLimitWarningModal" tabindex="-1" role="dialog" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">{{ __('PO monthly limit warning') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="poLimitWarningModalText"></p>
                <p class="mb-0 small text-muted">{{ __('You must acknowledge to continue. If you change supplier, date, or subtotal, you may see this again.') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="poLimitWarningAckBtn">{{ __('I understand, continue') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var PO_KIND = @json($poKind ?? 'furniture');
    var EXCLUDE_FURNITURE_PO_ID = @json($excludeFurniturePoId ?? null);
    var EXCLUDE_CONSUMABLE_PO_ID = @json($excludeConsumablePoId ?? null);
    var STATUS_URL = @json(url('/purchaseOrder/supplierPoLimitStatus'));

    var poLimitAcknowledged = false;
    var poLimitLastKey = '';
    var poLimitCheckTimer = null;
    var poLimitPreviousLevel = 'ok';
    var poLimitSessionKey = '';

    function poLimitSupplierId() {
        var $sel = $('select[name="supplier_id"]');
        var v = $sel.val();
        if ((!v || v === '') && typeof $sel.selectpicker === 'function') {
            v = $sel.selectpicker('val');
        }
        if (Array.isArray(v)) {
            v = v[0] || '';
        }
        return v || '';
    }

    function poLimitSupplierDateKey() {
        return [
            poLimitSupplierId(),
            $('input[name="podate"]').val() || ''
        ].join('|');
    }

    function poLimitSnapshotKey() {
        return poLimitSupplierDateKey() + '|' + ($('#subtotalamount').val() || '');
    }

    function resetPoLimitAck() {
        poLimitAcknowledged = false;
    }

    function resetPoLimitCrossTracking() {
        poLimitPreviousLevel = 'ok';
        poLimitSessionKey = '';
    }

    function clearPoLimitBanners() {
        $('#poLimitStatusBanner, #poLimitStatusBlocked, #poLimitStatusError').hide().text('');
    }

    function applyPoLimitStatus(data, forceWarningModal) {
        if (!data || data.error) {
            return;
        }

        var sessionKey = poLimitSupplierDateKey();
        if (sessionKey !== poLimitSessionKey) {
            poLimitSessionKey = sessionKey;
            poLimitPreviousLevel = 'ok';
        }

        clearPoLimitBanners();

        if (!data.configured) {
            poLimitPreviousLevel = 'ok';
            return;
        }

        var level = data.level || 'ok';

        if (level === 'warning' && poLimitPreviousLevel === 'ok' && !forceWarningModal) {
            window.alert(data.message);
        }

        poLimitPreviousLevel = level;

        if (level === 'blocked') {
            $('#poLimitStatusBlocked').text(data.message).show();
        } else if (level === 'warning') {
            $('#poLimitStatusBanner').text(data.message).show();
        }

        if (level === 'warning' && !poLimitAcknowledged && forceWarningModal) {
            showPoLimitWarningModal(data.message);
        }
    }

    function showPoLimitError(message) {
        clearPoLimitBanners();
        poLimitPreviousLevel = 'ok';
        $('#poLimitStatusError').text(message).show();
    }

    function getPoLimitWarningModal() {
        var el = document.getElementById('poLimitWarningModal');
        if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }
        return bootstrap.Modal.getOrCreateInstance(el, {
            backdrop: 'static',
            keyboard: false
        });
    }

    function showPoLimitWarningModal(message) {
        $('#poLimitWarningModalText').text(message);
        var instance = getPoLimitWarningModal();
        if (instance) {
            instance.show();
        }
    }

    function hidePoLimitWarningModal() {
        var instance = getPoLimitWarningModal();
        if (instance) {
            instance.hide();
        }
    }

    window.checkPoSupplierLimit = function (forceWarningModal) {
        var supplierId = poLimitSupplierId();
        var podate = $('input[name="podate"]').val();
        var amount = parseFloat($('#subtotalamount').val() || '0') || 0;

        if (!supplierId || !podate) {
            clearPoLimitBanners();
            resetPoLimitCrossTracking();
            return $.Deferred().resolve({ configured: false, level: 'ok' }).promise();
        }

        var key = poLimitSnapshotKey();
        if (key !== poLimitLastKey) {
            resetPoLimitAck();
            poLimitLastKey = key;
        }

        var req = $.getJSON(STATUS_URL, {
            supplier_id: supplierId,
            podate: podate,
            amount: amount,
            po_kind: PO_KIND,
            exclude_furniture_po_id: EXCLUDE_FURNITURE_PO_ID,
            exclude_consumable_po_id: EXCLUDE_CONSUMABLE_PO_ID
        });
        req.done(function (data) {
            applyPoLimitStatus(data, forceWarningModal);
        });
        req.fail(function (xhr) {
            var msg = '{{ __('Could not verify supplier PO limit. Check your connection or contact support.') }}';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showPoLimitError(msg);
        });
        return req;
    };

    window.schedulePoLimitCheck = function () {
        clearTimeout(poLimitCheckTimer);
        poLimitCheckTimer = setTimeout(function () {
            window.checkPoSupplierLimit(false);
        }, 400);
    };

    $(document).ready(function () {
        $('body').prepend($('#po-limit-alerts-fixed'));

        var $supplier = $('select[name="supplier_id"]');
        $supplier.on('change', function () {
            resetPoLimitAck();
            resetPoLimitCrossTracking();
            window.schedulePoLimitCheck();
        });
        $supplier.on('changed.bs.select', function () {
            resetPoLimitAck();
            resetPoLimitCrossTracking();
            window.schedulePoLimitCheck();
        });

        $('input[name="podate"]').on('change', function () {
            resetPoLimitAck();
            resetPoLimitCrossTracking();
            window.schedulePoLimitCheck();
        });

        $('#subtotalamount').on('change input', window.schedulePoLimitCheck);

        $('#poLimitWarningAckBtn').on('click', function () {
            poLimitAcknowledged = true;
            hidePoLimitWarningModal();
        });

        window.schedulePoLimitCheck();
    });

    window.validatePoSupplierLimitOnSubmit = function () {
        var d = $.Deferred();
        window.checkPoSupplierLimit(true).done(function (data) {
            if (!data || !data.configured || data.level === 'ok') {
                d.resolve(true);
                return;
            }
            if (data.level === 'blocked') {
                window.alert(data.message);
                d.resolve(false);
                return;
            }
            if (data.level === 'warning') {
                if (poLimitAcknowledged) {
                    d.resolve(true);
                    return;
                }
                showPoLimitWarningModal(data.message);
                var ackBtn = $('#poLimitWarningAckBtn');
                ackBtn.off('click.poLimitSubmit').on('click.poLimitSubmit', function () {
                    poLimitAcknowledged = true;
                    hidePoLimitWarningModal();
                    d.resolve(true);
                });
                $('#poLimitWarningModal').off('hidden.bs.modal.poLimitSubmit').on('hidden.bs.modal.poLimitSubmit', function () {
                    if (!poLimitAcknowledged) {
                        d.resolve(false);
                    }
                });
                return;
            }
            d.resolve(true);
        }).fail(function (xhr) {
            var msg = $('#poLimitStatusError').text()
                || '{{ __('Could not verify supplier PO limit. Please try again.') }}';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            window.alert(msg);
            d.resolve(false);
        });
        return d.promise();
    };
})();
</script>
