@extends('layouts.app')

@section('content')
<style>
   td {
        padding: 20px; 
    }
    th {
      padding-left: 20px !important; 
    }
</style>
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Add Courier</h2>
        </div>

        <form method="POST" action="{{ url('/courier/create') }}">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">


                <div class=" row col-4">
                    <label class="control-label">{{ __('Courier Name') }}</label>
                    <input type="text" class="form-control" name="name" required="required" />
                    @if (isset($errors))
                        <p class="mt-2 mb-0" style="color:red">{{ $errors->first('name') }}</p>
                    @endif
                </div>

                <div class="row col-4 mt-3">
                    <label class="control-label">{{ __('Country') }}</label>
                    <select class="form-control" name="country" required="required" onchange="changeCurrency(this.value)" required>
                        <option >select Country</option>
                        <?php
                    foreach ($countriesArray as $key => $countriesArrayGet) { ?>
                        <option value="{{ $key }}">{{ $countriesArrayGet }}
                        </option>
                        <?php }
              ?>
                    </select>
                    @if (isset($errors))
                        <p class="mt-2 mb-0" style="color:red">{{ $errors->first('country') }}</p>
                    @endif
                </div>

                <div class=" row col-4 mt-3">
                    <label class="control-label">{{ __('Courier Rate') }}</label>
                    <input type="number" class="form-control" name="rate" step="any" required="required" />
                </div>

                <div class="row col-8 mt-3">
                    <label class="control-label" id="courier_lbl_fixed_weight">{{ __('Weight rate bands (KG)') }}</label>
                    <table class="table table-bordered mb-1" id="weight_tiers_table">
                        <thead>
                            <tr>
                                <th>Over weight (<span class="tier-unit-label">KG</span>)</th>
                                <th>Till weight (<span class="tier-unit-label">KG</span>, blank = and above)</th>
                                <th>Rate per <span class="tier-unit-label">KG</span></th>
                                <th style="width:60px">Action</th>
                            </tr>
                        </thead>
                        <tbody id="weight_tiers_body">
                            <tr class="tier-row">
                                <td><input type="number" step="any" min="0" class="form-control" name="tier_weight_from[]"></td>
                                <td><input type="number" step="any" min="0" class="form-control" name="tier_weight_to[]" placeholder="∞"></td>
                                <td><input type="number" step="any" min="0" class="form-control" name="tier_rate[]"></td>
                                <td class="text-center align-middle">
                                    <i class="fa fa-trash text-danger" style="cursor:pointer;font-size:18px" onclick="removeTierRow(this)"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addTierRow()">Add Weight Band</button>
                    </div>
                    <p class="text-muted small mb-0 mt-1">
                        The lowest "Over weight" is the included fixed weight. When chargeable weight exceeds it, the whole overage is charged at the rate of the highest matching band (not per band).
                    </p>
                </div>

                <div class="row mt-2">
                    <div class="col-lg-8">
                        <p class="text-muted small mb-0" id="courier_weight_rate_help" style="display: none;">
                            <strong id="courier_weight_rate_help_country">US / Canada:</strong> Enter bands and rates in lbs. Pricing compares volumetric weight (lbs) to the bands; each lb over the fixed weight is multiplied by the matching band rate, then fuel % applies to the total.
                        </p>
                    </div>
                </div>

                <div class=" row col-4 mt-3">
                    <label class="control-label">{{ __('Fuel Charge Percent') }}</label>
                    <input type="text" class="form-control" name="fuel_charge_percent" step="any" />
                </div>
                <div class=" row col-4 mt-3">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_default" value="1" />
                            Is Default
                        </label>
                    </div>
                </div>

                @include('courier.partials.us_rule_blocks', ['usRuleBlocksArray' => $usRuleBlocksArray ?? []])

                <div class="row">
                    <div class="col-2">
                        <h2>Conditions</h2>
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-primary mt-3" style="float: right" onclick="addCondition()">Add Custom Price
                            Condition</button>
                    </div>
                </div>
              <div class="hide_show mt-3" style="display: none">
    <table class="table table-bordered" width="100%" cellspacing="0">
        <thead>
            <tr>
                <th>Attribute</th>
                <th> </th>
                <th>Attribute Value</th>
                <th>Measurements</th>
                <th>Popup Price (<span id="change_currency">£</span>)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody class="append_tr">

        </tbody>
    </table>
</div>

            </div>
          </div>

</div>

<button type="submit" class="btn btn-success mt-3">Add Courier</button>
<input type="hidden" name="incValue" id="incValue" value="0">
</form>
</div>

<script>
let incValue = 0;
const measurementUnitMap = @json($measurementUnitMap ?? []);

