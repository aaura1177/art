<?php foreach ($buyer_data as $key => $row) {
    $key += 1;
    $dest = strtolower(trim((string) ($row->destination ?? '')));
    $isLbsDest = in_array($dest, ['us', 'canada'], true);
    $rowCouriers = isset($couriers) ? $couriers->filter(function ($c) use ($row) {
        return strtolower(trim((string) $c->country)) === strtolower(trim((string) ($row->destination ?? '')));
    }) : collect();
    if ($rowCouriers->isEmpty() && isset($couriers)) {
        $rowCouriers = $couriers;
    }
    ?>
<div id="deliveryCostSection_{{ $key }}" class="line_content">
    <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">Buyer {{ $key + 2 }}</label> <a href="{{ url('/temporaryBuyer/create') }}" target="_blank"
                style="float: right;"> (+New)</a>
            <input type="text" class="form-control" value="{{ $row->c_name }}" readonly="">
            <input type="hidden" name="buyer_id2_multiple[]" id="buyer_id2_{{ $key }}" value="{{ $row->buyer_id2 }}">
        </div>
        <input type="hidden" id="default_country_{{ $key }}" name="default_country_clone[]" value="{{ $row->destination ?? '' }}">
        <div class="col-4">
            <label class="control-label">Country of Destination</label>
            <input type="text" class="form-control" name="destination_clone[]" id="destination_{{ $key }}" value="{{ $row->destination ?? '' }}"
                readonly="">
        </div>
        <div class="col-4 ">
            <div class="form-group mt-4"><button onclick="removeCloneFulfillment('{{ $key }}')" class="btn btn-sm btn-danger"
                    type="button">Remove</button></div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">Currency</label>
            <input type="text" class="form-control" id="changecurrency_{{ $key }}" name="currency_clone[]" placeholder="Currency" value="{{ $row->currency ?? '' }}" readonly>
        </div>
        <div class="col-4" id="volWtKgCol_{{ $key }}" style="{{ $isLbsDest ? 'display:none;' : '' }}">
            <label class="control-label">{{ __('Volumetric Wt. (kg)') }}</label>
            <input type="number" min="0" class="form-control" name="volWt1_clone[]" id="volWt1_{{ $key }}" step="any" value="{{ $isLbsDest ? 0 : ($row->volWt ?? 0) }}" readonly />
        </div>
        <div class="col-4" id="volWtLbsCol_{{ $key }}" style="{{ $isLbsDest ? '' : 'display:none;' }}">
            <label class="control-label">{{ __('Volumetric Wt. (LBS)') }}</label>
            <input type="number" min="0" class="form-control" name="volWt_lbs_clone[]" id="volWt_lbs1_{{ $key }}" step="any" value="{{ $isLbsDest ? ($row->volWt ?? 0) : 0 }}" readonly />
        </div>
        <div class="col-4">
            <label class="control-label">Conversion Rate</label>
            <input type="number" min="0" class="form-control call_conversion" name="converRate_clone[]"
                id="converRate_{{ $key }}" step="any" value="{{ $row->converRate ?? 0 }}" required onchange="allCostNew(null,{{ $key }})">
        </div>
        <div class="col-4">
            <label class="control-label">FOB India Cost</label>
            <input type="number" min="0" class="form-control" name="fobINCost_clone[]" id="fobINCost_{{ $key }}"
                step="any" value="{{ $row->fobINCost ?? 0 }}" readonly="">
        </div>
        <div class="col-4">
            <label class="control-label">Tariff</label>
            <input type="number" min="0" class="form-control calculate_fulfillment_cost" name="tariff_percent_clone[]"
                id="tariff_percent_{{ $key }}" step="any" value="{{ $row->tariff_percent ?? 0 }}"
                data-tariff-loaded="1" onchange="allCostNew(null,{{ $key }})">
        </div>
        <div class="col-4">
            <label class="control-label">Tariff-Adjusted FOB Cost</label>
            <input type="number" min="0" class="form-control mandatory_add_multi" name="final_fob_in_cost_clone[]"
                id="final_fob_in_cost_{{ $key }}" step="any"
                value="{{ $row->final_fob_in_cost ?? $row->fobINCost ?? 0 }}" readonly="">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">Shipping Cost</label>
            <input type="number" min="0" class="form-control allCost" name="shippingCost2_clone[]"
                id="shippingCost2_{{ $key }}" step="any" value="{{ $row->shippingCost2 ?? 0 }}" onchange="allCostNew(null,{{ $key }})">
        </div>
        <div class="col-4">
            <label class="control-label">Storage Cost(1.5 month)</label>
            <input type="number" min="0" class="form-control allCost" name="StorageCost_clone[]"
                id="StorageCost_{{ $key }}" step="any" value="{{ $row->StorageCost ?? 0 }}" readonly="">
        </div>
        <div class="col-4">
            <label class="control-label">Box weight (buyer units)</label>
            <input type="text" class="form-control bg-light" id="boxWt_display_{{ $key }}" readonly value="0" autocomplete="off">
            <small class="text-muted d-block mt-1" id="boxWt_unit_hint_{{ $key }}"></small>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">Outbound Charges</label>
            <input type="number" min="0" class="form-control calculate_fulfillment_cost"
                name="adminCost2_clone[]" id="adminCost2_{{ $key }}" step="any" value="{{ $row->adminCost2 ?? 0 }}" required
                onchange="allCostNew(null,{{ $key }})">
        </div>
        <div class="col-4">
            <label class="control-label">Quality Assurance</label>
            <input type="number" min="0" class="form-control " name="qualityAssurance_clone[]"
                id="qualityAssurance_{{ $key }}" step="any" value="{{ $row->qualityAssurance ?? 0 }}" required onchange="allCostNew(null,{{ $key }})">
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">Landed Cost</label>
            <input type="number" min="0" class="form-control mandatory_add_multi" name="landedCost_clone[]"
                id="landedCost_{{ $key }}" step="any" value="{{ $row->landedCost ?? 0 }}" readonly="">
        </div>
        <div class="col-4">
            <label class="control-label">Admin Profit (%)</label>
            <input type="number" min="0" class="form-control " name="adminProfit_clone[]"
                id="adminProfit_{{ $key }}" step="any" value="{{ $row->adminProfit ?? 0 }}" required onchange="allCostNew(null,{{ $key }})">
        </div>
        <div class="col-4">
            <label class="control-label">Admin Price</label>
            <input type="number" min="0" class="form-control mandatory_add_multi" name="adminPrice_clone[]"
                id="adminPrice_{{ $key }}" step="any" value="{{ $row->adminPrice ?? 0 }}" readonly="">
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">Final Price (%)</label>
            <input type="number" min="0" class="form-control " name="finalPricePer_clone[]"
                id="finalPricePer_{{ $key }}" step="any" value="{{ $row->finalPricePer ?? 0 }}" required onchange="allCostNew(null,{{ $key }})">
        </div>
        <div class="col-4">
            <label class="control-label">Final Price</label>
            <input type="number" min="0" class="form-control mandatory_add_multi" name="finalPrice_clone[]"
                id="finalPrice_{{ $key }}" step="any" value="{{ $row->finalPrice ?? 0 }}" readonly="">
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('Type of Courier') }}</label>
            <select type="text" class="selectpicker stop_collapse" data-live-search="true" name="courierType_clone[]" id="courierType_{{ $key }}" onchange="changeCourierRate(this,{{ $key }})">
                <option value="">Select Courier</option>
                @foreach($rowCouriers as $c)
                    <option value="{{ $c->name }}" data-rate="{{ $c->rate }}" data-weight="{{ $c->fixed_rate_weight }}" data-perkg="{{ $c->rate_per_kg }}" data-fper="{{ $c->fuel_charge_percent }}" data-bands="{{ json_encode($c->weight_rate_tiers) }}" {{ ($row->courierType ?? '') === $c->name ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
                <option value="0" {{ ($row->courierType ?? '') === '0' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-4" hidden>
            <label class="control-label">Courier Cost</label>
            <input type="number" min="0" class="form-control " name="courierCost_clone1[]"
                id="courierCost_{{ $key }}" step="any" value="{{ $row->courierCost ?? 0 }}" required onchange="allCostNew(null,{{ $key }})">
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('Courier Cost') }}</label>
            <input type="number" min="0" class="form-control " name="courierCost_clone[]" id="custom_surcharge_{{ $key }}" step="any" value="{{ $row->courierCost ?? 0 }}" onchange="allCostNew(null,{{ $key }})"/>
        </div>
        <div class="col-4">
            <label class="control-label">Delivery Cost</label>
            <input type="number" min="0" class="form-control mandatory_add_multi"
                name="deliveryCost_clone[]" id="deliveryCost_{{ $key }}" step="any" value="{{ $row->deliveryCost ?? 0 }}" readonly=""
                onchange="allCostNew(null,{{ $key }})">
        </div>
        <div class="col-4">
            <label class="control-label">Cost Adjustment (%)</label>
            <input type="number" min="0" class="form-control " name="adjustment_clone[]" id="adjustment_{{ $key }}"
                step="any" value="{{ $row->adjustment ?? 0 }}" data-adjustment-loaded="1" required onchange="allCostNew(null,{{ $key }})">
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">Final Delivered Cost</label>
            <input type="number" min="0" class="form-control mandatory_add_multi" name="newDelCost_clone[]"
                id="newDelCost_{{ $key }}" step="any" value="{{ $row->newDelCost ?? 0 }}" readonly="">
        </div>
    </div>
</div>
<?php } ?>
