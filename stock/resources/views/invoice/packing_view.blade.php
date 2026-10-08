<?php
use App\Helpers\Common;
?>
@extends('layouts.app')

@section('content')
  {{-- Do not load Bootstrap 5 CSS here: layout uses Bootstrap 4 + bootstrap-select; BS5 breaks batch dropdowns. --}}
  <style>
.error-alert {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}
</style>

    <style>
        /* Fix dropdown menu height and scrollbar */
        .bootstrap-select .dropdown-menu.inner {
            max-height: 200px !important;
            overflow-y: auto !important;
        }

        /* Clean select box appearance */
        .bootstrap-select .btn {
            background-color: #fff !important;
            border: 1px solid #ced4da !important;
            color: #495057 !important;
            font-size: 14px;
        }

        /* Hover effect */
        .bootstrap-select .dropdown-item:hover {
            background-color: #f1f1f1 !important;
            color: #000 !important;
        }

        .bootstrap-select .dropdown-menu {
    max-height: 200px !important;
    overflow-y: auto !important;
    z-index: 9999 !important; /* keep dropdown above overlapping UI */
}

.table-responsive {
    overflow: visible !important; /* prevent cutting inside table */
}

.custom-alert {
    position: relative;
    background-color: #ffe6e6; /* soft red */
    color: #a80000;
    border: 1px solid #ff0000;
    border-radius: 10px;
    padding: 20px;
    font-size: 14px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.custom-alert h5 {
    margin-bottom: 15px;
    font-size: 16px;
}

.custom-alert .btn-close {
    position: absolute;
    top: 15px;
    right: 15px;
}

#supplierInvoiceModal table.ref-qty-modal-table { table-layout: fixed; }
#supplierInvoiceModal .ref-qty-modal-table col.col-ref-qty-check { width: 3rem; }
#supplierInvoiceModal .ref-qty-modal-table th.ref-qty-check-cell,
#supplierInvoiceModal .ref-qty-modal-table td.ref-qty-check-cell {
  width: 3rem; min-width: 3rem; max-width: 3rem; box-sizing: border-box;
  vertical-align: middle !important; text-align: center;
  padding: 0.4rem 0.35rem !important; overflow: hidden;
}
#supplierInvoiceModal .ref-qty-modal-table .ref-qty-checkbox {
  float: none !important; position: static !important; margin: 0 !important;
  display: inline-block !important; vertical-align: middle !important;
  width: 1.125rem; height: 1.125rem; cursor: pointer; flex-shrink: 0;
}
#supplierInvoiceModal .ref-qty-modal-table .ref-qty-check-wrap {
  display: flex; align-items: center; justify-content: center; width: 100%; min-height: 1.25rem;
}

