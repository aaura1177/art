@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Add Consumable</h2>
        </div>

        <form method="POST" action="{{ url('/consumables/create') }}" enctype="multipart/form-data">
            @csrf
            @php
                $gstVal = old('gst', '0');
                $isContVal = old('is_container', '0');
                $oldMeSupplier = old('monthEndpo_supplier');
                $showMonthEndBuyer = $oldMeSupplier !== null && $oldMeSupplier !== '' && (string) $oldMeSupplier !== '0';
            @endphp

            <div class="form-group">
                {{-- Basic information --}}
                <h6 class="text-muted text-uppercase small mb-3 border-bottom pb-2">{{ __('Basic information') }}</h6>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="control-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required="required" value="{{ old('name') }}" />
                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('name') }}</p>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="control-label">{{ __('Description') }} <span class="text-muted">({{ __('optional') }})</span></label>
                        <textarea class="form-control" name="description" rows="3" placeholder="{{ __('Optional notes for this item') }}">{{ old('description') }}</textarea>
                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('description') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Codes & unit --}}
                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Codes & unit') }}</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('HSN') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" style="text-transform: uppercase;" name="EAN" required="" value="{{ old('EAN') }}" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('SKU') }}</label>
                        <input type="text" class="form-control" style="text-transform: uppercase;" name="SKU" value="{{ old('SKU') }}" />
                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('SKU') }}</p>
                        @endif
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('Unit type') }} <span class="text-danger">*</span></label>
                        <a href="{{ url('/unitType') }}" class="float-end small" target="_blank"> (+{{ __('New') }})</a>
                        <select class="selectpicker form-control" data-live-search="true" name="unit_type_id" required="required">
                            <option value="" selected disabled>{{ __('Select unit type') }}</option>
                            @if (isset($unitType))
                                @foreach ($unitType as $unit)
                                    <option value="{{ $unit->id }}" data-unit-data-type="{{ $unit->data_type ?? '' }}" {{ (string) old('unit_type_id') === (string) $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                {{-- Pricing & terms --}}
                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Pricing & terms') }}</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('Rate') }} <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="rate" step="any" required="required" value="{{ old('rate') }}" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('GST slab (%)') }} <span class="text-danger">*</span></label>
                        <select class="selectpicker form-control" data-live-search="true" name="gst" required="required">
                            @foreach (['0', '5', '12', '18', '28'] as $g)
                                <option value="{{ $g }}" {{ (string) $gstVal === (string) $g ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('Payment terms') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="payment_terms" required="required" value="{{ old('payment_terms') }}" />
                    </div>
                </div>

                {{-- hardware_monthend_po_product hidden; always saved as 0 in controller --}}
                {{--
                @if (\Illuminate\Support\Facades\Schema::hasColumn('consumables', 'hardware_monthend_po_product'))
                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Month-end type') }}</h6>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="hidden" name="hardware_monthend_po_product" value="0">
                            <input class="form-check-input" type="checkbox" name="hardware_monthend_po_product"
                                id="hardware_monthend_po_product" value="1"
                                {{ (string) old('hardware_monthend_po_product', '0') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="hardware_monthend_po_product">
                                {{ __('Hardware month-end PO product') }}
                            </label>
                            <small class="text-muted d-block">{{ __('If checked, month-end buyer is not required when month end PO supplier is set.') }}</small>
                        </div>
                    </div>
                </div>
                @endif
                --}}

                {{-- Suppliers --}}
                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Suppliers') }}</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="control-label">{{ __('Supplier') }} <span class="text-danger">*</span></label>
                        <a href="{{ url('/supplier/create') }}" class="float-end small" target="_blank"> (+{{ __('New') }})</a>
                        <select class="selectpicker form-control" data-live-search="true" name="supplier[]" multiple required="required">
                            <option value="" disabled>{{ __('Select supplier') }}</option>
                            @if (isset($supplier))
                                @foreach ($supplier as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->c_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="control-label">{{ __('Month end PO supplier') }}</label>
                        <select class="selectpicker form-control" data-live-search="true" name="monthEndpo_supplier" id="monthEndpo_supplier_select">
                            <option value="" selected disabled>{{ __('Select supplier') }}</option>
                            @if (isset($suppliers))
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ (string) old('monthEndpo_supplier') === (string) $sup->id ? 'selected' : '' }}>{{ $sup->c_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="row" id="month_end_po_buyer_wrap" style="{{ $showMonthEndBuyer ? '' : 'display:none;' }}">
                    <div class="col-md-6 mb-3 offset-md-6">
                        <label class="control-label">{{ __('Buyer') }} <span class="text-danger" id="month_end_buyer_required_mark">*</span></label>
                        <select class="form-control" name="monthEndpo_buyer" id="monthEndpo_buyer_select">
                            <option value="" disabled {{ (string) old('monthEndpo_buyer', '') === '' ? 'selected' : '' }}>{{ __('Select') }}</option>
                            <option value="1" {{ (string) old('monthEndpo_buyer') === '1' ? 'selected' : '' }}>{{ __('UK-18') }}</option>
                            <option value="2" {{ (string) old('monthEndpo_buyer') === '2' ? 'selected' : '' }}>{{ __('Non UK-18') }}</option>
                            <option value="3" {{ (string) old('monthEndpo_buyer') === '3' ? 'selected' : '' }}>{{ __('Common') }}</option>
                        </select>
                        <small class="text-muted">{{ __('Same as Small Hardware: classifies this consumable for month-end PO (UK-18 / Non UK-18 / Common).') }}</small>
                    </div>
                </div>

                {{-- MOQ --}}
                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Minimum order quantity (MOQ)') }}</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('Has MOQ') }}</label>
                        <select class="form-control" name="is_moq" id="is_moq">
                            <option value="0" {{ old('is_moq', '0') == '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                            <option value="1" {{ old('is_moq') == '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="moq_qty_div" style="display:none;">
                        <label class="control-label">{{ __('MOQ quantity') }}</label>
                        <input type="number" class="form-control" name="moq_qty" id="moq_qty" step="any" min="0" value="{{ old('moq_qty') }}" />
                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('moq_qty') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Container --}}
                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Container') }}</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('Is container') }}</label>
                        <select class="form-control" name="is_container" id="is_container">
                            <option value="">{{ __('Select') }}</option>
                            <option value="0" {{ (string) $isContVal === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                            <option value="1" {{ (string) $isContVal === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="container_qty_div" style="display:none;">
                        <label class="control-label">{{ __('Container quantity') }}</label>
                        <input type="number" class="form-control" name="container_quantity" step="any" value="{{ old('container_quantity') }}" />
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-4">{{ __('Add consumable') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
  $(document).ready(function () {
    function syncContainerQtyStepFromUnitType() {
      var el = document.querySelector('select[name="unit_type_id"]');
      if (!el) return;
      var opt = el.options[el.selectedIndex];
      var dt = opt ? opt.getAttribute('data-unit-data-type') : '';
      var $inp = $('input[name="container_quantity"]');
      if (!$inp.length) return;
      if (dt === 'int') {
        $inp.attr('step', '1');
      } else {
        $inp.attr('step', 'any');
      }
    }

    function toggleContainerQtyRequirement() {
      if ($('#is_container').val() == "1") {
        $('#container_qty_div').show();
        $('input[name="container_quantity"]').attr('required', true);
        syncContainerQtyStepFromUnitType();
      } else {
        $('#container_qty_div').hide();
        $('input[name="container_quantity"]').val('');
        $('input[name="container_quantity"]').removeAttr('required');
      }
    }

    function toggleMoqQty() {
      if ($('#is_moq').val() == "1") {
        $('#moq_qty_div').show();
        $('input[name="moq_qty"]').attr('required', true);
      } else {
        $('#moq_qty_div').hide();
        $('input[name="moq_qty"]').val('');
        $('input[name="moq_qty"]').removeAttr('required');
      }
    }

    function toggleMonthEndPoBuyerRow() {
      var v = $('#monthEndpo_supplier_select').val();
      var has = v && String(v) !== '0';
      var $buyer = $('#monthEndpo_buyer_select');
      if (has) {
        $('#month_end_po_buyer_wrap').show();
        $buyer.prop('required', true);
        $('#month_end_buyer_required_mark').show();
      } else {
        $('#month_end_po_buyer_wrap').hide();
        $buyer.val('');
        $buyer.prop('required', false);
        $('#month_end_buyer_required_mark').hide();
      }
    }

    $('#monthEndpo_supplier_select').on('changed.bs.select', toggleMonthEndPoBuyerRow);
    $('#is_container').on('change', toggleContainerQtyRequirement);
    $('#is_moq').on('change', toggleMoqQty);
    $('select[name="unit_type_id"]').on('changed.bs.select', syncContainerQtyStepFromUnitType);
    toggleMonthEndPoBuyerRow();
    toggleContainerQtyRequirement();
    toggleMoqQty();
    syncContainerQtyStepFromUnitType();
  });

</script>

@endsection
