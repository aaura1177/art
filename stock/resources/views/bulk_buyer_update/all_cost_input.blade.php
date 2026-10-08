@extends('layouts.app')

@section('content')
<style>
  .checkbox-custom { transform: scale(2); }
</style>
<div class="mx-2">
  <div class="row mx-0 my-2">
    <h2>Create Bulk All Cost Input</h2>
  </div>
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
  @endif
  @if(session('danger'))
    <div class="alert alert-danger">{{ session('danger') }}</div>
  @endif

  <form method="POST" action="{{ url('/buyer/bulk-all-cost-input-update') }}" id="bulkCostForm">
    @csrf

    <div class="form-group">
      <div class="row mt-3">
        <div class="col-8">
          <h5>Select <strong>Buyer</strong> and <strong>Product(s)</strong>. Cost columns (Cost Price, Admin Cost, Final Cost, volWt) will be recalculated from each product’s stored cost inputs using <strong>dropship packaging only</strong> (wholesale packaging set to 0), then DB will be updated.</h5>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-6">
          <label class="control-label">{{ __('Buyer') }}</label>
          <select class="selectpicker" data-live-search="true" id="main_buyer_id" name="main_buyer_id" required onchange="getProducts(this.value)">
            <option value="" selected disabled>Select Buyer</option>
            @if(isset($buyerData))
              @foreach($buyerData as $buyerDatas)
                <option value="{{ $buyerDatas->id }}">{{ $buyerDatas->c_name }}</option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="col-6">
          <label class="control-label">{{ __('Product') }}</label>
          <select class="selectpicker" data-live-search="true" name="select_product[]" id="select_product" multiple>
            <option value="" selected disabled>Select Buyer first</option>
          </select>
          <div class="checkbox mt-2">
            <label><input type="checkbox" class="checkbox-custom" id="select_all" value="1" /> Select All</label>
          </div>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-4">
          <button type="submit" class="btn btn-primary">Recalculate & Update DB</button>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection

@section('footer')
<script>
  function getProducts(mainBuyerId) {
    if (!mainBuyerId) return;
    $.ajax({
      type: 'GET',
      url: '/buyer/get-product-by-buyer',
      data: { mainBuyerId: mainBuyerId },
      success: function(response) {
        var options = '<option value="" selected disabled>Select Products</option>';
        if (response && response.length > 0) {
          response.forEach(function(item) {
            options += '<option value="' + item.id + '">' + item.code + ' - ' + item.name + '</option>';
          });
        } else {
          options += '<option value="" disabled>No Products</option>';
        }
        $('#select_product').html(options);
        $('#select_product').selectpicker('refresh');
        $('#select_all').prop('checked', false);
      }
    });
  }

  $(document).ready(function() {
    $('#select_all').change(function() {
      var isChecked = $(this).prop('checked');
      $('#select_product').selectpicker('val', isChecked ? $('#select_product option').map(function() { return $(this).val(); }).get().filter(Boolean) : []);
    });
  });
</script>
@endsection