/* jQuery UI autocomplete above table / bootstrap-select */
.ui-autocomplete {
  z-index: 10050 !important;
  max-height: 260px;
  overflow-y: auto;
}

    </style>
  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Packing</h2>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert" id="customErrorBox">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" id="myForm" action="{{ url('/invoice/packing_view/'.$packing->id)}}">
      @csrf

      <div class="form-group">

          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('BuyerOrder No.') }}</label>
              <input type="text" class="form-control toUpperCase" name="buyer_order_no" value="{{$packing->buyer_order_no}}" required="required" />
            </div>
          </div>

          <div class="row mt-5 my-3">
              <div class="col-6">
                <h5>Products Details</h5>
              </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                  <tr id="mytable">
                    <th scope="col" style="min-width: 300px;">Product <a href="{{ url('/product/create')}}" target="_blank"> (+New)</a></th>
                    <th scope="col" style="min-width: 100px;">QTY</th>
                    <th scope="col" style="min-width: 120px;">Net Wt (Kg)</th>
                    <th scope="col" style="min-width: 150px;">Sub Total Net Wt (Kg)</th>
                    <th scope="col" style="min-width: 120px;">Gross Wt (Kg)</th>
                    <th scope="col" style="min-width: 150px;">Sub Total Gross Wt (Kg)</th>
                    <th scope="col" style="min-width: 100px;">Start Box</th>
                    <th scope="col" style="min-width: 100px;">End Box</th>
                    <th scope="col" style="min-width: 100px;">Sub Total Box</th>
                    <th scope="col" style="min-width: 100px;">PC/Box</th>
                    <th scope="col" style="min-width: 100px;">Batch No.</th>
                  </tr>
                </thead>
                <tbody id="productInvoice">
                  @if(isset($packingListProducts))
                    @foreach($packingListProducts as $key => $packingListProduct)
                      @php
                        $assigned = is_string($packingListProduct->batch_no)
                            ? json_decode($packingListProduct->batch_no, true)
                            : ($packingListProduct->batch_no ?? []);
                        if (! is_array($assigned)) {
                            $assigned = [];
                        }
                        $available = \App\BatchProduct::where('product_id', $packingListProduct->product_id)
                            ->join('batch', 'batch_product.batch_id', '=', 'batch.id')
                            ->select('batch.batch_no', 'batch_product.quantity as batch_balance')
                            ->get();
                        $initialBatchOrder = [];
                        if (! empty($refSelectionsByLine[$key]) && is_array($refSelectionsByLine[$key])) {
                            foreach ($refSelectionsByLine[$key] as $r) {
                                $b = trim((string) ($r['batch_no'] ?? ''));
                                if ($b !== '' && ! in_array($b, $initialBatchOrder, true)) {
                                    $initialBatchOrder[] = $b;
                                }
                            }
                        }
                        foreach ($assigned as $a) {
                            $a = trim((string) $a);
                            if ($a !== '' && ! in_array($a, $initialBatchOrder, true)) {
                                $initialBatchOrder[] = $a;
                            }
                        }
                      @endphp
                      <tr>
                        <td id="pr{{$key+1}}">
                          <input type="hidden" name="inv[{{ $key+1 }}][product_id]" value="{{$packingListProduct->product->id}}" class="product_id_hidden" id="product_id{{ $key+1 }}">
                          <input type="hidden" name="inv[{{ $key+1 }}][ref_selection_json]" id="ref_selection_json{{ $key+1 }}" class="ref_selection_json" value="{{ isset($refSelectionsByLine[$key]) ? e(json_encode($refSelectionsByLine[$key])) : '[]' }}">
                          <input type="hidden" class="batch_selection_order_json" name="inv[{{ $key+1 }}][batch_selection_order_json]" id="batch_selection_order_json{{ $key+1 }}" value="{{ e(json_encode(array_values($initialBatchOrder))) }}">
                          <input type="text" class="form-control product_name" id="product_name{{ $key+1 }}"
                              data-len="{{$key+1}}" data-row="{{$key+1}}" data-pro-id="{{$packingListProduct->product->id}}" value="{{$packingListProduct->product->code}} - {{$packingListProduct->product->name}}" required="required">
                        </td>
                        <td id="quan{{$key+1}}">
                          <input type="number" min="1" style="width:60px;" class="form-control quantity" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][quantity]" value="{{$packingListProduct->quantity}}" required />
                        </td>
                        <td id="wt{{$key+1}}">
                          <input type="number" min="0" step="any" class="form-control weight" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][weight]" value="{{$packingListProduct->weight}}" />
                        </td>
                        <td id="subtotalnetwt{{$key+1}}">
                          <input type="number" min="0" step="any" class="form-control subtotalnetwt" onchange="changeWeight(this);" name="inv[{{$key+1}}][subtotalnetwt]" value="{{$packingListProduct->subtotalnetwt}}" readonly/>
                        </td>
                        <td id="grosswt{{$key+1}}">
                          <input type="number" min="0" step="any" class="form-control grosswt" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][grosswt]" value="{{$packingListProduct->grosswt}}" />
                        </td>
                        <td id="subTotalGrossWT{{$key+1}}">
                          <input type="number" min="0" step="any" class="form-control subTotalGrossWT" onchange="changeWeight(this);" name="inv[{{$key+1}}][subtotalgrosswt]" value="{{$packingListProduct->subtotalgrosswt}}" readonly/>
                        </td>
                        <td id="box{{$key+1}}">
                          <input type="number" min="0" class="form-control box" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][box]" value="{{$packingListProduct->box}}" />
                        </td>
                        <td id="endBox{{$key+1}}">
                          <input type="number" min="0" class="form-control endBox" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][endBox]" value="{{$packingListProduct->endBox}}" />
                        </td>
                        <td id="subTotalBox{{$key+1}}">
                          <input type="number" min="0" class="form-control subTotalBox" onchange="changeWeight(this);" name="inv[{{$key+1}}][subTotalBox]" value="{{$packingListProduct->subTotalBox}}" readonly/>
                        </td>
                        <td id="qtybox{{$key+1}}">
                          <input type="text" min="0" class="form-control qtybox" name="inv[{{$key+1}}][qtybox]" value="{{$packingListProduct->qtybox}}" />
                        </td>

<td id="batch{{ $key + 1 }}">
    <select
        class="selectpicker form-control batch_select border rounded shadow-sm"
        data-live-search="true"
        data-container="body"
        id="select_batch{{ $key + 1 }}"
        data-row="{{ $key + 1 }}"
        data-len="{{ $key + 1 }}"
        name="inv[{{ $key + 1 }}][batch_no][]"
        required
        multiple
        data-size="6"
        data-dropup-auto="false"
        title="-- Select Batch Option --"
        style="max-height: 150px; overflow-y: auto;"
    >
        <option value="">-- Select Batch Option --</option>

        {{-- Assigned batches (selected) --}}
        @foreach ($assigned as $asn)
            @php
                $batch = $available->firstWhere('batch_no', $asn);
                $bal = $batch ? $batch->batch_balance : 0;
            @endphp
            <option value="{{ $asn }}" selected>
                {{ $asn }} (Available: {{ $bal }})
            </option>
        @endforeach

        {{-- Remaining unselected batches --}}
        @foreach ($available as $batch)
            @if (!in_array($batch->batch_no, $assigned))
                <option value="{{ $batch->batch_no }}">
                    {{ $batch->batch_no }} (Available: {{ $batch->batch_balance }})
                </option>
            @endif
        @endforeach
    </select>
