@extends('layouts.app')

@section('content')
  {{-- Layout is Bootstrap 4; do not load Bootstrap 5 CSS (breaks bootstrap-select). --}}
<style>
  /* dropdown fits small screens & caps height */
.bootstrap-select .dropdown-menu .inner{
  max-height: 50vh !important;
  overflow-y: auto !important;
}

/* make the toggle fill the cell */
.bootstrap-select > .dropdown-toggle{
  width: 100% !important;
}

/* keep the menu above tables/modals */
.bootstrap-select .dropdown-menu{
  z-index: 1060; /* above modal (1050) & backdrop (1040) */
}

/* better on very small screens */
@media (max-width: 576px){
  .bootstrap-select{ width: 100% !important; }
}

.ui-autocomplete {
  z-index: 10050 !important;
  max-height: 260px;
  overflow-y: auto;
}

#customErrorBox{
    position: sticky;
    top: 10px;
    z-index: 9999;
}

/*
 * Available ref quantity modal: BS5 .form-check-input uses float:left and breaks
 * table column alignment — keep checkboxes in their own narrow column.
 */
#supplierInvoiceModal table.ref-qty-modal-table {
  table-layout: fixed;
}
#supplierInvoiceModal .ref-qty-modal-table col.col-ref-qty-check {
  width: 3rem;
}
#supplierInvoiceModal .ref-qty-modal-table th.ref-qty-check-cell,
#supplierInvoiceModal .ref-qty-modal-table td.ref-qty-check-cell {
  width: 3rem;
  min-width: 3rem;
  max-width: 3rem;
  box-sizing: border-box;
  vertical-align: middle !important;
  text-align: center;
  padding: 0.4rem 0.35rem !important;
  overflow: hidden;
}
#supplierInvoiceModal .ref-qty-modal-table .ref-qty-checkbox {
  float: none !important;
  position: static !important;
  margin: 0 !important;
  display: inline-block !important;
  vertical-align: middle !important;
  width: 1.125rem;
  height: 1.125rem;
  cursor: pointer;
  flex-shrink: 0;
}
#supplierInvoiceModal .ref-qty-modal-table .ref-qty-check-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 1.25rem;
}


  </style>


@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert" id="customErrorBox">
    <strong>Error:</strong>
    <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>

    <button type="button"
            class="btn-close"
            onclick="hideErrorBox()"
            aria-label="Close"></button>
