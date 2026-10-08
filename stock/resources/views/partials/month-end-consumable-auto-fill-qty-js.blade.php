<script>
(function () {
    function monthEndInvoiceHeaderComplete() {
        const inv = $.trim($('#supinv').val() || '');
        const dt = ($('#invoice_date').val() || '').toString().trim();
        if (!inv || !dt) {
            return false;
        }
        const d = new Date(dt);
        return !isNaN(d.getTime());
    }

    function monthEndRemainingQtyFromRow($row, $input) {
        const maxVal = parseFloat($input.attr('max'));
        if (!isNaN(maxVal)) {
            return maxVal;
        }

        const lineKey = $input.attr('data-line-key');
        if (lineKey) {
            const cell = parseFloat($('#remqty' + lineKey).text());
            if (!isNaN(cell)) {
                return cell;
            }
        }

        const parentId = $input.parent().attr('id');
        if (parentId) {
            const $remCell = $('#remqty' + parentId);
            if ($remCell.length) {
                const t = parseFloat($remCell.text());
                if (!isNaN(t)) {
                    return t;
                }
            }
        }

        const $remInput = $row.find('input.remqty').first();
        if ($remInput.length) {
            const v = parseFloat($remInput.val());
            if (!isNaN(v)) {
                return v;
            }
        }

        return 0;
    }

    function isMonthEndInvoiceRow($row) {
        if (typeof MONTH_END_INVOICE !== 'undefined' && MONTH_END_INVOICE) {
            return true;
        }
        return $row.is('[data-poc-quantity]');
    }

    function setReceiveQtyValue($input, rem) {
        const step = ($input.attr('step') || '0.01').toString();
        if (step === '1') {
            $input.val(Math.floor(rem));
            return;
        }
        if (step === '0.01') {
            $input.val(Number(rem).toFixed(2));
            return;
        }
        $input.val(rem);
    }

    function refreshMonthEndInvoiceFooterTotals() {
        if (typeof invoiceType !== 'undefined' && parseInt(invoiceType, 10) === 3) {
            if (typeof calculateTotalCarton === 'function') {
                calculateTotalCarton();
                return;
            }
        }
        if (typeof calculateTotal === 'function') {
            calculateTotal();
        }
    }

    window.autoFillMonthEndReceiveQtyFromRemaining = function (scope) {
        if (!monthEndInvoiceHeaderComplete()) {
            return false;
        }

        const $scope = scope ? $(scope) : $('#productTable');
        let filled = false;

        window.__monthEndBatchRecalc = true;
        try {
            $scope.find('tr').each(function () {
                const $row = $(this);
                if (!isMonthEndInvoiceRow($row)) {
                    return;
                }

                const $input = $row.find('input.receiveqty').first();
                if (!$input.length) {
                    return;
                }

                const current = parseFloat($input.val()) || 0;
                if (Math.abs(current) > 0.0001) {
                    return;
                }

                const rem = monthEndRemainingQtyFromRow($row, $input);
                if (rem <= 0) {
                    return;
                }

                setReceiveQtyValue($input, rem);
                filled = true;
            });

            if (filled && typeof recalQuantity === 'function') {
                $scope.find('tr').each(function () {
                    const $row = $(this);
                    if (!isMonthEndInvoiceRow($row)) {
                        return;
                    }
                    const $input = $row.find('input.receiveqty').first();
                    if (!$input.length) {
                        return;
                    }
                    const qty = parseFloat($input.val()) || 0;
                    if (qty > 0) {
                        recalQuantity($input[0]);
                    }
                });
            }
        } finally {
            window.__monthEndBatchRecalc = false;
        }

        if (filled) {
            refreshMonthEndInvoiceFooterTotals();
            if (typeof handleTds === 'function') {
                handleTds($('#pbSubTotal').val() || 0);
            }
        }

        return filled;
    };

    window.bindMonthEndInvoiceHeaderAutoFill = function (opts) {
        opts = opts || {};
        if (opts.enabled === false) {
            return;
        }

        let monthEndAutoFillTimer = null;
        const handler = function () {
            clearTimeout(monthEndAutoFillTimer);
            monthEndAutoFillTimer = setTimeout(function () {
                autoFillMonthEndReceiveQtyFromRemaining();
            }, 350);
        };

        $('#supinv, #invoice_date')
            .off('input.monthEndAutoFill change.monthEndAutoFill')
            .on('change.monthEndAutoFill', handler)
            .on('input.monthEndAutoFill', handler);
    };
})();
</script>
