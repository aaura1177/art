@extends('layouts.app')

@section('content')
<style>
        .label-container label {
            margin-top: 10px;
        }
    </style>

    <div class="row mx-3 my-2">
        <h2>Products List</h2>

        @if (auth()->user()->role == 'Admin' || auth()->user()->role == 'Stockadmin' ||
            auth()->user()->role == 'compliance' || strtolower(auth()->user()->email ?? '') === 'compliance@artisanfurniture.net')
            <div class="col">

                <div class="btn-group float-end" role="group" style="color: #fff; margin-left: 5px;">
                    <button id="btnGroupDrop1" type="button" class="btn btn-secondary btn-success dropdown-toggle"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Downloads
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item " href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#modalDateFilter">Date wise stock sheet</a>
                        <a class="dropdown-item " href="{{ url('/product/export_erp') }}">Download CSV</a>
                        <a class="dropdown-item " href="{{ url('/product/erp_diff_manager_export') }}">Download
                            Discrepancies Sheet</a> <a class="dropdown-item "
                            href="{{ url('/export/ErpOverallStock') }}">Download Overall Stock</a>
                    </div>
                </div>
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="javascript:void(0);"
                    data-bs-toggle="modal" data-bs-target="#modalReasonDateFilter">Reason Filter</a>
                {{-- <div class="btn-group float-end" role="group" style="color: #fff; margin-left: 5px;">
            <button id="btnGroupDrop1" type="button" class="btn btn-secondary btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Reason Filter
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item " href="{{ url('/product/erp_reason/returns')}}">Returns</a>
                <a class="dropdown-item "  href="{{ url('/product/erp_reason/cancellation')}}">Cancellation</a>
                <a class="dropdown-item "  href="{{ url('/product/erp_reason/mis-shipment')}}">Mis-shipment</a>
                <a class="dropdown-item "  href="{{ url('/product/erp_reason/stock-correction')}}">Stock Correction</a>
            </div>
          </div> --}}
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalAddImportExcel">Inbound excel sheet</a>
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalLessImportExcel">Outbound excel sheet</a>


                <a class="btn btn-danger float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/product/erp_negetive') }}">Errors</a>
                <a class="btn btn-danger float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/product/erp_diff/all') }}">Discrepancies</a>
                <a class="btn btn-danger float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/product/erp_diff/automation') }}">Automation Discrepancies</a>    
                <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/product/erp') }}">View all</a>
                <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/product/erp_sheets') }}">Sheets</a>
            </div>

        @endhasrole
        @hasrole('office')
            <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                data-bs-target="#modalDateFilter">Date wise stock sheet</a>
        @endhasrole

        @hasrole('factory')
            <div class="col">
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/product/families') }}">Families</a>
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalPrint">Print</a>
            </div>
        @endhasrole
</div>
@if (isset($erp_diff))
    @if( !isset($type) )
    <div class="row">
        <a class="btn btn-danger" style="color: #fff; margin-left: 15px;"
            href="{{ url('/product/erp_diff/red') }}">Red</a>
        <a class="btn btn-success" style="color: #fff; margin-left: 5px;"
            href="{{ url('/product/erp_diff/green') }}">Green</a>
    </div>
    @endif
@endif
<div class="card mb-3">
<?php
$country = \Request::session()->get('country');

$names = [
    'us' => 'Fairview',
    'eu' => 'Magdeburg',
    'canada' => 'Canada',
    'california' => 'California'
];