</div>
@endif


  <div class="mx-2">
    <div class="row mx-0 my-2">
    <h2>Add Packing</h2>
    </div>

    <form id='myForm' method="POST" action="{{ url('/invoice/packing_create') }}">
    @csrf

    <!-- Form Starts -->
    <div class="form-group">


      <!-- First row -->
      <div class="row mt-3">
      <div class="col-4">
        <label class="control-label">{{ __('Buyer Order No.') }}</label>
        <input type="text" class="form-control toUpperCase" name="buyerorderno" required="required" />
      </div>
      </div>

      <!-- Product Details-->
      <div class="row mt-5 my-3">
      <div class="col-6">
        <h5>Products Details</h5>
      </div>
      </div>
      <div class="table-responsive">
      <table class="table table-hover">
        <thead>
        <tr id="mytable">
          <th scope="col" style="min-width: 300px;">Product <a href="{{ url('/product/create')}}" target="_blank">
            (+New)</a></th>
          <th scope="col" style="min-width: 100px;">QTY</th>
          <th scope="col" style="min-width: 120px;">Net Wt (Kg)</th>
          <th scope="col" style="min-width: 150px;">Sub Total Net Wt (Kg)</th>
          <th scope="col" style="min-width: 120px;">Gross Wt (Kg)</th>
          <th scope="col" style="min-width: 150px;">Sub Total Gross Wt (Kg)</th>
          <th scope="col" style="min-width: 100px;">Start Box</th>
          <th scope="col" style="min-width: 100px;">End Box</th>
          <th scope="col" style="min-width: 100px;">Sub Total Box</th>
          <th scope="col" style="min-width: 100px;">PC/Box</th>
          <th scope="col" style="min-width: 250px;">Batch No</th>
        </tr>
        </thead>
        <tbody id="productInvoice">
        <tr>
          <td id="pr1">
          <input type="hidden" name="inv[1][product_id]" class="product_id_hidden" id="product_id1">
          <input type="hidden" name="inv[1][ref_selection_json]" id="ref_selection_json1" class="ref_selection_json" value="">
          <input type="hidden" name="inv[1][batch_selection_order_json]" id="batch_selection_order_json1" class="batch_selection_order_json" value="[]">

          <input type="text" class="form-control product_name" id="product_name1"
            data-len="1" data-row="1" data-pro-id="" required="required">
          <span id="plus1" onclick="showDescription(1)" style="cursor: pointer;">(+)</span>
          <span id="minus1" onclick="showDescription(1)" style="cursor:pointer; display:none;">(-)</span>
          <textarea class="form-control" id="descriptionBox1" style="display:none;" rows="2"
            name="inv[1][descriptionBox]"></textarea>
          </td>
          <td id="quan1">
          <input type="number" min="1" class="form-control quantity" onchange="changeWeight(this);" data-len="1"
            name="inv[1][quantity]" value="1" required>
          </td>
          <td id="wt1">
          <input type="number" step="any" min="0" class="form-control weight" onchange="changeWeight(this);"
            data-len="1" name="inv[1][weight]" value="0" required="">
          </td>
          <td id="subtotalnetwt1">
          <input type="number" step="any" min="0" class="form-control subtotalnetwt"
            onchange="changeWeight(this);" data-len="1" name="inv[1][subtotalnetwt]" value="0" readonly="">
          </td>
          <td id="grosswt1">
          <input type="number" step="any" min="0" class="form-control grosswt" onchange="changeWeight(this);"
            data-len="1" name="inv[1][grosswt]" value="0" required="">
          </td>
          <td id="subTotalGrossWT1">
          <input type="number" step="any" min="0" class="form-control subTotalGrossWT"
            onchange="changeWeight(this);" data-len="1" name="inv[1][subtotalgrosswt]" value="0" readonly="">
          </td>
          <td id="box1">
          <input type="number" step="any" class="form-control box" onchange="changeWeight(this);" data-len="1"
            name="inv[1][box]" value="0" min="0" required="">
          </td>
          <td id="endBox1">
          <input type="number" step="any" class="form-control endBox" onchange="changeWeight(this);" data-len="1"
            name="inv[1][endBox]" value="0" min="0" required="">
          </td>
          <td id="subTotalBox1">
          <input type="number" step="any" class="form-control subTotalBox" onchange="changeWeight(this);"
            data-len="1" name="inv[1][subTotalBox]" value="0" min="0" readonly="">
          </td>
          <td id="qtybox1">
          <input type="text" step="any" class="form-control qtybox" name="inv[1][qtybox]" value="1 Pc/Box" min="0"
            readonly="">
          </td>
     <td id="batch1">

  <select
    class="selectpicker form-control batch_select"
    id="select_batch1"
    data-row="1"
    name="inv[1][batch_no][]"
    multiple
    required
    data-live-search="true"
    data-actions-box="true"
    data-container="body">

    @foreach($batches as $batch)
      <option value="{{ $batch->batch_no }}">
        {{ $batch->batch_no }} (Available: {{ $batch->batch_balance }})
      </option>
    @endforeach
  </select>
