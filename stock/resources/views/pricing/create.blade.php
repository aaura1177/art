@extends('layouts.app')

@section('content')
<style>
  .line_content {
    border-top: 1px solid;
    margin-top: 50px;
  }

  .adjust_delete_btn {
    width: fit-content;
    margin-left: auto;
  }
</style>
<div class="mx-2">
  <div class="row mx-0 my-2">
    <h2>Add Pricing</h2>
  </div>

  <form method="POST" action="{{ url('/pricing/create') }}">
    @csrf

    <!-- Form Starts -->
    <div class="form-group">

      <!-- first row -->
      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Products') }}</label> <a href="{{ url('/temporaryProduct/create')}}" target="_blank" style="float: right;"> (+New)</a>
          <select type="text" class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="product_id" id="selectProduct" required>
            <option selected>Select Products</option>
            <optgroup value="" label="Temporary Products">
              @if(isset($tempProducts)) @foreach($tempProducts as $key => $tempProduct)
              <option value="{{$tempProduct->id}}">
                {{$tempProduct->code}} - {{$tempProduct->name}}
              </option>
              @endforeach @endif
            </optgroup>
            <optgroup value="" label="Actual Products">
              @if(isset($products)) @foreach($products as $key => $product)
              <option value="{{$product->id}}">
                {{$product->code}} - {{$product->name}}
              </option>
              @endforeach @endif
            </optgroup>
          </select>
          <div id="productPricingAlert" class="alert alert-warning mt-2 d-none" role="alert"></div>
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Buyer') }}</label> <a href="{{ url('/temporaryBuyer/create')}}" target="_blank" style="float: right;"> (+New)</a>
          <select type="text" class="selectpicker" data-live-search="true" name="buyer_id" id="buyer_id" required>
            <option value="" selected disabled>Select Buyer</option>
            @if(isset($tempBuyers)) @foreach($tempBuyers as $key => $tempBuyer)
            <option value="{{$tempBuyer->id}}" data-country="{{$tempBuyer->country}}">
              {{$tempBuyer->c_name}}
            </option>
            @endforeach @endif
          </select>
        </div>
      </div>
      <input type="hidden" id="default_country" name="default_country">
      <!-- second row -->
      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Valid From') }}</label>
          <input type="date" class="form-control" name="startDate" value="<?php echo date('Y-m-d'); ?>" />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Valid Till') }}</label>
          <input type="date" class="form-control" name="endDate" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" />
        </div>
        <input type="hidden" name="productType" id="productType" value="" />
      </div>

      <!-- third row -->
      <div class="row mt-3">
        <div class="col-8">
          <label class="control-label">{{ __('Reference') }}</label>
          <textarea class="form-control" name="remarks" rows="2"></textarea>
        </div>
      </div>


      <!-- Next Section 2-->

      <div class="row mx-0 mt-5">
        <h2>Product Details</h2>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <img class="img-thumbnail img-fluid product-img-100" id="imgURL" src="{{ asset('uploads/product/' . $product->imageURL) }}" alt="Product Image" />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Product Name') }}</label>
          <input type="text" id="name" class="form-control" name="name" disabled />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Product Height (cm)') }}</label>
          <input type="number" min="0" class="form-control" name="productheight" id="height" step="any" placeholder="in cm" readonly />
        </div>


        <div class="col-4">
          <label class="control-label">{{ __('Product Width (cm)') }}</label>
          <input type="number" min="0" class="form-control" name="productwidth" id="width" step="any" placeholder="in cm" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Product Depth (cm)') }}</label>
          <input type="number" min="0" class="form-control" name="productdepth" id="depth" step="any" placeholder="in cm" readonly />
        </div>
        <input type="hidden" min="0" class="form-control" name="" id="gross_weight" step="any" placeholder="in cm" readonly />

      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Box Height (cm)') }}</label>
          <input type="number" min="0" class="form-control" name="boxheight" id="boxheight" step="any" placeholder="in cm" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Box Width (cm)') }}</label>
          <input type="number" min="0" class="form-control" name="boxwidth" id="boxwidth" step="any" placeholder="in cm" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Box Depth (cm)') }}</label>
          <input type="number" min="0" class="form-control" name="boxdepth" id="boxdepth" step="any" placeholder="in cm" readonly />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('WholeSale Volume') }} <span>(m<sup>3</sup>)</span></label>
          <input type="number" min="0" class="form-control" name="wholesalevolume" id="wholesalevolume" step="any" placeholder="m3" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('DropShip Volume') }} <span>(m<sup>3</sup>)</span></label>
          <input type="number" min="0" class="form-control" name="dropshipvolume" id="dropshipvolume" step="any" placeholder="m3" readonly />
        </div>
      </div>
      <!--/ Next Section 2-->

      <!-- Next Section Cost 3-->
      <div class="row mx-0 mt-5">
        <h2>Cost (INR)</h2>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Buying Cost') }}</label>
          <input type="number" min="0" class="form-control cost allCost" name="buyingCost" id="buyingCost" step="any" value="0" required />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Fabric Type') }}</label>
          <input type="text" class="form-control" name="fabricCost" value="" />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Tapestry Consumed (m)') }}</label>
          <input type="number" min="0" class="form-control tapestryCalculate" id="tapestryConsumed" name="tapestryConsumed" step="any" value="0" required />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Tapestry Unit Cost') }}</label>
          <input type="number" min="0" class="form-control tapestryCalculate" id="tapestryUnitCost" name="tapestryUnitCost" step="any" value="0" required />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Tapestry Cost') }}</label>
          <input type="number" min="0" class="form-control cost allCost" id="tapestryCost" name="tapestryCost" step="any" value="0" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Filler Cost') }}</label>
          <input type="number" min="0" class="form-control cost allCost" name="fillerCost" id="fillerCost" step="any" value="0" required />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Labour Cost') }}</label>
          <input type="number" min="0" class="form-control cost allCost" name="labourCost" id="labourCost" step="any" value="0" required />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Hardware 1, Quantity, Cost') }}</label>
          <div class="row">
            <div class="col-6">
              <select type="text" class="selectpicker" data-live-search="true" name="hardware1" id="hardware1">
                <option value="0">Select</option>
                @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                <option value="{{$hardware->id}}">
                  {{$hardware->name}}
                </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control" name="hardware1_quantity" id="hardware1_quantity" step="any" placeholder="Q" value="{{ old('hardware1_quantity') }}" />
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control cost allCost" name="hardwareCost1" step="any" value="0" id="hardware1_cost" />
            </div>
          </div>

        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Hardware 2, Quantity, Cost') }}</label>
          <div class="row">
            <div class="col-6">
              <select type="text" class="selectpicker" data-live-search="true" name="hardware2" id="hardware2">
                <option value="0">Select</option>
                @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                <option value="{{$hardware->id}}">
                  {{$hardware->name}}
                </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control" name="hardware2_quantity" id="hardware2_quantity" step="any" placeholder="Q" value="{{ old('hardware2_quantity') }}" />
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control cost allCost" name="hardwareCost2" step="any" value="0" id="hardware2_cost" />
            </div>
          </div>

        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Hardware 3, Quantity, Cost') }}</label>
          <div class="row">
            <div class="col-6">
              <select type="text" class="selectpicker" data-live-search="true" name="hardware3" id="hardware3">
                <option value="0">Select</option>
                @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                <option value="{{$hardware->id}}">
                  {{$hardware->name}}
                </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control" name="hardware3_quantity" id="hardware3_quantity" step="any" placeholder="Q" value="{{ old('hardware3_quantity') }}" />
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control cost allCost" name="hardwareCost3" step="any" value="0" id="hardware3_cost" />
            </div>
          </div>
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Hardware 4, Quantity, Cost') }}</label>
          <div class="row">
            <div class="col-6">
              <select type="text" class="selectpicker" data-live-search="true" name="hardware4" id="hardware4">
                <option value="0">Select</option>
                @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                <option value="{{$hardware->id}}">
                  {{$hardware->name}}
                </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control" name="hardware4_quantity" id="hardware4_quantity" step="any" placeholder="Q" value="{{ old('hardware4_quantity') }}" />
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control cost allCost" name="hardwareCost4" step="any" value="0" id="hardware4_cost" />
            </div>
          </div>
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Hardware 5, Quantity, Cost') }}</label>
          <div class="row">
            <div class="col-6">
              <select type="text" class="selectpicker" data-live-search="true" name="hardware5" id="hardware5">
                <option value="0">Select</option>
                @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                <option value="{{$hardware->id}}">
                  {{$hardware->name}}
                </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control" name="hardware5_quantity" id="hardware5_quantity" step="any" placeholder="Q" value="{{ old('hardware5_quantity') }}" />
            </div>
            <div class="col-3">
              <input type="number" min="0" class="form-control cost allCost" name="hardwareCost5" step="any" value="0" id="hardware5_cost" />
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Polish Unit Cost') }}</label>
          <input type="number" min="0" class="form-control" name="pUnitCost" id="pUnitCost" step="any" value="120000" required />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Polish Cost') }}</label>
          <input type="number" min="0" class="form-control cost allCost" name="polishCost" id="polishCost" step="any" value="0" readonly />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <div>
            <label class="control-label">{{ __('WholeSale Packaging Cost') }}</label>
            <input type="checkbox" id="wSPackageCostCheck" style="width: 20px; height: 17px; cursor: pointer;" />
          </div>
          <input type="number" min="0" class="form-control allCost" name="wSPackageCost" id="wSPackageCost" step="any" disabled />
        </div>
        <div class="col-4">
          <div>
            <label class="control-label">{{ __('DropShip Packaging Cost') }}</label>
            <input type="checkbox" id="dSPackageCostCheck" style="width: 20px; height: 17px; cursor: pointer;" checked />
          </div>
          <input type="number" min="0" class="form-control cost allCost" name="dSPackageCost" id="dSPackageCost" step="any" value="0" />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('India Shipping Cost') }}</label>
          <input type="number" min="0" class="form-control cost allCost" name="shippingCost" id="shippingCost" step="any" value="0" required />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Cost Price') }}</label>
          <input type="number" min="0" class="form-control" name="costPrice" id="costPrice" step="any" value="0" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Admin Cost (%)') }}</label>
          <input type="number" min="0" class="form-control allCost" name="adminCostPercent" id="adminCostPercent" step="any" value="{{$settings_option['admin_cost_percentage']}}" required />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Admin Cost') }}</label>
          <input type="number" min="0" class="form-control profitCost" name="adminCost" id="adminCost" step="any" value="0" readonly />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Profit (%)') }}</label>
          <input type="number" min="0" class="form-control profitCost allCost" name="profitPercent" id="profitCost" step="any" value="{{$settings_option['profit_percentage']}}" required />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Final Cost') }}</label>
          <input type="number" min="0" class="form-control" name="finalCost" id="finalCost" step="any" value="0" readonly />
        </div>
      </div>

      <div class="row mx-0 mt-4">
        <h4>India Destination Cost</h4>
      </div>
      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">Shipping Cost India</label>
          <input type="number" min="0" class="form-control india-cost-input" name="indiaShippingCost" id="indiaShippingCost" step="any" value="0" />
        </div>
        <div class="col-4">
          <label class="control-label">India Cost Price</label>
          <input type="number" min="0" class="form-control" name="indiaCostPrice" id="indiaCostPrice" step="any" value="0" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">India Admin Cost (%)</label>
          <input type="number" min="0" class="form-control india-cost-input" name="indiaAdminCostPercent" id="indiaAdminCostPercent" step="any" value="{{ $settings_option['india_admin_cost_percentage'] ?? $settings_option['admin_cost_percentage'] }}" />
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">India Admin Cost</label>
          <input type="number" min="0" class="form-control" name="indiaAdminCost" id="indiaAdminCost" step="any" value="0" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">India Profit (%)</label>
          <input type="number" min="0" class="form-control india-cost-input" name="indiaProfitPercent" id="indiaProfitPercent" step="any" value="{{ $settings_option['india_profit_percentage'] ?? $settings_option['profit_percentage'] }}" />
        </div>
        <div class="col-4">
          <label class="control-label">India Final Cost</label>
          <input type="number" min="0" class="form-control" name="indiaFinalCost" id="indiaFinalCost" step="any" value="0" readonly />
        </div>
      </div>

      <!--  Next Section 5-->
      <div class="row mx-0 mt-5">
        <h2>Storage Cost</h2>
      </div>

      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Box Wt. (kg)') }}</label>
          <input type="number" min="0.001" class="form-control" name="boxWt" id="boxWt" step="any" value="" required />
        </div>
        <input type="hidden" name="volWt" id="volWt" step="any" value="0" />
        <input type="hidden" name="volWt_lbs" id="volWt_lbs" step="any" value="0" />
      </div>

      <!-- / Next Section 5-->

      <div class="row mx-0 mt-5">
        <h2>Fulfilment Cost</h2>
      </div>

      <div id="deliveryCostSection">
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Add More Buyer') }}</label> <a href="{{ url('/temporaryBuyer/create')}}" target="_blank" style="float: right;"> (+New)</a>
            <select type="text" class="selectpicker select_action" data-live-search="true" name="buyer_id2" id="buyer_id2_0" onchange="fulFillmentCostBuyer2Calculation(this.value,0)" required>
              <option value="" selected>Select Buyer</option>
              @if(isset($tempBuyers)) @foreach($tempBuyers as $key => $tempBuyer)
              <option value="{{$tempBuyer->id}}">
                {{$tempBuyer->c_name}}
              </option>
              @endforeach @endif
            </select>
          </div>
          <input type="hidden" id="default_country_0" name="default_country_0">
          <div class="col-4">
            <label class="control-label">{{ __('Country of Destination') }}</label>
            <input type="text" class="form-control" name="destination" id="destination_0" value="" readonly />
          </div>
          <div class="col-4" id="volWtKgCol_0" style="display:none;">
            <label class="control-label">Volumetric Wt. (kg)</label>
            <input type="number" min="0" class="form-control" name="volWt1" id="volWt1_0" step="any" value="0" readonly />
          </div>

          <div class="col-4" id="volWtLbsCol_0" style="display:none;">
            <label class="control-label">Volumetric Wt. (LBS)</label>
            <input type="number" min="0" class="form-control" name="volWt_lbs1" id="volWt_lbs1_0" step="any" value="0" readonly />
          </div>


        </div>
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Currency') }}</label>
            <input type="text" class="form-control" id="changecurrency_0" name="currency" placeholder="Currency" readonly>

          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Conversion Rate') }}</label>
            <input type="number" min="0" class="form-control call_conversion" name="converRate" id="converRate_0" step="any" value="0" required onchange="allCostNew(null,0)" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('FOB India Cost') }}</label>
            <input type="number" min="0" class="form-control" name="fobINCost" id="fobINCost_0" step="any" value="0" readonly />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Tariff') }}</label>
            <input type="number" min="0" class="form-control calculate_fulfillment_cost" name="tariff_percent" id="tariff_percent_0" step="any" value="0" onchange="allCostNew(null,0)" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Tariff-Adjusted FOB Cost') }}</label>
            <input type="number" min="0" class="form-control" name="final_fob_in_cost" id="final_fob_in_cost_0" step="any" value="0" readonly />
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Shipping Cost') }}</label>
            <input type="number" min="0" class="form-control allCost" name="shippingCost2" id="shippingCost2_0" step="any" value="0" onchange="allCostNew(null,0)" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Storage Cost(1 month)') }}</label>
            <input type="number" min="0" class="form-control allCost" name="StorageCost" id="StorageCost_0" step="any" value="0" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Box weight (buyer units)') }}</label>
            <input type="text" class="form-control bg-light" id="boxWt_display_0" readonly value="0" autocomplete="off" />
            <small class="text-muted d-block mt-1" id="boxWt_unit_hint_0"></small>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Outbound Charges') }}</label>
            <input type="number" min="0" class="form-control calculate_fulfillment_cost" name="adminCost2" id="adminCost2_0" step="any" value="0" required onchange="allCostNew(null,0)" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Quality Assurance') }}</label>
            <input type="number" min="0" class="form-control " name="qualityAssurance" id="qualityAssurance_0" step="any" value="0" required onchange="allCostNew(null,0)" />
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Landed Cost') }}</label>
            <input type="number" min="0" class="form-control" name="landedCost" id="landedCost_0" step="any" value="0" readonly />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Admin Profit (%)') }}</label>
            <input type="number" min="0" class="form-control " name="adminProfit" id="adminProfit_0" step="any" value="0" required onchange="allCostNew(null,0)" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Admin Price') }}</label>
            <input type="number" min="0" class="form-control" name="adminPrice" id="adminPrice_0" step="any" value="0" readonly />
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Final Price (%)') }}</label>
            <input type="number" min="0" class="form-control " name="finalPricePer" id="finalPricePer_0" step="any" value="0" required onchange="allCostNew(null,0)" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Final Price') }}</label>
            <input type="number" min="0" class="form-control" name="finalPrice" id="finalPrice_0" step="any" value="0" readonly />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Type of Courier') }}</label>
            <select type="text" class="selectpicker " data-live-search="true" name="courierType" id="courierType_0" onchange="changeCourierRate(this,0)">
              <option selected>Select Courier</option>
              @if(isset($couriers)) @foreach($couriers as $key => $courier)
              <option value="{{$courier->name}}" data-rate="{{$courier->rate}}" data-weight="{{$courier->fixed_rate_weight}}" data-perkg="{{$courier->rate_per_kg}}" data-fper="{{$courier->fuel_charge_percent}}" data-bands="{{ json_encode($courier->weight_rate_tiers) }}">
                {{$courier->name}}
              </option>
              @endforeach @endif
            </select>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-4" hidden>
            <label class="control-label">{{ __('Courier Cost') }}</label>
            <input type="number" min="0" class="form-control " name="courierCost1" id="courierCost_0" step="any" value="0" required onchange="allCostNew(null,0)" />
          </div>
          <div class="col-4">
            {{-- <label class="control-label">{{ __('SurCharge Custom Condition') }}</label> --}}
            <label class="control-label">{{ __('Courier Cost') }}</label>
            <input type="number" min="0" class="form-control " name="courierCost" id="custom_surcharge_0" step="any" value="0" onchange="allCostNew(null,0)" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Delivery Cost') }}</label>
            <input type="number" min="0" class="form-control " name="deliveryCost" id="deliveryCost_0" step="any" value="0" readonly required />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Cost Adjustment (%)') }}</label>
            <input type="number" min="0" class="form-control " name="adjustment" id="adjustment_0" step="any" value="0" required onchange="allCostNew(null,0)" />
          </div>
        </div>


        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Final Delivered Cost') }}</label>
            <input type="number" min="0" class="form-control" name="newDelCost" id="newDelCost_0" step="any" value="0" readonly />
          </div>
        </div>
      </div>
      <input type="hidden" id="incrementFlag" name="incrementFlag" value="0">
      <div class="add_more"></div>
      <div class="row mx-0 mt-5">
        <h2>Add New Buyer</h2>
      </div>

      <div>
        <div class="row mt-3 align-items-end">
          <div class="col-2">
            <select class="selectpicker mandatory_add_multi" data-live-search="true" name="buyer_id2_clone" id="buyer_id2_clone">
              <option value="" selected>Select Buyer</option>
              @if(isset($tempBuyers)) @foreach($tempBuyers as $key => $tempBuyer)
              <option value="{{$tempBuyer->id}}">
                {{$tempBuyer->c_name}}
              </option>
              @endforeach @endif
            </select>
            <span id="required_field" style="color:red"></span>
          </div>
          <div class="col-4">
            {{-- <label class="control-label">Zero courier charges</label> <input class="form-control" type="checkbox" name="zeroCourier" style="width: 40px; height: 20px; cursor: pointer;" /> --}}
            <button class="btn btn-success" onclick="addCloneFulfillment()" type="button">Add Buyer</button>
          </div>
        </div>
      </div>

      <input type="hidden" name="deliveredCostStatus" id="deliveredCostStatus" value="1" />
      <!-- / Next Section 6-->

      <!-- Next Section 7 Hidden fields for Setting -->
      @if(isset($settings)) @foreach($settings as $key => $setting)
      <!-- Country-based Volumetric Weight Settings (kg for UK, EU, California, Australia; LBS for US, Canada) -->
      <input type="hidden" name="settingVolWt_uk_kg" id="settingVolWt_uk_kg" value="{{$setting->uk_volumetric_weight_kg ?? ''}}" />
      <input type="hidden" name="settingVolWt_us_lbs" id="settingVolWt_us_lbs" value="{{$setting->us_volumetric_weight_lbs ?? ''}}" />
      <input type="hidden" name="settingVolWt_eu_kg" id="settingVolWt_eu_kg" value="{{$setting->eu_volumetric_weight_kg ?? ''}}" />
      <input type="hidden" name="settingVolWt_canada_lbs" id="settingVolWt_canada_lbs" value="{{$setting->canada_volumetric_weight_lbs ?? ''}}" />
      <input type="hidden" name="settingVolWt_california_kg" id="settingVolWt_california_kg" value="{{$setting->california_volumetric_weight_kg ?? ''}}" />
      <input type="hidden" name="settingVolWt_australia_kg" id="settingVolWt_australia_kg" value="{{$setting->australia_volumetric_weight_kg ?? ''}}" />
      <input type="hidden" name="settingVolWt_india_kg" id="settingVolWt_india_kg" value="{{$setting->india_volumetric_weight_kg ?? 5000}}" />
      <!-- Legacy settings (keeping for backward compatibility) -->
      <input type="hidden" name="settingVolWt" id="settingVolWt" value="{{$setting->volWt}}" />
      <input type="hidden" name="settingVolWtLbs" id="settingVolWtLbs" value="{{$setting->volumetric_weight_lbs}}" />
      <input type="hidden" name="settingShippingCost2_uk" id="settingShippingCost2_uk" value="{{$settings_option['shipping_cost_uk']}}" />
      <input type="hidden" name="settingStorageCost_uk" id="settingStorageCost_uk" value="{{$settings_option['storage_cost_uk']}}" />
      <input type="hidden" name="settingShippingCost2_us" id="settingShippingCost2_us" value="{{$settings_option['shipping_cost_us']}}" />
      <input type="hidden" name="settingStorageCost_us" id="settingStorageCost_us" value="{{$settings_option['storage_cost_us']}}" />
      <input type="hidden" name="settingShippingCost2_eu" id="settingShippingCost2_eu" value="{{$settings_option['shipping_cost_eu']}}" />
      <input type="hidden" name="settingStorageCost_eu" id="settingStorageCost_eu" value="{{$settings_option['storage_cost_eu']}}" />
      <input type="hidden" name="settingShippingCost2_canada" id="settingShippingCost2_canada" value="{{$settings_option['shipping_cost_canada']}}" />
      <input type="hidden" name="settingStorageCost_canada" id="settingStorageCost_canada" value="{{$settings_option['storage_cost_canada']}}" />
      <input type="hidden" name="settingShippingCost2_australia" id="settingShippingCost2_australia" value="{{$settings_option['shipping_cost_australia']}}" />
      <input type="hidden" name="settingStorageCost_australia" id="settingStorageCost_australia" value="{{$settings_option['storage_cost_australia']}}" />

      <input type="hidden" name="settingWholsalePackaging" id="settingWholsalePackaging" value="{{$setting->wspackaging}}" />
      <input type="hidden" name="settingDropshipPackaging" id="settingDropshipPackaging" value="{{$setting->dspackaging}}" />
      <input type="hidden" name="settingIndiaShipping" id="settingIndiaShipping" value="{{$setting->ishippingcost}}" />
      @endforeach @endif
      <!-- / Next Section 7 Hidden fields for Setting -->
      <input type="hidden" name="total_inc_valuee" id="total_inc_valuee" value="0" />
      <input type="hidden" name="inr_to_pound_conversion_rate" id="inr_to_pound_conversion_rate" value="{{$settings_option['inr_to_pound_conversion_rate']}}" />
      <input type="hidden" name="inr_to_dollar_conversion_rate" id="inr_to_dollar_conversion_rate" value="{{$settings_option['inr_to_dollar_conversion_rate']}}" />
      <input type="hidden" name="inr_to_euro_conversion_rate" id="inr_to_euro_conversion_rate" value="{{$settings_option['inr_to_euro_conversion_rate']}}" />
      <input type="hidden" name="inr_to_canadian_conversion_rate" id="inr_to_canadian_conversion_rate" value="{{$settings_option['inr_to_canadian_conversion_rate']}}" />
      <input type="hidden" name="inr_to_australia_conversion_rate" id="inr_to_australia_conversion_rate" value="{{$settings_option['inr_to_australia_conversion_rate']}}" />
      <div class="row col-4 mt-3">
        <button type="submit" class="btn btn-primary mt-3" id="pricingSubmitBtn">Add Pricing</button>
      </div>

    </div>
    <input type="hidden" id="duplicatePage" name="duplicatePage" value="0">
  </form>