</td>


                        <td>
                          <button type="button" class="btn btn-danger btn-sm deleteRow">Delete</button>
                        </td>
                      </tr>
                    @endforeach
                  @endif
                </tbody>
            </table>
          </div>

          <div class="row mt-2">
              <div class="col">
                <input type="button" id="addProductinvoice" onclick="addProduct(this)" class="btn btn-primary" value="Add Product" />
              </div>
          </div>


          <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Total Quantity') }}</label>
                    <input type="number" class="form-control" name="tquantity" id="tquantity" value="{{$packing->tquantity}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Net Weight (kg)') }}</label>
                    <input type="number" class="form-control" step="any" name="totalwt" id="totalwt" value="{{$packing->totalwt}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Gross Wt(kg)') }}</label>
                    <input type="number" class="form-control" step="any" name="grosswt" id="grosswt" value="{{$packing->grosswt}}" readonly />
                </div>
          </div>

           <div class="row mt-3">
                <div class="col-4">
                  <label class="control-label">{{ __('Total Box') }}</label>
                  <input type="number" class="form-control" name="totalbox" id="totalbox" value="{{$packing->totalbox}}" readonly />
                </div>
          </div>

          @if(!$packing->is_sent_for_invoice || auth()->user()->hasRole('admin'))
            <div class="row col-4">
              <button type="submit" id="submitBtn" onclick="return validateSubmit(event);" class="btn btn-primary mt-3">Update Packing</button>
            </div>
          @endif



      </div>
    </form>

   @if($packing->is_sent_for_invoice == 0)
      <form action="{{ route('invoice.lock_packing_list')}}" method="post">
        @csrf
        <input type="hidden" name="id" value="{{ $packing->id }}" />
        <button type="submit" class="btn btn-success mt-3">Send for Invoice</button>
      </form>
    @else

      @if(auth()->user()->hasRole('admin'))
       <form action="{{ route('invoice.unlock_packing_list')}}" method="post">
          @csrf
          <input type="hidden" name="id" value="{{ $packing->id }}" />
          <button type="submit" class="btn btn-success mt-3">Enable Editing</button>
        </form>
      @endif

    @endif

  </div>

