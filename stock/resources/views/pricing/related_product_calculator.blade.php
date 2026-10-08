@extends('layouts.app')

@section('content')

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Related Product Calculator</h2>
    </div>

    <form method="POST" action="{{ url('/pricing/create') }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">              
              <select type="text" class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="product_id" id="selectProduct" required>
                <option selected>Select Products</option>
                
                <optgroup value="" label="All Products">
                @if(isset($products)) @foreach($products as $key => $product)
                  <option value="{{$product->id}}">
                  {{$product->code}} - {{$product->name}}
                  </option>
                @endforeach @endif
                </optgroup>
              </select>
            </div>            
          </div>
        <!-- Next Section 2-->
         

          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Box Height (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxheight" id="boxheight" step="any" placeholder="in cm" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Box Width (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxwidth" id="boxwidth" step="any" placeholder="in cm" readonly/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Box Depth (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxdepth" id="boxdepth" step="any" placeholder="in cm" readonly />
            </div>
          </div>

          <div class="row mt-3">
              <input type="hidden" min="0" class="form-control" name="newcbm" id="newcbm" step="any" placeholder="m3"  />
              <input type="hidden" min="0" class="form-control" name="dropshipvolume" id="dropshipvolume" step="any" placeholder="m3"  />
              <input type="hidden" min="0" class="form-control" name="finaldeliveredcost" id="finaldeliveredcost" step="any" placeholder="m3"  />
            
            <div class="col-4">
                <label class="control-label">{{ __('Final Cost') }} </label>
                <input type="number" min="0" class="form-control" name="finalcost" id="finalcost" step="any" placeholder="0.00" readonly />
            </div>
          </div>
        <!--/ Next Section 2-->        
        <div>
		  
         </div>
        
        <!-- / Next Section 6-->  
        
      </div>
    </form>
</div>
@endsection

@section('footer')

<script type="text/javascript">

  $(document).ready(function(){

    $.ajax({
        'url': "{{ url('/pricing/data') }}"
    }).done(function(data) {
        if (data) {
            products = data.product;
            hardwares = data.hardwares;
        }
      });     
  });

  var products = [];
  var hardwares = [];  

  function changeDetails(ref){
    var id = $(ref).val();
    var labelName = $('#selectProduct :selected').parent().attr('label');

    $("#boxheight").attr("readonly", false); 
    $("#boxwidth").attr("readonly", false); 
    $("#boxdepth").attr("readonly", false); 
    

    function findHardware(hardware) {
      return hardware.id == hid;
    }
	
	  function findProduct(product) {
      return product.id == id;
    }
    
    function updateDropdownText(hobj, hcobj, hqty, hqobj){
      h_cost = 0;
      h1_text = "Select";
      hobj.find("option").each(function(){
        if($(this).val() == hobj.val() && $(this).val() != 0){
          h1_text = $(this).text();
          hid = $(this).val();
          var hardware = hardwares.find(findHardware);
          hcobj.val(hardware.rate * hqty);
        }
      });
      hqobj.change(function() {
        hid = hobj.val();
        var hardware = hardwares.find(findHardware);
        hcobj.val(hardware.rate * hqobj.val());
      });
      hobj.change(function() {
        hid = hobj.val();
        var hardware = hardwares.find(findHardware);
        hcobj.val(hardware.rate * hqobj.val());
      });
      hobj.parent(".bootstrap-select").find("button").find(".filter-option-inner-inner").text(h1_text);
    } 
    var finaldelcost = [];
    var product = products.find(findProduct);
    token 	=	$("[name^='_token']").val();
    
    var productid = product.id;
    $.ajax({
      'url': "{{ url('/pricing/getnewdelcost/') }}",
      'method': 'POST',
      'data': {"_token":token,"id":productid},
      }).done(function(data) {
        if (data) {
          finaldelcost = data.newDelCost;
          $('#finaldeliveredcost').val(finaldelcost);
         // console.log(finaldelcost);
        }
    });        

    if(labelName != "Temporary Products"){
        var product = products.find(findProduct);        
        
        $("#boxwidth").val(product.boxwidth);
        $("#boxheight").val(product.boxheight);
        $("#boxdepth").val(product.boxdepth);
        $("#wholesalevolume").val(product.wholesalevolume);
        $("#dropshipvolume").val(product.dropshipvolume);
        
        var finalcostval = $('#finaldeliveredcost').val();  
        var currentcbm = $('#dropshipvolume').val();
        var newcbm = 0; 
        finalcost = ( finalcostval/currentcbm  * newcbm )+ 0.25;
        $("#finalcost").val(finalcost.toFixed(4));       
		
    }
     
    var shippingCost2 = $('#shippingCost2').val();
    var StorageCost = $('#StorageCost').val();
    var fobINCost = $('#fobINCost').val();
    var adminCost2 = $('#adminCost2').val();
    var qualityAssurance = $('#qualityAssurance').val();
    var sublanded = parseFloat(shippingCost2)+parseFloat(StorageCost)+parseInt(fobINCost)+parseFloat(adminCost2)+parseFloat(qualityAssurance);
    $('#landedCost').val(sublanded.toFixed(2));
    
  }

$("#boxwidth, #boxheight, #boxdepth").on('change',function() {
    var boxwidth = $("#boxwidth").val();
    var boxheight = $("#boxheight").val();
    var boxdepth = $("#boxdepth").val();
    var currentcbm = $('#dropshipvolume').val();
    var newcbm = (boxwidth*boxheight*boxdepth)/1000000;   
      
    $("#newcbm").val(newcbm);
    
    // (Final delivered cost/Current cbm * new cbm )  + 25%
    //(newDelCost/wholesale volume ) * new cbm) + 25%

    var finalcostval = $('#finaldeliveredcost').val(); 
    console.log(finalcostval+'--'+currentcbm+'--'+newcbm)  ;    
    finalcost = ( finalcostval/currentcbm  * newcbm) * 1.25 ;
    $("#finalcost").val(finalcost.toFixed(4));
});

</script>

@endsection