</td>


          <td> </td>
        </tr>

        </tbody>
      </table>
      </div>

      <div class="row mt-2">
      <div class="col">
        <input type="button" id="addProductInvoice" class="btn btn-primary" value="Add Product" />
      </div>
      </div>


      <!-- ninth row -->
      <div class="row mt-3">
      <div class="col-4">
        <label class="control-label">{{ __('Total Quantity') }}</label>
        <input type="number" class="form-control" name="tquantity" id="tquantity" readonly />
      </div>
      <div class="col-4">
        <label class="control-label">{{ __('Total Net Weight (kg)') }}</label>
        <input type="number" class="form-control" step="any" name="totalwt" id="totalwt" readonly />
      </div>
      <div class="col-4">
        <label class="control-label">{{ __('Total Gross Wt (kg)') }}</label>
        <input type="number" class="form-control" step="any" name="grosswt" id="grosswt" readonly />
      </div>
      </div>

      <!-- tenth row -->
      <div class="row mt-3">
      <div class="col-4">
        <label class="control-label">{{ __('Total Box') }}</label>
        <input type="number" class="form-control" id="totalbox" name="totalbox" readonly />
      </div>
      </div>

      <div class="row col-4">
          <button id='submitBtn' type="submit" form='myForm' class="btn btn-primary mt-3">Create Packing</button>
      </div>
    </div>
    </form>
  </div>

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
            <col span="1">
            <col span="1">
            <col span="1">
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
  $(function () {
    var $batch = $('#select_batch1').selectpicker();

    function setMenuMaxHeight(){
      var inst = $batch.data('selectpicker');
      if (!inst) return;
      var h = Math.floor((window.innerHeight || document.documentElement.clientHeight) * 0.5);
      inst.$menu.find('.inner').css({ 'max-height': h + 'px', 'overflow-y': 'auto' });
    }

    // when opened, size the list for current viewport
    $batch.on('shown.bs.select', setMenuMaxHeight);

    // if the page or a responsive table scrolls, keep it tidy
    $(window).on('resize scroll', function(){
      if ($batch.parent().hasClass('show')) setMenuMaxHeight();
    });

    // if inside a responsive table wrapper, adjust on horizontal scroll
    $('.table-responsive').on('scroll', function(){
      if ($batch.parent().hasClass('show')) $batch.selectpicker('render');
    });

    // if used in a modal, ensure correct placement on open
    $('.modal').on('shown.bs.modal', function(){
      $batch.selectpicker('render');
    });
  });