/* ADD CONDITION ROW */
function addCondition(){

let html = `
<tr class="incValue_${incValue}">
<td>
<select class="form-control"
    name="attribute[]"
    onchange="getChangesUnit(this.value, ${incValue})"
    id="attribute_${incValue}">
    @foreach($measurementsArray as $k => $v)
        <option value="{{ $k }}">{{ $v }}</option>
    @endforeach
</select>
</td>

<td><b>></b></td>

<td id="box_values_${incValue}">
    <div class="box-values-container">
        <div class="box-row">
            <input type="number" class="form-control"
                name="attribute_val[${incValue}][]" required>
        </div>
    </div>
    <button type="button"
        class="btn btn-sm btn-secondary mt-2 d-none"
        id="add_more_btn_${incValue}"
        onclick="addMoreBoxRows(${incValue})">Add More</button>
</td>

<td>
<select class="form-control" id="attribute_units_${incValue}" disabled>
    @foreach($getMeasurementsUnits as $k => $v)
        <option value="{{ $k }}">{{ $v }}</option>
    @endforeach
</select>
</td>

<td id="popup_price_${incValue}">
    <div class="price-row">
        <input type="number" step="any"
            class="form-control"
            name="popup_price[${incValue}][]" required>
    </div>
</td>

<td id="action_box_${incValue}">
    <div class="action-rows">
        <div class="action-row">
            <i class="fa fa-trash text-danger"
                style="cursor:pointer;font-size:22px"
                onclick="removeCloneFulfillment(${incValue})"></i>
        </div>
    </div>
</td>
</tr>`;

$('.append_tr').append(html);
$('.hide_show').show();
incValue++;
$('#incValue').val(incValue);
}

/* REMOVE FULL CONDITION */
function removeCloneFulfillment(i){
    $('.incValue_'+i).remove();
}

/* UNIT CHANGE */
function getChangesUnit(value, i){
    $('#add_more_btn_'+i).addClass('d-none');

    if(value === 'box'){
        $('#add_more_btn_'+i).removeClass('d-none');
    }
    if (measurementUnitMap[value]) {
        $('#attribute_units_'+i).val(measurementUnitMap[value]);
    }
}

/* ADD MORE BOX */
function addMoreBoxRows(i){

let index = $('#box_values_'+i+' .box-row').length;

$('#box_values_'+i+' .box-values-container').append(`
<div class="box-row mt-2">
    <input type="number" class="form-control"
        name="attribute_val[${i}][]" required>
</div>`);

$('#popup_price_'+i).append(`
<div class="price-row mt-2">
    <input type="number" step="any"
        class="form-control"
        name="popup_price[${i}][]" required>
</div>`);

$('#action_box_'+i+' .action-rows').append(`
<div class="action-row mt-2">
    <button type="button" class="btn btn-danger btn-sm"
        onclick="removeBoxRow(${i}, ${index})">×</button>
</div>`);
}

/* REMOVE SINGLE BOX ROW */
function removeBoxRow(i, index){
if(index === 0){
    alert('Minimum one box value required');
    return;
}
$('#box_values_'+i+' .box-row').eq(index).remove();
$('#popup_price_'+i+' .price-row').eq(index).remove();
$('#action_box_'+i+' .action-row').eq(index).remove();
}

/* WEIGHT BAND ROWS */
function addTierRow(){
    $('#weight_tiers_body').append(`
        <tr class="tier-row">
            <td><input type="number" step="any" min="0" class="form-control" name="tier_weight_from[]"></td>
            <td><input type="number" step="any" min="0" class="form-control" name="tier_weight_to[]" placeholder="∞"></td>
            <td><input type="number" step="any" min="0" class="form-control" name="tier_rate[]"></td>
            <td class="text-center align-middle">
                <i class="fa fa-trash text-danger" style="cursor:pointer;font-size:18px" onclick="removeTierRow(this)"></i>
            </td>
        </tr>
    `);
}
function removeTierRow(el){
    if ($('#weight_tiers_body .tier-row').length <= 1) {
        $(el).closest('tr').find('input').val('');
        return;
    }
    $(el).closest('tr').remove();
}

/* Display-only currency / unit labels by country (does not affect calculations). */
function courierCurrencySymbol(val) {
    var u = String(val || '').toUpperCase();
    if (u === 'US') return '$';
    if (u === 'CANADA') return 'C$';
    if (u === 'EU') return '€';
    if (u === 'AUSTRALIA') return 'A$';
    if (u === 'UK') return '£';
    return '£';
}

function changeCurrency(val){
    var u = String(val || '').toUpperCase();
    var lbs = (u === 'US' || u === 'CANADA');
    var fixedLbl = document.getElementById('courier_lbl_fixed_weight');
    var help = document.getElementById('courier_weight_rate_help');
    var helpCountry = document.getElementById('courier_weight_rate_help_country');
    if (fixedLbl) fixedLbl.textContent = lbs ? 'Weight rate bands (LBS)' : 'Weight rate bands (KG)';
    $('.tier-unit-label').text(lbs ? 'LBS' : 'KG');
    if (help) help.style.display = lbs ? 'block' : 'none';
    if (helpCountry) {
        if (u === 'US') helpCountry.textContent = 'US:';
        else if (u === 'CANADA') helpCountry.textContent = 'Canada:';
        else helpCountry.textContent = 'US / Canada:';
    }

    $('#change_currency').text(courierCurrencySymbol(val));

    var blockCountries = ['US', 'UK', 'EU', 'CANADA', 'AUSTRALIA'];
    var showBlocks = blockCountries.indexOf(u) >= 0;
    var wrap = document.getElementById('us-rule-engine-wrap');
    if (wrap) wrap.style.display = showBlocks ? 'block' : 'none';
    if (typeof window.setRuleBlocksCountryMode === 'function') {
        window.setRuleBlocksCountryMode(showBlocks ? val : '');
    }
}
</script>
@endsection
