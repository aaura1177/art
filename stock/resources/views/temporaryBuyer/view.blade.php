@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Temporary Buyer</h2>
    </div>

    <form method="POST" action="{{ url('/temporaryBuyer/view/'.$tempBuyer->id)}}" id="tempBuyerForm">
      @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Buyer Code') }}</label>
              <input type="text" class="form-control toUpperCase" name="code" value="{{$tempBuyer->code}}" disabled />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Company Name') }}</label>
              <input type="text" class="form-control" name="c_name" value="{{$tempBuyer->c_name}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Name') }}</label>
              <input type="text" class="form-control" name="name" value="{{$tempBuyer->name}}" />
            </div>
          </div>  

          <!-- second row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 1') }}</label>
              <input type="text" class="form-control" name="address1" value="{{$tempBuyer->address1}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 2') }}</label>
              <input type="text" class="form-control" name="address2" value="{{$tempBuyer->address2}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('City') }}</label>
              <input type="text" class="form-control" name="city" value="{{$tempBuyer->city}}" />
            </div>
          </div>
              
          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('State') }}</label>
              <input type="text" class="form-control" name="state" value="{{$tempBuyer->state}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Country') }}</label>
              <select class="form-control" name="country" required="required" >
                <?php
                foreach ($countriesArray as $key => $countriesArrayGet) { ?>
                  <option value="{{$key}}" {{$key == $tempBuyer->country ? 'selected' : ''}}>{{$countriesArrayGet}}</option>
               <?php }
                ?>
               </select>
            </div>
            
            <div class="col-4">
              <label class="control-label">{{ __('Postcode') }}</label>
              <input type="text" class="form-control" name="postcode" value="{{$tempBuyer->postcode}}" />
            </div>  
          </div>

          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Profit (%)') }}</label>
              <input type="number" class="form-control" name="profit" step="any" value="{{$tempBuyer->profit}}" />
            </div>
          </div>
		  
		  <div class="row mx-0 mt-5">
			<h4>Pricing Fields</h4>
		</div>
		  
		  <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Admin Profit (%)') }}</label>
          <input type="number" class="form-control " name="adminProfit" value="{{$tempBuyer->admin_profit}}"/>
        </div>
            <div class="col-4">
              <label class="control-label">{{ __('Final Price %') }}</label>
              <input type="number" class="form-control" name="final_price_percent" value="{{$tempBuyer->final_price_percent}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Type of Courier') }}</label>
                <select type="text" class="selectpicker allCost" data-live-search="true" name="courierType" id="courierType" onchange="changeCourierRate(this)" required>
                  <option selected value="">Select Courier</option>
                  @if(isset($couriers)) @foreach($couriers as $key => $courier)
                    <option value="{{$courier->id}}" data-rate="{{$courier->rate}}" data-weight="{{$courier->fixed_rate_weight}}" data-perkg="{{$courier->rate_per_kg}}" data-fper="{{$courier->fuel_charge_percent}}"  {{($tempBuyer->courierType == $courier->id)?'selected':''}}>
                    {{$courier->name}}
                    </option>
                  @endforeach @endif
                  <option {{($tempBuyer->courierType === 0)?'selected':''}} value="0">No Courier</option>
                </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Cost Adjustments') }}</label>
              <input type="number" class="form-control" name="cost_adjustments" value="{{$tempBuyer->cost_adjustments}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Solid wood- 10%') }}</label>
              <input type="number" class="form-control" name="tariff_solid_wood_percent" step="any" value="{{ $tempBuyer->tariff_solid_wood_percent ?? 0 }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Upholstered Furniture - 25%') }}</label>
              <input type="number" class="form-control" name="tariff_upholstered_percent" step="any" value="{{ $tempBuyer->tariff_upholstered_percent ?? 0 }}" />
            </div>
          </div>

          <div class="row mx-0 mt-4">
            <h4>Invoice Buyers (FOB pricing)</h4>
          </div>
          <div class="row mt-3">
            <div class="col-8">
              <label class="control-label">{{ __('Assign invoice buyers') }}</label>
              <select class="selectpicker" data-live-search="true" name="invoice_buyer_ids[]" id="invoiceBuyerSelect" multiple data-actions-box="true" title="Select invoice buyers">
                @foreach($invoiceBuyers ?? [] as $invoiceBuyer)
                  <option value="{{ $invoiceBuyer->id }}"
                    data-temp-buyer-id="{{ $invoiceBuyer->temp_buyer_id }}"
                    data-temp-buyer-name="{{ $tempBuyerNames[$invoiceBuyer->temp_buyer_id] ?? '' }}"
                    {{ in_array($invoiceBuyer->id, old('invoice_buyer_ids', $assignedBuyerIds ?? [])) ? 'selected' : '' }}>
                    {{ $invoiceBuyer->c_name }} ({{ $invoiceBuyer->code }})
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Selected buyers use this temp buyer's pre-tariff FOB India Cost on export invoices.</small>
            </div>
          </div>
          
          <div class="row col-4">
            <button type="submit" class="btn btn-primary mt-3">Update Temporary Buyer</button>
          </div>

      </div>
    </form>  
  </div> 

  <script>
    document.getElementById('tempBuyerForm').addEventListener('submit', function (e) {
      var currentTempBuyerId = '{{ $tempBuyer->id }}';
      var select = document.getElementById('invoiceBuyerSelect');
      var conflicts = [];

      Array.prototype.forEach.call(select.selectedOptions, function (opt) {
        var owner = opt.getAttribute('data-temp-buyer-id');
        if (owner && owner !== currentTempBuyerId) {
          var ownerName = opt.getAttribute('data-temp-buyer-name') || ('#' + owner);
          conflicts.push(opt.text.trim() + ' — currently assigned to ' + ownerName);
        }
      });

      if (conflicts.length > 0) {
        var message = 'The following invoice buyer(s) are already assigned to another temp buyer and will be moved to this one:\n\n'
          + conflicts.join('\n')
          + '\n\nDo you want to continue?';
        if (!confirm(message)) {
          e.preventDefault();
        }
      }
    });
  </script>

@endsection