@if(!empty($batchErrors) && count($batchErrors) > 0)
  <div class="custom-alert alert-danger alert-dismissible fade show mt-4" role="alert">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

    <h5><b>⚠ Batch Errors Found</b></h5>

    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
                <th>Product Code</th>
                <th>Packing List Qty</th>
                <th>Batch Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($batchErrors as $err)
                <tr>
                    <td>{{ $err['product_code'] }}</td>
                    <td>{{ $err['required_qty'] }}</td>
                    <td>{{ $err['available_qty'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="text-danger"><b>⚠ Please fix these issues before proceeding.</b></p>
</div>
@endif

@if(!empty($uniqueRefWarnings) && count($uniqueRefWarnings) > 0)
  <div class="custom-alert alert-warning alert-dismissible fade show mt-4" role="alert">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

    <h5><b>⚠ Unique Reference Qty Warnings</b></h5>

    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
                <th>Product Code</th>
                <th>Packing List Qty</th>
                <th>Selected Unique Ref Total Available</th>
                <th>Unique Reference ID</th>
                <th>Batch No</th>
                <th>Available Qty (SUM)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($uniqueRefWarnings as $err)
                <tr>
                    <td>{{ $err['product_code'] ?? '' }}</td>
                    <td>{{ $err['required_qty'] ?? '' }}</td>
                    <td>{{ $err['total_available_qty'] ?? '' }}</td>
                    <td>{{ $err['unique_referencenumber_id'] ?? '' }}</td>
                    <td>{{ $err['batch_no'] ?? '' }}</td>
                    <td>{{ $err['available_qty'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="text-warning mb-0"><b>Warning:</b> This is display-only. You can still update the packing list, but current ref pool might be insufficient.</p>
  </div>
@endif

<div class="modal fade" id="supplierInvoiceModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Available ref quantity</h5>
          <small class="text-muted">Batch &rarr; supplier invoice &rarr; unique reference pool (remaining qty from approved receive)</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm table-hover ref-qty-modal-table mb-0">
          <colgroup>
            <col class="col-ref-qty-check" span="1">
            <col span="1"><col span="1"><col span="1">
          </colgroup>
          <thead class="table-light">
            <tr>
              <th scope="col" class="ref-qty-check-cell">
                <span class="ref-qty-check-wrap">
                  <input type="checkbox" id="supplierInvoiceModalCheckAll" class="ref-qty-checkbox" title="Select all" aria-label="Select all lines">
                </span>
              </th>
              <th scope="col">Batch No</th>
              <th scope="col">Supplier Invoice No</th>
              <th scope="col">Total available qty</th>
            </tr>
          </thead>
          <tbody id="supplierInvoiceModalBody"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="supplierInvoiceModalApply">Apply</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('footer')
@push('styles')
<link href="{{ asset('ui-vendor/jquery-ui/css/jquery-ui.min.css') }}" rel="stylesheet">
@endpush
  @push('scripts')
<script src="{{ asset('ui-vendor/jquery-ui/js/jquery-ui.min.js') }}"></script>
@endpush
  <script>
    function reindexRows() {
      // Prevent batch change handler from clearing ref JSON during internal DOM updates.
      window.__packingBulkUpdating = true;

      $('#productInvoice tr').each(function (index) {
        let rowIndex = index + 1;

        // Update TR ID
        $(this).attr('id', `row${rowIndex}`);

        // Update TD IDs safely
        $(this).find('td').each(function () {
          if ($(this).attr('id')) {
            let newTdId = $(this).attr('id').replace(/\d+$/, rowIndex);
            $(this).attr('id', newTdId);
          }
        });

        // SURGICAL UPDATE: Update specifically identified fields to avoid corrupting Bootstrap Select
        $(this).find('.product_id_hidden').attr('id', 'product_id' + rowIndex).attr('name', `inv[${rowIndex}][product_id]`);
        $(this).find('.ref_selection_json').attr('id', 'ref_selection_json' + rowIndex).attr('name', `inv[${rowIndex}][ref_selection_json]`);
        $(this).find('.batch_selection_order_json').attr('id', 'batch_selection_order_json' + rowIndex).attr('name', `inv[${rowIndex}][batch_selection_order_json]`);
        $(this).find('.product_name').attr('id', 'product_name' + rowIndex).attr('data-row', rowIndex).attr('data-len', rowIndex);
        $(this).find('.quantity').attr('id', 'quan_in' + rowIndex).attr('name', `inv[${rowIndex}][quantity]`).attr('data-len', rowIndex);
        $(this).find('.weight').attr('id', 'wt_in' + rowIndex).attr('name', `inv[${rowIndex}][weight]`).attr('data-len', rowIndex);
        $(this).find('.subtotalnetwt').attr('id', 'sub_wt_in' + rowIndex).attr('name', `inv[${rowIndex}][subtotalnetwt]`);
        $(this).find('.grosswt').attr('id', 'gross_in' + rowIndex).attr('name', `inv[${rowIndex}][grosswt]`).attr('data-len', rowIndex);
        $(this).find('.subTotalGrossWT').attr('id', 'sub_gross_in' + rowIndex).attr('name', `inv[${rowIndex}][subtotalgrosswt]`);
        $(this).find('.box').attr('id', 'box_in' + rowIndex).attr('name', `inv[${rowIndex}][box]`).attr('data-len', rowIndex);
        $(this).find('.endBox').attr('id', 'endbox_in' + rowIndex).attr('name', `inv[${rowIndex}][endBox]`).attr('data-len', rowIndex);
        $(this).find('.subTotalBox').attr('id', 'sub_box_in' + rowIndex).attr('name', `inv[${rowIndex}][subTotalBox]`);
        $(this).find('.qtybox').attr('id', 'qtybox_in' + rowIndex).attr('name', `inv[${rowIndex}][qtybox]`);

        // Safely update the selectpicker element
        let $batchSelect = $(this).find('select.batch_select');
        if ($batchSelect.length) {
            $batchSelect.attr('id', 'select_batch' + rowIndex)
                        .attr('data-row', rowIndex)
                        .attr('data-len', rowIndex)
                        .attr('name', `inv[${rowIndex}][batch_no][]`);
        }

        // Reset span/textarea toggle
        $(this).find('textarea').hide();
        $(this).find('span[id^="plus"]').show();
        $(this).find('span[id^="minus"]').hide();
      });

      if ($.fn.selectpicker) {
        $('#productInvoice select.selectpicker').each(function () {
          var $s = $(this);
          if ($s.parent().hasClass('bootstrap-select')) {
            $s.data('suppress-ref-modal', true);
            $s.selectpicker('refresh');
          }
        });

        setTimeout(function () {
          $('#productInvoice select.selectpicker').each(function () {
            $(this).removeData('suppress-ref-modal');
          });
          window.__packingBulkUpdating = false;
        }, 1500);
      } else {
          setTimeout(function () { window.__packingBulkUpdating = false; }, 1800);
      }
    }


    // Delete row
    $(document).on('click', '.deleteRow', function () {
      $(this).closest('tr').remove();
      reindexRows();
      changeWeight();
    });

    function showDescription(ref) {
      $('#descriptionBox' + ref).toggle();
      $('#plus' + ref).toggle();
      $('#minus' + ref).toggle();
    }

    function initAutocomplete() {
    $(".product_name").each(function () {
      const $input = $(this);
      const row = $input.attr('data-row');
      const hiddenInput = $('#product_id' + row);

      try {
        if ($input.data('ui-autocomplete')) {
          $input.autocomplete('destroy');
        }
      } catch (e) { /* ignore */ }

      // If field is pre-filled (edit page), mark as valid
      let validSelection = !!($input.val() && hiddenInput.val());

      $input.autocomplete({
        source: "{{ route('purchaseOrder.autocomplete.product-name') }}",
        minLength: 2,
        appendTo: 'body',
        select: function (event, ui) {
          const selectedId = ui.item.id;

          // Check for duplicate
          let duplicateFound = false;
          $('.product_id_hidden').each(function () {
            if ($(this).attr('id') === 'product_id' + row) return; // skip current row
            if ($(this).val() == selectedId) {
              duplicateFound = true;
              return false;
            }
          });

          if (duplicateFound) {
            alert(`Product already added: ${ui.item.label}`);
            $input.val('');
            hiddenInput.val('');
            validSelection = false;
            return false;
          }

          $input.val(ui.item.label);
          hiddenInput.val(selectedId);
          changeHSN(selectedId, row);

          validSelection = true;
          return false;
        }
      });

      $input.on('input', function () {
        validSelection = false; // reset on typing
      });

      $input.on('blur', function () {
        if (!validSelection || !hiddenInput.val()) {
          alert('Please select a valid product from the list.');
          $input.val('');
          hiddenInput.val('');
        }
      });
    });
  }


    function changeBatch(e)
    {
      var row_id = $(e).attr('data-row');
      var selectedId = $("#product_name"+row_id).attr('data-pro-id');
      changeHSN(selectedId, row_id);
    }

    function addProduct()
    {

      let $original = $('#productInvoice tr:first');
      let $clone = $original.clone(false); // Don't clone plugin-enhanced DOM

      // Clean cloned inputs
      $clone.find('input, textarea').each(function () {
        if (!$(this).prop('readonly')) {
        $(this).val('');
        } else {
        $(this).val('0');
        }
      });
      $clone.find('.batch_selection_order_json').val('[]');
      $clone.find('textarea').hide();
      
      $clone.find('input.quantity').val('1');
      $clone.find('input.weight, input.grosswt, input.box, input.endBox').val('0');
      $clone.find('input.qtybox').val('0');

      var options = <?php echo json_encode($batches); ?>;
      const $batchSelect = $clone.find('select.batch_select');
      
      if($batchSelect.length) {
          $batchSelect.empty();
          $batchSelect.append('<option value="">-- Select Batch Option --</option>');
          $.each(options, function(index, item) {
              $batchSelect.append(
                  $('<option></option>')
                      .val(item.batch_no)
                      .text(`${item.batch_no} (Available: ${item.batch_balance})`)
              );
          });
          // Ensure it's reset to an empty array for multiselect logic
          $batchSelect.val([]);
      }

      // Remove any selectpicker wrappers that may have been cloned
      $clone.find('select.selectpicker').each(function () {
        // Remove generated dropdown if exists
        $(this).siblings('.dropdown-toggle, .dropdown-menu').remove();
        // Ensure it's just a raw <select>
        $(this).removeClass('bs-select-hidden').show();
      });

      // Add delete button
      $clone.find('td:last').html('<button type="button" class="btn btn-danger btn-sm deleteRow">Delete</button>');

      $('#productInvoice').append($clone);

      reindexRows();
      initAutocomplete();

      if ($.fn.selectpicker) {
        $clone.find('select.selectpicker').selectpicker({
          liveSearch: true,
          container: 'body',
        });
      }
      
      const qtyInput = $clone.find('input.quantity').get(0);
      if (qtyInput) changeWeight(qtyInput);
    }


    // Call once on page load: bootstrap-select must be initialized (raw class="selectpicker" is not enough).
    $(document).ready(function ()
    {
      if ($.fn.selectpicker) {
        $('#productInvoice select.selectpicker').each(function () {
          var $s = $(this);
          if (!$s.parent().hasClass('bootstrap-select')) {
            $s.selectpicker({
              liveSearch: true,
              container: 'body',
            });
          }
        });
      }

      initAutocomplete();
    });


    function validateSubmit(e) {
    if ($('#productInvoice tr').length < 1) {
      alert("No product added. Add atleast 1 product.");
      if(e && e.preventDefault) e.preventDefault();
      return false;
    }

    if (typeof packingSyncAllBatchOrderFields === 'function') {
      packingSyncAllBatchOrderFields();
    }

    var productTableRow = 0;
    var hasError = false;
    $('#productInvoice select.batch_select').each(function () {
      productTableRow++;
      var val = $(this).val();
      if (!val || val.length === 0) {
        alert("Product Row " + productTableRow + " empty. Select a batch or delete the row.");
        if(e && e.preventDefault) e.preventDefault();
        hasError = true;
        return false;
      }
    });
    if(hasError) return false;
    
    var refBlock = false;
    $('#productInvoice tr').each(function () {
      var $sel = $(this).find('select.batch_select');
      if (!$sel.length) return;
      var rid = $sel.attr('data-row');
      var v = $sel.val();
      if (!v || v.length === 0) return;
      var js = ($('#ref_selection_json' + rid).val() || '').trim();
      if (js === '' || js === '[]') {
        alert('Row ' + rid + ': open “Available ref quantity”, select supplier invoice line(s), and click Apply.');
        refBlock = true;
        return false;
      }
    });
    if (refBlock) {
      if(e && e.preventDefault) e.preventDefault();
      return false;
    }
    }

    function changeHSN(id, row_id)
    {
      //console.log(" REf Id - "+id+" row - "+row_id);
      $.ajax
      ({
        'url': "{{ url('/getbatchesbyid/') }}" + '/' + id,
        'method': 'GET',
      }).done(function (data)
      {
          console.log("batch data " + JSON.stringify(data));
          if (data)
          {
            //batches = data;
            checking(data, row_id);
          }
      });
    }


      $('#myForm').on('submit', function (e)
      {
        if ($('#productInvoice tr').length < 1) {
          alert("No product added. Add atleast 1 product.");
          e.preventDefault();
        } else {
          var productTableRow = 0;
          var submitFlag = 0;
          $('#productInvoice select.batch_select').each(function () {
          productTableRow++;
          var val = $(this).val();
          if (!val || val.length === 0) {
            alert("Product Row " + productTableRow + " empty. Select a batch or delete the row.");
            e.preventDefault();
            submitFlag++;
            return false;
          }
          });

          if (submitFlag == 0) {
            var refBlock = false;
            $('#productInvoice tr').each(function () {
              var $sel = $(this).find('select.batch_select');
              if (!$sel.length) return;
              var rid = $sel.attr('data-row');
              var v = $sel.val();
              if (!v || v.length === 0) return;
              var js = ($('#ref_selection_json' + rid).val() || '').trim();
              if (js === '' || js === '[]') {
                alert('Row ' + rid + ': open “Available ref quantity”, select supplier invoice line(s), and click Apply.');
                refBlock = true;
                return false;
              }
            });
            if (refBlock) {
              e.preventDefault();
              return false;
            }
            $('#submitBtn').prop('disabled', 'true');
          }
        }
      });


      //Add Table
      function changeWeight(ref)
      {

        var len = $(ref).data('len');
        var conrate = $('#conrate').val();
        
        var quantity = $("#quan_in" + len).val() || $("#quan" + len + " input").val();
        var grosswt = $("#gross_in" + len).val() || $("#grosswt" + len + " input").val();
        var box = $("#box_in" + len).val() || $("#box" + len + " input").val();
        var endBox = $("#endbox_in" + len).val() || $("#endBox" + len + " input").val();
        var subTotalBox = (endBox - box) + 1;
        var pcbox = quantity / subTotalBox;
        var netwt = $("#wt_in" + len).val() || $("#wt" + len + " input").val();
        var subtotalnetwt = netwt * quantity;
        subtotalnetwt = subtotalnetwt.toFixed(2);
        var subTotalGrossWT = grosswt * quantity;
        subTotalGrossWT = subTotalGrossWT.toFixed(2);

        $("#sub_wt_in" + len).val(subtotalnetwt) || $("#subtotalnetwt" + len + " input").val(subtotalnetwt);
        $("#sub_box_in" + len).val(subTotalBox) || $("#subTotalBox" + len + " input").val(subTotalBox);
        $("#qtybox_in" + len).val(pcbox) || $("#qtybox" + len + " input").val(pcbox);
        $("#sub_gross_in" + len).val(subTotalGrossWT) || $("#subTotalGrossWT" + len + " input").val(subTotalGrossWT);

        var arrq = document.getElementsByClassName('quantity');
        var arrwt = document.getElementsByClassName('subtotalnetwt');
        var arrgrosswt = document.getElementsByClassName('subTotalGrossWT');
        var arrbox = document.getElementsByClassName('subTotalBox');

        var totq = 0;
        var totwt = 0;
        var totgrosswt = 0;
        var totalbox = 0;


        for (var i = 0; i < arrq.length; i++) {
          if (parseFloat(arrq[i].value))
          totq += parseInt(arrq[i].value);
        }
        document.getElementById('tquantity').value = totq;



        for (var i = 0; i < arrgrosswt.length; i++) {
          if (parseFloat(arrgrosswt[i].value))
          totgrosswt += parseFloat(arrgrosswt[i].value);
        }
        document.getElementById('grosswt').value = totgrosswt.toFixed(2);

        for (var i = 0; i < arrwt.length; i++) {
          if (parseFloat(arrwt[i].value))
          totwt += parseFloat(arrwt[i].value);
        }
        document.getElementById('totalwt').value = totwt.toFixed(2);

        for (var i = 0; i < arrbox.length; i++) {
          if (parseFloat(arrbox[i].value))
          totalbox += parseInt(arrbox[i].value);
        }
        document.getElementById('totalbox').value = totalbox;

      }

      function deleterowInvoice(ref) {
        $(ref).parents("tr").remove();
        changeWeight();
      }


    function checking(batches, rowId)
    {
      // Get the selected product ID for the current row
      const selectedProduct = $(`[name="inv[${rowId}][product_id]"]`).val();

      // Filter the batches array based on the selected product
      const filteredBatches = batches.filter(batch => batch.product_id == selectedProduct);
      //const filteredBatches = selectedProduct;
      console.log("filteredBatches - " + JSON.stringify(filteredBatches));

      // Generate the new options for the batch dropdown
      let batchOptions = '<option  disabled>-- SELECT BATCH --</option>';
      filteredBatches.forEach(batch => {
        batchOptions += `<option value="${batch.batch_no}">${batch.batch_no} (Available: ${batch.batch_balance})</option>`;
      });

      // Update the batch dropdown in the respective row
      const $batchSelect = $(`[name="inv[${rowId}][batch_no][]"]`);
      $batchSelect.html(batchOptions);

      // Important: Since the product and possible batches completely changed, clear ref json validation
      $('#ref_selection_json' + rowId).val('');
      var $bo = $('#batch_selection_order_json' + rowId);
      if ($bo.length) {
        $bo.val('[]');
      }

      if ($.fn.selectpicker) {
        if ($batchSelect.parent().hasClass('bootstrap-select')) {
          $batchSelect.selectpicker('destroy');
        }
        $batchSelect.addClass('selectpicker');
        $batchSelect.selectpicker({
          liveSearch: true,
          container: 'body',
        });
      }
    }


    // Prevent auto-hide for batch error alert

  </script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const alertBox = document.querySelector('.error-alert');
    if (alertBox) {
        alertBox.style.display = 'block';  // Always show
        alertBox.style.opacity = 1;        // Prevent fade
    }
   $(".alert").show();

$(".error-alert").fadeIn();



});
</script>


<script>
    document.querySelectorAll('.btn-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.custom-alert').style.display = 'none';
        });
    });
