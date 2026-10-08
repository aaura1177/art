@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
      <h2>Update Shipping</h2>
    </div>

    <form method="POST" action="{{ url('/shipping/view/'.$shipping->id) }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Month') }}</label>
            <select class="form-control" name="monthselect" onchange="getShippingData(this)" disabled>
              @for($i = 0;$i < 12;$i++)
                <option value="{{ date('M Y',strtotime('+'.$i.' month')) }}" {{($shipping->month == date('M Y',strtotime('+'.$i.' month')))?'selected':''}}>{{ date('M Y',strtotime('+'.$i.' month')) }}</option>
              @endfor
            </select>
            <input type="hidden" name="month" value="{{$shipping->month}}" />
          </div>              
        </div>
        <div class="row mx-0 mt-5">
          <h3>India</h3>
        </div>
        <!-- first row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Reference') }}</label>
            <input type="text" class="form-control toUpperCase" name="reference" value="{{$shipping->reference}}" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Container') }}</label>
            <input type="text" class="form-control" name="container" value="{{$shipping->container}}" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Shipping Line') }}</label><a href="{{ url('/shippingLines/create')}}" style="float: right;"> (+New)</a>
            <select type="text" class="selectpicker" data-live-search="true" name="shipping_line_id" required="required">
                <option value="" selected disabled>Select Shipping Line</option>
                <@if(isset($shippingLines)) @foreach($shippingLines as $key => $shippingLine)
                <option value="{{$shippingLine->id}}" {{($shipping->shipping_line_id == $shippingLine->id)?'selected':''}}>
                {{$shippingLine->name}}
                </option>
                @endforeach @endif 
            </select>
          </div>                
        </div>

        <!-- second row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('THC (INR)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">₹</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="thc" name="thc" required=""  value="{{$shipping->thc}}" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('B/L (INR)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">₹</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="bl" name="bl" required=""  value="{{$shipping->bl}}" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Incidental Charges (INR)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">₹</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="incidental_charges" name="incidental_charges" required=""  value="{{$shipping->incidental_charges}}" />
            </div>
          </div>               
        </div>

        <!-- third row -->
        <div class="row mt-3">          
          <div class="col-4">
            <label class="control-label">{{ __('Type') }}</label>
            <select type="text" class="selectpicker" name="type" required="required">
                <option value="" selected disabled>Select Type</option>                
                <option value="Direct" {{($shipping->type == 'Direct')?'selected':''}}>Direct</option>
                <option value="Indirect" {{($shipping->type == 'Indirect')?'selected':''}}>Indirect</option>                
            </select>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Transit Time(Days)') }}</label>
            <input type="number" min="0" step="any" class="form-control" id="transit_time" name="transit_time" required=""  value="{{$shipping->transit_time}}" />
          </div>  
          <div class="col-4">
            <label class="control-label">{{ __('Total (INR)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">₹</span>
              </div>
              <input type="number" value="{{$shipping->total_india}}" class="form-control" name="total_india_read" id="total_india_read" readonly />
              <input type="hidden" value="{{$shipping->total_india}}" name="total_india" id="total_india"/>
            </div>
          </div>        
        </div>
        <div class="row mx-0 mt-5">
          <h3>UK</h3>
        </div>
        <!-- first row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Ocean Freight') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">$</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="ocean_freight" name="ocean_freight" required=""   value="{{$shipping->ocean_freight}}" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Conversion Rate') }}</label>
            <input type="number" min="0" step="any" class="form-control" id="conversion_rate" name="conversion_rate" required=""   value="{{$shipping->conversion_rate}}" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('THC UK') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="thc_uk" name="thc_uk" required=""   value="{{$shipping->thc_uk}}" />
            </div>
          </div>                         
        </div>

        <!-- second row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Handling Charges UK') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="handling_charges" name="handling_charges" required=""   value="{{$shipping->handling_charges}}" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Other Charges UK') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="other" name="other" required=""   value="{{$shipping->other}}" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Total (UK)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">£</span>
              </div>
              <input type="number"  value="{{$shipping->total_uk}}" class="form-control" name="total_uk_read" id="total_uk_read" readonly />
              <input type="hidden"  value="{{$shipping->total_uk}}"name="total_uk" id="total_uk"/>
            </div>
          </div>                        
        </div>

        @if($shipping->container)
        <!--Magnus-->
        <div class="row mx-0 mt-5">
          <h3>Magnus</h3>
        </div>

        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('ETA at Port') }}</label>
            <div class="input-group mb-3">
              <input type="date" class="form-control" id="other" name="eta_at_port" value="{{$shipping->eta_at_port}}" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Booking with Magnus') }}</label>
            <div class="input-group mb-3">
              <select id="magnus" class="form-control" name="magnus" >               
                  <option value="0" {{($shipping->magnus)?'':'selected'}}>No</option>
                  <option value="1" {{($shipping->magnus)?'selected':''}}>Yes</option>
              </select>
            </div>
          </div>
          
          <div class="col-4 magnus_det">
            <label class="control-label">{{ __('Magnus Delivery date and Time') }}</label>
            <div class="input-group mb-3">
              <input type="datetime-local" class="form-control" name="magnus_date" value="{{date('Y-m-d',strtotime($shipping->magnus_date))}}T{{date('H:i:s',strtotime($shipping->magnus_date))}}" />
            </div>
          </div>                        
        </div>
        @endif

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Update Shipping</button>
        </div>

      </div>
    </form>
  </div>

@endsection

@section('footer')
<!-- Script start for cost & volume multiply -->
<script>

    $(function(){
      
      if($("#magnus").val() == 1){
        $('.magnus_det').show();
      }else{
        $('.magnus_det').hide();
      }
      $("#magnus").change(function(){
        if($("#magnus").val() == 1){
          $('.magnus_det').show();
        }else{
          $('.magnus_det').hide();
        }
      });
    });

    $('#thc, #bl, #incidental_charges').change(function () {
        var thc = $('#thc').val();
        var bl = $('#bl').val();
        var ic = $('#incidental_charges').val();
        $('#total_india, #total_india_read').val(Math.round(Number(thc)+Number(bl)+Number(ic)));       
    });
    $('#ocean_freight, #thc_uk, #conversion_rate, #handling_charges, #other').change(function () {
        var ocean_freight = $('#ocean_freight').val();
        var conversion_rate = $('#conversion_rate').val();
        var ocean_freight_pound = Number(ocean_freight) / Number(conversion_rate)
        var thc_uk = $('#thc_uk').val();
        var handling_charges = $('#handling_charges').val();
        var other = $('#other').val();
        $('#total_uk, #total_uk_read').val(Math.round(Number(ocean_freight_pound)+Number(thc_uk)+Number(handling_charges)+Number(other)));       
    });  
</script>
<!-- Script end -->
@endsection