</div>
@endsection

@section('footer')

<script type="text/javascript">
  let GLOBAL_VOL_WT = 0;
  let GLOBAL_VOL_WT_LBS = 0;
  let GLOBAL_Gross = 0;
  window.tariffDefaultsByFlag = window.tariffDefaultsByFlag || {};
  window.lastTapestryCategory = null;

  function getTapestryCategory() {
    return (parseFloat($('#tapestryCost').val()) || 0) > 0 ? 'upholstered' : 'solid';
  }

  function normalizeTariffDefault(value) {
    var n = parseFloat(value);
    return isNaN(n) ? 0 : n;
  }

  function tariffPercentForCategory(defaults) {
    if (!defaults) {
      return 0;
    }
    return getTapestryCategory() === 'upholstered'
      ? normalizeTariffDefault(defaults.upholstered)
      : normalizeTariffDefault(defaults.solid);
  }

  function computeTariffAdjustedFob(baseFob, tariffPercent) {
    var base = parseFloat(baseFob) || 0;
    var pct = parseFloat(tariffPercent) || 0;
    var raw = base + ((base * pct) / 100);
    return Math.round(parseFloat(raw.toFixed(2)));
  }

  function applyTariffDefaultForFlag(flag, force) {
    var defaults = window.tariffDefaultsByFlag[flag];
    if (!defaults) {
      return;
    }
    var $el = $('#tariff_percent_' + flag);
    if (!$el.length) {
      return;
    }
    if (!force && $el.data('manual-tariff') === 1) {
      return;
    }
    $el.val(tariffPercentForCategory(defaults));
    if (force) {
      $el.data('manual-tariff', 0);
    }
  }

  function applyTariffDefaultsOnCategoryChange() {
    var newCat = getTapestryCategory();
    if (window.lastTapestryCategory === null) {
      window.lastTapestryCategory = newCat;
      return;
    }
    if (newCat === window.lastTapestryCategory) {
      return;
    }
    window.lastTapestryCategory = newCat;
    Object.keys(window.tariffDefaultsByFlag).forEach(function(flag) {
      applyTariffDefaultForFlag(flag, true);
    });
  }

  // Volumetric weight: round up to whole number (33.75 → 34)
  function roundUpVolWt(val) {
    var n = parseFloat(val);
    if (isNaN(n)) return '0';
    return String(Math.ceil(n));
  }

  /** US/Canada volumetric lb: each cm edge → in, ceil to integer inch, then in³ ÷ divisor. Final UI still uses roundUpVolWt(). */
  function volumetricLbsRawFromCm(widthCm, heightCm, depthCm, divisor) {
    var div = Number(divisor);
    if (!div || div <= 0 || isNaN(div)) div = 166;
    var w = Math.ceil((Number(widthCm) || 0) / 2.54);
    var h = Math.ceil((Number(heightCm) || 0) / 2.54);
    var d = Math.ceil((Number(depthCm) || 0) / 2.54);
    return (w * h * d) / div;
  }

  $(document).ready(function() {
    window.lastTapestryCategory = getTapestryCategory();
    applyPackagingSelection('dropship');

    $.ajax({
      'url': "{{ url('/pricing/data') }}"
    }).done(function(data) {
      if (data) {
        products = data.product;
        tempProducts = data.tempProduct;
        hardwares = data.hardwares;
      }
    });
  });

  $(document).on('input change', '#boxWt', function() {
    refreshAllBoxWtDisplays();
    recalculateCourierSurchargesFromCurrentSelection();
    changeCloneCalculation();
  });

  $('form[action="{{ url('/pricing/create') }}"]').on('submit', function(e) {
    if (parseBoxWtKgFromInput() <= 0) {
      e.preventDefault();
      alert('Box Wt. (kg) must be greater than 0.');
      $('#boxWt').focus();
      return false;
    }
    if (hasDuplicateDestinationBuyers()) {
      e.preventDefault();
      alert(getDuplicateDestinationBuyerMessage());
      return false;
    }
  });

  $('#buyer_id').on('change', function() {
    var selectedOption = $(this).find('option:selected');
    var selectedCountry = selectedOption.data('country');
    $('#default_country').val(selectedCountry);
  });

  var products = [];
  var tempProducts = [];
  var hardwares = [];
  var hardwareBindingNamespace = '.pricingHardwareCost';
  var __packagingApplySync = false;

  function recalculatePackagingCostValues() {
    var wholesalevolume = Number($('#wholesalevolume').val()) || 0;
    var dropshipvolume = Number($('#dropshipvolume').val()) || 0;
    var wspackagingsetting = Number($('#settingWholsalePackaging').val()) || 0;
    var dspackagingsetting = Number($('#settingDropshipPackaging').val()) || 0;

    return {
      wholesale: ((wholesalevolume * wspackagingsetting) / 60).toFixed(2),
      dropship: ((dropshipvolume * dspackagingsetting) / 60).toFixed(2)
    };
  }

  function applyPackagingSelection(which) {
    __packagingApplySync = true;
    var costs = recalculatePackagingCostValues();

    if (which === 'wholesale') {
      $('#wSPackageCostCheck').prop('checked', true);
      $('#dSPackageCostCheck').prop('checked', false);
      $('#wSPackageCost').val(costs.wholesale).prop('disabled', false).addClass('cost');
      $('#dSPackageCost').val(0).prop('disabled', true).removeClass('cost');
    } else {
      $('#dSPackageCostCheck').prop('checked', true);
      $('#wSPackageCostCheck').prop('checked', false);
      $('#dSPackageCost').val(costs.dropship).prop('disabled', false).addClass('cost');
      $('#wSPackageCost').val(0).prop('disabled', true).removeClass('cost');
    }

    __packagingApplySync = false;
    allCost();
  }

  function findHardwareById(hardwareId) {
    if (!hardwareId || hardwareId === '0') {
      return null;
    }
    return hardwares.find(function(hardware) {
      return String(hardware.id) === String(hardwareId);
    }) || null;
  }

  function recalculateHardwareCost(hobj, hcobj, hqobj) {
    var hardwareId = hobj.val();
    var qty = parseFloat(hqobj.val()) || 0;
    var hardware = findHardwareById(hardwareId);

    if (!hardware || qty <= 0) {
      hcobj.val(0);
    } else {
      hcobj.val((parseFloat(hardware.rate) * qty).toFixed(2));
    }

    hcobj.trigger('change');
  }

  function bindHardwareRow(hobj, hcobj, hqobj, recalculateOnBind) {
    function onHardwareChange() {
      recalculateHardwareCost(hobj, hcobj, hqobj);
    }

    hqobj.off('input' + hardwareBindingNamespace + ' change' + hardwareBindingNamespace)
      .on('input' + hardwareBindingNamespace + ' change' + hardwareBindingNamespace, onHardwareChange);

    hobj.off('change' + hardwareBindingNamespace + ' changed.bs.select' + hardwareBindingNamespace)
      .on('change' + hardwareBindingNamespace + ' changed.bs.select' + hardwareBindingNamespace, onHardwareChange);

    if (recalculateOnBind) {
      onHardwareChange();
    }
  }

  function refreshHardwareSelectpickers() {
    for (var i = 1; i <= 5; i++) {
      var $hardware = $('#hardware' + i);
      if ($hardware.data('selectpicker')) {
        $hardware.selectpicker('refresh');
      }
    }
  }

  function applyHardwareFromProduct(productData) {
    for (var i = 1; i <= 5; i++) {
      $('#hardware' + i).val(productData['hardware' + i] || 0);
      $('#hardware' + i + '_quantity').val(productData['hardware' + i + '_quantity'] || 0);
    }
    refreshHardwareSelectpickers();
    for (var j = 1; j <= 5; j++) {
      bindHardwareRow(
        $('#hardware' + j),
        $('#hardware' + j + '_cost'),
        $('#hardware' + j + '_quantity'),
        true
      );
    }
  }

  function setProductPricingBlocked(blocked, message) {
    var $alert = $('#productPricingAlert');
    if (blocked) {
      $alert.removeClass('d-none').text(message || 'This product already has pricing.');
      $('#pricingSubmitBtn').prop('disabled', true);
    } else {
      $alert.addClass('d-none').text('');
      $('#pricingSubmitBtn').prop('disabled', false);
    }
  }

  function changeDetails(ref) {
    var id = $(ref).val();
    var labelName = $('#selectProduct :selected').parent().attr('label');
    if (!id) {
      setProductPricingBlocked(false, '');
      return;
    }

    var productType = labelName != "Temporary Products" ? 1 : 0;
    $.get("{{ url('/pricing/check-product-pricing') }}", {
      product_id: id,
      product_type: productType
    }).done(function(res) {
      if (res && res.exists) {
        setProductPricingBlocked(true, res.message);
        return;
      }
      setProductPricingBlocked(false, '');
      applyProductDetails(id, labelName);
    });
  }

  function applyProductDetails(id, labelName) {

    function findProduct(product) {
      return product.id == id;
    }

    function findTempProduct(tempProduct) {
      return tempProduct.id == id;
    }

    if (labelName != "Temporary Products") {
      var product = products.find(findProduct);
      if (!product) {
        return;
      }
      $("#imgURL").attr("src", "{{ asset('uploads/product')}}/" + product.imageURL);
      $("#name").val(product.name);
      $("#width").val(product.width);
      $("#height").val(product.height);
      $("#gross_weight").val(product.gross_weight);
      softSyncBoxWtFromGross();
      $("#depth").val(product.depth);
      $("#boxwidth").val(product.boxwidth);
      $("#boxheight").val(product.boxheight);
      $("#boxdepth").val(product.boxdepth);
      $("#wholesalevolume").val(product.wholesalevolume);
      $("#dropshipvolume").val(product.dropshipvolume);
      var productType = 1;
      $('#productType').val(productType);
      applyHardwareFromProduct(product);

    } else {
      var tempProduct = tempProducts.find(findTempProduct);
      if (!tempProduct) {
        return;
      }
      $("#imgURL").attr("src", "{{ asset('uploads/product')}}/" + tempProducts.imageURL);
      $("#name").val(tempProduct.name);
      $("#width").val(tempProduct.width);
      $("#height").val(tempProduct.height);
      $("#depth").val(tempProduct.depth);
      $("#boxwidth").val(tempProduct.boxwidth);
      $("#boxheight").val(tempProduct.boxheight);
      $("#boxdepth").val(tempProduct.boxdepth);
      $("#wholesalevolume").val(tempProduct.wholesalevolume);
      $("#dropshipvolume").val(tempProduct.dropshipvolume);
      var productType = 0;
      $('#productType').val(productType);
      applyHardwareFromProduct(tempProduct);
    }

    var dropshipvolume = $('#dropshipvolume').val();


    var pUnitCost = $('#pUnitCost').val();
    var wholesalevolume = $('#wholesalevolume').val();
    var output = (dropshipvolume * pUnitCost) / 60;

    $('#polishCost').val(output.toFixed(2));

    var wspackagingsetting = $('#settingWholsalePackaging').val();
    var dspackagingsetting = $('#settingDropshipPackaging').val();
    var ishippingcost = $('#settingIndiaShipping').val();

    var shippingCost = (dropshipvolume * ishippingcost) / 60;
    $('#shippingCost').val(shippingCost.toFixed(2));


    var width = $('#boxwidth').val();
    var height = $('#boxheight').val();
    var depth = $('#boxdepth').val();
    var settingVolWt = $('#settingVolWt').val();
    GLOBAL_Gross = $('#gross_weight').val();


    var settingVolWtLbs = $('#settingVolWtLbs').val();
    const volWt = (width * height * depth) / settingVolWt;
    const volWtLbs = volumetricLbsRawFromCm(width, height, depth, settingVolWtLbs);
    GLOBAL_VOL_WT = (width * height * depth) / settingVolWt;
    GLOBAL_VOL_WT_LBS = volWtLbs;

    $('#volWt').val(roundUpVolWt(volWt));
    $('#volWt_lbs').val(roundUpVolWt(volWtLbs));

    if ($('#wSPackageCostCheck').is(':checked')) {
      applyPackagingSelection('wholesale');
    } else {
      applyPackagingSelection('dropship');
    }

    //////////CHANGE THE CALCULATION OF CURRENCY AND SHIPPING AND STORAGE WHEN PRODUCT SELECT
    $('.call_conversion').trigger('change');
    $('#select_action').trigger('change');

  }

  // Calculate Tapestry Cost
  $('.tapestryCalculate').change(function() {
    var tapestryConsumed = $('#tapestryConsumed').val();
    var tapestryUnitCost = $('#tapestryUnitCost').val();
    var output = tapestryConsumed * tapestryUnitCost;
    $('#tapestryCost').val(output.toFixed(2));
    applyTariffDefaultsOnCategoryChange();
    allCost();
  });

  $(document).on('change', '[id^="tariff_percent_"]', function() {
    $(this).data('manual-tariff', 1);
    var m = (this.id || '').match(/tariff_percent_(\d+)/);
    if (m) {
      allCostNew(null, parseInt(m[1], 10));
    }
  });

  // Calculate Polish Cost
  $('#pUnitCost').change(function() {
    var dropshipvolume = $('#dropshipvolume').val();
    var pUnitCost = $('#pUnitCost').val();
    var wholesalevolume = $('#wholesalevolume').val();
    var output = (dropshipvolume * pUnitCost) / 60;
    $('#polishCost').val(output.toFixed(2));
    allCost();
  });

  // Wholesale vs dropship packaging: exactly one counts toward cost; inactive field is zeroed.
  $('#wSPackageCostCheck').on('change', function() {
    if (__packagingApplySync) return;
    if ($(this).is(':checked')) {
      applyPackagingSelection('wholesale');
    } else {
      applyPackagingSelection('dropship');
    }
  });

  $('#dSPackageCostCheck').on('change', function() {
    if (__packagingApplySync) return;
    if ($(this).is(':checked')) {
      applyPackagingSelection('dropship');
    } else {
      applyPackagingSelection('wholesale');
    }
  });

  // Checkbox for Delivered Cost Section
  $('#deliveredCostChecked').click(function() {
    if ($(this).is(':checked')) {
      $('#deliveryCostSection').removeClass('d-none');
      $('#deliveryCostSection :input').prop('disabled', false);
      var deliveredCostStatus = 1;
      $('#deliveredCostStatus').val(deliveredCostStatus);
      allCost();
    } else {
      $('#deliveryCostSection').addClass('d-none');
      $('#deliveryCostSection :input').prop('disabled', true);
      var deliveredCostStatus = 0;
      $('#deliveredCostStatus').val(deliveredCostStatus);
      // allCost();
    }

  });


  $('.allCost').change(function() {
    allCost();
  });

  $(document).on('input change', '.india-cost-input', function() {
    recalcIndiaCostStack();
    changeCloneCalculation();
  });

  function chargeableWeightForBaseCourier(flag) {
    var default_country = ($('#default_country_' + flag).val() || $('#destination_' + flag).val() || '').toString().trim().toLowerCase();
    var volWt = parseFloat($('#volWt1_' + flag).val()) || 0;
    if (default_country === 'us' || default_country === 'canada') {
      volWt = parseFloat($('#volWt_lbs1_' + flag).val()) || 0;
      var boxLbs = Math.ceil(resolveBoxWtKg() * 2.20462);
      return Math.max(Math.ceil(volWt), boxLbs);
    }
    var boxKg = Math.ceil(resolveBoxWtKg());
    return Math.max(Math.ceil(volWt), boxKg);
  }

  /** Per-destination-row volumetric kg (surcharge metrics); row field before global hidden #volWt. */
  function volumetricKgForRow(flag) {
    var el = $('#volWt1_' + flag);
    if (el.length) {
      var v = Number(el.val());
      if (!isNaN(v)) return v;
    }
    return Number($('#volWt').val()) || 0;
  }

  /** Per-destination-row volumetric lbs (surcharge metrics); row field before global hidden #volWt_lbs. */
  function volumetricLbsForRow(flag) {
    var el = $('#volWt_lbs1_' + flag);
    if (el.length) {
      var v = Number(el.val());
      if (!isNaN(v)) return v;
    }
    return Number($('#volWt_lbs').val()) || 0;
  }

  /**
   * Highest-band-only overage: whole overage from the lowest band start is
   * charged at the rate of the highest band the weight exceeds.
   * Falls back to a single legacy band (fixed weight / rate per kg).
   */
  function tierOverageCharge(bands, volWt, legacyFixedWeight, legacyRatePerKg) {
    if (!Array.isArray(bands) || bands.length === 0) {
      bands = [{ from: parseFloat(legacyFixedWeight) || 0, to: null, rate: parseFloat(legacyRatePerKg) || 0 }];
    }
    var fixedWeight = Math.min.apply(null, bands.map(function(b) { return parseFloat(b.from) || 0; }));
    if (volWt <= fixedWeight) return 0;
    var selected = null;
    bands.forEach(function(b) {
      var from = parseFloat(b.from) || 0;
      if (volWt <= from) return;
      if (selected === null || from > selected.from) {
        selected = { from: from, rate: parseFloat(b.rate) || 0 };
      }
    });
    if (!selected) return 0;
    return (volWt - fixedWeight) * selected.rate;
  }

  function changeCourierRate(ref, flag) {
    var finalcouriercost = 0;
    var fuelCharge = 0;
    var rate = 0;
    var fixedrateweight = 0;
    var rateperkg = 0;
    var fper = 0;
    rate = $(ref).find(':selected').data('rate');
    fixedrateweight = $(ref).find(':selected').data('weight');
    rateperkg = $(ref).find(':selected').data('perkg');
    fper = $(ref).find(':selected').data('fper');
    var bands = $(ref).find(':selected').data('bands');
    var volWt = chargeableWeightForBaseCourier(flag);

    rate = rate + tierOverageCharge(bands, volWt, fixedrateweight, rateperkg);
    finalcouriercost = rate;
    fuelCharge = (fper * finalcouriercost) / 100;
    rate = rate + fuelCharge;
    $('#courierCost_' + flag).val(rate.toFixed(2));
    var courierType = $('#courierType_' + flag).val()
    getCustomRateAjax(courierType, flag)
  }

  function allCost() {


    //Cost Price Calculation - INR
    var costPrice = 0;
    $('.cost').each(function() {
      costPrice += parseFloat($(this).val());
    });
    costPrice = costPrice.toFixed(2);
    $('#costPrice').val(costPrice);

    //Admin Cost
    var adminCostPercent = $('#adminCostPercent').val();
    var subadminCost = (costPrice * adminCostPercent) / 100;
    adminCost = parseFloat(costPrice) + parseFloat(subadminCost);
    adminCost = adminCost.toFixed(2);
    $('#adminCost').val(adminCost);

    //Final Cost
    var profitCost = $('#profitCost').val();
    var subfinalCost = (adminCost * profitCost) / 100;
    finalCost = parseFloat(adminCost) + parseFloat(subfinalCost);
    finalCost = finalCost.toFixed(2);

    $('#finalCost').val(finalCost);

    recalcIndiaCostStack();

    changeCloneCalculation();
  }

  /**
   * India owns a separate cost stack: common cost components without the common
   * India shipping amount, plus its own editable shipping and percentages.
   */
  function recalcIndiaCostStack() {
    var commonCostPrice = Number($('#costPrice').val()) || 0;
    var commonShipping = Number($('#shippingCost').val()) || 0;
    var indiaShipping = Number($('#indiaShippingCost').val()) || 0;
    var indiaCostPrice = Math.max(0, commonCostPrice - commonShipping) + indiaShipping;
    var indiaAdminPercent = Number($('#indiaAdminCostPercent').val()) || 0;
    var indiaAdminCost = indiaCostPrice * (1 + indiaAdminPercent / 100);
    var indiaProfitPercent = Number($('#indiaProfitPercent').val()) || 0;
    var indiaFinalCost = indiaAdminCost * (1 + indiaProfitPercent / 100);
    $('#indiaCostPrice').val(indiaCostPrice.toFixed(2));
    $('#indiaAdminCost').val(indiaAdminCost.toFixed(2));
    $('#indiaFinalCost').val(indiaFinalCost.toFixed(2));
  }

  /////////////////////////////////////////changes start from here

  function allCostNew(getCurrency, flag = '', currentValue = true) {

    var dropshipvolume = $('#dropshipvolume').val();
    var costPrice = $('#costPrice').val();
    var adminCost = $('#adminCost').val();
    var finalCost = $('#finalCost').val();
    var destination = ($('#default_country_' + flag).val() || $('#destination_' + flag).val() || '').toString().trim().toLowerCase();

    // India-only domestic pricing: keep the INR Final Cost stack, then add
    // the fully calculated courier (bands + fuel + surcharge). All other
    // destinations continue through the existing conversion/landed path.
    if (destination === 'india') {
      var indiaFinalCost = Number($('#indiaFinalCost').val()) || 0;
      var indiaCourierCost = Number($('#custom_surcharge_' + flag).val()) || Number($('#courierCost_' + flag).val()) || 0;
      var indiaDeliveryCost = Number((indiaFinalCost + indiaCourierCost).toFixed(2));

      $('#changecurrency_' + flag).val('₹');
      $('#converRate_' + flag).val(0);
      $('#fobINCost_' + flag).val(0);
      $('#tariff_percent_' + flag).val(0);
      $('#final_fob_in_cost_' + flag).val(0);
      $('#shippingCost2_' + flag).val(0);
      $('#StorageCost_' + flag).val(0);
      $('#adminCost2_' + flag).val(0);
      $('#qualityAssurance_' + flag).val(0);
      $('#landedCost_' + flag).val(indiaFinalCost.toFixed(2));
      $('#adminProfit_' + flag).val(0);
      $('#adminPrice_' + flag).val(indiaFinalCost.toFixed(2));
      $('#finalPricePer_' + flag).val(0);
      $('#finalPrice_' + flag).val(indiaFinalCost.toFixed(2));
      $('#adjustment_' + flag).val(0);
      $('#deliveryCost_' + flag).val(indiaDeliveryCost);
      $('#newDelCost_' + flag).val(Math.round(indiaDeliveryCost));
      return;
    }

    var settingShippingCost2 = $('#shippingCost2_' + flag).val() ?? 0;
    var settingStorageCost = $('#StorageCost_' + flag).val() ?? 0;

    ///////////////////////CONVERSION//////////
    var converRate = 0;
    if (currentValue) {
      converRate = $('#converRate_' + flag).val();
    }
    if (getCurrency == null) {
      var getCountry = $('#default_country_' + flag).val();
      getCountry = getCountry.toLowerCase();
      if (getCountry == 'uk') {
        if (converRate == 0) {
          converRate = $('#inr_to_pound_conversion_rate').val();
        }
        getCurrency = 'pound';
      } else if (getCountry == 'us') {
        if (converRate == 0) {
          converRate = $('#inr_to_dollar_conversion_rate').val();
        }
        getCurrency = 'dollar';

      } else if (getCountry == 'canada') {
        if (converRate == 0) {
          converRate = $('#inr_to_canadian_conversion_rate').val();
        }
        getCurrency = 'canadian dollar';
      } else if (getCountry == 'australia') {
        if (converRate == 0) {
          converRate = $('#inr_to_australia_conversion_rate').val();
        }
        getCurrency = 'Australia dollar';

      } else if (getCountry == 'eu') {
        if (converRate == 0) {
          converRate = $('#inr_to_euro_conversion_rate').val();
        }
        getCurrency = 'euro';
      }
      // settingShippingCost2 = $('#settingShippingCost2_'+getCountry).val();
      // settingStorageCost = $('#settingStorageCost_'+getCountry).val();
    }
    $('#converRate_' + flag).val(converRate);
    var fobINCost = parseFloat(finalCost) / parseFloat(converRate);
    fobINCost = fobINCost.toFixed(2);
    fobINCost = Math.round(fobINCost);
    $('#fobINCost_' + flag).val(fobINCost);

    var tariffPercent = $('#tariff_percent_' + flag).val();
    if (tariffPercent === '' || tariffPercent === null || typeof tariffPercent === 'undefined') {
      tariffPercent = 0;
    }
    var fobForLanded = computeTariffAdjustedFob(fobINCost, tariffPercent);
    $('#final_fob_in_cost_' + flag).val(fobForLanded);

    ////////////////////////SHIPPING AND STORAGE///////////////////


    //   settingShippingCost2 = (dropshipvolume*settingShippingCost2);
    //   $('#shippingCost2_'+flag).val(settingShippingCost2.toFixed(2));
    //  //////////////////////////////////////
    //   settingStorageCost = (dropshipvolume*settingStorageCost*1.5);
    //   $('#StorageCost_'+flag).val(settingStorageCost.toFixed(2));

    ///////////////////////////////////////////////////////////////////

    //////////////////////changes from here of flags
    var adminCost2 = $('#adminCost2_' + flag).val();
    var qualityAssurance = $('#qualityAssurance_' + flag).val();
    var sublanded = parseFloat(settingShippingCost2) + parseFloat(settingStorageCost) + parseFloat(adminCost2) + parseFloat(qualityAssurance) + parseFloat(fobForLanded);
    sublanded = sublanded.toFixed(2);
    $('#landedCost_' + flag).val(sublanded);

    // Admin Price
    var adminProfit = $('#adminProfit_' + flag).val();
    var output = (sublanded * adminProfit) / 100;
    var adminPrice = parseFloat(sublanded) + parseFloat(output);
    adminPrice = adminPrice.toFixed(2);
    $('#adminPrice_' + flag).val(adminPrice);

    // Final Price
    var finalPricePercent = $('#finalPricePer_' + flag).val();
    var output1 = (adminPrice * finalPricePercent) / 100;
    var finalPrice = parseFloat(adminPrice) + parseFloat(output1);
    finalPrice = finalPrice.toFixed(2);
    $('#finalPrice_' + flag).val(finalPrice);

    // Delivered Cost
    var courierCost = $('#courierCost_' + flag).val();
    var customSurcharge = $('#custom_surcharge_' + flag).val();

    // UK rule: if Courier Cost > 100, force it to 115
    var _countryForCourierRule = ($('#default_country_' + flag).val() || '').toString().toLowerCase();
    var _customSurchargeNum = Number(customSurcharge) || 0;
    if (_countryForCourierRule === 'uk' && _customSurchargeNum > 100) {
      _customSurchargeNum = 115;
      $('#custom_surcharge_' + flag).val(_customSurchargeNum);
      customSurcharge = _customSurchargeNum;
    }


    var deliveryCost = parseFloat(finalPrice) + parseFloat(customSurcharge);
    deliveryCost = parseFloat(deliveryCost.toFixed(2));
    $('#deliveryCost_' + flag).val(deliveryCost);

    // Cost Adjustment
    var adjustment = $('#adjustment_' + flag).val();
    var adjustmentOutput = (deliveryCost * adjustment) / 100;
    var finalAdjustOutput = parseFloat(deliveryCost) + parseFloat(adjustmentOutput);

    // New Delivered Cost
    var newDeliveryCost = finalAdjustOutput.toFixed();
    $('#newDelCost_' + flag).val(newDeliveryCost);
  }

  ////////////fulfillment cost buyer2 calculation start from here
  function fulFillmentCostBuyer2Calculation(selectedId, flag) {

    var addSelect = '';
    var is_default = '';
    var selectedIdCourier = '';
    var custom_conditions = 0;
    $.ajax({
      type: "GET",
      url: '/pricing/check-courier-country',
      data: {
        selectedId
      },
      success: function(response) {
        $('#adminProfit_' + flag).val(response.adminProfitPercentage);
        $('#finalPricePer_' + flag).val(response.finalPricePercentag);
        window.tariffDefaultsByFlag[flag] = {
          solid: normalizeTariffDefault(response.tariffSolidWoodPercent),
          upholstered: normalizeTariffDefault(response.tariffUpholsteredPercent)
        };
        var $tariffEl = $('#tariff_percent_' + flag);
        if ($tariffEl.data('tariff-loaded') === 1) {
          $tariffEl.data('manual-tariff', 1);
        } else {
          applyTariffDefaultForFlag(flag, true);
        }

        var $adjustmentEl = $('#adjustment_' + flag);
        if ($adjustmentEl.length && $adjustmentEl.data('adjustment-loaded') !== 1) {
          $adjustmentEl.val(response.costAdjustmentsPercentage ?? 0);
        }

        $('#volWt1_' + flag).val(roundUpVolWt(GLOBAL_VOL_WT));
        $('#volWt_lbs1_' + flag).val(roundUpVolWt(GLOBAL_VOL_WT_LBS));
        const courierName = response.courierName || "Select Courier";
        const pricingCountry = response.pricingCountry;
        if (pricingCountry) {
          ////////////////////////SHIPPING AND STORAGE///////////////////
          var dropshipvolume = $('#dropshipvolume').val();
          var pricingCountryLowerCase = pricingCountry.toLowerCase();
          var isIndiaDestination = pricingCountryLowerCase === 'india';
          var settingShippingCost2 = isIndiaDestination ? 0 : Number($('#settingShippingCost2_' + pricingCountryLowerCase).val());
          var settingStorageCost = isIndiaDestination ? 0 : Number($('#settingStorageCost_' + pricingCountryLowerCase).val());
          settingShippingCost2 = isIndiaDestination ? 0 : (dropshipvolume * settingShippingCost2);
          $('#shippingCost2_' + flag).val(settingShippingCost2.toFixed(2));

          settingStorageCost = isIndiaDestination ? 0 : (dropshipvolume * settingStorageCost * 1.5);

          // if (selectedId == 2 || selectedId == 3) {
          //   settingStorageCost = (1 * settingStorageCost);

          // }else{

          //   settingStorageCost = (dropshipvolume*settingStorageCost);
          // }

          $('#StorageCost_' + flag).val('0');
          //////////////////////////////////////
          $('#default_country_' + flag).val(pricingCountry);
          $('#destination_' + flag).val(pricingCountry);
          
          // Calculate volumetric weight based on destination country
          var width = Number($('#boxwidth').val()) || 0;
          var height = Number($('#boxheight').val()) || 0;
          var depth = Number($('#boxdepth').val()) || 0;
          var volWtKg = 0;
          var volWtLbs = 0;
          
          if (pricingCountryLowerCase.toLowerCase() === 'us') {
            $('#changecurrency_' + flag).val('$');
            $('.change_unit').html('Volumetric Wt. (LBS)');
            $('#volWtLbsCol_' + flag).show();
            $('#volWtKgCol_' + flag).hide();
            
            // US uses LBS only
            var settingVolWtLbs = Number($('#settingVolWt_us_lbs').val()) || 166;
            volWtLbs = volumetricLbsRawFromCm(width, height, depth, settingVolWtLbs);
            GLOBAL_VOL_WT_LBS = volWtLbs;
            $('#volWt_lbs1_' + flag).val(roundUpVolWt(volWtLbs));

          } else if (pricingCountryLowerCase.toLowerCase() === 'canada') {
            $('#changecurrency_' + flag).val('C$');
            $('#volWtLbsCol_' + flag).show();
            $('#volWtKgCol_' + flag).hide();
            $('.change_unit').html('Volumetric Wt. (LBS)');
            
            // Canada uses LBS only
            var settingVolWtLbs = Number($('#settingVolWt_canada_lbs').val()) || 166;
            volWtLbs = volumetricLbsRawFromCm(width, height, depth, settingVolWtLbs);
            GLOBAL_VOL_WT_LBS = volWtLbs;
            $('#volWt_lbs1_' + flag).val(roundUpVolWt(volWtLbs));

          } else {
            // UK, EU, California, Australia use kg only
            var settingVolWtKg = 5000;
            if (pricingCountryLowerCase.toLowerCase() === 'eu') {
              $('#changecurrency_' + flag).val('€');
              settingVolWtKg = Number($('#settingVolWt_eu_kg').val()) || 5000;
            } else if (pricingCountryLowerCase.toLowerCase() === 'australia') {
              $('#changecurrency_' + flag).val('A$');
              settingVolWtKg = Number($('#settingVolWt_australia_kg').val()) || 5000;
            } else if (pricingCountryLowerCase.toLowerCase() === 'california') {
              $('#changecurrency_' + flag).val('$');
              settingVolWtKg = Number($('#settingVolWt_california_kg').val()) || 5000;
            } else if (pricingCountryLowerCase.toLowerCase() === 'india') {
              $('#changecurrency_' + flag).val('₹');
              settingVolWtKg = Number($('#settingVolWt_india_kg').val()) || 5000;
            } else {
              // UK or default
              $('#changecurrency_' + flag).val('£');
              settingVolWtKg = Number($('#settingVolWt_uk_kg').val()) || 5000;
            }
            
            $('.change_unit').html('Volumetric Wt. (kg)');
            $('#volWtKgCol_' + flag).show();
            $('#volWtLbsCol_' + flag).hide();
            
            volWtKg = (width * height * depth) / settingVolWtKg;
            GLOBAL_VOL_WT = volWtKg;
            $('#volWt1_' + flag).val(roundUpVolWt(volWtKg));
          }
        } else {
          $('#destination_' + flag).val('');
        }
        addSelect += `<option value="">Select Courier</option>`;

        // Temp buyer with no courier (null/''/0): do not auto-select country default.
        // When buyer has a courier configured, keep previous behaviour (country is_default).
        var buyerHasCourier = response.buyerHasCourier === true || response.buyerHasCourier === 1;
        if (courierName.length > 0) {
          for (const item of courierName) {
            is_default = '';
            if (buyerHasCourier && item.is_default == 1) {
              custom_conditions = item.custom_condition;
              is_default = 'selected';
              selectedIdCourier = item.name;
            }
            addSelect += `<option value="${item.name}" data-rate="${item.rate}" data-weight="${item.fixed_rate_weight}" data-perkg="${item.rate_per_kg}" data-fper="${item.fuel_charge_percent}" data-bands='${JSON.stringify(item.weight_rate_tiers || [])}' ${is_default}>${item.name}</option>`;
          }
        }

        if (buyerHasCourier && !selectedIdCourier && Array.isArray(courierName)) {
          const defaultItem = courierName.find(c => c && c.is_default == 1);
          if (defaultItem && defaultItem.name) {
            selectedIdCourier = defaultItem.name;
            custom_conditions = defaultItem.custom_condition || custom_conditions;
          }
        }

        addSelect += `<option value="0">Other</option>`;


        $("#courierType_" + flag).html(addSelect);
        $('#courierType_' + flag).selectpicker('refresh');
        if (selectedIdCourier) {
          $("#courierType_" + flag).val(selectedIdCourier).change();
        } else {
          $("#courierType_" + flag).val('').change();
          $("#courierCost_" + flag).val(0);
          $('#custom_surcharge_' + flag).val(0);
          allCostNew(null, flag, false);
        }
        refreshAllBoxWtDisplays();
      }
    });

  }
  //////////////////////CLONING/////////
  var inc = 1

  function collectDestinationBuyerIds() {
    var ids = [];
    var main = $('#buyer_id2_0').val();
    if (main) {
      ids.push(String(main));
    }
    $('input[name="buyer_id2_multiple[]"]').each(function() {
      if (this.value) {
        ids.push(String(this.value));
      }
    });
    return ids;
  }

  function getDuplicateDestinationBuyerIds() {
    var ids = collectDestinationBuyerIds();
    var seen = {};
    var dupes = [];
    ids.forEach(function(id) {
      if (seen[id]) {
        if (dupes.indexOf(id) === -1) {
          dupes.push(id);
        }
      }
      seen[id] = true;
    });
    return dupes;
  }

  function hasDuplicateDestinationBuyers() {
    return getDuplicateDestinationBuyerIds().length > 0;
  }

  function getDuplicateDestinationBuyerMessage() {
    return 'Each destination buyer can only be selected once for this product. Remove the duplicate buyer section or choose a different destination buyer.';
  }

  function destinationBuyerAlreadyUsed(buyerId) {
    return collectDestinationBuyerIds().indexOf(String(buyerId)) !== -1;
  }

  function addCloneFulfillment() {
    var isValid1 = true;
    var warningText = '';
    if (!$('#buyer_id2_clone').val()) {
      isValid1 = false;
      warningText = 'Field Is Required';
    }
    var selectedOption = $("#buyer_id2_clone option:selected");
    var buyerValueClone = selectedOption.val();
    if (isValid1 && destinationBuyerAlreadyUsed(buyerValueClone)) {
      isValid1 = false;
      warningText = 'This destination buyer is already added. Each destination buyer can only be used once.';
    }
    $('#required_field').html(warningText);
    if (isValid1 == true) {
      var buyerHeadingClone = selectedOption.text().trim();
      $('#incrementFlag').val(inc);
      var incrementBuyerLabel = inc + 2;
      var htmlContent = `
      <div id="deliveryCostSection_${inc}" class="line_content">
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Buyer ${incrementBuyerLabel}') }}</label> <a href="{{ url('/temporaryBuyer/create')}}" target="_blank" style="float: right;"> (+New)</a>
              <input type="text" class="form-control" value="${buyerHeadingClone}" readonly/>
              <input type="hidden" name="buyer_id2_multiple[]" id="buyer_id2_${inc}" value="${buyerValueClone}">
            </div>
            <input type="hidden" id="default_country_${inc}" name="default_country_clone[]">
            <div class="col-4">
                <label class="control-label">{{ __('Country of Destination') }}</label>
                <input type="text" class="form-control" name="destination_clone[]" id="destination_${inc}" value="" readonly/>
            </div>
            
            <div class="col-4 ">
              <div class="form-group mt-4"><button onclick="removeCloneFulfillment('${inc}')" class="btn btn-sm btn-danger" type="button">Remove</button></div>
            </div>
          </div>
          <div class="row mt-3">
          
       

            <div class="col-4">
              <label class="control-label">{{ __('Currency') }}</label>
              <input type="text" class="form-control" id="changecurrency_${inc}" name="currency_clone[]" placeholder="Currency" value="0" readonly>
            </div>

               <div class="col-4" id="volWtKgCol_${inc}" style="display:none;">
  <label class="control-label">{{ __('Volumetric Wt. (kg)') }}</label>
  <input type="number" min="0" class="form-control" name="volWt1_clone[]" id="volWt1_${inc}" step="any" value="${roundUpVolWt(GLOBAL_VOL_WT)}" readonly />
</div>

<div class="col-4" id="volWtLbsCol_${inc}" style="display:none;">
  <label class="control-label">{{ __('Volumetric Wt. (LBS)') }}</label>
  <input type="number" min="0" class="form-control" name="volWt_lbs_clone[]" id="volWt_lbs1_${inc}" step="any" value="${roundUpVolWt(GLOBAL_VOL_WT_LBS)}" readonly />
</div>
            <div class="col-4">
              <label class="control-label">{{ __('Conversion Rate') }}</label>
                  <input type="number" min="0" class="form-control call_conversion" name="converRate_clone[]" id="converRate_${inc}" step="any" value="0" required onchange="allCostNew(null,${inc})"/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('FOB India Cost') }}</label>
                <input type="number" min="0" class="form-control" name="fobINCost_clone[]" id="fobINCost_${inc}" step="any" value="0" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Tariff') }}</label>
              <input type="number" min="0" class="form-control calculate_fulfillment_cost" name="tariff_percent_clone[]" id="tariff_percent_${inc}" step="any" value="0" onchange="allCostNew(null,${inc})" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Tariff-Adjusted FOB Cost') }}</label>
              <input type="number" min="0" class="form-control mandatory_add_multi" name="final_fob_in_cost_clone[]" id="final_fob_in_cost_${inc}" step="any" value="0" readonly />
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost') }}</label>
                <input type="number" min="0" class="form-control allCost" name="shippingCost2_clone[]" id="shippingCost2_${inc}" step="any" value="0" onchange="allCostNew(null,${inc})"/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Storage Cost(1 month)') }}</label>
                <input type="number" min="0" class="form-control allCost" name="StorageCost_clone[]" id="StorageCost_${inc}" step="any" value="0" readonly/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Box weight (buyer units)') }}</label>
                <input type="text" class="form-control bg-light" id="boxWt_display_${inc}" readonly value="0" autocomplete="off" />
                <small class="text-muted d-block mt-1" id="boxWt_unit_hint_${inc}"></small>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Outbound Charges') }}</label>
                <input type="number" min="0" class="form-control calculate_fulfillment_cost" name="adminCost2_clone[]" id="adminCost2_${inc}" step="any" value="0" required onchange="allCostNew(null,${inc})"/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Quality Assurance') }}</label>
                <input type="number" min="0" class="form-control " name="qualityAssurance_clone[]" id="qualityAssurance_${inc}" step="any" value="0" required onchange="allCostNew(null,${inc})"/>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Landed Cost') }}</label>
                <input type="number" min="0" class="form-control" name="landedCost_clone[]" id="landedCost_${inc}" step="any" value="0" readonly  />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Admin Profit (%)') }}</label>
                <input type="number" min="0" class="form-control " name="adminProfit_clone[]" id="adminProfit_${inc}" step="any" value="0" required onchange="allCostNew(null,${inc})"/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Admin Price') }}</label>
                <input type="number" min="0" class="form-control" name="adminPrice_clone[]" id="adminPrice_${inc}" step="any" value="0" readonly  />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Final Price (%)') }}</label>
                <input type="number" min="0" class="form-control " name="finalPricePer_clone[]" id="finalPricePer_${inc}" step="any" value="0" required onchange="allCostNew(null,${inc})"/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Final Price') }}</label>
                <input type="number" min="0" class="form-control" name="finalPrice_clone[]" id="finalPrice_${inc}" step="any" value="0" readonly  />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Type of Courier') }}</label>
                <select type="text" class="selectpicker " data-live-search="true" name="courierType_clone[]" id="courierType_${inc}" onchange="changeCourierRate(this,${inc})">
                  <option selected>Select Courier</option>
                  @if(isset($couriers)) @foreach($couriers as $key => $courier)
                    <option value="{{$courier->name}}" data-rate="{{$courier->rate}}" data-weight="{{$courier->fixed_rate_weight}}" data-perkg="{{$courier->rate_per_kg}}" data-fper="{{$courier->fuel_charge_percent}}" data-bands="{{ json_encode($courier->weight_rate_tiers) }}">
                    {{$courier->name}}
                    </option>
                  @endforeach @endif
                </select>
            </div>
          </div>

          <div class="row mt-3" >
            <div class="col-4" hidden>
                <label class="control-label">{{ __('Courier Cost') }}</label>
                <input type="number" min="0" class="form-control " name="courierCost_clone1[]" id="courierCost_${inc}" step="any" value="0" required onchange="allCostNew(null,${inc})"/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Courier Cost') }}</label>
                <input type="number" min="0" class="form-control " name="courierCost_clone[]" id="custom_surcharge_${inc}" step="any" value="0" onchange="allCostNew(null,${inc})"/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Delivery Cost') }}</label>
                <input type="number" min="0" class="form-control" name="deliveryCost_clone[]" id="deliveryCost_${inc}" step="any" value="0" readonly onchange="allCostNew(null,${inc})"/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Cost Adjustment (%)') }}</label>
                <input type="number" min="0" class="form-control " name="adjustment_clone[]" id="adjustment_${inc}" step="any" value="0" required onchange="allCostNew(null,${inc})"/>
            </div>
          </div>


                      <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Final Delivered Cost') }}</label>
                <input type="number" min="0" class="form-control" name="newDelCost_clone[]" id="newDelCost_${inc}" step="any" value="0" readonly  />
            </div>
          </div>
        </div>
`;

      // $('.add_more').prepend(htmlContent);
      $('.add_more').append(htmlContent);
      $('#total_inc_valuee').val(inc);

      fulFillmentCostBuyer2Calculation(buyerValueClone, inc);
      refreshAllBoxWtDisplays();

      $('#buyer_id2_clone').val('');
      $('.selectpicker').selectpicker('refresh'); ///////USE FOR APPEARING SELECTPICKER CLASS IN DYNAMIC JS
      inc++;
    }
  }

  function removeCloneFulfillment(num) {
    $('#deliveryCostSection_' + num).remove();
  }

  function changeCloneCalculation() {
    var getIncValue = $('#total_inc_valuee').val();
    for (var ii = 0; ii <= getIncValue; ii++) {
      allCostNew(null, ii)
    }
  }

  function parseBoxWtKgFromInput() {
    var el = document.getElementById('boxWt');
    if (!el) return 0;
    var raw = el.value;
    if (raw === '' || raw === null || typeof raw === 'undefined') return 0;
    var n = parseFloat(String(raw).trim());
    if (isNaN(n)) return 0;
    return n < 0 ? 0 : n;
  }

  /** Soft box weight: saved boxWt if > 0, else product gross_weight. */
  function resolveBoxWtKg() {
    var boxWtFieldKg = parseBoxWtKgFromInput();
    var grossKg = Number($('#gross_weight').val()) || 0;
    return boxWtFieldKg > 0 ? boxWtFieldKg : grossKg;
  }

  function isUsCanadaDestination(flag) {
    var cc = ($('#default_country_' + flag).val() || '').toString().trim().toLowerCase();
    return cc === 'us' || cc === 'canada';
  }

  function resolveBoxWtForCourier(flag) {
    var kg = resolveBoxWtKg();
    if (isUsCanadaDestination(flag)) {
      return { value: kg * 2.20462, unit: 'lbs', kg: kg };
    }
    return { value: kg, unit: 'kg', kg: kg };
  }

  function softSyncBoxWtFromGross() {
    var grossKg = Number($('#gross_weight').val()) || 0;
    if (grossKg > 0 && parseBoxWtKgFromInput() <= 0) {
      $('#boxWt').val(grossKg);
      refreshAllBoxWtDisplays();
    }
  }

  function updateBoxWtDisplayForFlag(flag) {
    var kg = resolveBoxWtKg();
    var disp = document.getElementById('boxWt_display_' + flag);
    var hint = document.getElementById('boxWt_unit_hint_' + flag);
    if (!disp) return;
    var cc = ($('#default_country_' + flag).val() || '').toString().trim().toLowerCase();
    var usCanada = (cc === 'us' || cc === 'canada');
    if (usCanada) {
      var lb = kg * 2.20462;
      disp.value = (lb === 0 ? '0' : lb.toFixed(2));
      if (hint) hint.textContent = 'lb — converted from Box Wt. (kg)';
    } else {
      disp.value = (kg === 0 ? '0' : String(kg));
      if (hint) hint.textContent = cc ? 'kg — same as Box Wt. (kg)' : '';
    }
  }

  function refreshAllBoxWtDisplays() {
    $('[id^="boxWt_display_"]').each(function() {
      var suffix = this.id.replace('boxWt_display_', '');
      var flag = parseInt(suffix, 10);
      if (!isNaN(flag)) updateBoxWtDisplayForFlag(flag);
    });
  }

  function computeUSMetricsForPricing(flag) {
    var bw = Number($('#boxwidth').val()) || 0;
    var bh = Number($('#boxheight').val()) || 0;
    var bd = Number($('#boxdepth').val()) || 0;
    var L = bw / 2.54, W = bh / 2.54, D = bd / 2.54;
    var arr = [L, W, D].sort(function (a, b) { return b - a; });
    var lengthLong = arr[0], mid = arr[1], small = arr[2];
    var boxKg = resolveBoxWtKg();
    var boxWeightLbs = boxKg * 2.20462;
    return {
      oneSideMax: lengthLong,
      boxLIn: L,
      boxWIn: W,
      boxDIn: D,
      lengthLong: lengthLong,
      girth: 2 * (mid + small) + lengthLong,
      lengthPlusGirth: lengthLong + 2 * (mid + small),
      kg: volumetricKgForRow(flag),
      lbs: volumetricLbsForRow(flag),
      boxWeightLbs: boxWeightLbs,
      boxWeightKg: boxKg,
      boxWeightUnit: 'lb'
    };
  }

  function computeUKMetricsForPricing(flag) {
    var bw = Number($('#boxwidth').val()) || 0;
    var bh = Number($('#boxheight').val()) || 0;
    var bd = Number($('#boxdepth').val()) || 0;
    var L = bw, W = bh, D = bd;
    var arr = [L, W, D].sort(function (a, b) { return b - a; });
    var lengthLong = arr[0], mid = arr[1], small = arr[2];
    var volKg = volumetricKgForRow(flag);
    var boxKg = resolveBoxWtKg();
    return {
      oneSideMax: lengthLong,
      boxLIn: L,
      boxWIn: W,
      boxDIn: D,
      lengthLong: lengthLong,
      girth: 2 * (mid + small) + lengthLong,
      lengthPlusGirth: lengthLong + 2 * (mid + small),
      kg: volKg,
      lbs: 0,
      boxWeightLbs: boxKg * 2.20462,
      boxWeightKg: boxKg,
      boxWeightUnit: 'kg'
    };
  }
  function usMetricForAttribute(key, m) {
    var k = (key || '').toLowerCase().replace(/-/g, '_');
    if (k === '1_side') k = '1_sides';
    switch (k) {
      case '1_sides': return m.oneSideMax;
      case '2_sides':
      case '3_sides':
        return null;
      case 'length': return m.lengthLong;
      case 'girth': return m.girth;
      case 'length_plus_girth': return m.lengthPlusGirth;
      case 'kg': return m.kg;
      case 'lbs': return m.lbs;
      case 'gross_lbs':
        return m.boxWeightLbs;
      case 'box_weight':
      case 'boxweight':
        if (m.boxWeightUnit === 'kg') return Number(m.boxWeightKg);
        return m.boxWeightLbs;
      default: return null;
    }
  }
  /** Both edges must satisfy the same tier rule (pair logic for 2_sides). */
  function usBothEdgesSatisfyTier(op, vmin, vmax, a, b) {
    if (op === 'always') return true;
    return usTierMatches(op, vmin, vmax, a) && usTierMatches(op, vmin, vmax, b);
  }
  function usAnyTwoSidesMatchTier(L, W, D, tier) {
    return usBothEdgesSatisfyTier(tier.operator, tier.value_min, tier.value_max, L, W) ||
      usBothEdgesSatisfyTier(tier.operator, tier.value_min, tier.value_max, W, D) ||
      usBothEdgesSatisfyTier(tier.operator, tier.value_min, tier.value_max, D, L);
  }
  function usAllThreeSidesMatchTier(L, W, D, tier) {
    if (tier.operator === 'always') return true;
    return usTierMatches(tier.operator, tier.value_min, tier.value_max, L) &&
      usTierMatches(tier.operator, tier.value_min, tier.value_max, W) &&
      usTierMatches(tier.operator, tier.value_min, tier.value_max, D);
  }
  function usBestSurchargeFromTiersMultiSide(mode, L, W, D, tiers) {
    if (!tiers || !tiers.length) return null;
    var matched = [];
    for (var i = 0; i < tiers.length; i++) {
      var t = tiers[i];
      var ok = (mode === '2_sides')
        ? usAnyTwoSidesMatchTier(L, W, D, t)
        : usAllThreeSidesMatchTier(L, W, D, t);
      if (ok) matched.push(t);
    }
    if (!matched.length) return null;
    var best = matched[0], bestK = usTierThresholdKey(best);
    for (var j = 1; j < matched.length; j++) {
      var k2 = usTierThresholdKey(matched[j]);
      if (k2 > bestK) { best = matched[j]; bestK = k2; }
    }
    return Number(best.surcharge);
  }
  function usTierMatches(op, vmin, vmax, v) {
    if (op === 'always') return true;
    var minn = (vmin === null || vmin === undefined || vmin === '') ? null : Number(vmin);
    var maxx = (vmax === null || vmax === undefined || vmax === '') ? null : Number(vmax);
    v = Number(v);
    switch (op) {
      case '>': return minn !== null && v > minn;
      case '>=': return minn !== null && v >= minn;
      case '<': return minn !== null && v < minn;
      case '<=': return minn !== null && v <= minn;
      case '=': return minn !== null && v === minn;
      case 'between': return minn !== null && maxx !== null && v >= minn && v <= maxx;
      default: return false;
    }
  }
  function usTierThresholdKey(t) {
    if (t.operator === 'always') return -Infinity;
    if (t.operator === 'between') {
      var x = t.value_max != null ? Number(t.value_max) : null;
      var n = t.value_min != null ? Number(t.value_min) : 0;
      return x != null ? x : n;
    }
    return t.value_min != null ? Number(t.value_min) : 0;
  }
  function usBestSurchargeFromTiers(tiers, metricVal) {
    if (!tiers || !tiers.length || metricVal === null || metricVal === undefined) return null;
    var matched = [];
    for (var i = 0; i < tiers.length; i++) {
      var t = tiers[i];
      if (usTierMatches(t.operator, t.value_min, t.value_max, metricVal)) matched.push(t);
    }
    if (!matched.length) return null;
    var best = matched[0], bestK = usTierThresholdKey(best);
    for (var j = 1; j < matched.length; j++) {
      var k = usTierThresholdKey(matched[j]);
      if (k > bestK) { best = matched[j]; bestK = k; }
    }
    return Number(best.surcharge);
  }
  function evaluateUSOneBlock(block, metrics, dbg) {
    var L = function (msg) { if (dbg) { dbg.push(msg); } };
    var Li = metrics.boxLIn, Wi = metrics.boxWIn, Di = metrics.boxDIn;
    var conds = (block.conditions || []).slice().sort(function (a, b) {
      return (a.condition_priority || 0) - (b.condition_priority || 0);
    });
    var op = (block.condition_operator || 'OR').toUpperCase();
    L('  Block: "' + (block.block_name || '(unnamed)') + '" join=' + op + ' displayOrder=' + (block.block_priority || 0));
    function surchargeForCondition(c) {
      var ak = (c.attribute_key || '').toLowerCase().replace(/-/g, '_');
      if (ak === '1_side') ak = '1_sides';
      if (ak === '2_sides') {
        L('    Condition [P' + (c.condition_priority || 0) + '] 2_sides (any pair both pass tier) edges in=' + Li + ',' + Wi + ',' + Di);
        return usBestSurchargeFromTiersMultiSide('2_sides', Li, Wi, Di, c.tiers || []);
      }
      if (ak === '3_sides') {
        L('    Condition [P' + (c.condition_priority || 0) + '] 3_sides (all edges pass tier) edges in=' + Li + ',' + Wi + ',' + Di);
        return usBestSurchargeFromTiersMultiSide('3_sides', Li, Wi, Di, c.tiers || []);
      }
      var mv = usMetricForAttribute(c.attribute_key, metrics);
      L('    Condition [P' + (c.condition_priority || 0) + '] ' + (c.attribute_key || '') + ' → metric value = ' + mv);
      if (mv === null) return undefined;
      return usBestSurchargeFromTiers(c.tiers, mv);
    }
    if (op === 'AND') {
      var sum = 0;
      for (var i = 0; i < conds.length; i++) {
        var s = surchargeForCondition(conds[i]);
        if (s === undefined) {
          L('      → unknown attribute, AND block fails');
          return 0;
        }
        if (s === null) {
          L('      → no tier matched, AND block fails');
          return 0;
        }
        L('      → highest-threshold matched tier surcharge: +' + s + ' USD');
        sum += s;
      }
      L('    AND block total surcharge: ' + sum);
      return sum;
    }
    for (var j = 0; j < conds.length; j++) {
      var s2 = surchargeForCondition(conds[j]);
      if (s2 === undefined) {
        L('    Condition [P' + (conds[j].condition_priority || 0) + '] ' + (conds[j].attribute_key || '') + ' → (unknown attr), skip');
        continue;
      }
      if (s2 !== null) {
        L('      → first match wins (OR): +' + s2 + ' USD');
        return s2;
      }
      L('      → no tier matched, try next condition');
    }
    L('    OR block: no condition matched → 0');
    return 0;
  }
  function calculateCountryBlockEngineSurcharge(payload, baseCourier, flag, profile) {
    var metrics = profile === 'uk' ? computeUKMetricsForPricing(flag) : computeUSMetricsForPricing(flag);
    var blocks = (payload.blocks || []).slice().sort(function (a, b) {
      return (a.block_priority || 0) - (b.block_priority || 0);
    });
    var dbg = [];
    var label = profile === 'uk' ? 'UK' : 'US';
    dbg.push('Flag (fulfilment row): ' + flag);
    dbg.push('Engine: ' + (payload.engine || '') + ' | combine: ' + (payload.combine_mode || '') + ' | tier pick: ' + (payload.tier_selection || 'highest_threshold'));
    dbg.push('Base courier (rate + fuel): ' + baseCourier);
    dbg.push(label + ' metrics: ' + JSON.stringify(metrics));
    dbg.push('Block count: ' + blocks.length);
    var total = 0;
    for (var i = 0; i < blocks.length; i++) {
      dbg.push('--- ' + label + ' Block ' + (i + 1) + ' of ' + blocks.length + ' ---');
      var blockAmt = evaluateUSOneBlock(blocks[i], metrics, dbg);
      dbg.push('  → Block surcharge subtotal: ' + blockAmt);
      total += blockAmt;
    }
    dbg.push('Sum of all block surcharges: ' + total);
    var finalCost = parseFloat((baseCourier + total).toFixed(2));
    dbg.push('Final ' + label + ' courier (base + surcharges): ' + finalCost);
    console.log('=== ' + label + ' Courier block surcharge ===');
    dbg.forEach(function (line) { console.log(line); });
    console.log('Rule payload (raw):', payload);
    console.log('========================================');
    return finalCost;
  }

  function calculateUSBlockSurcharge(payload, baseCourier, flag) {
    return calculateCountryBlockEngineSurcharge(payload, baseCourier, flag, 'us');
  }

  function maybeUpgradeUkCourierToPalletways(flag) {
    var country = ($('#default_country_' + flag).val() || '').toString().toLowerCase();
    if (country !== 'uk') return false;
    var currentVal = ($('#courierType_' + flag).val() || '').toString();
    if (!currentVal || currentVal === '0') return false;
    if (currentVal.toLowerCase() === 'palletways') return false;
    var total = Number($('#custom_surcharge_' + flag).val());
    if (isNaN(total) || total <= 100) return false;
    var $sel = $('#courierType_' + flag);
    var palletOptionVal = null;
    $sel.find('option').each(function() {
      var v = ($(this).val() || '').toString();
      if (v && v !== '0' && v.toLowerCase() === 'palletways') {
        palletOptionVal = v;
        return false;
      }
    });
    if (!palletOptionVal) return false;
    $sel.val(palletOptionVal);
    if ($sel.data('selectpicker')) {
      $sel.selectpicker('refresh');
    }
    changeCourierRate($sel[0], flag);
    return true;
  }

  function finishCourierSurchargeAndDeliveredCosts(flag) {
    if (maybeUpgradeUkCourierToPalletways(flag)) return;
    allCostNew(null, flag);
  }

  function calCulateSurchargeCustomCondition(custom_conditions, flag) {
    var originalCourierCost = Number($('#courierCost_' + flag).val());

    if (custom_conditions && typeof custom_conditions === 'object' && custom_conditions !== null && custom_conditions.engine === 'us_blocks_v1') {
      var usTot = calculateCountryBlockEngineSurcharge(custom_conditions, originalCourierCost, flag, 'us');
      $('#custom_surcharge_' + flag).val(usTot);
      finishCourierSurchargeAndDeliveredCosts(flag);
      return;
    }

    if (custom_conditions && typeof custom_conditions === 'object' && custom_conditions !== null && custom_conditions.engine === 'uk_blocks_v1') {
      var ukTot = calculateCountryBlockEngineSurcharge(custom_conditions, originalCourierCost, flag, 'uk');
      $('#custom_surcharge_' + flag).val(ukTot);
      finishCourierSurchargeAndDeliveredCosts(flag);
      return;
    }

    var courierCost = Number($('#courierCost_' + flag).val());
    var surCharge = 0;
    var isslideCondition = true;
    var issKgLbsCondition = true;

    if (custom_conditions) {

      var customConditionsArray;
      try {
        customConditionsArray = typeof custom_conditions === 'string' ? JSON.parse(custom_conditions) : custom_conditions;
      } catch (e) {
        $('#custom_surcharge_' + flag).val(originalCourierCost);
        finishCourierSurchargeAndDeliveredCosts(flag);
        return;
      }

      var boxheight = Number($('#boxheight').val());
      var boxwidth = Number($('#boxwidth').val());
      var boxdepth = Number($('#boxdepth').val());

      var volWt = volumetricKgForRow(flag);
      var volWt_lbs = volumetricLbsForRow(flag);

      if (boxheight && boxwidth && boxdepth) {

        var numberOfConditionSatisfy = 0;
        var satisfiedConditionArr = [];

        for (var i = 0; i < customConditionsArray.length; i++) {

          if (surCharge > 0) {
            courierCost = surCharge;
          }

          const conditionType = customConditionsArray[i].attribute;
          const conditionVals = customConditionsArray[i].attribute_val;
          const conditionPrice = customConditionsArray[i].popup_price;

          /* ---------- 1 / 2 / 3 SIDES ---------- */
          if (conditionType === '1_sides' && isslideCondition) {
            if (
              boxheight > conditionVals ||
              boxwidth > conditionVals ||
              boxdepth > conditionVals
            ) {
              surCharge = Number(conditionPrice) + courierCost;
              numberOfConditionSatisfy++;
              satisfiedConditionArr.push(customConditionsArray[i]);
            }
          } else if (conditionType === '2_sides' && isslideCondition) {
            if (
              (boxheight > conditionVals && boxwidth > conditionVals) ||
              (boxwidth > conditionVals && boxdepth > conditionVals) ||
              (boxdepth > conditionVals && boxheight > conditionVals)
            ) {
              surCharge = Number(conditionPrice) + courierCost;
              numberOfConditionSatisfy++;
              satisfiedConditionArr.push(customConditionsArray[i]);
            }
          } else if (conditionType === '3_sides' && isslideCondition) {
            if (
              boxheight > conditionVals &&
              boxwidth > conditionVals &&
              boxdepth > conditionVals
            ) {
              surCharge = Number(conditionPrice) + courierCost;
              numberOfConditionSatisfy++;
              satisfiedConditionArr.push(customConditionsArray[i]);
            }
          }


          if (conditionType === 'box') {

            let boxVals = conditionVals;
            let boxPrices = conditionPrice;
            let boxWt = resolveBoxWtForCourier(flag);
            let wt = boxWt.value;

            let maxMatchPrice = 0;

            for (let b = 0; b < boxVals.length; b++) {

              let requiredWeight = Number(boxVals[b]);
              let price = Number(boxPrices[b]);

              if (wt >= requiredWeight && price > maxMatchPrice) {
                maxMatchPrice = price;
              }
            }


            if (maxMatchPrice > 0) {
              surCharge = courierCost + maxMatchPrice;
              console.log(courierCost);
              console.log(maxMatchPrice);
              console.log(surCharge);

              numberOfConditionSatisfy++;
              satisfiedConditionArr.push(customConditionsArray[i]);
            }
          }

          if (conditionType === 'kg' && issKgLbsCondition) {
            if (volWt > Number(conditionVals[0])) {
              surCharge = Number(conditionPrice[0]) + courierCost;
              numberOfConditionSatisfy++;
              satisfiedConditionArr.push(customConditionsArray[i]);
            }
          }

          if (conditionType === 'lbs' && issKgLbsCondition) {
            if (volWt_lbs > Number(conditionVals[0])) {
              surCharge = Number(conditionPrice[0]) + courierCost;
              numberOfConditionSatisfy++;
              satisfiedConditionArr.push(customConditionsArray[i]);
            }
          }
        }

        if (numberOfConditionSatisfy > 1) {
          var maxCost = findHighestCostFromCustomCondition(satisfiedConditionArr);
          surCharge = maxCost + originalCourierCost;
        }

        surCharge = parseFloat(surCharge.toFixed(2));


        $('#custom_surcharge_' + flag).val(
          surCharge > 0 ? surCharge : courierCost
        );

      } else {
        $('#custom_surcharge_' + flag).val(courierCost);
      }

    } else {
      $('#custom_surcharge_' + flag).val(courierCost);
    }



    // $('#courierCost_'+flag).val(surCharge);
    finishCourierSurchargeAndDeliveredCosts(flag);

  }

  function recalculateCourierSurchargesFromCurrentSelection() {
    $('[id^="courierType_"]').each(function() {
      var match = (this.id || '').match(/^courierType_(\d+)$/);
      if (!match) {
        return;
      }
      var flag = parseInt(match[1], 10);
      if (isNaN(flag)) {
        return;
      }
      var courierType = $(this).val();
      if (!courierType || courierType === 'Select Courier') {
        return;
      }
      getCustomRateAjax(courierType, flag);
    });
  }

  function getCustomRateAjax(courierType, flag) {
    allCostNew(null, flag, false);
    $.ajax({
      type: "GET",
      url: '/pricing/get-custom-rate',
      data: {
        courierType: courierType,
        country: ($('#default_country_' + flag).val() || '').toString(),
      },
      success: function(response) {
        calCulateSurchargeCustomCondition(response, flag)
      },
    });
  }

  /** Find max Cost from Custom Condition */
  function findHighestCostFromCustomCondition(arr) {
    let maxValue = null;
    arr.forEach(subArray => {
      let value = parseInt(subArray[2]);

      if (maxValue === null || value > maxValue) {
        maxValue = value;
      }
    });
    return maxValue;
  }
</script>

@endsection