@extends('layouts.app')

@section('content')
<div class="mx-2">
  <div class="row mx-0 my-2">
    <h2>Create Carton Swapping</h2>
  </div>

  <form method="POST" action="{{ url('/invoice/createcartonswapping') }}" id="cartonSwapForm">
    @csrf
    <div class="form-group">
      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">Reference No.</label>
          <input type="text" class="form-control toUpperCase" name="invoice_no" required />
        </div>
      </div>

      <div class="row mt-5 my-3">
        <div class="col-6">
          <h5>Carton Rows</h5>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th style="min-width: 250px;">Product Out</th>
              <th style="min-width: 250px;">Product In</th>
              <th style="min-width: 140px;">Carton Type</th>
              <th style="min-width: 120px;">Qty</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="cartonTable"></tbody>
        </table>
      </div>

      <div class="row mt-2">
        <div class="col">
          <input type="button" id="addCartonRow" class="btn btn-primary" value="Add Row" />
        </div>
      </div>

      <div class="col-4">
        <button type="submit" id="submitBtn" class="btn btn-primary mt-3">Add Carton Swapping</button>
      </div>
    </div>
  </form>
</div>
@endsection

@section('footer')
<script type="text/javascript">
  var rowNo = 0;
  var products = @json($products);

  function productOptions() {
    var html = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';
    products.forEach(function (p) {
      var code = p.product && p.product.code ? p.product.code : '';
      var name = p.product && p.product.name ? p.product.name : '';
      html += '<option value="' + p.product_id + '">' + code + ' - ' + name + '</option>';
    });
    return html;
  }

  function addRow() {
    rowNo += 1;
    var html = '';
    html += '<tr>';
    html += '<td><select class="selectpicker form-control" data-live-search="true" name="po[' + rowNo + '][product]" required>' + productOptions() + '</select></td>';
    html += '<td><select class="selectpicker form-control" data-live-search="true" name="po[' + rowNo + '][swapped_with]" required>' + productOptions() + '</select></td>';
    html += '<td><select class="form-control" name="po[' + rowNo + '][carton_type]" required><option value="" selected disabled>-- SELECT --</option><option value="box_1_qty">Box 1</option><option value="box_2_qty">Box 2</option></select></td>';
    html += '<td><input type="number" min="1" class="form-control" name="po[' + rowNo + '][quantity]" value="1" required></td>';
    html += '<td><button type="button" class="close" onclick="deleteRow(this);" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
    html += '</tr>';
    $('#cartonTable').append(html);
    $('.selectpicker').selectpicker();
  }

  function deleteRow(btn) {
    $(btn).closest('tr').remove();
  }

  $('#addCartonRow').click(function () {
    addRow();
  });

  $('#cartonSwapForm').on('submit', function (e) {
    if ($('#cartonTable tr').length < 1) {
      alert('Add at least one carton row.');
      e.preventDefault();
      return;
    }
    $('#submitBtn').prop('disabled', true);
  });

  $(document).ready(function () {
    addRow();
  });
</script>
@endsection