$name = $names[$country] ?? 'IP-03'; // Default value if country not found
?>
    @if( !isset($type) )
    <div class="card-body">
        Last Sheet ({{$name}}) uploaded is - <b>{{ $last_sheet_ip->name ?? '' }}</b><br>
        Last Sheet (Wayfair) uploaded is - <b>{{ $last_sheet_w->name ?? '' }}</b><br>
        Last Container No. - <b>{{ $last_container }}</b><br>
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Sku</th>
                        <th>India Quantity(as per sheets)</th>
                        <th>Fulfillment Quantity</th>
                        <th>Warehouse Quantity</th>
                        <th>Discrepancy</th>
                        @hasrole('admin')
                            <th>Options</th>
                        @endhasrole
                    </tr>
                </thead>
                <tbody>
                    @if (isset($products))
                        @foreach ($products as $key => $product)
                            <?php
                            $bc = '#fff';
                            $bcf = '#fff';
                            if ($product->quantity > $product->warehouse_quantity) {
                                $bc = '#ED99A1';
                            }
                            if ($product->quantity < $product->warehouse_quantity) {
                                $bc = '#B7E1CD';
                            }
                            //}
                            if ($product->quantity < 0) {
                                $bcf = '#ED9951';
                            }
                            ?>
                            <tr>
                                <td>{{ $product->sku }}</td>
                                <td style="background-color:{{ $bcf }}" id="quant_{{ $product->id }}">
                                    {{ $product->quantity }}</td>
                                {{-- NEW LINE ADD HERE --}}
                                <td style="background-color:{{ $bc }}" id="diff_{{ $product->id }}">
                                    {{-- <a href="{{ url('/fulfillment/logs/' . $product->sku) }}"> --}}
                                    <a href="{{ url('fulfillment/list?sku=' . $product->sku) }}">
                                        {{ $product->fullfillment_qty }}</a>
                                </td>
                                {{-- NEW LINE END HERE --}}

                                <td style="background-color:{{ $bc }}" id="ukquant_{{ $product->id }}">
                                    {{ $product->warehouse_quantity ?? 0 }}</td>

                                <td style="background-color:{{ $bc }}" id="diff_{{ $product->id }}">
                                    {{ $product->warehouse_quantity - ($product->quantity + $product->fullfillment_qty) }}
                                </td>

                                @hasrole('admin')
                                    <td>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                            <button class="btn btn-info"
                                                onclick="updateProduct('{{ $product->id }}','{{ $product->sku }}','{{ $product->quantity }}')">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        </span>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Fulfillment Add">
                                            <button class="btn btn-info"
                                                onclick="fulFillmentDataAdd('{{ $product->sku }}')">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </span>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Warehouse History">
                                            <a class="btn btn-info"
                                                href="{{ url('/view-erp-history-manager/' . $product->id) }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </span>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="India History">
                                            <a class="btn btn-warning"
                                                href="{{ url('/view-erp-history/' . $product->id) }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </span>

                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Fulfillment Logs">
                                            <a class="btn btn-primary"
                                                href="{{ url('fulfillment/logs?sku=' . $product->sku) }}">
                                                <i class="fa fa-history"></i>
                                            </a>
                                        </span>
                                    </td>
                                @endhasrole
                                @hasrole('stockadmin')
                                    <td>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Edit">
                                            <button class="btn btn-info"
                                                onclick="updateProduct('{{ $product->id }}','{{ $product->sku }}','{{ $product->quantity }}')">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        </span>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Warehouse History">
                                            <a class="btn btn-info"
                                                href="{{ url('/view-erp-history-manager/' . $product->id) }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </span>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="India History">
                                            <a class="btn btn-warning"
                                                href="{{ url('/view-erp-history/' . $product->id) }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </span>

                                    </td>
                                @endhasrole
                                @hasrole('office')
                                    <td>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Edit">

                                            <a class="btn btn-info"
                                                href="{{ url('/view-erp-history/' . $product->id) }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </span>

                                    </td>
                                @endhasrole
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if( isset($type) )
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>History Stock</th>
                    <th>Product Qty</th>
                    <th>Difference</th>
                    <th>History ID</th>
                    <th>Product ID</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product->sku }}</td>
                        <td>{{ $product->history_stock }}</td>
                        <td>{{ $product->product_quantity }}</td>
                        <td>{{ $product->difference }}</td>
                        <td>{{ $product->history_id }}</td>
                        <td>{{ $product->product_id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>

<!-- MODAL FOR DATE -->
<div class="modal fade" id="modalDateFilter" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Enter Date</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ url('/product/datewiseerp') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6">
                            <input class="form-control" type="date" name="fsd" id="fsd" required
                                value="" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- MODAL FOR DATE -->
<div class="modal fade" id="modalReasonDateFilter" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Enter Date</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ url('/product/reason-filter') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6" style="margin-bottom:20px;">
                            <select name="reason" class="form-control" id="reason_modal">
                                <option value="">Select Reason</option>
                                <option value="Returns">Returns</option>
                                <option value="Cancellation">Cancellation</option>
                                <option value="Mis-shipment">Mis-shipment</option>
                                <option value="Stock Correction">Stock Correction</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <input class="form-control" type="date" name="fsd" id="fsd" required
                                value="" />
                        </div>
                        <div class="col-6">
                            <input class="form-control" type="date" name="fed" id="fed" required
                                value="" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" onclick="return validate()" class="btn btn-success">View</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- MODAL FOR IMPORT EXCEL -->
<div class="modal fade" id="modalAddImportExcel" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"> Upload Excel File</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importAddErpCSV') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body pb-0">
                    <input type="file" name="importAddCSV" id="importAddCSV" />
                    <p class="mt-3">Make sure excel has sku and quantity fields</p>
                    <p class="mt-4 mb-0">Are You sure you want to Upload this?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                    <button type="submit" id="modalImportExcelForm" onclick="return validate()"
                        class="btn btn-success">Yes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL FOR IMPORT EXCEL -->

<div class="modal fade" id="modalLessImportExcel" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Upload Excel File (Less)</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importLessErpCSV') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body pb-0">
        <div id="fileInputContainer">
            <div class="file-input-group">
                <!-- Allow selecting multiple files -->
                <input type="file" name="importLessCSV[]" id="importLessCSV" class="form-control mb-2" required multiple/>
            </div>

            <div class="file-input-group">
                <!-- Single select for all files -->
                <select name="erp_sheet_type" class="form-control mt-2" required>
                    <option value="" disabled selected>Select Sheet</option>
                    <option value="0">Artisan</option>
                    <option value="1">Wayfair</option>
                </select>
                           
                        </div>
                    </div>

                    
                    <p class="mt-3">Make sure the excel has SKU and Quantity fields</p>
                    <p class="mt-4 mb-0">Are you sure you want to upload this?</p>
                </div>
                <div class="modal-footer">
                   
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                    <button type="submit" id="modalImportExcelFormSubmit" onclick="return validate()" class="btn btn-success">Yes</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- MODAL FOR IMPORT EXCEL -->
<div class="modal fade" id="updateModal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Update <span id="skushow"></span></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="updateSkuForm" action="javascript:void(0);" enctype="multipart/form-data">
                @csrf
                <div class="modal-body pb-0">
                    <input class="form-control" type="hidden" name="product_id" id="product_id" />
                    <label>Quantity</label>
                    <input class="form-control" type="number" name="sku_quant" id="sku_quant" />
                    <label>Reason</label>
                    <select name="reason" class="form-control" id="reason_modal">
                        <option value="">Select Reason</option>
                        <option value="Returns">Returns</option>
                        <option value="Cancellation">Cancellation</option>
                        <option value="Mis-shipment">Mis-shipment</option>
                        <option value="Stock Correction">Stock Correction</option>
                    </select>
                    <label>Remarks</label>
                    <textarea class="form-control" name="remarks" id="remarks_modal"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="modalImportExcelForm" class="btn btn-success">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL FOR Print -->
<div class="modal fade" id="modalPrint" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"> Print</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="modalPrintForm" action="{{ url('/product/printList') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body pb-0">
                    <label>Select Code type</label>
                    <select class="selectpicker" name="code" required>
                        <option value="">Select Code type</option>
                        <option value="All">All</option>
                        <option value="IN">IN</option>
                        <option value="BO">BO</option>
                        <option value="AH">AH</option>
                        <option value="ASB">ASB</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="modalImportExcelForm" onclick="return printpdf()"
                        class="btn btn-success">Print</button>
                    <button type="submit" id="DownloadCSV" onclick="return downloadcsv()"
                        class="btn btn-success">Download</button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- MODAL FOR Image View -->
<div class="modal fade" id="viewImage" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-0">
                <img style="max-width:100%" id="bigProImage" alt="No Image" src="" />
            </div>
        </div>
    </div>
</div>

{{-- FULFILLMENT POPUP --}}
<div class="modal fade" id="fulFillmentModal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Fulfillment Add (<span id="skushowfulFillment"></span>)</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="" action="{{ url('/fulfillment/fulfillment-order-manually') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body pb-0 label-container">
                    <label>Action <span style="color:red;">*</span></label>
                    <select name="action" class="form-control mandatory_field">
                        <option value="">Select Action</option>
                        <option value="add-to-bucket">Fulfillment Order</option>
                        <option value="less-from-bucket">Bucket Order</option>
                        <option value="return-into-bucket">Return Bucket Order</option>
                        <option value="refunded-add-to-india-qty">Refunded and Add to India Quantity</option>
                    </select>
                    <label>Order Id <span style="color:red;">*</span></label>
                    <input class="form-control mandatory_field" type="text" name="order_id" id="order_id" />

                    <label>Quantity <span style="color:red;">*</span></label>
                    <input class="form-control mandatory_field numeric-input" type="text" name="quantity"
                        id="quantity" />

                    <label>Customer Name</label>
                    <input class="form-control" type="text" name="customer_name" id="customer_name" />

                    <label>Customer Email <span style="color:red;">*</span></label>
                    <input class="form-control mandatory_field" type="email" name="customer_email"
                        id="customer_email" />
                    <span id="emailIDValidation" style="color: red"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="modalImportExcelForm" class="btn btn-success submit_button"
                        style="display: none">Submit</button>
                    <button type="button" id="modalImportExcelForm" class="btn btn-success"
                        onclick="submitForm()">Submit</button>
                </div>
                <input type="hidden" id="sku" name="sku">
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer')
<script type="text/javascript">
    $(function() {
        $('[data-bs-toggle="tooltip"]').tooltip()

        $('#updateSkuForm').submit(function() {
            $.ajax({
                'url': "{{ url('/update-erp-sku') }}",
                'method': 'POST',
                'data': $('#updateSkuForm').serialize()

            }).done(function(data) {
                $('#quant_' + data.id).html(data.quantity);
                $('#diff_' + data.id).html(data.diff_quant);
                $('#updateModal').modal('hide');
            });
            return false;
        });
    });

    function deleteModal(id) {
        $('#deleteProduct').modal('show');
        $('#deleteProductForm').attr('action', "{{ url('/product/delete') }}" + '/' + id);
    }

    function updateProduct(id, sku, quantity) {
        $('#updateModal').modal('show');
        $('#skushow').html(sku);
        $('#sku_quant').val(quantity);
        $('#product_id').val(id);
        $('#remark_modal').val('');
        $('#reason_modal').prop('selectedIndex', 0);
    }

    function downloadcsv() {
        $("#modalPrintForm").attr("action", "{{ url('/product/exportCodeWiseExcel') }}");
        return true;
    }

    function printpdf() {
        $("#modalPrintForm").attr("action", "{{ url('/product/printList') }}");
        return true;
    }

    function fetchImage(obj, product) {
        if ($(obj).html() == "") {
            var img = "{{ asset('uploads/allproducts/') }}/" + product + "/" + product + "-1.jpg";
            $(obj).html(
                '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' +
                product + '\')"><img class="img-thumbnail img-fluid product-img-100" src="' + img +
                '" alt="No Image" /></a>');
        }
    }

    function updateSrc(product) {
        var img = "{{ asset('uploads/allproducts/') }}/" + product + "/" + product + "-1.jpg";
        $("#bigProImage").attr('src', img);
        $("#viewImage").find("h4").html(product);
    }

    /////////////FULFILLMENTPOPUP/////////////
    function fulFillmentDataAdd(sku) {
        $('#fulFillmentModal').modal('show');
        $('#skushowfulFillment').html(sku);
        $('#sku').val(sku);

    }

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function submitForm() {
        var emailID = $("#customer_email").val();
        var isValid = true;
        var isValidEmail = true;
        $("#emailIDValidation").html('');
        $(".mandatory_field").each(function() {
            if ($.trim($(this).val()) == "") {
                isValid = false;
                $(this).css({
                    border: "1px solid red",
                    width: "50px !important",
                });
            } else {
                $(this).css({
                    border: "",
                    background: "",
                });
            }
        });
        if (!validateEmail(emailID)) {
            isValidEmail = false;
            $("#emailIDValidation").html("EmailID is not Valid");
        }
        if (isValid && validateEmail(emailID)) {
            $(".submit_button").click();
        }
    }
</script>
{{-- ONLY NUMBER TYPE --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var numericInputs = document.querySelectorAll('.numeric-input');
        numericInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    });
</script>
@endsection
