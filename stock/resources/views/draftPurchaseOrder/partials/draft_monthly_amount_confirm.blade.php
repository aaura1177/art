{{-- Draft PO monthly amount confirm (furniture + consumable create/update). Static until Save or Delete. --}}
@php
    $draftPoKind = $draftPoKind ?? 'furniture';
    $excludeFurnitureDraftId = $excludeFurnitureDraftId ?? null;
    $excludeConsumableDraftId = $excludeConsumableDraftId ?? null;
    $draftFormSelector = $draftFormSelector ?? '#draftPoForm';
@endphp

<div class="modal fade" id="draftMonthlyAmountModal" tabindex="-1" role="dialog" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Draft PO monthly amounts') }}</h5>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    <strong id="draftMonthlySupplierName"></strong>
                    <span class="text-muted" id="draftMonthlyMonthLabel"></span>
                </p>
                <p class="small text-muted mb-3" id="draftMonthlyWindow"></p>
                <table class="table table-sm mb-3">
                    <tbody>
                        <tr id="draftMonthlyFurnitureRow" style="display:none;">
                            <td>{{ __('Furniture draft amount') }}</td>
                            <td class="text-end" id="draftMonthlyFurnitureAmount">0.00</td>
                        </tr>
                        <tr id="draftMonthlyConsumableRow" style="display:none;">
                            <td>{{ __('Consumable draft amount') }}</td>
                            <td class="text-end" id="draftMonthlyConsumableAmount">0.00</td>
                        </tr>
                        <tr id="draftMonthlyTotalRow" style="display:none;">
                            <th>{{ __('Total') }}</th>
                            <th class="text-end" id="draftMonthlyTotalAmount">0.00</th>
                        </tr>
                        <tr id="draftMonthlyThisPoRow">
                            <td>{{ __('This draft PO') }}</td>
                            <td class="text-end" id="draftMonthlyThisPoAmount">0.00</td>
                        </tr>
                    </tbody>
                </table>
                <p class="mb-0">{{ __('Save this draft PO, or delete (discard) this submit and stay on the form.') }}</p>
                <div id="draftMonthlyAmountError" class="alert alert-danger mt-2 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="draftMonthlyDeleteBtn">{{ __('Delete') }}</button>
                <button type="button" class="btn btn-primary" id="draftMonthlySaveBtn">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var DRAFT_PO_KIND = @json($draftPoKind);
    var EXCLUDE_FURNITURE_DRAFT_ID = @json($excludeFurnitureDraftId);
    var EXCLUDE_CONSUMABLE_DRAFT_ID = @json($excludeConsumableDraftId);
    var DRAFT_FORM_SELECTOR = @json($draftFormSelector);
    var AMOUNTS_URL = @json(url('/draftPurchaseOrder/supplierMonthlyDraftAmounts'));
    var draftAmountConfirmed = false;
    var draftAmountPendingSubmit = false;

    function formatMoney(n) {
        var v = parseFloat(n);
        if (isNaN(v)) v = 0;
        return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function draftSupplierId() {
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

    function getDraftMonthlyModal() {
        var el = document.getElementById('draftMonthlyAmountModal');
        if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }
        return bootstrap.Modal.getOrCreateInstance(el, {
            backdrop: 'static',
            keyboard: false
        });
    }

    function showDraftMonthlyModal() {
        var instance = getDraftMonthlyModal();
        if (instance) {
            instance.show();
        }
    }

    function hideDraftMonthlyModal() {
        var instance = getDraftMonthlyModal();
        if (instance) {
            instance.hide();
        }
    }

    function fillDraftMonthlyModal(data) {
        $('#draftMonthlyAmountError').hide().text('');
        $('#draftMonthlySupplierName').text(data.supplier_name || '');
        $('#draftMonthlyMonthLabel').text(data.month_label ? ' — ' + data.month_label : '');
        $('#draftMonthlyWindow').text(
            (data.date_from && data.date_to)
                ? ('{{ __("Window") }}: ' + data.date_from + ' {{ __("to") }} ' + data.date_to)
                : ''
        );
        $('#draftMonthlyFurnitureAmount').text(formatMoney(data.furniture_amount));
        $('#draftMonthlyConsumableAmount').text(formatMoney(data.consumable_amount));
        $('#draftMonthlyTotalAmount').text(formatMoney(data.total_amount));
        $('#draftMonthlyThisPoAmount').text(formatMoney(data.this_po));

        if (data.show_both) {
            $('#draftMonthlyFurnitureRow, #draftMonthlyConsumableRow, #draftMonthlyTotalRow').show();
        } else if (data.show_furniture) {
            $('#draftMonthlyFurnitureRow').show();
            $('#draftMonthlyConsumableRow, #draftMonthlyTotalRow').hide();
        } else {
            $('#draftMonthlyConsumableRow').show();
            $('#draftMonthlyFurnitureRow, #draftMonthlyTotalRow').hide();
        }
    }

    window.openDraftMonthlyAmountConfirm = function () {
        var supplierId = draftSupplierId();
        var podate = $('input[name="podate"]').val();
        var amount = parseFloat($('#subtotalamount').val() || '0') || 0;

        if (!supplierId || !podate) {
            alert('{{ __("Select supplier and PO date first.") }}');
            return $.Deferred().reject().promise();
        }

        draftAmountPendingSubmit = true;
        $('#draftMonthlySaveBtn, #draftMonthlyDeleteBtn').prop('disabled', true);
        $('#draftMonthlyAmountError').hide().text('');
        showDraftMonthlyModal();
        $('#draftMonthlySupplierName').text('{{ __("Loading...") }}');
        $('#draftMonthlyMonthLabel').text('');
        $('#draftMonthlyWindow').text('');

        return $.getJSON(AMOUNTS_URL, {
            supplier_id: supplierId,
            podate: podate,
            amount: amount,
            po_kind: DRAFT_PO_KIND,
            exclude_furniture_draft_id: EXCLUDE_FURNITURE_DRAFT_ID,
            exclude_consumable_draft_id: EXCLUDE_CONSUMABLE_DRAFT_ID
        }).done(function (data) {
            fillDraftMonthlyModal(data);
            $('#draftMonthlySaveBtn, #draftMonthlyDeleteBtn').prop('disabled', false);
        }).fail(function (xhr) {
            var msg = '{{ __("Could not load draft monthly amounts.") }}';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            $('#draftMonthlyAmountError').text(msg).show();
            $('#draftMonthlyDeleteBtn').prop('disabled', false);
            $('#draftMonthlySaveBtn').prop('disabled', true);
        });
    };

    window.isDraftAmountConfirmed = function () {
        return draftAmountConfirmed === true;
    };

    $(document).ready(function () {
        $('#draftMonthlySaveBtn').on('click', function () {
            if (!draftAmountPendingSubmit) {
                return;
            }
            draftAmountConfirmed = true;
            draftAmountPendingSubmit = false;
            hideDraftMonthlyModal();
            $('#submitBtn').prop('disabled', true);
            var form = document.querySelector(DRAFT_FORM_SELECTOR);
            if (form) {
                // Native submit bypasses jQuery handlers (already validated + confirmed).
                HTMLFormElement.prototype.submit.call(form);
            }
        });

        $('#draftMonthlyDeleteBtn').on('click', function () {
            draftAmountConfirmed = false;
            draftAmountPendingSubmit = false;
            hideDraftMonthlyModal();
            $('#submitBtn').prop('disabled', false);
        });
    });
})();
</script>
