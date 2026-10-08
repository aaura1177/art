<?php 
use App\Helpers\Common;
?>
@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
        <h2>Container Allocation</h2>
        <div class="col">
            <a class="btn btn-secondary float-end" style="color: #fff;" href="{{ route('allocation-management.index')}}"> <i class="fa fa-arrow-left"></i> Go Back</a>
        </div>
            
    </div>
      
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Allocate Supplier</h3>
        </div>
        <div class="card-body">
            <ul>
                <li class="fw-bold"> Buyer Order Number : {{$record->tenant."#".$record->container_number }}</li>
                <li class="fw-bold"> Planned Date : {{ $record->planned_date }}</li>
                <li class="fw-bold"> Total Container Volume Consumed : {{ $record->containerAllocationItems->sum('drop_ship_volume') }}</li>
                <li class="fw-bold"> Total Physical Volume Consumed: {{ $record->containerAllocationItems->sum('physical_volume') }}</li>
            </ul>
            <form id="allocation-form" action="{{ route('allocation-management.update_allocation', $record->id) }}" method="POST">
                @csrf                
                @foreach($sku_records as $i => $sku_record)
                    @php 
                       $unassignedQty = $sku_record['product_qty'] - $sku_record['current_allocation']; 
                    @endphp 

                    <div class="sku-block mb-4">
                    <table class="table table-bordered">
                        <caption  style="caption-side:top" class="bg-warning-subtle fw-bold">
                            <h4 class="text-start ms-2">
                               <span class=""> {{ $sku_record['product_sku']." - ".$sku_record['product'] }}</span> 
                            </h2>
                            <span class="text-start ms-2 fw-bold">
                                Total Qty: {{ $sku_record['product_qty'] }} <br/>
                            </span>
                            <span class="text-start ms-2 fw-bold">
                                Unassigned Qty: <span class="{{ ($unassignedQty > 0) ? 'text-danger': '' }}">{{ $unassignedQty }} </span>
                            </span>
                            @if($unassignedQty < 0)
                                <p class='text-danger fw-bold ms-2 '> There is a quantity mismatch, please fix this issue </p>
                            @endif
                        </caption>
                        <thead class="table-dark">
                            <tr>
                                <th>Supplier Name</th>
                                <th>Supplier Price</th>
                                <th>Monthly Capacity</th>
                                <th>Invoice Generated Till Now</th>
                                <th>Select Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if($sku_record['product_qty'] == 0)
                            <input type="hidden"  class="block-total" value="0">
                            <tr class="table-danger">
                                <td colspan="6" class="text-center"> This Item Has Not Valid Quantity. </td>
                            </tr>
                        @else
                        
                            <!-- total for this block -->
                            <input type="hidden" name="total_qty[{{ $i }}]" class="block-total" value="{{ $sku_record['product_qty'] }}">
                            <input type="hidden" name="product_id[{{ $i }}]" value="{{ $sku_record['product_id'] }}">
                            <input type="hidden" name="product_sku[{{ $i }}]" value="{{ $sku_record['product_sku'] }}">
                            
                            @foreach(collect($sku_record['supplier'])->sortBy(fn($item)=>$item->supplierProduct[0]->rate ?? PHP_INT_MAX) as $item)
                                <?php 
                                    
                                    $commonClass = new Common();
                                    $query = $commonClass->getItemDetailForSupplier(@$item->supplierProduct[0]->supplier_id ?? 0,$sku_record['product_id'],$record->id);

                                    $record_exist = $query['record_exists'];
                                    $askedQty = $query['asked_quantity'];
                                    $row_id = $query['row_id'];
                                    $po_exists = $query['po_exists'];
                                    
                                    // check if po is accepted
                                    $po_accepted = 0;
                                    if($po_exists && $record_exist)
                                    {
                                        $po_accepted = $commonClass->checkPoAccepted(@$record_exist->purchase_order_id);
                                    }
                                    
                                    $users = collect($item->user)->pluck('supplier_id')->toArray();
                                     
                                    $invoiceGeneratedInMonth = $commonClass->getSupplierMonthlyInvoice($users[0],$record->planned_date);

                                ?>
                                <tr @if($invoiceGeneratedInMonth > $item->monthly_invoice_limit) class="table-danger" @endif>
                                    <td>
                                        <input type="hidden" name="supplier_id[{{ $i }}][]" value="{{ $item->supplierProduct[0]->supplier_id }}">
                                        <input type="text" class="form-control" readonly value="{{ $item->c_name }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" readonly value="{{ $item->supplierProduct[0]->rate }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" readonly value="{{ $item->monthly_invoice_limit }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" readonly value="{{ $invoiceGeneratedInMonth }}">
                                    </td>
                                    <td>
                                        <input type="number" name="asked_quantity[{{ $i }}][]" class="form-control select-qty"
                                            value="{{ $askedQty }}" min="0" oninput="allowDecimalOnly(this)" @if($invoiceGeneratedInMonth > $item->monthly_invoice_limit ||  $record_exist == true) readonly @endif onchange="checkTotal(`{{ $invoiceGeneratedInMonth}}`,`{{ $item->monthly_invoice_limit}}`,`{{ $item->supplierProduct[0]->rate}}`,this)">
                                       
                                    </td>
                                    <td>

                                        @if($record_exist == true)
                                            <!-- Allow Edit only if PO is not accepted by supplier -->
                                            @hasrole('admin')
                                                <button type="button" class="btn btn-success edit-btn" data-bs-toggle="modal" data-bs-target="#editQty" 
                                                data-product_id="{{ $sku_record['product_id'] }}" 
                                                data-product_name="{{ $sku_record['product_sku']." - ".$sku_record['product'] }}" 
                                                data-supplier_id="{{ $item->supplierProduct[0]->supplier_id }}" data-container_id="{{ $record->id }}" 
                                                data-id="{{ $row_id }}" 
                                                data-action="{{ route('allocation-management.update_allocation_detail') }}" 
                                                data-supplier_name="{{ $item->c_name }}"
                                                data-supplier_rate="{{ $item->supplierProduct[0]->rate }}"
                                                data-monthly_invoice="{{ $item->monthly_invoice_limit }}"
                                                data-asked_qty="{{ $askedQty }}"
                                                data-po_exist="{{ $po_exists }}"
                                                data-po_accepted="{{ $po_accepted }}"
                                                data-invoice_generated_in_month="{{ $invoiceGeneratedInMonth }}"
                                                data-total_qty="{{ $sku_record['product_qty'] }}"
                                                >
                                                    Edit Qty
                                                </button>
                                            @endhasrole
                                        @else
                                         <button class="btn btn-danger btn-sm" type="button" onclick="if(confirm('Are you sure?This will also delete PO if exists.')) deleteRow(this);"> <i class="fa fa-trash"></i> Delete </button>
                                        @endif
                                    </td>
                                </tr>
                                
                            @endforeach
                            <tr class="new-supplier-anchor" data-sku-index="{{ $i }}"></tr>
                            @php 
                               
                                $commonClass = new Common(); 
                                $query = $commonClass->getItemDetailForSupplier(0,$sku_record['product_id'],$record->id);

                                $record_exist = $query['record_exists'];
                                $askedQty = $query['asked_quantity'];
                                $row_id = $query['row_id'];

                                // check if po is generated or not
                                $po_exists = 0;
                                $po_accepted = 0;
                                
                            @endphp
                            <tr>
                                <td>
                                    <input type="text" class="form-control" readonly name="supplier_id[{{ $i }}][]" value="IN STOCK" placeholder="In Stock">
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="" readonly>
                                </td>
                                <td>
                                    <input type="number" name="asked_quantity[{{ $i }}][]" class="form-control select-qty"
                                        value="{{ $askedQty }}" min="0" @if($record_exist == true) readonly @endif oninput="allowDecimalOnly(this)">
                                </td>
                                <td>
                                    @if($record_exist == true)
                                        @if(auth()->user()->hasRole('admin') || auth()->user()->can('can-edit-allocation-quantity'))

                                            <button type="button" class="btn btn-success edit-btn" data-bs-toggle="modal" data-bs-target="#editQty" 
                                            data-product_id="{{ $sku_record['product_id'] }}" 
                                            data-product_name="{{ $sku_record['product_sku']." - ".$sku_record['product'] }}" 
                                            data-supplier_id="IN STOCK" 
                                            data-container_id="{{ $record->id }}" 
                                            data-id="{{ $row_id }}" 
                                            data-action="{{ route('allocation-management.update_allocation_detail') }}" 
                                            data-supplier_name="IN STOCK"
                                            data-supplier_rate="0"
                                            data-monthly_invoice="0"
                                            data-asked_qty="{{ $askedQty }}"
                                            data-po_exist="{{ $po_exists }}"
                                            data-po_accepted="{{ $po_accepted }}"
                                            data-invoice_generated_in_month="0"
                                            data-total_qty="{{ $sku_record['product_qty'] }}"
                                            >
                                                Edit Qty
                                            </button>
                                         @endhasrole
                                    @else
                                        <button class="btn btn-danger btn-sm" type="button" onclick="if(confirm('Are you sure?')) deleteRow(this);"> <i class="fa fa-trash"></i> Delete </button>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td colspan="6">
                                    <div class="d-flex align-items-end gap-2 flex-wrap">
                                        <input type="hidden" class="new-map-product-id" value="{{ $sku_record['product_id'] }}">
                                        <input type="hidden" class="new-map-sku-index" value="{{ $i }}">
                                        <div>
                                            <label class="form-label mb-1">Select Supplier</label>
                                            <select class="form-control new-map-supplier-id">
                                                <option value="">Select Supplier</option>
                                                @foreach($allSuppliers as $supplierOption)
                                                    <option value="{{ $supplierOption->id }}">{{ $supplierOption->c_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label mb-1">Supplier Price</label>
                                            <input type="number" step="0.01" min="0.01" class="form-control new-map-rate">
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-info btn-sm save-supplier-price-btn">Save Supplier Price</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                        @endif
                        
                        </tbody>
                    </table>
                    </div>
                @endforeach

                <button class="btn btn-success" type="submit">Allocate To Supplier</button>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editQty" tabindex="-1" aria-labelledby="editQtyLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editQtyLabel">Modal title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" id="updateForm" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="supplier_name" class="form-label">Supplier Name</label>
                            <input type="text" class="form-control" id="supplier_name" name="supplier_name" required readonly >
                            <label for="supplier_price" class="form-label">Supplier Price</label>
                            <input type="text" class="form-control" id="supplier_price" name="supplier_price" required readonly >
                            <label for="quantity" class="form-label">Asked Quantity</label>
                            <input type="number" class="form-control" id="asked_quantity" name="asked_quantity" min="0" required>
                           
                            <input type="hidden" class="form-control" id="allocation_container_id" name="allocation_container_id" required>
                            <input type="hidden" class="form-control" id="product_id" name="product_id" required>
                            <input type="hidden" class="form-control" id="row_id" name="row_id" required>
                            <input type="hidden" class="form-control" id="total_qty" required>
                            <input type="hidden" class="form-control" id="in_stock_class" required>
                            <input type="hidden" class="form-control" id="non_stock_class" required>
                        </div>
                        <button type="submit" class="btn btn-primary" id="update-btn">Update</button>
                    </form>
                    <div id="poexists">
                        
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
@section('footer')


<script>
    $(document).ready(function() {
        $('.edit-btn').on('click', function() {
            var itemId = $(this).data('product_id');
            var ProductName = $(this).data('product_name');
            var container_id = $(this).data('container_id');
            var action = $(this).data('action');
            var supplier_name = $(this).data('supplier_name');
            var supplier_id = $(this).data('supplier_id');
            var supplier_price = $(this).data('supplier_rate');
            var asked_quantity = $(this).data('asked_qty');
            var po_exist = $(this).data('po_exist');
            var po_accepted = $(this).data('po_accepted');
            var row_id = $(this).data('id');
            var total_qty = $(this).data('total_qty');
            var in_stock_class = $(this).data('in_stock_class');
            var non_stock_class = $(this).data('non_stock_class');

            $('#editQtyLabel').text('Edit Qty For ' + ProductName);
            $('#updateForm').attr('action', action);
            $('#supplier_name').val(supplier_name);
            $('#supplier_price').val(supplier_price);
            $('#asked_quantity').val(asked_quantity);
            $('#allocation_container_id').val(container_id);
            $('#product_id').val(itemId);
            $('#row_id').val(row_id);
            $('#total_qty').val(total_qty);
            $('#in_stock_class').val(in_stock_class);
            $('#non_stock_class').val(non_stock_class);

            $("#asked_quantity").attr('max',total_qty);

            console.log("po_exist - " +po_exist);
            if(po_exist == true )
            {
                $("#asked_quantity").attr("readonly","readonly");
                if(supplier_id !=0)
                {
                    $("#update-btn").attr("disabled", true);
                }

                var content =`<span class="text-danger">PO Already Exists. Please 
                        <button type="button" class="btn btn-danger btn-sm" onclick="DeleteOldEntry(`+supplier_id+`,`+itemId+`, `+container_id+`)"> Delete Old PO </button>
                        To create a new record </span>`;
                $("#poexists").html(content);   
            }
            else
            {
                $("#asked_quantity").removeAttr("readonly");
                if(supplier_id !=0)
                {
                    $("#update-btn").removeAttr("disabled");
                }
            }
        });
    });
</script>

<script>

function allowDecimalOnly(input) {
    input.value = input.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); // Only allow one decimal
}
function deleteRow(button) {
    button.closest('tr').remove();
}

function checkTotal(invoiceGeneratedInMonth, monthly_invoice_limit, product_rate, e) {

    qty = parseFloat($(e).val());
    product_rate = parseFloat(product_rate) || 0;
    invoiceGeneratedInMonth = parseFloat(invoiceGeneratedInMonth) || 0;
    monthly_invoice_limit = parseFloat(monthly_invoice_limit) || 0;

    var new_amount = product_rate * qty;
    var new_sum = invoiceGeneratedInMonth + new_amount;

    if (new_sum > monthly_invoice_limit) {
        alert('Supplier exceeds the monthly limit.');
        $(e).val(0);
    }
}

document.querySelectorAll('.save-supplier-price-btn').forEach((btn) => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        if (!row) return;

        const productId = row.querySelector('.new-map-product-id')?.value || '';
        const skuIndex = row.querySelector('.new-map-sku-index')?.value || '';
        const supplierId = row.querySelector('.new-map-supplier-id')?.value || '';
        const rate = row.querySelector('.new-map-rate')?.value || '';

        if (!supplierId) {
            alert('Please select a supplier.');
            return;
        }
        if (!rate || parseFloat(rate) <= 0) {
            alert('Please enter a valid supplier price.');
            return;
        }

        const existingSupplierInputs = document.querySelectorAll(`[name="supplier_id[${skuIndex}][]"]`);
        const duplicateSupplier = Array.from(existingSupplierInputs).some((el) => String(el.value) === String(supplierId));
        if (duplicateSupplier) {
            alert('This supplier is already available for this product.');
            return;
        }

        $.ajax({
            url: "{{ route('allocation-management.add_supplier_product', $record->id) }}",
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                product_id: productId,
                supplier_id: supplierId,
                rate: rate
            },
            success: function(response) {
                if (!response || !response.success || !response.data) {
                    alert('Unable to save supplier price.');
                    return;
                }

                const data = response.data;
                const newRow = document.createElement('tr');
                if (Number(data.invoice_generated_in_month) > Number(data.monthly_invoice_limit || 0)) {
                    newRow.classList.add('table-danger');
                }
                const onchangeJs = `checkTotal('${data.invoice_generated_in_month}','${data.monthly_invoice_limit}','${data.rate}',this)`;
                newRow.innerHTML = `
                    <td>
                        <input type="hidden" name="supplier_id[${skuIndex}][]" value="${data.supplier_id}">
                        <input type="text" class="form-control" readonly value="${data.supplier_name}">
                    </td>
                    <td><input type="text" class="form-control" readonly value="${data.rate}"></td>
                    <td><input type="text" class="form-control" readonly value="${data.monthly_invoice_limit}"></td>
                    <td><input type="text" class="form-control" readonly value="${data.invoice_generated_in_month}"></td>
                    <td>
                        <input type="number" name="asked_quantity[${skuIndex}][]" class="form-control select-qty"
                            value="0" min="0" oninput="allowDecimalOnly(this)" onchange="${onchangeJs}">
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm" type="button" onclick="if(confirm('Are you sure?This will also delete PO if exists.')) deleteRow(this);"> <i class="fa fa-trash"></i> Delete </button>
                    </td>
                `;

                const anchor = document.querySelector(`.new-supplier-anchor[data-sku-index="${skuIndex}"]`);
                if (anchor && anchor.parentNode) {
                    anchor.parentNode.insertBefore(newRow, anchor);
                } else {
                    row.parentNode.insertBefore(newRow, row);
                }

                row.querySelector('.new-map-supplier-id').value = '';
                row.querySelector('.new-map-rate').value = '';
                alert(response.message || 'Supplier saved successfully.');
            },
            error: function(xhr) {
                const msg = xhr?.responseJSON?.message || xhr?.responseJSON?.error || 'Failed to save supplier price.';
                alert(msg);
            }
        });
    });
});

