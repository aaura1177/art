<script>
(function () {
    window.SupplierInvoiceTds = {
        apply: function (pbSubTotal) {
            var subTotal = pbSubTotal;
            if (subTotal === undefined || subTotal === null || subTotal === '') {
                var subEl = document.querySelector('input[name="pbSubTotal"]');
                subTotal = subEl ? subEl.value : 0;
            }
            subTotal = Number(subTotal) || 0;

            var tdsPercentEl = document.getElementById('tdsPercent')
                || document.querySelector('input[name="tdspercent"]');
            var tdsPercent = Number(tdsPercentEl ? tdsPercentEl.value : 0) || 0;

            var invoiceDateEl = document.getElementById('invoice_date')
                || document.querySelector('input[name="invoice_date"]')
                || document.querySelector('input[name="supp_inv_date"]');
            var tdsDateEl = document.getElementById('tdsDate');
            var invoiceDateVal = invoiceDateEl ? invoiceDateEl.value : '';
            var tdsDateVal = tdsDateEl ? tdsDateEl.value : '';

            var tdsAmount = 0;
            if (tdsPercent > 0 && subTotal > 0) {
                if (!tdsDateVal || !invoiceDateVal) {
                    tdsAmount = (subTotal * tdsPercent) / 100;
                } else {
                    var currentDate = new Date(invoiceDateVal);
                    var tdsDate = new Date(tdsDateVal);
                    if (!isNaN(currentDate.getTime()) && !isNaN(tdsDate.getTime()) && currentDate > tdsDate) {
                        tdsAmount = (subTotal * tdsPercent) / 100;
                    }
                }
            }

            var outEl = document.getElementById('tdsToal')
                || document.getElementById('tdsTotal')
                || document.querySelector('input[name="tdsTotal"]');
            if (outEl) {
                outEl.value = tdsAmount.toFixed(2);
            }

            return tdsAmount;
        },
        bindInvoiceDate: function () {
            var invoiceDateEl = document.getElementById('invoice_date')
                || document.querySelector('input[name="invoice_date"]')
                || document.querySelector('input[name="supp_inv_date"]');
            if (!invoiceDateEl) {
                return;
            }
            invoiceDateEl.addEventListener('change', function () {
                window.SupplierInvoiceTds.apply();
            });
        },
        initFromTotals: function () {
            if (typeof window.calculateTotalCarton === 'function') {
                window.calculateTotalCarton();
            } else if (typeof window.calculateTotal === 'function') {
                window.calculateTotal();
            } else {
                window.SupplierInvoiceTds.apply();
            }
        }
    };

    window.handleTds = function (pbSubTotal) {
        return window.SupplierInvoiceTds.apply(pbSubTotal);
    };
})();
</script>
