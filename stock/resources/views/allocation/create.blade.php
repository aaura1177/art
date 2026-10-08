@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Allocation</h2>
    </div>

    <form method="POST" action="{{ url('/allocation/create') }}">
      @csrf
  
      <!-- Form Starts -->
      <div class="form-group"> 
          
            <!-- first row -->
            <div class="row mt-3">  
              <div class="col-4">
                <label class="control-label">{{ __('Contractor') }}</label><a href="{{ url('/contractor/create')}}" style="float: right;"> (+New)</a>
                <select type="text" class="selectpicker" data-live-search="true" name="contractor_id" required="required">
                    <option value="" selected disabled>Select Contractor</option>
                    <@if(isset($contractor)) @foreach($contractor as $key => $contractor)
                    <option value="{{$contractor->id}}">
                    {{$contractor->c_name}}
                    </option>
                    @endforeach @endif 
                </select>
              </div>
              <div class="col-4">
                <label class="control-label">{{ __('Product') }}</label><a href="{{ url('/product/create')}}" style="float: right;"> (+New)</a>
                <select type="text" class="selectpicker" data-live-search="true" class="form-control" onchange="getVolume(this);" name="product_id" required="required">
                    <option value="" selected disabled>Select Product</option>
                    <@if(isset($product)) @foreach($product as $key => $product)
                    <option value="{{$product->id}}">
                    {{$product->code}} - {{$product->name}}
                    </option>
                    @endforeach @endif 
                </select>
                <input type="hidden" class="form-control" name="pVolume" id="pVolume" value="0" />
              </div>
              <div class="col-4">
                <label class="control-label">{{ __('Reference No.') }}</label>
                <input type="text" class="form-control toUpperCase" name="refno" />
              </div>
            </div>

            <!-- second row -->
            <div class="row mt-3">  
              <div class="col-4">
                <label class="control-label">{{ __('Quantity') }}</label>
                <div class="input-group mb-3">
                    <div class="input-group-append">
                        <select type="text" class="form-control" name="vol_unit" required="required">
                            <option value="" selected disabled>Select</option>
                            <option value="No.">No.</option>
                            <option value="Kg.">Kg.</option>
                            <option value="Lt.">Lt.</option>
                            <option value="M3">M3</option>
                        </select>
                    </div>
                    <input type="number" step="any" min="0" class="form-control" value="0.00" name="quantity" id="tqty" required="required" />
                </div>
              </div>
              <div class="col-4">
                <label class="control-label">{{ __('Finish') }}</label>
                <input type="text" class="form-control" name="finish" />
              </div>
              <div class="col-4">
                <label class="control-label">{{ __('Remarks') }}</label>
                <textarea type="text-area" class="form-control" name="remarks"></textarea> 
              </div>
            </div>

            <!-- third row -->
            <div class="row mt-3">  
                <div class="col-4">
                  <label class="control-label">{{ __('Cost/m3') }}</label>
                  <div class="input-group mb-3">
                      <div class="input-group-append">
                        <span class="input-group-text">₹</span>
                      </div>
                      <input type="number" min="0" step="any" class="form-control" id="ucost" name="ucost" required=""  value="0" />
                  </div>
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Volume') }}</label>
                    <input type="number" min="0" step="any" class="form-control" id="tvol" name="tvol" placeholder="" readonly required="required" />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Cost') }}</label>
                    <div class="input-group mb-3">
                      <div class="input-group-append">
                        <span class="input-group-text">₹</span>
                      </div>
                      <input type="number" min="0" class="form-control" name="tcost" id="tcost" readonly />
                    </div>                    
                </div>
            </div>
          
            <div class="row col-4">
              <button type="submit" class="btn btn-primary mt-3">Add Allocation</button>
            </div>
      
      </div>
    </form>  
  </div> 
@endsection

@section('footer')


<!-- Script start for cost & volume multiply -->

<script>
    var products = [];

    $('#ucost, #tqty, #pVolume').change(function () {
        var pvol = $('#pVolume').val();
        var tqty = $('#tqty').val();
        var tvol = (pvol*tqty).toFixed(4);
        $('#tvol').val(tvol);
        var ucost = $('#ucost').val();
        var subtotal = parseFloat(ucost) * parseFloat(tvol);
        var total =subtotal.toFixed(2);
        $('#tcost').val(total);
    });

    function getVolume(ref){
      var id = $(ref).val();

      function findProduct(product) {
        return product.id == id;
      }

      var product = products.find(findProduct);
      $('#pVolume').val(product.volume);

      var pvol = $('#pVolume').val();
      var tqty = $('#tqty').val();
      var tvol = (pvol*tqty).toFixed(4);
      $('#tvol').val(tvol);
      var ucost = $('#ucost').val();
      var subtotal = parseFloat(ucost) * parseFloat(tvol);
      $('#tcost').val(subtotal.toFixed(2));

    }

    $(document).ready(function(){
    
    $.ajax({
        'url': '{{ url("/purchaseOrder/data") }}',
        'method': 'GET'
    }).done(function(data) {
        if (data) {
            products = data.product;
        }
      });
  });

</script>
<!-- Script end -->

@endsection