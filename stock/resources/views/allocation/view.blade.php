@extends('layouts.app')

@section('content')
    

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Allocation</h2>
    </div>

    <form method="POST" action="{{ url('/allocation/view/'.$allocation->id) }}">
      @csrf

      <div class="form-group"> 
        
        <!-- first row -->
        <div class="row mt-3">  
          <div class="col-4">
            <label class="control-label">{{ __('Allocation Code') }}</label>
            <input type="text" class="form-control" name="code" required="required" value="{{$allocation->id}}" disabled="">
          </div>
        </div>
          
          <!-- first row -->
        <div class="row mt-3">  
          <div class="col-4">
            <label class="control-label">{{ __('Contractor') }}</label>
            <select type="text" class="form-control" name="contractor_id" required="required">
                @if(isset($contractor)) @foreach($contractor as $key => $contractor)
                  <option value="{{$contractor->id}}" {{($allocation->contractor_id == $contractor->id)?'selected':''}}>
                  {{$contractor->c_name}}
                  </option>
                  @endforeach @endif  
            </select>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Product') }}</label>
            <select type="text" class="selectpicker" data-live-search="true" class="form-control" onchange="getVolume(this);" name="product_id" required="required">
                @if(isset($product)) @foreach($product as $key => $product)
                <option value="{{$product->id}}" {{($allocation->product_id == $product->id)?'selected':''}}>
                {{$product->code}} - {{$product->name}}
                </option>
                @endforeach @endif 
            </select>
            <input type="hidden" class="form-control" name="pVolume" id="pVolume" value="0" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Reference No.') }}</label>
            <input type="text" class="form-control toUpperCase" name="refno" value="{{$allocation->refno}}" />
          </div>
        </div>

        <!-- second row -->
        <div class="row mt-3">  
          <div class="col-4">
            <label class="control-label">{{ __('Quantity') }}</label>
            <div class="input-group mb-3">
                <div class="input-group-append">
                    <select type="text" class="form-control" name="vol_unit" required="required">
                        <option value="" disabled>Select</option>
                        <option value="No." {{($allocation->vol_unit == "No.")?'selected':''}}>No.</option>
                        <option value="Kg." {{($allocation->vol_unit == "Kg.")?'selected':''}}>Kg.</option>
                        <option value="Lt." {{($allocation->vol_unit == "Lt.")?'selected':''}}>Lt.</option>
                        <option value="M3" {{($allocation->vol_unit == "M3")?'selected':''}}>M3</option>
                    </select>
                </div>
                <input type="number" min="0" step="any" class="form-control" name="quantity" id="tqty" required="required" value="{{$allocation->quantity}}" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Finish') }}</label>
            <input type="text" class="form-control" name="finish" value="{{$allocation->finish}}" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Remarks') }}</label>
            <textarea type="text-area" class="form-control" name="remarks">{{$allocation->remarks}}</textarea> 
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
                  <input type="number" min="0" step="0.01" class="form-control" id="ucost" name="ucost" value="{{$allocation->ucost}}" />
              </div>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Total Volume') }}</label>
                <input type="number" min="0" step="any" class="form-control" id="tvol" name="tvol" placeholder="" required="required" value="{{$allocation->tvol}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Total Cost') }}</label>
                <div class="input-group mb-3">
                  <div class="input-group-append">
                  <span class="input-group-text">₹</span>
                  </div>
                  <input type="number" min="0" class="form-control" name="tcost" id="tcost" readonly value="{{$allocation->tcost}}" />
                </div>                    
            </div>
        </div>
      </div>
          
      <div class="row col-4">
        <button type="submit" class="btn btn-primary mt-3">Update allocation</button>
      </div>
    
    </div>      
        
    </form>  
    </div> 

@endsection

@section('footer')

<!-- Script start for cost & volume multiply -->
<script>

    var initialtvol = $('#tvol').val();
    var initialtqty = $('#tqty').val();
    var initialpvol = initialtvol/initialtqty;
    $('#pVolume').val(initialpvol);
    
    var products = [];
    $('#ucost, #tqty, #tvol').change(function () {
        var pvol = $('#pVolume').val();
        var tqty = $('#tqty').val();
        var tvol = (pvol*tqty).toFixed(4);
        $('#tvol').val(tvol);
        var ucost = $('#ucost').val();
        var subtotal = parseFloat(ucost) * parseFloat(tvol);
        $('#tcost').val(subtotal.toFixed(2));
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
        'url': "{{ url('/purchaseOrder/data') }}",
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