document.getElementById('allocation-form').addEventListener('submit', function(e) {
    let hasError = false;
    let needsConfirmation = false;

    document.querySelectorAll('.sku-block').forEach((block, idx) => {
        const totalInput = block.querySelector('.block-total');
        const total = totalInput ? parseFloat(totalInput.value) || 0 : 0;

        const sum = Array.from(block.querySelectorAll('.select-qty'))
                         .reduce((acc, inp) => acc + (parseFloat(inp.value) || 0), 0);

        if (sum > total) {
            hasError = true;
            alert(
                `Product #${idx + 1}: sum of selected quantities (${sum}) ` +
                `must not exceed total quantity (${total}).`
            );
        } else if (sum < total) {
            needsConfirmation = true;
        }
    });

    if (hasError) {
        e.preventDefault(); // block form if there's any over-allocated product
        return;
    }

    if (needsConfirmation) {
        const confirmed = confirm('Some products have partial quantities. Are you sure you want to submit?');
        if (!confirmed) {
            e.preventDefault(); // block form if user cancels
        }
    }
});

function DeleteOldEntry(supplier_id, product_id, record_id) {
    if (confirm('Are you sure? This will delete the old PO and you need to reassign the PO.')) {

        $.ajax({
            url: "{{route('allocation-management.delete_old_entry')}}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                supplier_id: supplier_id,
                product_id: product_id,
                container_allocation_id: record_id
            },
            success: function(response) {
                alert('Old entry deleted successfully.');
                location.reload();  
            },
            error: function(xhr) {
                alert('Error deleting old entry: ' + xhr.responseText);
            }
        });
    }
}
</script>


@endsection
