@extends('layouts.app')

@section('content')
    <style>
        .checkbox-custom {
            transform: scale(2);
            /* Adjust the scale factor as needed */
        }
    </style>
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Create Bulk Pricing</h2>
        </div>

        <div class="">
            <div class="form-group">
                <form method="POST" action="{{ url('/buyer/bulk-buyer-update') }}" id="bulkBuyer2Form">
                    @csrf
                    <div class="row mt-3">
                        <div class="col-8">
                            <h5>Updates fulfilment pricing for the selected destination buyer. Products must already have at least one pricing row. If a row already exists for that product and destination, it will be <strong>updated</strong>. Each product can have only <strong>one row per destination buyer</strong> (per product type).</h5>
                        </div>
                    </div>
                    <div class="row mt-2" id="bulk_duplicate_precheck_wrap" style="display:none;">
                        <div class="col-8">
                            <div class="alert alert-warning mb-0" id="bulk_duplicate_precheck_msg"></div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <select class="selectpicker" data-live-search="true" id="main_buyer_id" onchange="getProduct(this.value)" name="main_buyer_id">
                                <option value="" selected disabled>Select Buyer</option>
                                @if (isset($buyerData))
                                    @foreach ($buyerData as $key => $buyerDatas)
                                        <option value="{{ $buyerDatas->id }}" data-country="{{ $buyerDatas->country }}">
                                            {{ $buyerDatas->c_name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-6">
                            <select class="selectpicker" data-live-search="true" id="source_buyer_id" onchange="getProduct(this.value)">
                                <option value="" selected disabled>Select Buyer (Source)</option>
                                @if (isset($buyer2Data))
                                    @foreach ($buyer2Data as $key => $buyer2Datas)
                                        <option value="{{ $buyer2Datas->id }}" data-country="{{ $buyer2Datas->country }}">
                                            {{ $buyer2Datas->c_name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-6">
                            <select class="selectpicker" data-live-search="true" name="select_product[]" id="select_product"
                                required multiple>
                                <option value="" selected disabled>Select Products</option>
                                @if (isset($products))
                                    @foreach ($products as $key => $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->code }} - {{ $product->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-6">
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div class="checkbox">
                                        <label>
                                            <h5><input type="checkbox" class="checkbox-custom" id="select_all"
                                                    value="1" />&nbsp;
                                                Select All</h5>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <select class="selectpicker" data-live-search="true" name="buyer_id" id="buyer_id" required>
                                <option value="" selected disabled>Select Buyer 2 (Destination)</option>
                                @if (isset($tempBuyers))
                                    @foreach ($tempBuyers as $key => $tempBuyer)
                                        <option value="{{ $tempBuyer->id }}" data-country="{{ $tempBuyer->country }}">
                                            {{ $tempBuyer->c_name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="row col-4">
                        <button type="submit" class="btn btn-primary mt-3">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <script>
        var bulkDuplicatePrecheckXhr = null;
        var bulkSubmitConfirmed = false;

        function selectedBulkProductIds() {
            return ($('#select_product').val() || []).filter(function(id) {
                return id && String(id).length > 0;
            });
        }

        function formatBulkDuplicateLines(duplicates) {
            return duplicates.map(function(item) {
                return item.code + ' (pricing ids: ' + item.pricing_ids.join(', ') + '; will update id ' + item.canonical_id + ')';
            }).join('\n');
        }

        function refreshBulkDuplicatePrecheck() {
            var buyerId = $('#buyer_id').val();
            var productIds = selectedBulkProductIds();

            if (!buyerId || productIds.length === 0) {
                $('#bulk_duplicate_precheck_wrap').hide();
                $('#bulk_duplicate_precheck_msg').empty();
                return;
            }

            if (bulkDuplicatePrecheckXhr) {
                bulkDuplicatePrecheckXhr.abort();
            }

            bulkDuplicatePrecheckXhr = $.ajax({
                type: 'GET',
                url: '/buyer/check-bulk-destination-duplicates',
                data: {
                    buyer_id: buyerId,
                    'select_product': productIds
                },
                success: function(response) {
                    var duplicates = (response && response.duplicates) ? response.duplicates : [];
                    if (!duplicates.length) {
                        $('#bulk_duplicate_precheck_wrap').hide();
                        $('#bulk_duplicate_precheck_msg').empty();
                        return;
                    }

                    var html = '<strong>Duplicate destination rows in database</strong> (manual cleanup recommended before or after bulk run):<ul class="mb-0 mt-2">';
                    duplicates.forEach(function(item) {
                        html += '<li><strong>' + item.code + '</strong> — ' + item.row_count + ' rows (ids: '
                            + item.pricing_ids.join(', ') + '). Bulk will update id ' + item.canonical_id + '.</li>';
                    });
                    html += '</ul>';
                    $('#bulk_duplicate_precheck_msg').html(html);
                    $('#bulk_duplicate_precheck_wrap').show();
                }
            });
        }

        $(document).ready(function() {
            $('#select_all').change(function() {
                var isChecked = $(this).prop('checked');
                $('#select_product').selectpicker('val', isChecked ? $('#select_product option').map(
                    function() {
                        return $(this).val();
                    }).get() : []);
                refreshBulkDuplicatePrecheck();
            });

            $('#select_product, #buyer_id').on('changed.bs.select change', function() {
                refreshBulkDuplicatePrecheck();
            });

            $('#bulkBuyer2Form').on('submit', function(e) {
                if (bulkSubmitConfirmed) {
                    bulkSubmitConfirmed = false;
                    return true;
                }

                var buyerId = $('#buyer_id').val();
                var productIds = selectedBulkProductIds();
                if (!buyerId || !productIds.length) {
                    return true;
                }

                e.preventDefault();
                var form = this;

                $.ajax({
                    type: 'GET',
                    url: '/buyer/check-bulk-destination-duplicates',
                    data: {
                        buyer_id: buyerId,
                        'select_product': productIds
                    },
                    success: function(response) {
                        var duplicates = (response && response.duplicates) ? response.duplicates : [];
                        if (!duplicates.length) {
                            form.submit();
                            return;
                        }

                        var message = 'Some selected products already have more than one pricing row for this destination buyer:\n\n'
                            + formatBulkDuplicateLines(duplicates)
                            + '\n\nBulk will update the lowest pricing id per product. Extra rows will NOT be deleted automatically.\n\nContinue?';

                        if (window.confirm(message)) {
                            bulkSubmitConfirmed = true;
                            form.submit();
                        }
                    },
                    error: function() {
                        if (window.confirm('Could not verify duplicate destination rows. Continue with bulk update anyway?')) {
                            bulkSubmitConfirmed = true;
                            form.submit();
                        }
                    }
                });

                return false;
            });
        });

      
        function getProduct(sourceBuyerId) {
            var mainBuyerId = $('#main_buyer_id').val();
            $.ajax({
                type: "GET",
                url: '/buyer/get-product-by-buyer',
                data: {
                    mainBuyerId: mainBuyerId,
                    // sourceBuyerId: sourceBuyerId
                },
                success: function(response) {
                    var productsSelect = '';
                    if (response && response.length > 0) {
                        productsSelect += `<option value="" selected disabled>Select Products</option>`;
                        response.forEach(item => {
                            productsSelect +=
                                `<option value="${item.id}">${item.code} - ${item.name}</option>`;
                        });
                    } else {
                        productsSelect += `<option value="" selected disabled>No Products</option>`;
                    }
                    $('#select_product').html(productsSelect);
                    $('#select_product').selectpicker('refresh');
                    refreshBulkDuplicatePrecheck();
                }
            });
        }

        $(document).ready(function() {
            $('#main_buyer_id').change(function(){
               var mainBuyerId = $(this).val();
                $.ajax({
                    type: "GET",
                    url: '/buyer/get-source-buyer',
                    data: {
                        mainBuyerId: mainBuyerId
                    },
                    success: function(response) {
                        var sourceBuyerSelect = '';
                        if (response && response.length > 0) {
                            sourceBuyerSelect += `<option value="" selected disabled>Select Buyer (Source)</option>`;
                            response.forEach(item => {
                                sourceBuyerSelect +=
                                    `<option value="${item.id}">${item.c_name}</option>`;
                            });
                        } else {
                            sourceBuyerSelect += `<option value="" selected disabled>Select Buyer (Source)</option>`;
                        }
                        $('#source_buyer_id').html(sourceBuyerSelect);
                        $('#source_buyer_id').selectpicker('refresh');
                    }
                });
            })
        });

    </script>
@endsection