</script>

<script>
/** Preserve multiselect batch order by selection order (not &lt;option&gt; DOM order). */
function packingMergeBatchSelectionOrder(prevOrder, currentValArray) {
    var cur = $.makeArray(currentValArray || []).map(function (b) { return String(b).trim(); }).filter(Boolean);
    var prev = $.makeArray(prevOrder || []).map(function (b) { return String(b).trim(); }).filter(Boolean);
    var setCur = Object.create(null);
    for (var i = 0; i < cur.length; i++) {
        setCur[cur[i]] = true;
    }
    var out = [];
    for (var j = 0; j < prev.length; j++) {
        if (setCur[prev[j]] && out.indexOf(prev[j]) === -1) {
            out.push(prev[j]);
        }
    }
    for (var k = 0; k < cur.length; k++) {
        if (out.indexOf(cur[k]) === -1) {
            out.push(cur[k]);
        }
    }
    return out;
}
function packingSetBatchOrderForRow(rowId, orderArr) {
    var json = JSON.stringify(orderArr || []);
    var $h = $('#batch_selection_order_json' + rowId);
    if ($h.length) {
        $h.val(json);
    }
    $('#select_batch' + rowId).data('batch-selection-order', orderArr || []);
}
function packingGetBatchOrderForRow(rowId) {
    var $h = $('#batch_selection_order_json' + rowId);
    if ($h.length) {
        try {
            var p = JSON.parse($h.val() || '[]');
            if (Array.isArray(p)) {
                return p.map(String);
            }
        } catch (e1) { /* ignore */ }
    }
    var d = $('#select_batch' + rowId).data('batch-selection-order');
    return $.makeArray(d).map(String);
}
function packingSyncBatchOrderFromSelect(rowId) {
    var $sel = $('#select_batch' + rowId);
    if (!$sel.length) {
        return;
    }
    var merged = packingMergeBatchSelectionOrder(packingGetBatchOrderForRow(rowId), $sel.val());
    packingSetBatchOrderForRow(rowId, merged);
}
function packingSyncAllBatchOrderFields() {
    $('#productInvoice select.batch_select').each(function () {
        var rid = $(this).attr('data-row');
        if (rid) {
            packingSyncBatchOrderFromSelect(rid);
        }
    });
}

