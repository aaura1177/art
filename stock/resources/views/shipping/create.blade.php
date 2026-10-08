@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Shipping</h2>
    </div>

    <form method="POST" action="{{ url('/shipping/create') }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class="row mt-3">
        <div class="col-4">
    <label class="control-label">{{ __('Month') }}</label>
    <select class="selectpicker" name="month" onchange="getShippingData(this)" id="selMonth">
        @php
            use Carbon\Carbon;
        @endphp
        @for ($i = -5; $i < 12; $i++)
            @php
                $date = Carbon::now()->addMonths($i);
            @endphp
            <option value="{{ $date->format('M Y') }}">{{ $date->format('M Y') }}</option>
        @endfor
    </select>
</div>

          <div class="col-4">
            <label class="control-label">{{ __('Shipping Line') }}</label><a href="{{ url('/shippingLines/create')}}" style="float: right;"> (+New)</a>
            <select type="text" class="form-control" data-live-search="true" name="shipping_line_id" required="required" onchange="getShippingData(this)" id="selShippingLine">
                <option value="" selected disabled>Select Shipping Line</option>
                <@if(isset($shippingLines)) @foreach($shippingLines as $key => $shippingLine)
                <option value="{{$shippingLine->id}}">
                {{$shippingLine->name}}
                </option>
                @endforeach @endif 
            </select>
          </div>   
        </div>
        <div class="row mx-0 mt-5">
          <h3>India</h3>
        </div>
        <!-- first row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Shipment Number') }}</label>
            <input type="text" class="form-control toUpperCase" name="reference" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Container') }}</label>
            <input type="text" class="form-control" name="container" />
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
              <input type="number" min="0" step="any" class="form-control" id="thc" name="thc" required=""  value="0" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('B/L (INR)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">₹</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="bl" name="bl" required=""  value="0" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Incidental Charges (INR)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">₹</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="incidental_charges" name="incidental_charges" required=""  value="0" />
            </div>
          </div>               
        </div>

        <!-- third row -->
        <div class="row mt-3">          
          <div class="col-4">
            <label class="control-label">{{ __('Type') }}</label>
            <select type="text" class="form-control" name="type" required="required">
                <option value="" selected disabled>Select Type</option>                
                <option value="Direct">Direct</option>
                <option value="Indirect">Indirect</option>                
            </select>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Transit Time(Days)') }}</label>
            <input type="number" min="0" step="any" class="form-control" id="transit_time" name="transit_time" required=""  value="0" />
          </div>  
          <div class="col-4">
            <label class="control-label">{{ __('Total (INR)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">₹</span>
              </div>
              <input type="number" value="0" class="form-control" name="total_india_read" id="total_india_read" readonly />
              <input type="hidden" name="total_india" id="total_india"/>
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
              <input type="number" min="0" step="any" class="form-control" id="ocean_freight" name="ocean_freight" required=""  value="0" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Conversion Rate') }}</label>
            <input type="number" min="0" step="any" class="form-control" id="conversion_rate" name="conversion_rate" required=""  value="0" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('THC UK') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="thc_uk" name="thc_uk" required=""  value="0" />
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
              <input type="number" min="0" step="any" class="form-control" id="handling_charges" name="handling_charges" required=""  value="0" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Other Charges UK') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" min="0" step="any" class="form-control" id="other" name="other" required=""  value="0" />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Total (UK)') }}</label>
            <div class="input-group mb-3">
              <div class="input-group-append">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" value="0" class="form-control" name="total_uk_read" id="total_uk_read" readonly />
              <input type="hidden" name="total_uk" id="total_uk"/>
            </div>
          </div>                        
        </div>

        <!--Magnus-->
        <div class="row mx-0 mt-5">
          <h3>Magnus</h3>
        </div>

        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('ETA at Port') }}</label>
            <div class="input-group mb-3">
              <input type="date" class="form-control" id="other" name="eta_at_port"  />
            </div>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Booking with Magnus') }}</label>
            <div class="input-group mb-3">
              <select id="magnus" class="form-control" name="magnus" >               
                  <option value="0">No</option>
                  <option value="1">Yes</option>
              </select>
            </div>
          </div>
          
          <div class="col-4 magnus_det">
            <label class="control-label">{{ __('Magnus Delivery date and Time') }}</label>
            <div class="input-group mb-3">
              <input type="datetime-local" class="form-control" name="magnus_date" />
            </div>
          </div>                        
        </div>

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Shipping</button>
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
    function getShippingData(obj){
      month = $('#selMonth').val();
      shipping_line = $('#selShippingLine').val();
      token = $('form').find('input[name=_token]').val();
      if(month != null && shipping_line != null){
        $.ajax({
          url:"{{ url('/shipping/getShippingData') }}",
          type:'POST',
          data:{'month':month,'shipping_line_id':shipping_line,'_token':token},
          success:function(res){
            if(res.res.exist){
              $('form').find('input').each(function(){
                name = $(this).attr('name');
                if(res.res.body[name]){
                  $(this).val(res.res.body[name]);
                }
              });
              $('form').find('select').each(function(){
                name = $(this).attr('name');
                if(res.res.body[name]){
                  $(this).val(res.res.body[name]);
                }
              });
              $('#total_india_read').val($('#total_india').val());
              $('#total_uk_read').val($('#total_uk').val());
            }
          }
        });
      }
    }
</script>
<!-- Script end -->
@endsection