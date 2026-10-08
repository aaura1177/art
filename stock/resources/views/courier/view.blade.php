@extends('layouts.app')

@section('content')

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Courier</h2>
    </div>

    <form method="POST" action="{{ url('/courier/view/'.$courier->id) }}">
        @csrf

        <div class="form-group">

            <!-- BASIC INFO -->
            <div class="row col-4">
                <label>Courier Name</label>
                <input type="text" class="form-control" name="name"
                       value="{{ $courier->name }}" required>
            </div>

            <div class="row col-4 mt-3">
                <label>Country</label>
                <select class="form-control" name="country" onchange="toggleCountryRuleBlocksWrap(this.value)">
                    @foreach($countriesArray as $k => $v)
                        <option value="{{ $k }}" {{ $k==$courier->country?'selected':'' }}>
                            {{ $v }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row col-4 mt-3">
                <label>Courier Rate</label>
                <input type="number" step="0.1" class="form-control" name="rate"
                       value="{{ $courier->rate }}">
            </div>

            @php
                $isLbsCourier = in_array(strtoupper((string) $courier->country), ['US', 'CANADA'], true);
                $tierUnit = $isLbsCourier ? 'LBS' : 'KG';
                $tiersForForm = $weightRateTiers ?? [];
                if (count($tiersForForm) === 0) {
                    $tiersForForm = [['from' => $courier->fixed_rate_weight, 'to' => null, 'rate' => $courier->rate_per_kg]];
                }
            @endphp
            <div class="row col-8 mt-3">
                <label id="courier_lbl_fixed_weight">Weight rate bands ({{ $tierUnit }})</label>
                <table class="table table-bordered mb-1" id="weight_tiers_table">
                    <thead>
                        <tr>
                            <th>Over weight (<span class="tier-unit-label">{{ $tierUnit }}</span>)</th>
                            <th>Till weight (<span class="tier-unit-label">{{ $tierUnit }}</span>, blank = and above)</th>
                            <th>Rate per <span class="tier-unit-label">{{ $tierUnit }}</span></th>
                            <th style="width:60px">Action</th>
                        </tr>
                    </thead>
                    <tbody id="weight_tiers_body">
                        @foreach($tiersForForm as $tier)
                        <tr class="tier-row">
                            <td><input type="number" step="any" min="0" class="form-control" name="tier_weight_from[]" value="{{ $tier['from'] }}"></td>
                            <td><input type="number" step="any" min="0" class="form-control" name="tier_weight_to[]" value="{{ $tier['to'] }}" placeholder="∞"></td>
                            <td><input type="number" step="any" min="0" class="form-control" name="tier_rate[]" value="{{ $tier['rate'] }}"></td>
                            <td class="text-center align-middle">
                                <i class="fa fa-trash text-danger" style="cursor:pointer;font-size:18px" onclick="removeTierRow(this)"></i>
                            </td>
                        </tr>
                        @endforeach
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
                    <p class="text-muted small mb-0" id="courier_weight_rate_help" style="{{ $isLbsCourier ? '' : 'display: none;' }}">
                        <strong id="courier_weight_rate_help_country">{{ strtoupper((string) $courier->country) === 'CANADA' ? 'Canada:' : 'US:' }}</strong> Values are in lbs. Volumetric weight (lbs) over the fixed weight × matching band rate per lb, then fuel % on total.
                    </p>
                </div>
            </div>

            <div class="row col-4 mt-3">
                <label>Fuel Charge Percent</label>
                <input class="form-control" name="fuel_charge_percent"
                       value="{{ $courier->fuel_charge_percent }}">
            </div>

            <div class="row col-4 mt-3">
                <label>
                    <input type="checkbox" name="is_default" value="1"
                        {{ $courier->is_default ? 'checked' : '' }}>
                    Is Default
                </label>
            </div>

            <!-- CONDITIONS -->
            <div class="row mt-4">
                <div class="col-3"><h3>Conditions</h3></div>
                <div class="col-3">
                    <button type="button" class="btn btn-primary"
                            onclick="addCondition()">Add Custom Condition</button>
                </div>
            </div>

            <div class="hide_show mt-3" style="{{ count($customCondition)?'':'display:none' }}">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Attribute</th>
                            <th></th>
                            <th>Attribute Value</th>
                            <th>Unit</th>
                            @php
                                $popupCurrency = match (strtoupper((string) $courier->country)) {
                                    'US' => '$',
                                    'CANADA' => 'C$',
                                    'EU' => '€',
                                    'AUSTRALIA' => 'A$',
                                    default => '£',
                                };
                            @endphp
                            <th>Popup Price (<span id="change_currency">{{ $popupCurrency }}</span>)</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody class="append_tr">
                    @foreach($customCondition as $i => $cond)
                    <tr class="incValue_{{$i}}">

                        <!-- ATTRIBUTE -->
                        <td>
                            <select class="form-control"
                                name="attribute[{{$i}}]"
                                onchange="getChangesUnit(this.value, {{$i}})">
                                @foreach($measurementsArray as $k=>$v)
                                    <option value="{{$k}}" {{ ($cond['attribute'] ?? null)==$k?'selected':'' }}>
                                        {{$v}}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>></td>

                        <!-- ATTRIBUTE VALUES -->
                        <td id="box_values_{{$i}}">
                            <div class="box-values-container">
                                @foreach(($cond['attribute_val'] ?? []) as $val)
                                <div class="box-row mb-2">
                                    <input type="number"  class="form-control"
                                           name="attribute_val[{{$i}}][]"
                                           value="{{$val}}">
                                </div>
                                @endforeach
                            </div>

                            <button type="button"
                                id="add_more_btn_{{$i}}"
                                class="btn btn-sm btn-secondary mt-2 {{ ($cond['attribute'] ?? null)=='box'?'':'d-none' }}"
                                onclick="addMoreBoxRows({{$i}})">
                                Add More
                            </button>
                        </td>

                        <!-- UNIT -->
                        <td>
                            @php
                                $unit = ($measurementUnitMap[$cond['attribute'] ?? ''] ?? 'cm');
                            @endphp
                            <select class="form-control" id="attribute_units_{{$i}}" disabled>
                                @foreach($getMeasurementsUnits as $uk => $uv)
                                    <option value="{{ $uk }}" {{ $uk === $unit ? 'selected' : '' }}>{{ $uv }}</option>
                                @endforeach
                            </select>
                        </td>

                        <!-- POPUP PRICE -->
                        <td id="popup_price_{{$i}}">
                            @foreach(($cond['popup_price'] ?? []) as $p)
                            <div class="price-row mb-2">
                                <input type="number" step="0.01" class="form-control"
                                       name="popup_price[{{$i}}][]"
                                       value="{{$p}}">
                            </div>
                            @endforeach
                        </td>

                        <!-- ACTION -->
                        <td id="action_box_{{$i}}">
                            <div class="action-rows">
                                <div class="action-row mb-2">
                                    <i class="fa fa-trash text-danger"
                                       style="cursor:pointer"
                                       onclick="removeCloneFulfillment({{$i}})"></i>
                                </div>

                                @if(($cond['attribute'] ?? null)=='box')
                                    @for($x=1;$x<count($cond['attribute_val'] ?? []);$x++)
                                        <div class="action-row mb-2">
                                            <button type="button"
                                                class="btn btn-danger btn-sm"
                                                onclick="removeBoxRowByIndex({{$i}}, {{$x}})">×</button>
                                        </div>
                                    @endfor
                                @endif
                            </div>
                        </td>

                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <input type="hidden" name="incValue" id="incValue"
                   value="{{ count($customCondition) }}">

            @include('courier.partials.us_rule_blocks', ['usRuleBlocksArray' => $usRuleBlocksArray ?? []])

            <div class="row col-4">
                <button type="submit" class="btn btn-success mt-3">
                    Update Courier
                </button>
            </div>

        </div>
    </form>
</div>

{{-- ================= JS ================= --}}
<script>
function courierCurrencySymbol(val) {
    var u = String(val || '').toUpperCase();
    if (u === 'US') return '$';
    if (u === 'CANADA') return 'C$';
    if (u === 'EU') return '€';
    if (u === 'AUSTRALIA') return 'A$';
    if (u === 'UK') return '£';
    return '£';
}

function toggleUsRateWeightLabels(v) {
    var u = String(v).toUpperCase();
    var lbs = (u === 'US' || u === 'CANADA');
    var fixed = document.getElementById('courier_lbl_fixed_weight');
    var help = document.getElementById('courier_weight_rate_help');
    var helpCountry = document.getElementById('courier_weight_rate_help_country');
    if (fixed) fixed.textContent = lbs ? 'Weight rate bands (LBS)' : 'Weight rate bands (KG)';
    $('.tier-unit-label').text(lbs ? 'LBS' : 'KG');
    if (help) help.style.display = lbs ? 'block' : 'none';
    if (helpCountry) {
        if (u === 'US') helpCountry.textContent = 'US:';
        else if (u === 'CANADA') helpCountry.textContent = 'Canada:';
        else helpCountry.textContent = 'US / Canada:';
    }
    var cur = document.getElementById('change_currency');
    if (cur) cur.textContent = courierCurrencySymbol(v);
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

function toggleCountryRuleBlocksWrap(v) {
    var el = document.getElementById('us-rule-engine-wrap');
    if (!el) return;
    var u = String(v || '').toUpperCase();
    var blockCountries = ['US', 'UK', 'EU', 'CANADA', 'AUSTRALIA'];
    var show = blockCountries.indexOf(u) >= 0;
    el.style.display = show ? 'block' : 'none';
    if (typeof window.setRuleBlocksCountryMode === 'function') {
        window.setRuleBlocksCountryMode(show ? v : '');
    }
    toggleUsRateWeightLabels(v);
}
var incValue = {{ count($customCondition) }};
const measurementUnitMap = @json($measurementUnitMap ?? []);

/* ADD CONDITION */
function addCondition() {
    $('.hide_show').show();

    let row = `
    <tr class="incValue_${incValue}">
        <td>
            <select class="form-control"
                name="attribute[${incValue}]"
                onchange="getChangesUnit(this.value, ${incValue})">
                @foreach($measurementsArray as $k=>$v)
                    <option value="{{$k}}">{{$v}}</option>
                @endforeach
            </select>
        </td>

        <td>></td>

        <td id="box_values_${incValue}">
            <div class="box-values-container">
                <div class="box-row mb-2">
                    <input type="number" class="form-control"
                        name="attribute_val[${incValue}][]">
                </div>
            </div>

            <button type="button"
                id="add_more_btn_${incValue}"
                class="btn btn-sm btn-secondary mt-2 d-none"
                onclick="addMoreBoxRows(${incValue})">
                Add More
            </button>
        </td>

        <td>
            <select class="form-control" id="attribute_units_${incValue}" disabled>
                @foreach($getMeasurementsUnits as $uk => $uv)
                    <option value="{{ $uk }}">{{ $uv }}</option>
                @endforeach
            </select>
        </td>

        <td id="popup_price_${incValue}">
            <div class="price-row mb-2">
                <input type="number" step="0.01" class="form-control"
                    name="popup_price[${incValue}][]">
            </div>
        </td>

        <td id="action_box_${incValue}">
            <div class="action-rows">
                <div class="action-row mb-2">
                    <i class="fa fa-trash text-danger"
                       onclick="removeCloneFulfillment(${incValue})"
                       style="cursor:pointer"></i>
                </div>
            </div>
        </td>
    </tr>`;

    $('.append_tr').append(row);
    incValue++;
    $('#incValue').val(incValue);
}

/* REMOVE CONDITION */
function removeCloneFulfillment(i){
    $('.incValue_'+i).remove();
}

/* ADD MORE BOX */
function addMoreBoxRows(i){

    $('#box_values_'+i+' .box-values-container').append(`
        <div class="box-row mb-2">
            <input type="number" class="form-control"
                name="attribute_val[${i}][]">
        </div>
    `);

    $('#popup_price_'+i).append(`
        <div class="price-row mb-2">
            <input type="number" step="0.01" class="form-control"
                name="popup_price[${i}][]">
        </div>
    `);

    let index = $('#box_values_'+i+' .box-row').length - 1;

    $('#action_box_'+i+' .action-rows').append(`
        <div class="action-row mb-2">
            <button type="button"
                class="btn btn-danger btn-sm"
                onclick="removeBoxRowByIndex(${i}, ${index})">×</button>
        </div>
    `);
}

/* REMOVE SINGLE BOX ROW */
function removeBoxRowByIndex(i, index){
    $('#box_values_'+i+' .box-row').eq(index).remove();
    $('#popup_price_'+i+' .price-row').eq(index).remove();
    $('#action_box_'+i+' .action-row').eq(index).remove();
}

/* ATTRIBUTE CHANGE */
function getChangesUnit(val,i){
    if(val === 'box'){
        $('#add_more_btn_'+i).removeClass('d-none');
    } else {
        $('#add_more_btn_'+i).addClass('d-none');
    }
    if (measurementUnitMap[val]) {
        $('#attribute_units_'+i).val(measurementUnitMap[val]);
    }
}
</script>

@endsection