function mergeRefQtyRowsForPackingModal(rows) {
    var merged = [];
    var byKey = Object.create(null);
    if (!rows || !rows.length) {
        return merged;
    }
    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        var uid = parseInt(row.unique_referencenumber_id, 10) || 0;
        var qty = parseInt(row.available_qty, 10) || 0;
        if (row.is_no_supplier_invoice) {
            if (uid <= 0 || qty <= 0) {
                continue;
            }
            merged.push({
                batch_no: row.batch_no,
                supplier_invoice_number: row.supplier_invoice_number || 'No supplier invoice',
                available_qty: qty,
                is_no_supplier_invoice: true,
                ur_ids: [uid]
            });
            continue;
        }
        var bn = String(row.batch_no || '');
        var sid = parseInt(row.supplier_invoice_id, 10) || 0;
        if (uid <= 0 || qty <= 0 || !bn) {
            continue;
        }
        var key = bn + '\u0000' + sid;
        if (!byKey[key]) {
            byKey[key] = {
                batch_no: row.batch_no,
                supplier_invoice_number: row.supplier_invoice_number || '',
                available_qty: 0,
                is_no_supplier_invoice: false,
                ur_ids: []
            };
            merged.push(byKey[key]);
        }
        byKey[key].available_qty += qty;
        byKey[key].ur_ids.push(uid);
    }
    return merged;
}