</script>

  <script>

    function reindexRows() {
    $('#productInvoice tr').each(function (index) {
      let rowIndex = index + 1;

      // Update ID
      $(this).attr('id', `row${rowIndex}`);


      $(this).find('td').each(function () {
      if ($(this).attr('id')) {
        let newTdId = $(this).attr('id').replace(/\d+/, rowIndex);
        $(this).attr('id', newTdId);

      }
      });

      $(this).find('input, textarea, select, span').each(function ()
      {
        let $el = $(this);

        // Update ID
        if ($el.attr('id')) {
          let newId = $el.attr('id').replace(/\d+/, rowIndex);
          $el.attr('id', newId);

        }

        if ($el.attr('data-row')) {
          let newId = $el.attr('data-row').replace(/\d+/, rowIndex);
          $el.attr('data-row', newId);
        }

        // Update name
        if ($el.attr('name')) {
          let newName = $el.attr('name').replace(/inv\[\d+\]/, `inv[${rowIndex}]`);
          $el.attr('name', newName);
        }

        // Update data-len
        if ($el.attr('data-len')) {
          $el.attr('data-len', rowIndex);
        }

        // Update onclick handlers like showDescription(1)
        if ($el.attr('onclick')) {
          $el.attr('onclick', $el.attr('onclick').replace(/\(\d+\)/, `(${rowIndex})`));
        }
      });

      // Reset span/textarea toggle
      $(this).find('textarea').hide();
      $(this).find('span[id^="plus"]').show();
      $(this).find('span[id^="minus"]').hide();
    });
    }

    $('#addProductInvoice').click(function () {
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

      var options = <?php echo json_encode($batches); ?>;

      $.each(options, function(index, item) {
          $clone.find('select').append(
              $('<option></option>')
                  .val(item.batch_no)
                  .text(`${item.batch_no} (Available: ${item.batch_balance})`)
          );
      });

      $clone.find('select').val('');

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

      // Re-initialize selectpicker
      if ($.fn.selectpicker) {
        $clone.find('select.selectpicker').selectpicker('render').selectpicker('refresh');
      }
    });

    // Delete row
    $(document).on('click', '.deleteRow', function () {
      $(this).closest('tr').remove();
      reindexRows();
      changeWeight();
    });
  </script>





  <script>

    function showDescription(ref) {
      $('#descriptionBox' + ref).toggle();
      $('#plus' + ref).toggle();
      $('#minus' + ref).toggle();
    }

   function initAutocomplete() {
      $(".product_name").each(function () {
        const $input = $(this);
        let validSelection = false;

        $input.autocomplete({
          source: "{{ route('purchaseOrder.autocomplete.product-name') }}",
          minLength: 2,
          appendTo: 'body',
          select: function (event, ui) {
            console.log(ui.item);
            const selectedId = ui.item.id;
            const row = $input.attr('data-row');

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
              $('#product_id' + row).val('');
              validSelection = false;
              return false;
            }

            $input.val(ui.item.label);
            $('#product_id' + row).val(selectedId);
            changeHSN(selectedId, row);

            validSelection = true;
            return false;
          }
        });

        // Handle blur (leaving input)
        $input.on('blur', function () {
          const row = $input.attr('data-row');
          const hiddenVal = $('#product_id' + row).val();

          if (!validSelection || !hiddenVal) {
            alert('Please select a valid product from the list.');
            $input.val('');
            $('#product_id' + row).val('');
          }
        });

        // Reset validSelection on input
        $input.on('input', function () {
          validSelection = false;
        });
      });
    }


    function changeBatch(e)
    {
      var row_id = $(e).attr('data-row');
      var selectedId = $("#product_name"+row_id).attr('data-pro-id');
      changeHSN(selectedId, row_id);
    }


    //  Call once on page load
    $(document).ready(function ()
    {
      initAutocomplete();
    });

    //var productRows = 0;

    //console.log(batches);
    function validateSubmit() {
    if ($('#productInvoice tr').length < 1) {
      alert("No product added. Add atleast 1 product.");
      event.preventDefault();
    }

    else {
      var productTableRow = 0;
      $('#productInvoice select').each(function () {
      productTableRow++
      if (!$(this).val()) {
        alert("Product Row " + productTableRow + " empty. Select a product or delete the row.");
        event.preventDefault();
        return false;
      }
      });
    }
    };

    function changeHSN(id, row_id)
    {
      //console.log(" REf Id - "+id+" row - "+row_id);
      $.ajax
      ({
        'url': "{{ url('/getbatchesbyid/') }}" + '/' + id,
        'method': 'GET',
        }).done(function (data) {
          console.log("batch data " + data);
          if (data) {
            //batches = data;
            checking(data, row_id);
          }
      });

    }


      $('#myForm').on('submit', function ()
      {
        if ($('#productInvoice tr').length < 1) {
          alert("No product added. Add atleast 1 product.");
          event.preventDefault();
        } else {
          var productTableRow = 0;
          var submitFlag = 0;
          $('#productInvoice select').each(function () {
          productTableRow++;
          if (!$(this).val()) {
            alert("Product Row " + productTableRow + " empty. Select a product or delete the row.");
            event.preventDefault();
            submitFlag++;
            return false;
          }
          });

          if (submitFlag == 0) {
            if (typeof packingSyncAllBatchOrderFields === 'function') {
              packingSyncAllBatchOrderFields();
            }
            var refBlock = false;
            $('#productInvoice tr').each(function () {
              var $sel = $(this).find('select.batch_select');
              if (!$sel.length) {
                return;
              }
              var rid = $sel.data('row');
              var v = $sel.val();
              if (!v || !v.length) {
                return;
              }
              var js = ($('#ref_selection_json' + rid).val() || '').trim();
              if (js === '' || js === '[]') {
                alert('Row ' + rid + ': open “Available ref quantity”, select supplier invoice line(s), and click Apply.');
                refBlock = true;
                return false;
              }
            });
            if (refBlock) {
              event.preventDefault();
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
        var quantity = $("#quan" + len + " input").val();
        var grosswt = $("#grosswt" + len + " input").val();
        var box = $("#box" + len + " input").val();
        var endBox = $("#endBox" + len + " input").val();
        var subTotalBox = (endBox - box) + 1;
        var pcbox = quantity / subTotalBox;
        var netwt = $("#wt" + len + " input").val();
        var subtotalnetwt = netwt * quantity;
        subtotalnetwt = subtotalnetwt.toFixed(2);
        var subTotalGrossWT = grosswt * quantity;
        subTotalGrossWT = subTotalGrossWT.toFixed(2);

        $("#subtotalnetwt" + len + " input").val(subtotalnetwt);
        $("#subTotalBox" + len + " input").val(subTotalBox);
        $("#qtybox" + len + " input").val(pcbox);
        $("#subTotalGrossWT" + len + " input").val(subTotalGrossWT);

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

      // $("#addProductInvoice").click(function ()
      // {
      //     var options = '<option selected disabled>-- SELECT PRODUCT --</option>';
      //     var batchOptions = '<option selected disabled>-- SELECT BATCH --</option>';

      //     // Populate products
      //     $.each(products, function (index, value) {
      //       options += '<option value="' + value.id + '">' + value.code + " - " + value.name + "</option>";
      //     });

      //     // Populate batches
      //     $.each(batches, function (index, batch) {
      //       batchOptions += `<option value="${batch.batch_no}">${batch.batch_no} (Available: ${batch.batch_balance})</option>`;
      //     });

      //     productRows += 1;

      //     var $block = "";
      //     $block += "<tr>";
      //     $block += '<td id="pr">';
      //     $block +=
      //       '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-row="' + productRows + '" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][product_id]" required="required">';
      //     $block += options + "</select>";
      //     $block +=
      //       '<span id="plus' +
      //       productRows +
      //       '" onclick="showDescription(' +
      //       productRows +
      //       ')" style="cursor: pointer;">(+)</span><span id="minus' +
      //       productRows +
      //       '" onclick="showDescription(' +
      //       productRows +
      //       ')" style="cursor:pointer; display:none;">(-)</span><textarea class="form-control" id="descriptionBox' +
      //       productRows +
      //       '" style="display:none;" rows="2" name="inv[' +
      //       productRows +
      //       '][descriptionBox]"/></textarea></td>';
      //     $block +=
      //       '<td id="quan' +
      //       productRows +
      //       '"><input type="number" min="1" class="form-control quantity" onchange="changeWeight(this);" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][quantity]" value="1" /></td>';
      //     $block +=
      //       '<td id="wt' +
      //       productRows +
      //       '"><input type="number" step="any" min="0" class="form-control weight" onchange="changeWeight(this);" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][weight]" value="0" required /></td>';
      //     $block +=
      //       '<td id="subtotalnetwt' +
      //       productRows +
      //       '"><input type="number" step="any" min="0" class="form-control subtotalnetwt" onchange="changeWeight(this);" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][subtotalnetwt]" value="0" readonly/></td>';
      //     $block +=
      //       '<td id="grosswt' +
      //       productRows +
      //       '"><input type="number" step="any" min="0" class="form-control grosswt" onchange="changeWeight(this);" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][grosswt]" value="0" required /></td>';
      //     $block +=
      //       '<td id="subTotalGrossWT' +
      //       productRows +
      //       '"><input type="number" step="any" min="0" class="form-control subTotalGrossWT" onchange="changeWeight(this);" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][subtotalgrosswt]" value="0" readonly/></td>';
      //     $block +=
      //       '<td id="box' +
      //       productRows +
      //       '"><input type="number" step="any" class="form-control box" onchange="changeWeight(this);" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][box]" value="0" min="0" required /></td>';
      //     $block +=
      //       '<td id="endBox' +
      //       productRows +
      //       '"><input type="number" step="any" class="form-control endBox" onchange="changeWeight(this);" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][endBox]" value="0" min="0" required /></td>';
      //     $block +=
      //       '<td id="subTotalBox' +
      //       productRows +
      //       '"><input type="number" step="any" class="form-control subTotalBox" onchange="changeWeight(this);" data-len="' +
      //       productRows +
      //       '" name="inv[' +
      //       productRows +
      //       '][subTotalBox]" value="0" min="0" readonly/></td>';
      //     $block +=
      //       '<td id="qtybox' +
      //       productRows +
      //       '"><input type="text" step="any" class="form-control qtybox" name="inv[' +
      //       productRows +
      //       '][qtybox]" value="1 Pc/Box" min="0" readonly/></td>';
      //     $block +=
      //       '<td id="batch' +
      //       productRows +
      //       '"><select type="text" class="form-control batch_no selectpicker" data-live-search="true" name="inv[' +
      //       productRows +
      //       '][batch_no][]" multiple required>' +
      //       batchOptions +
      //       "</select></td>";
      //     $block +=
      //       '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';

      //     $block += "</tr>";
      //     $("#productInvoice").append($block);
      //     $(".selectpicker").selectpicker();
      // });


    function checking(batches, rowId)
    {
      // Get the selected product ID for the current row
      const selectedProduct = $(`[name="inv[${rowId}][product_id]"]`).val();

      // Filter the batches array based on the selected product
      const filteredBatches = batches.filter(batch => batch.product_id == selectedProduct);

      console.log("filteredBatches - " + filteredBatches);

      // Generate the new options for the batch dropdown
      let batchOptions = '<option  disabled>-- SELECT BATCH --</option>';
      filteredBatches.forEach(batch => {
        batchOptions += `<option value="${batch.batch_no}">${batch.batch_no} (Available: ${batch.batch_balance})</option>`;
      });

      // Update the batch dropdown in the respective row
      const $batchSelect = $(`[name="inv[${rowId}][batch_no][]"]`);
      $batchSelect.html(batchOptions);

      // Refresh the selectpicker to reflect the new options
      $batchSelect.selectpicker('refresh');
      var $bo = $('#batch_selection_order_json' + rowId);
      if ($bo.length) {
        $bo.val('[]');
      }
    }


  </script>
  <!-- Script End -->
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

/**
 * Merge modal rows: same batch + supplier invoice (referacid) → one row, qty summed.
 * "No supplier invoice" lines stay one row per unique_referencenumber (not merged).
 */
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

/**
 * Batch select → Open modal
 */
$(document).on('changed.bs.select', '.batch_select', function () {

    if ($(this).data('suppress-ref-modal')) {
        return;
    }

    let rowId     = String($(this).attr('data-row') || $(this).data('row') || '');
    let batchNosRaw  = $(this).val();
    let productId = $('#product_id' + rowId).val();

    if (!batchNosRaw || !productId) return;

    var batchNos = packingMergeBatchSelectionOrder(packingGetBatchOrderForRow(rowId), batchNosRaw);
    packingSetBatchOrderForRow(rowId, batchNos);

    var batchKeyNorm = $.map($.makeArray(batchNos), function (b) { return String(b); }).sort().join('\0');
    var prevJson = $('#ref_selection_json' + rowId).val() || '';
    var prevBatchKey = '';
    try {
        var prevArr = JSON.parse(prevJson);
        if (Array.isArray(prevArr)) {
            prevBatchKey = $.map(prevArr, function (x) { return String(x.batch_no); }).sort().join('\0');
        }
    } catch (e2) {}
    if (batchKeyNorm !== prevBatchKey) {
        $('#ref_selection_json' + rowId).val('');
    }

    $('#supplierInvoiceModalBody').html(`
        <tr>
            <td colspan="4" class="text-center">Loading...</td>
        </tr>
    `);

    $.ajax({
        url: "{{ route('supplier.invoice.batch.details') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            batch_no: batchNos,
            product_id: productId
        },
        success: function (res) {

            let html = '';

            function escapeAttr(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            var prevSel = [];
            try {
                prevSel = JSON.parse($('#ref_selection_json' + rowId).val() || '[]');
            } catch (e) {
                prevSel = [];
            }
            if (!Array.isArray(prevSel)) {
                prevSel = [];
            }
            var prevIdSet = new Set($.map(prevSel, function (x) {
                return String(x.unique_referencenumber_id != null ? x.unique_referencenumber_id : '');
            }));

            var displayRows = mergeRefQtyRowsForPackingModal(res);

            if (displayRows.length === 0) {
                $('#supplierInvoiceModalCheckAll').prop('checked', false);
                html = `
                    <tr>
                        <td colspan="4" class="text-center text-danger">
                            No available ref qty for this product on selected batches
                        </td>
                    </tr>`;
            } else {
                displayRows.forEach(function (row) {
                    var bn = row.batch_no || '';
                    var tot = row.available_qty != null ? parseInt(row.available_qty, 10) : 0;
                    var urIds = row.ur_ids || [];
                    var urIdsAttr = urIds.join(',');
                    var allPrev = urIds.length > 0 && urIds.every(function (id) {
                        return prevIdSet.has(String(id));
                    });
                    var checked = allPrev ? ' checked' : '';
                    var labelIds = urIds.length > 1 ? urIdsAttr : String(urIds[0] || '');
                    html += `
                        <tr data-ur-ids="${escapeAttr(urIdsAttr)}" data-batch-no="${escapeAttr(bn)}" data-available-qty="${escapeAttr(tot)}">
                            <td class="ref-qty-check-cell">
                                <span class="ref-qty-check-wrap">
                                    <input type="checkbox" class="ref-qty-checkbox ref-batch-cb"${checked} aria-label="Select reference ${escapeAttr(labelIds)}">
                                </span>
                            </td>
                            <td>${escapeAttr(bn)}</td>
                            <td>${escapeAttr(row.supplier_invoice_number || (row.is_no_supplier_invoice ? 'No supplier invoice' : ''))}</td>
                            <td><strong>${Number.isFinite(tot) ? tot : 0}</strong></td>
                        </tr>`;
                });
            }

            $('#supplierInvoiceModalBody').html(html);
            var $modalCbs = $('#supplierInvoiceModalBody .ref-batch-cb');
            $('#supplierInvoiceModalCheckAll').prop(
                'checked',
                $modalCbs.length > 0 && $modalCbs.length === $modalCbs.filter(':checked').length
            );

            $('#supplierInvoiceModal')
                .data('row', rowId)
                .modal('show');
        }
    });
});

$(document).on('change', '#supplierInvoiceModalCheckAll', function () {
    var on = $(this).prop('checked');
    $('#supplierInvoiceModalBody .ref-batch-cb').prop('checked', on);
});

$(document).on('change', '#supplierInvoiceModalBody .ref-batch-cb', function () {
    var $cbs = $('#supplierInvoiceModalBody .ref-batch-cb');
    $('#supplierInvoiceModalCheckAll').prop(
        'checked',
        $cbs.length > 0 && $cbs.length === $cbs.filter(':checked').length
    );
});

$(document).on('click', '#supplierInvoiceModalApply', function () {
    var rowId = $('#supplierInvoiceModal').data('row');
    var lines = [];
    var sumRef = 0;
    $('#supplierInvoiceModalBody tr[data-ur-ids]').each(function () {
        if (!$(this).find('.ref-batch-cb').prop('checked')) {
            return;
        }
        var bn = String($(this).attr('data-batch-no') || '');
        var aq = parseInt($(this).data('available-qty'), 10) || 0;
        sumRef += aq;
        var idsRaw = String($(this).attr('data-ur-ids') || '');
        var parts = idsRaw.split(',').map(function (s) { return parseInt(String(s || '').trim(), 10) || 0; }).filter(function (n) { return n > 0; });
        for (var pi = 0; pi < parts.length; pi++) {
            if (bn) {
                lines.push({
                    unique_referencenumber_id: parts[pi],
                    batch_no: bn
                });
            }
        }
    });
    if (lines.length === 0) {
        alert('Please select at least one reference line.');
        return;
    }
    var packQty = parseInt($('#quan' + rowId + ' input.quantity').val(), 10) || 0;
    if (packQty > sumRef) {
        alert('Packing quantity (' + packQty + ') is greater than total ref from selected lines (' + sumRef + '). Select more lines or reduce quantity.');
        return;
    }
    var batchesUnique = [];
    lines.forEach(function (L) {
        if (batchesUnique.indexOf(L.batch_no) === -1) {
            batchesUnique.push(L.batch_no);
        }
    });
    $('#ref_selection_json' + rowId).val(JSON.stringify(lines));
    packingSetBatchOrderForRow(rowId, batchesUnique);
    var $sel = $('#select_batch' + rowId);
    if (!$sel.length) {
        return;
    }
    $sel.data('suppress-ref-modal', true);
    $sel.selectpicker('val', batchesUnique);
    $sel.selectpicker('refresh');
    $('#supplierInvoiceModal').modal('hide');
    setTimeout(function () {
        $sel.removeData('suppress-ref-modal');
    }, 0);
});
</script>

<script>
function hideErrorBox() {
    document.getElementById('customErrorBox').style.display = 'none';
}
</script>


@endsection