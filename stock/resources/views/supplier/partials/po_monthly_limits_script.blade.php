<script>
(function () {
    function supplierPoLimitsToggle() {
        var type = ($('#supplier_type').val() || '').trim();
        var isMerged = $('#is_merged').is(':checked');
        var noLimits = (type === 'Service' || type === 'Packaging');

        $('#po-limit-none-msg').toggle(noLimits);
        $('#supplier-is-merged-row').toggle(!noLimits);

        if (noLimits) {
            $('#po-limit-furniture, #po-limit-consumable, #po-limit-merged').hide();
            return;
        }

        if (isMerged) {
            $('#po-limit-merged').show();
            $('#po-limit-furniture, #po-limit-consumable').hide();
            return;
        }

        $('#po-limit-merged').hide();
        $('#po-limit-furniture').toggle(type === 'Furniture' || type === 'Both');
        $('#po-limit-consumable').toggle(type === 'Consumable' || type === 'Both');
    }

    $(document).ready(function () {
        $('#supplier_type, #is_merged').on('change', supplierPoLimitsToggle);
        $('#supplier_type').on('changed.bs.select', supplierPoLimitsToggle);
        supplierPoLimitsToggle();
    });
})();
</script>
