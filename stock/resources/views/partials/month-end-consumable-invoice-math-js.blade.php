<script>
    function monthEndConsumableLineTotals(quantity, pocQuantity, pocAmount, pocGstAmount) {
        const qty = parseFloat(quantity) || 0;
        const pocQty = parseFloat(pocQuantity) || 0;
        const pocAmt = parseFloat(pocAmount) || 0;
        const pocGst = parseFloat(pocGstAmount) || 0;

        if (qty <= 0 || pocQty <= 0) {
            return { amount: 0, gst: 0 };
        }

        if (Math.abs(qty - pocQty) < 0.0001) {
            return { amount: pocAmt, gst: pocGst };
        }

        const ratio = qty / pocQty;

        return {
            amount: Math.round(pocAmt * ratio * 100) / 100,
            gst: Math.round(pocGst * ratio * 100) / 100,
        };
    }

    function monthEndConsumableRowTotals($row, quantity) {
        const pocQty = parseFloat($row.attr('data-poc-quantity')) || 0;
        const pocAmt = parseFloat($row.attr('data-poc-amount')) || 0;
        const pocGst = parseFloat($row.attr('data-poc-gstamount')) || 0;

        return monthEndConsumableLineTotals(quantity, pocQty, pocAmt, pocGst);
    }

    function monthEndInvoiceHasRows() {
        return $('#productTable tr[data-poc-quantity]').length > 0;
    }

    function monthEndRoundOffAdd() {
        if (typeof MONTH_END_INVOICE !== 'undefined' && MONTH_END_INVOICE) {
            $('#roundoff').val(0);
            return 0;
        }
        if (monthEndInvoiceHasRows()) {
            $('#roundoff').val(0);
            return 0;
        }

        return parseFloat($('#roundoff').val()) || 0;
    }
</script>