$(document).on('changed.bs.select', '.batch_select', function () {
    if ($(this).data('suppress-ref-modal')) {
        return;
    }
    if (window.__packingBulkUpdating) {
        return;
    }
    
    // Safely use attr instead of data to avoid cached ID errors
    let rowId = $(this).attr('data-row');
    let batchNosRaw = $(this).val();
    let productId = $('#product_id' + rowId).val();
    if (!batchNosRaw || !productId) return;

    var batchNos = packingMergeBatchSelectionOrder(packingGetBatchOrderForRow(rowId), batchNosRaw);
    packingSetBatchOrderForRow(rowId, batchNos);

    var batchKeyNorm = $.map($.makeArray(batchNos), function (b) { return String(b); }).sort().join('\0');
    var prevJson = $('#ref_selection_json' + rowId).val() || '';
    var prevBatchKey = '';
    var parsedOk = false;
    try {
        var prevArr = JSON.parse(prevJson);
        if (Array.isArray(prevArr)) {
            var prevBatches = $.map(prevArr, function (x) { return String(x.batch_no).trim(); });
            prevBatches = prevBatches.filter(function (s) { return s !== ''; });
            prevBatches = Array.from(new Set(prevBatches)).sort();
            prevBatchKey = prevBatches.join('\0');
            parsedOk = true;
        }
    } catch (e2) {
        parsedOk = false;
    }
    
    if (parsedOk && prevBatchKey !== '' && batchKeyNorm !== prevBatchKey) {
        $('#ref_selection_json' + rowId).val('');
    }

    $('#supplierInvoiceModalBody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
    $.ajax({
        url: "{{ route('supplier.invoice.batch.details') }}",
        type: "POST",
        data: { _token: "{{ csrf_token() }}", batch_no: batchNos, product_id: productId },
        success: function (res) {
            let html = '';
            function escapeAttr(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            }
            var prevSel = [];
            try { prevSel = JSON.parse($('#ref_selection_json' + rowId).val() || '[]'); } catch (e) { prevSel = []; }
            if (!Array.isArray(prevSel)) prevSel = [];
            var prevIdSet = new Set($.map(prevSel, function (x) { return String(x.unique_referencenumber_id != null ? x.unique_referencenumber_id : ''); }));

            var displayRows = mergeRefQtyRowsForPackingModal(res);

            if (displayRows.length === 0) {
                $('#supplierInvoiceModalCheckAll').prop('checked', false);
                html = '<tr><td colspan="4" class="text-center text-danger">No available ref qty for this product on selected batches</td></tr>';
            } else {
                $.each(displayRows, function (i, row) {
                    var tot = row.available_qty != null ? row.available_qty : 0;
                    var bn = row.batch_no || '';
                    var urIds = row.ur_ids || [];
                    var urIdsAttr = urIds.join(',');
                    var allInPrev = urIds.length > 0 && urIds.every(function (id) { return prevIdSet.has(String(id)); });
                    var checked = (prevSel.length === 0 || allInPrev) ? ' checked' : '';
                    var labelIds = urIds.length > 1 ? urIdsAttr : String(urIds[0] || '');
                    html += '<tr data-ur-ids="' + escapeAttr(urIdsAttr) + '" data-batch-no="' + escapeAttr(bn) + '" data-available-qty="' + escapeAttr(tot) + '">' +
                        '<td class="ref-qty-check-cell"><span class="ref-qty-check-wrap">' +
                        '<input type="checkbox" class="ref-qty-checkbox ref-batch-cb"' + checked + ' aria-label="Select reference ' + escapeAttr(labelIds) + '">' +
                        '</span></td><td>' + escapeAttr(bn) + '</td><td>' + escapeAttr(row.supplier_invoice_number || '') + '</td><td><strong>' + tot + '</strong></td></tr>';
                });
            }
            $('#supplierInvoiceModalBody').html(html);
            $('#supplierInvoiceModalCheckAll').prop('checked',
                displayRows.length > 0 && $('#supplierInvoiceModalBody .ref-batch-cb').length === $('#supplierInvoiceModalBody .ref-batch-cb:checked').length);
            
            // Set safely using attr and data
            $('#supplierInvoiceModal').attr('data-row', rowId).data('row', rowId).modal('show');
        }
    });
});

$(document).on('change', '#supplierInvoiceModalCheckAll', function () {
    var on = $(this).prop('checked');
    $('#supplierInvoiceModalBody .ref-batch-cb').prop('checked', on);
});

$(document).on('change', '#supplierInvoiceModalBody .ref-batch-cb', function () {
    var $cbs = $('#supplierInvoiceModalBody .ref-batch-cb');
    $('#supplierInvoiceModalCheckAll').prop('checked',
        $cbs.length > 0 && $cbs.length === $cbs.filter(':checked').length);
});

$(document).on('click', '#supplierInvoiceModalApply', function () {
    // Read from DOM attribute (cache-safe), fallback to jQuery data.
    var rowId = $('#supplierInvoiceModal').attr('data-row') || $('#supplierInvoiceModal').data('row');
    var lines = [];
    var sumRef = 0;
    $('#supplierInvoiceModalBody tr[data-ur-ids]').each(function () {
        if (!$(this).find('.ref-batch-cb').prop('checked')) return;
        var bn = String($(this).attr('data-batch-no') || '');
        var aq = parseInt($(this).data('available-qty'), 10) || 0;
        sumRef += aq;
        var idsRaw = String($(this).attr('data-ur-ids') || '');
        var parts = idsRaw.split(',').map(function (s) { return parseInt(String(s || '').trim(), 10) || 0; }).filter(function (n) { return n > 0; });
        for (var pi = 0; pi < parts.length; pi++) {
            if (bn) {
                lines.push({ unique_referencenumber_id: parts[pi], batch_no: bn });
            }
        }
    });
    if (lines.length === 0) {
        alert('Please select at least one reference line.');
        return;
    }
    
    // Correctly reference the quantity using the exact updated ID or original ID structure
    var packQty = parseInt($("#quan_in" + rowId).val() || $('#quan' + rowId + ' input.quantity').val(), 10) || 0;
    
    if (packQty > sumRef) {
        alert('Packing quantity (' + packQty + ') is greater than total ref from selected lines (' + sumRef + '). Select more lines or reduce quantity.');
        return;
    }
    var batchesUnique = [];
    lines.forEach(function (L) {
        if (batchesUnique.indexOf(L.batch_no) === -1) batchesUnique.push(L.batch_no);
    });
    $('#ref_selection_json' + rowId).val(JSON.stringify(lines));
    packingSetBatchOrderForRow(rowId, batchesUnique);
    var $sel = $('#select_batch' + rowId);
    if (!$sel.length) return;
    $sel.data('suppress-ref-modal', true);
    $sel.selectpicker('val', batchesUnique);
    $sel.selectpicker('refresh');
    $('#supplierInvoiceModal').modal('hide');
    setTimeout(function () { $sel.removeData('suppress-ref-modal'); }, 200);
});
</script>

@endsection