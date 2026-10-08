@extends('layouts.app')

@section('content')

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Pricing(Duplicate Pricing)</h2>
    </div>

    <form method="POST" action="{{ url('/pricing/duplicate')}}">
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
                  @if($pricing->productType == 0)
                  <option value="{{$tempProduct->id}}" {{($pricing->product_id == $tempProduct->id)?'selected':''}}>
                  @else
                  <option value="{{$tempProduct->id}}">
                  @endif
                  {{$tempProduct->code}} - {{$tempProduct->name}}
                  </option>
                @endforeach @endif
                </optgroup>
                <optgroup value="" label="Actual Products">
                @if(isset($products)) @foreach($products as $key => $product)
                 @if($pricing->productType == 1)
                  <option value="{{$product->id}}" {{($pricing->product_id == $product->id)?'selected':''}}>
                  @else
                  <option value="{{$product->id}}">
                @endif
                  {{$product->code}} - {{$product->name}}
                  </option>
                @endforeach @endif
                </optgroup>
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Buyer') }}</label> <a href="{{ url('/temporaryBuyer/create')}}" target="_blank" style="float: right;"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="buyer_id" required>
                <option value="" selected disabled>Select Buyer</option>
                @if(isset($tempBuyers)) @foreach($tempBuyers as $key => $tempBuyer)
                <option value="{{$tempBuyer->id}}">
                  {{$tempBuyer->c_name}}
                  </option>
                @endforeach @endif
              </select>
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Valid From') }}</label>
              <input type="date" class="form-control" name="startDate" value="{{$pricing->startDate}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Valid Till') }}</label>
              <input type="date" class="form-control" name="endDate" value="{{$pricing->endDate}}" />
            </div>
            <input type="hidden" name="productType" id="productType" value="{{$pricing->productType}}" />
          </div>

          <!-- third row -->
          <div class="row mt-3">
            <div class="col-8">
                <label class="control-label">{{ __('Reference') }}</label>
                <textarea class="form-control" name="remarks" rows="2">{{$pricing->remarks}}</textarea>
            </div>
          </div>


        <!-- Next Section 2-->

          <div class="row mx-0 mt-5">
            <h2>Product Details</h2>
          </div>

          <div class="row mt-3">
            <div class="col-4">
              <img class="img-thumbnail img-fluid product-img-100" id="imgURL" src="{{ asset('uploads/product/' . $theProduct->imageURL) }}" alt="Product Image" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Product Name') }}</label>
              <input type="text" id="name" class="form-control" name="name" value="{{$theProduct->name}}" disabled />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Product Height (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="height" id="height" step="any" placeholder="in cm" value="{{$theProduct->height}}" disabled/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Product Width (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="width" id="width" step="any" placeholder="in cm" value="{{$theProduct->width}}" disabled/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Product Depth (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="depth" id="depth" step="any" placeholder="in cm" value="{{$theProduct->depth}}" disabled/>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Box Height (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxheight" id="boxheight" step="any" placeholder="in cm" value="{{$theProduct->boxheight}}" disabled/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Box Width (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxwidth" id="boxwidth" step="any" placeholder="in cm" value="{{$theProduct->boxwidth}}" disabled/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Box Depth (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxdepth" id="boxdepth" step="any" placeholder="in cm" value="{{$theProduct->boxdepth}}"  disabled/>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('WholeSale Volume') }} <span>(m<sup>3</sup>)</span></label>
                <input type="number" min="0" class="form-control" name="wholesalevolume" id="wholesalevolume" step="any" placeholder="m3" value="{{$theProduct->wholesalevolume}}" disabled/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Dropship Volume') }} <span>(m<sup>3</sup>)</span></label>
                <input type="number" min="0" class="form-control" name="dropshipvolume" id="dropshipvolume" step="any" placeholder="m3" value="{{$theProduct->dropshipvolume}}" disabled/>
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
                <input type="number" min="0" class="form-control cost allCost" name="buyingCost" id="buyingCost" step="any" value="{{$pricing->buyingCost}}" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Fabric Type') }}</label>
                <input type="text" class="form-control" name="fabricCost" value="{{$pricing->fabricCost}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Tapestry Consumed (m)') }}</label>
                <input type="number" min="0" class="form-control tapestryCalculate" id="tapestryConsumed" name="tapestryConsumed" step="any" value="{{$pricing->tapestryConsumed}}" required />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Tapestry Unit Cost') }}</label>
                <input type="number" min="0" class="form-control tapestryCalculate" id="tapestryUnitCost" name="tapestryUnitCost" step="any" value="{{$pricing->tapestryUnitCost}}" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Tapestry Cost') }}</label>
                <input type="number" min="0" class="form-control cost allCost" id="tapestryCost" name="tapestryCost" step="any" value="{{$pricing->tapestryCost}}" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Filler Cost') }}</label>
                <input type="number" min="0" class="form-control cost allCost" name="fillerCost" id="fillerCost" step="any" value="{{$pricing->fillerCost}}" required />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Labour Cost') }}</label>
                <input type="number" min="0" class="form-control cost allCost" name="labourCost" id="labourCost" step="any" value="{{$pricing->labourCost}}" required/>
            </div>
            <div class="col-4">
                                <label class="control-label">{{ __('Hardware Cost 1') }}</label>
                                <div class="row">
                                        <div class="col-6">
                                                  <select type="text" class="selectpicker" data-live-search="true" name="hardware1" id="hardware1" >
                                                          <option value="0">Select</option>
                                                          @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                                                          <option value="{{$hardware->id}}" {{($pricing->hardware1 == $hardware->id)?'selected':''}}>
                                                          {{$hardware->name}}
                                                          </option>
                                                          @endforeach @endif
                                                  </select>
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control" name="hardware1_quantity" id="hardware1_quantity" step="any" placeholder="Q" value="{{$pricing->hardware1_quantity}}"  />
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control cost allCost" name="hardwareCost1" step="any" value="{{$pricing->hardwareCost1}}" id="hardware1_cost"  />
                                        </div>
                                </div>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Hardware Cost 2') }}</label>
                                <div class="row">
                                        <div class="col-6">
                                                  <select type="text" class="selectpicker" data-live-search="true" name="hardware2" id="hardware2" >
                                                          <option value="0">Select</option>
                                                          @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                                                          <option value="{{$hardware->id}}" {{($pricing->hardware2 == $hardware->id)?'selected':''}}>
                                                          {{$hardware->name}}
                                                          </option>
                                                          @endforeach @endif
                                                  </select>
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control" name="hardware2_quantity" id="hardware2_quantity" step="any" placeholder="Q" value="{{$pricing->hardware2_quantity}}"  />
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control cost allCost" name="hardwareCost2" step="any" value="{{$pricing->hardwareCost2}}" id="hardware2_cost"  />
                                        </div>
                                </div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Hardware Cost 3') }}</label>
                                <div class="row">
                                        <div class="col-6">
                                                  <select type="text" class="selectpicker" data-live-search="true" name="hardware3" id="hardware3" >
                                                          <option value="0">Select</option>
                                                          @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                                                          <option value="{{$hardware->id}}" {{($pricing->hardware3 == $hardware->id)?'selected':''}}>
                                                          {{$hardware->name}}
                                                          </option>
                                                          @endforeach @endif
                                                  </select>
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control" name="hardware3_quantity" id="hardware3_quantity" step="any" placeholder="Q" value="{{$pricing->hardware3_quantity}}"  />
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control cost allCost" name="hardwareCost3" step="any" value="{{$pricing->hardwareCost3}}" id="hardware3_cost" />
                                        </div>
                                </div>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Hardware Cost 4') }}</label>
                                <div class="row">
                                        <div class="col-6">
                                                  <select type="text" class="selectpicker" data-live-search="true" name="hardware4" id="hardware4" >
                                                          <option value="0">Select</option>
                                                          @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                                                          <option value="{{$hardware->id}}" {{($pricing->hardware4 == $hardware->id)?'selected':''}}>
                                                          {{$hardware->name}}
                                                          </option>
                                                          @endforeach @endif
                                                  </select>
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control" name="hardware4_quantity" id="hardware4_quantity" step="any" placeholder="Q" value="{{$pricing->hardware4_quantity}}" />
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control cost allCost" name="hardwareCost4" step="any" value="{{$pricing->hardwareCost4}}" id="hardware4_cost" />
                                        </div>
                                </div>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Hardware Cost 5') }}</label>
                                <div class="row">
                                        <div class="col-6">
                                                  <select type="text" class="selectpicker" data-live-search="true" name="hardware5" id="hardware5">
                                                          <option value="0">Select</option>
                                                          @if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                                                          <option value="{{$hardware->id}}" {{($pricing->hardware5 == $hardware->id)?'selected':''}}>
                                                          {{$hardware->name}}
                                                          </option>
                                                          @endforeach @endif
                                                  </select>
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control" name="hardware5_quantity" id="hardware5_quantity" step="any" placeholder="Q" value="{{$pricing->hardware5_quantity}}"  />
                                        </div>
                                        <div class="col-3">
                                                <input type="number" min="0" class="form-control cost allCost" name="hardwareCost5" step="any" value="{{$pricing->hardwareCost5}}" id="hardware5_cost"  />
                                        </div>
                                </div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Polish Unit Cost') }}</label>
                <input type="number" min="0" class="form-control" name="pUnitCost" id="pUnitCost" step="any" value="{{$pricing->pUnitCost}}" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Polish Cost') }}</label>
                <input type="number" min="0" class="form-control cost allCost" name="polishCost" id="polishCost" step="any" value="{{$pricing->polishCost}}" readonly />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <div>
                  <label class="control-label">{{ __('WholeSale Packaging Cost') }}</label>
                  @if($pricing->wSPackageCost > 0)
                  <input type="checkbox" id="wSPackageCostCheck" style="width: 20px; height: 17px; cursor: pointer;" checked />
                </div>
                <input type="number" min="0" class="form-control cost allCost" name="wSPackageCost" id="wSPackageCost" step="any" value="{{$pricing->wSPackageCost}}" />
                @else
                  <input type="checkbox" id="wSPackageCostCheck" style="width: 20px; height: 17px; cursor: pointer;" />
                </div>
                <input type="number" min="0" class="form-control allCost" name="wSPackageCost" id="wSPackageCost" step="any" value="0" disabled />
                @endif
            </div>
            <div class="col-4">
                <div>
                  <label class="control-label">{{ __('DropShip Packaging Cost') }}</label>
                  @if($pricing->dSPackageCost > 0)
                  <input type="checkbox" id="dSPackageCostCheck" style="width: 20px; height: 17px; cursor: pointer;" checked />
                </div>
                <input type="number" min="0" class="form-control cost allCost" name="dSPackageCost" id="dSPackageCost" step="any" value="{{$pricing->dSPackageCost}}" />
                @else
                <input type="checkbox" id="dSPackageCostCheck" style="width: 20px; height: 17px; cursor: pointer;"/>
                </div>
                <input type="number" min="0" class="form-control allCost" name="dSPackageCost" id="dSPackageCost" step="any" value="0" disabled />
                @endif
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost') }}</label>
                <input type="number" min="0" class="form-control cost allCost" name="shippingCost" id="shippingCost" step="any" value="{{$pricing->shippingCost}}" required />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Cost Price') }}</label>
                <input type="number" min="0" class="form-control" name="costPrice" id="costPrice" step="any" value="{{$pricing->costPrice}}" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Admin Cost (%)') }}</label>
                <input type="number" min="0" class="form-control allCost" name="adminCostPercent" id="adminCostPercent" step="any" value="{{$pricing->adminCostPercent}}" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Admin Cost') }}</label>
                <input type="number" min="0" class="form-control profitCost" name="adminCost" id="adminCost" step="any" value="{{$pricing->adminCost}}" readonly />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Profit (%)') }}</label>
                <input type="number" min="0" class="form-control profitCost allCost" name="profitPercent" id="profitCost" step="any" value="{{$pricing->profitPercent}}" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Final Cost') }}</label>
                <input type="number" min="0" class="form-control" name="finalCost" id="finalCost" step="any" value="{{$pricing->finalCost}}" readonly />
            </div>
          </div>

          <div class="row mx-0 mt-4">
            <h4>India Destination Cost</h4>
          </div>
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">Shipping Cost India</label>
              <input type="number" min="0" class="form-control india-cost-input" name="indiaShippingCost" id="indiaShippingCost" step="any" value="{{ $pricing->indiaShippingCost ?? 0 }}" />
            </div>
            <div class="col-4">
              <label class="control-label">India Cost Price</label>
              <input type="number" min="0" class="form-control" name="indiaCostPrice" id="indiaCostPrice" step="any" value="{{ $pricing->indiaCostPrice ?? 0 }}" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">India Admin Cost (%)</label>
              <input type="number" min="0" class="form-control india-cost-input" name="indiaAdminCostPercent" id="indiaAdminCostPercent" step="any" value="{{ (float) ($pricing->indiaAdminCostPercent ?? 0) > 0 ? $pricing->indiaAdminCostPercent : $pricing->adminCostPercent }}" />
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">India Admin Cost</label>
              <input type="number" min="0" class="form-control" name="indiaAdminCost" id="indiaAdminCost" step="any" value="{{ $pricing->indiaAdminCost ?? 0 }}" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">India Profit (%)</label>
              <input type="number" min="0" class="form-control india-cost-input" name="indiaProfitPercent" id="indiaProfitPercent" step="any" value="{{ (float) ($pricing->indiaProfitPercent ?? 0) > 0 ? $pricing->indiaProfitPercent : $pricing->profitPercent }}" />
            </div>
            <div class="col-4">
              <label class="control-label">India Final Cost</label>
              <input type="number" min="0" class="form-control" name="indiaFinalCost" id="indiaFinalCost" step="any" value="{{ $pricing->indiaFinalCost ?? 0 }}" readonly />
            </div>
          </div>

        <!-- / Next Section Cost 3-->

        <!--  Next Section FOB Cost 4-->
        <div class="row mx-0 mt-5">
          <h2>FOB Cost</h2>
        </div>

        <div class="row mt-3">
          <div class="col-4">
              <label class="control-label">{{ __('Currency') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" id="changecurrency" name="currency" required>
                <option value="" selected disabled>Select Currency</option>
                <option value="$" {{($pricing->currency == "$")?'selected':''}}>$</option>
                <option value="€" {{($pricing->currency == "€")?'selected':''}}>€</option>
                <option value="£" {{($pricing->currency == "£")?'selected':''}}>£</option>
                <option value="C$" {{($pricing->currency == "C$")?'selected':''}}>C$</option>
                <option value="₹" {{($pricing->currency == "₹")?'selected':''}}>₹</option>
              </select>
          </div>
          <div class="col-4">
                <label class="control-label">{{ __('Conversion Rate') }}</label>
                <input type="number" min="0" class="form-control allCost" name="converRate" id="converRate" step="any" value="{{$pricing->converRate}}" required />
          </div>
          <div class="col-4">
              <label class="control-label">{{ __('FOB India Cost') }}</label>
              <input type="number" min="0" class="form-control" name="fobINCost" id="fobINCost" step="any" value="{{$pricing->fobINCost}}" readonly />
          </div>
        </div>

        <!-- / Next Section FOB Cost 4-->


        <!--  Next Section 5-->
        <div class="row mx-0 mt-5">
          <h2>Shipping & Storage Cost</h2>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Box Wt. (kg)') }}</label>
                <input type="number" min="0" class="form-control" name="boxWt" id="boxWt" step="any" value="{{$pricing->boxWt}}" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Volumetric Wt. (kg)') }}</label>
                <input type="number" min="0" class="form-control" name="volWt" id="volWt" step="any" value="{{$pricing->volWt}}" readonly />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost') }}</label>
                <input type="number" min="0" class="form-control allCost" name="shippingCost2" id="shippingCost2" step="any" value="{{$pricing->shippingCost2}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Storage Cost (1.5 month)') }}</label>
                <input type="number" min="0" class="form-control allCost" name="StorageCost" id="StorageCost" step="any" value="{{$pricing->StorageCost}}" />
            </div>
        </div>


        <!--  Next Section 6-->
        <div class="row mx-0 mt-5">
          <h2>Fulfilment Cost
            @if($pricing->deliveredCostStatus == 1)<input type="checkbox" id="deliveredCostChecked" style="width: 40px; height: 20px; cursor: pointer;" checked />
            @else
            <input type="checkbox" id="deliveredCostChecked" style="width: 40px; height: 20px; cursor: pointer;"/>
            @endif
          </h2>
        </div>

        <div id="deliveryCostSection" class="d-none">
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Buyer 2') }}</label> <a href="{{ url('/temporaryBuyer/create')}}" target="_blank" style="float: right;"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="buyer_id2" required>
                <option value="" selected disabled>Select Buyer</option>
                @if(isset($tempBuyers)) @foreach($tempBuyers as $key => $tempBuyer)
                <option value="{{$tempBuyer->id}}">
                  {{$tempBuyer->c_name}}
                  </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Country of Destination') }}</label>
                <input type="text" class="form-control" name="destination" id="destination" value="{{$pricing->destination}}" />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Outbound Charges') }}</label>
                <input type="number" min="0" class="form-control allCost delCostCalculate" name="adminCost2" id="adminCost2" step="any" value="{{$pricing->adminCost2}}" required disabled />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Quality Assurance') }}</label>
                <input type="number" min="0" class="form-control allCost delCostCalculate" name="qualityAssurance" id="qualityAssurance" step="any" value="{{$pricing->qualityAssurance}}" required disabled />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Landed Cost') }}</label>
                <input type="number" min="0" class="form-control" name="landedCost" id="landedCost" step="any" value="{{$pricing->landedCost}}" readonly disabled />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Admin Profit (%)') }}</label>
                <input type="number" min="0" class="form-control allCost delCostCalculate" name="adminProfit" id="adminProfit" step="any" value="{{$pricing->adminProfit}}" required disabled />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Admin Price') }}</label>
                <input type="number" min="0" class="form-control" name="adminPrice" id="adminPrice" step="any" value="{{$pricing->adminPrice}}" readonly disabled />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Final Price (%)') }}</label>
                <input type="number" min="0" class="form-control allCost delCostCalculate" name="finalPricePer" id="finalPricePer" step="any" value="{{$pricing->finalPricePer}}" required disabled />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Final Price') }}</label>
                <input type="number" min="0" class="form-control" name="finalPrice" id="finalPrice" step="any" value="{{$pricing->finalPrice}}" readonly disabled />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Type of Courier') }}</label>
                <select type="text" class="selectpicker allCost" data-live-search="true" name="courierType" id="courierType" onchange="changeCourierRate(this)">
                  <option selected>Select Courier</option>
                  @if(isset($couriers)) @foreach($couriers as $key => $courier)
                    <option value="{{$courier->name}}" data-rate="{{$courier->rate}}" data-weight="{{$courier->fixed_rate_weight}}" data-perkg="{{$courier->rate_per_kg}}" data-fper="{{$courier->fuel_charge_percent}}" data-bands="{{ json_encode($courier->weight_rate_tiers) }}" {{($pricing->courierType == $courier->name)?'selected':''}}>
                    {{$courier->name}}
                    </option>
                  @endforeach @endif
                </select>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Courier Cost') }}</label>
                <input type="number" min="0" class="form-control allCost" name="courierCost" id="courierCost" step="any" value="{{$pricing->courierCost}}" required disabled />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Delivery Cost') }}</label>
                <input type="number" min="0" class="form-control allCost" name="deliveryCost" id="deliveryCost" step="any" value="{{$pricing->deliveryCost}}" readonly disabled />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Cost Adjustment (%)') }}</label>
                <input type="number" min="0" class="form-control allCost" name="adjustment" id="adjustment" step="any" value="{{$pricing->adjustment}}" required disabled />
            </div>
          </div>



          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Final Delivered Cost') }}</label>
                <input type="number" min="0" class="form-control" name="newDelCost" id="newDelCost" step="any" value="{{$pricing->newDelCost}}" readonly disabled />
            </div>
          </div>
        </div>
        <input type="hidden" name="deliveredCostStatus" id="deliveredCostStatus" value="{{$pricing->deliveredCostStatus}}" />
        <!-- / Next Section 6-->

        <!-- Next Section 7 Hidden fields for Setting -->
        @if(isset($settings)) @foreach($settings as $key => $setting)
            <input type="hidden" name="settingVolWt" id="settingVolWt" value="{{$setting->volWt}}" />
            <input type="hidden" name="settingShippingCost2" id="settingShippingCost2" value="{{$setting->shippingCost2}}" />
            <input type="hidden" name="settingStorageCost" id="settingStorageCost" value="{{$setting->StorageCost}}" />
            {{-- Match pricing/view.blade.php: India cost building blocks from settings row --}}
            <input type="hidden" name="settingIndiaShipping" id="settingIndiaShipping" value="{{ $setting->ishippingcost ?? 0 }}" />
            <input type="hidden" name="settingWholsalePackaging" id="settingWholsalePackaging" value="{{ $setting->wspackaging ?? 0 }}" />
            <input type="hidden" name="settingDropshipPackaging" id="settingDropshipPackaging" value="{{ $setting->dspackaging ?? 0 }}" />
        @endforeach @endif
        <!-- / Next Section 7 Hidden fields for Setting -->

        <div class="row col-4 mt-3">
          <button type="submit" class="btn btn-primary mt-3">Add Pricing</button>
        </div>

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
            tempProducts = data.tempProduct;
                        hardwares = data.hardwares;
        }
      });

    // Checkbox for Delivered Cost Section
    if($('#deliveredCostChecked').is(':checked')){
      $('#deliveryCostSection').removeClass('d-none');
      $('#deliveryCostSection :input').prop('disabled', false);
    }
    else{
      $('#deliveryCostSection').addClass('d-none');
      $('#deliveryCostSection :input').prop('disabled', true);
    }

  });

  var products = [];
  var tempProducts = [];

  function changeDetails(ref){
    var id = $(ref).val();
    var labelName = $('#selectProduct :selected').parent().attr('label');

        function findHardware(hardware) {
      return hardware.id == hid;
    }

    function findProduct(product) {
      return product.id == id;
    }

    function findTempProduct(tempProduct) {
      return tempProduct.id == id;
    }

        function updateDropdownText(hobj, hcobj, hqty){
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

    if(labelName != "Temporary Products"){
        var product = products.find(findProduct);
        $("#imgURL").attr("src", "{{ asset('uploads/product')}}/"+product.imageURL);
        $("#name").val(product.name);
        $("#width").val(product.width);
        $("#height").val(product.height);
        $("#depth").val(product.depth);
        $("#boxwidth").val(product.boxwidth);
        $("#boxheight").val(product.boxheight);
        $("#boxdepth").val(product.boxdepth);
        $("#wholesalevolume").val(product.wholesalevolume);
        $("#dropshipvolume").val(product.dropshipvolume);
        var productType = 1;
        $('#productType').val(productType);
                $('#hardware1').val(product.hardware1);
                $('#hardware2').val(product.hardware2);
                $('#hardware3').val(product.hardware3);
                $('#hardware4').val(product.hardware4);
                $('#hardware5').val(product.hardware5);
                updateDropdownText($('#hardware1'), $('#hardware1_cost'), product.hardware1_quantity, $('#hardware1_quantity'));
                updateDropdownText($('#hardware2'), $('#hardware2_cost'), product.hardware2_quantity, $('#hardware2_quantity'));
                updateDropdownText($('#hardware3'), $('#hardware3_cost'), product.hardware3_quantity, $('#hardware3_quantity'));
                updateDropdownText($('#hardware4'), $('#hardware4_cost'), product.hardware4_quantity, $('#hardware4_quantity'));
                updateDropdownText($('#hardware5'), $('#hardware5_cost'), product.hardware5_quantity, $('#hardware5_quantity'));
                $('#hardware1_quantity').val(product.hardware1_quantity);
                $('#hardware2_quantity').val(product.hardware2_quantity);
                $('#hardware3_quantity').val(product.hardware3_quantity);
                $('#hardware4_quantity').val(product.hardware4_quantity);
                $('#hardware5_quantity').val(product.hardware5_quantity);
    }
      else{
        var tempProduct = tempProducts.find(findTempProduct);
        $("#imgURL").attr("src", "{{ asset('uploads/product')}}/"+tempProducts.imageURL);
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
                $('#hardware1').val(tempProduct.hardware1);
                $('#hardware2').val(tempProduct.hardware2);
                $('#hardware3').val(tempProduct.hardware3);
                $('#hardware4').val(tempProduct.hardware4);
                $('#hardware5').val(tempProduct.hardware5);
                updateDropdownText($('#hardware1'), $('#hardware1_cost'), tempProduct.hardware1_quantity, $('#hardware1_quantity'));
                updateDropdownText($('#hardware2'), $('#hardware2_cost'), tempProduct.hardware2_quantity, $('#hardware2_quantity'));
                updateDropdownText($('#hardware3'), $('#hardware3_cost'), tempProduct.hardware3_quantity, $('#hardware3_quantity'));
                updateDropdownText($('#hardware4'), $('#hardware4_cost'), tempProduct.hardware4_quantity, $('#hardware4_quantity'));
                updateDropdownText($('#hardware5'), $('#hardware5_cost'), tempProduct.hardware5_quantity, $('#hardware5_quantity'));
                $('#hardware1_quantity').val(tempProduct.hardware1_quantity);
                $('#hardware2_quantity').val(tempProduct.hardware2_quantity);
                $('#hardware3_quantity').val(tempProduct.hardware3_quantity);
                $('#hardware4_quantity').val(tempProduct.hardware4_quantity);
                $('#hardware5_quantity').val(tempProduct.hardware5_quantity);
      }

    var dropshipvolume = Number($('#dropshipvolume').val()) || 0;
    var pUnitCost = Number($('#pUnitCost').val()) || 0;
    var wholesalevolume = Number($('#wholesalevolume').val()) || 0;
    // Same divisor and setting-driven rates as pricing/view.blade.php (edit)
    $('#polishCost').val(((dropshipvolume * pUnitCost) / 60).toFixed(2));
    var wspackagingsetting = Number($('#settingWholsalePackaging').val()) || 0;
    var dspackagingsetting = Number($('#settingDropshipPackaging').val()) || 0;
    var ishippingcost = Number($('#settingIndiaShipping').val()) || 0;
    $('#wSPackageCost').val(((wholesalevolume * wspackagingsetting) / 60).toFixed(2));
    $('#dSPackageCost').val(((dropshipvolume * dspackagingsetting) / 60).toFixed(2));
    $('#shippingCost').val(((dropshipvolume * ishippingcost) / 60).toFixed(2));

    var settingShippingCost2 = $('#settingShippingCost2').val();
    var shippingCost2 = (dropshipvolume*settingShippingCost2);
    $('#shippingCost2').val(shippingCost2.toFixed(2));

    var settingStorageCost = $('#settingStorageCost').val();
    var StorageCost = (dropshipvolume*settingStorageCost*1.5);
    $('#StorageCost').val(StorageCost.toFixed(2));

    var width = $('#boxwidth').val();
    var height = $('#boxheight').val();
    var depth = $('#boxdepth').val();
    var settingVolWt = $('#settingVolWt').val();
    var volWt = (width*height*depth)/settingVolWt;
    $('#volWt').val(volWt.toFixed(2));

    var shippingCost2 = $('#shippingCost2').val();
    var StorageCost = $('#StorageCost').val();
    var fobINCost = $('#fobINCost').val();
    var adminCost2 = $('#adminCost2').val();
    var qualityAssurance = $('#qualityAssurance').val();
    var sublanded = parseFloat(shippingCost2)+parseFloat(StorageCost)+parseFloat(adminCost2)+parseFloat(qualityAssurance)+parseFloat(fobINCost);
    $('#landedCost').val(sublanded.toFixed(2));
    allCost();
}

// Calculate Tapestry Cost
$('.tapestryCalculate').change(function() {
    var tapestryConsumed = $('#tapestryConsumed').val();
    var tapestryUnitCost = $('#tapestryUnitCost').val();
    var output = tapestryConsumed * tapestryUnitCost;
    $('#tapestryCost').val(output.toFixed(2));
    allCost();
});

// Calculate Polish Cost
$('#pUnitCost').change(function() {
    var dropshipvolume = Number($('#dropshipvolume').val()) || 0;
    var pUnitCost = Number($('#pUnitCost').val()) || 0;
    $('#polishCost').val(((dropshipvolume * pUnitCost) / 60).toFixed(2));
    allCost();
});

//Check Box
$('#wSPackageCostCheck').click(function(){
    if($(this).is(':checked')){
      $('#wSPackageCost').prop('disabled', false);
      $('#wSPackageCost').addClass('cost');
      allCost();
    }
    else{
      $('#wSPackageCost').prop('disabled', true);
      $('#wSPackageCost').removeClass('cost');
      allCost();
    }
});

$('#dSPackageCostCheck').click(function(){
    if($(this).is(':checked')){
      $('#dSPackageCost').prop('disabled', false);
      $('#dSPackageCost').addClass('cost');
      allCost();
    }
    else{
      $('#dSPackageCost').prop('disabled', true);
      $('#dSPackageCost').removeClass('cost');
      allCost();
    }
});

// Checkbox for Delivered Cost Section
$('#deliveredCostChecked').click(function(){
    if($(this).is(':checked')){
      $('#deliveryCostSection').removeClass('d-none');
      $('#deliveryCostSection :input').prop('disabled', false);
      var deliveredOutput = 0;
      $('#boxWt').val(deliveredOutput);
      $('#adminCost2').val(deliveredOutput);
      $('#qualityAssurance').val(deliveredOutput);
      $('#adminProfit').val(deliveredOutput);
      $('#finalPricePer').val(deliveredOutput);
      $('#courierCost').val(deliveredOutput);

      var dropshipvolume = $('#dropshipvolume').val();
      var shippingCost2 = (dropshipvolume*30);
      $('#shippingCost2').val(shippingCost2.toFixed(2));

      var StorageCost = (dropshipvolume*11*1.5);
      $('#StorageCost').val(StorageCost.toFixed(2));

      var width = $('#boxwidth').val();
      var height = $('#boxheight').val();
      var depth = $('#boxdepth').val();
      var volWt = (width*height*depth)/4000;
      $('#volWt').val(volWt.toFixed(2));
      var deliveredCostStatus = 1;
      $('#deliveredCostStatus').val(deliveredCostStatus);
      allCost();
    }
    else{
      $('#deliveryCostSection').addClass('d-none');
      $('#deliveryCostSection :input').prop('disabled', true);
      var deliveredCostStatus = 0;
      $('#deliveredCostStatus').val(deliveredCostStatus);
      allCost();
    }
});

$('.allCost').change(function() {
  allCost();
});

$(document).on('input change', '.india-cost-input', function() {
  allCost();
});

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

  return indiaFinalCost;
}

$(document).ready(function() {
  recalcIndiaCostStack();
});


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

function changeCourierRate(ref){
  var rate = $(ref).find(':selected').data('rate');
  var fixedrateweight = $(ref).find(':selected').data('weight');
  var rateperkg = $(ref).find(':selected').data('perkg');
  var fper = $(ref).find(':selected').data('fper');
  var bands = $(ref).find(':selected').data('bands');
  var volWt = parseFloat($("#volWt").val()) || 0;
  rate = rate + tierOverageCharge(bands, volWt, fixedrateweight, rateperkg);
  var finalcouriercost = rate;
  var fuelCharge = (fper*finalcouriercost) / 100;
  rate = rate + fuelCharge;
  $('#courierCost').val(rate.toFixed(2));
  allCost();
}


function allCost(){
  //Section - Cost INR
    //Cost Price Calculation - INR
    var isIndiaDestination = (($('#destination').val() || '').toString().trim().toLowerCase() === 'india');
    var costPrice = 0;
    $('.cost').each(function () {
        if($(this).val() == ''){
          $(this).val(0);
        }
        costPrice += parseFloat($(this).val());
    });
    costPrice = costPrice.toFixed(2);
    $('#costPrice').val(costPrice);
    if($('#qualityAssurance').val() == ""){
        $('#qualityAssurance').val(0);
    }
    //Admin Cost
    var adminCostPercent = $('#adminCostPercent').val();
    var subadminCost = (costPrice*adminCostPercent)/100;
    adminCost = parseFloat(costPrice)+parseFloat(subadminCost);
    adminCost = adminCost.toFixed(2);
    $('#adminCost').val(adminCost);

    //Final Cost
    var profitCost = $('#profitCost').val();
    var subfinalCost = (adminCost*profitCost)/100;
    finalCost = parseFloat(adminCost)+parseFloat(subfinalCost);
    finalCost = finalCost.toFixed(2);
    $('#finalCost').val(finalCost);

    var indiaFinalCost = recalcIndiaCostStack();

    if (isIndiaDestination) {
      var indiaCourierCost = Number($('#courierCost').val()) || 0;
      var indiaDeliveryCost = Number((indiaFinalCost + indiaCourierCost).toFixed(2));
      $('#changecurrency').val('₹').change();
      $('#converRate').val(0);
      $('#fobINCost').val(0);
      $('#shippingCost2').val(0);
      $('#StorageCost').val(0);
      $('#adminCost2').val(0);
      $('#qualityAssurance').val(0);
      $('#landedCost').val(indiaFinalCost.toFixed(2));
      $('#adminProfit').val(0);
      $('#adminPrice').val(indiaFinalCost.toFixed(2));
      $('#finalPricePer').val(0);
      $('#finalPrice').val(indiaFinalCost.toFixed(2));
      $('#adjustment').val(0);
      $('#deliveryCost').val(indiaDeliveryCost);
      $('#newDelCost').val(Math.round(indiaDeliveryCost));
      return;
    }

  //Section - FOB INDIA
    //FOB India Cost
    var converRate = $('#converRate').val();
    var fobINCost = parseFloat(finalCost)/parseFloat(converRate);
    fobINCost = fobINCost.toFixed(2);
        fobINCost = Math.round(fobINCost);
    $('#fobINCost').val(fobINCost);

  //Section - Section 5
    // Landed Cost
    var shippingCost2 = $('#shippingCost2').val();
    var StorageCost = $('#StorageCost').val();
    var adminCost2 = $('#adminCost2').val();
    var qualityAssurance = $('#qualityAssurance').val();
    var sublanded = parseFloat(shippingCost2)+parseFloat(StorageCost)+parseFloat(adminCost2)+parseFloat(qualityAssurance)+parseFloat(fobINCost);
    sublanded = sublanded.toFixed(2);
    $('#landedCost').val(sublanded);

    // Admin Price
    var adminProfit = $('#adminProfit').val();
    var output = (sublanded*adminProfit)/100;
    var adminPrice = parseFloat(sublanded)+parseFloat(output);
    adminPrice = adminPrice.toFixed(2);
    $('#adminPrice').val(adminPrice);

    // Final Price
    var finalPricePercent = $('#finalPricePer').val();
    var output1 = (adminPrice*finalPricePercent)/100;
    var finalPrice = parseFloat(adminPrice)+parseFloat(output1);
    finalPrice = finalPrice.toFixed(2);
    $('#finalPrice').val(finalPrice);

    // Delivered Cost
    var courierCost = $('#courierCost').val();
    var deliveryCost = parseFloat(finalPrice)+parseFloat(courierCost);
    deliveryCost = parseFloat(deliveryCost.toFixed(2));
    $('#deliveryCost').val(deliveryCost);

    // Cost Adjustment
    var adjustment = $('#adjustment').val();
    var adjustmentOutput = (deliveryCost*adjustment)/100;
    var finalAdjustOutput = parseFloat(deliveryCost)+parseFloat(adjustmentOutput);

    // New Delivered Cost
    var newDeliveryCost = finalAdjustOutput.toFixed();
    $('#newDelCost').val(newDeliveryCost);
}


</script>

@endsection