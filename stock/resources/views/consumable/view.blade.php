@extends('layouts.app')

@section('content')

@php
  $isMoqOld = old('is_moq');
  if ($isMoqOld !== null) {
      $isMoqSelected = $isMoqOld == '1';
  } else {
      $isMoqSelected = !empty($consumable->is_moq);
  }

  $selectedSuppliers = [];
  if (isset($consumable)) {
      $supplierValue = $consumable->supplier;
      if (is_string($supplierValue) && str_starts_with($supplierValue, '[')) {
          $selectedSuppliers = json_decode($supplierValue, true) ?? [];
      } else {
          $selectedSuppliers = [$supplierValue];
      }
  }

  $mbeForSelect = old('monthEndpo_buyer');
  if ($mbeForSelect === null) {
      $rawM = $consumable->monthEndpo_buyer ?? null;
      if (is_array($rawM) && $rawM !== []) {
          $mbeForSelect = (string) (int) reset($rawM);
      } elseif ($rawM !== null && $rawM !== '') {
          if (is_numeric($rawM)) {
              $mbeForSelect = (string) (int) $rawM;
          } else {
              $dec = json_decode((string) $rawM, true);
              if (is_array($dec) && $dec !== []) {
                  $mbeForSelect = (string) (int) reset($dec);
              } else {
                  $mbeForSelect = '';
              }
          }
      }
  }
  $meSupplierVal = old('monthEndpo_supplier', $consumable->monthEndpo_supplier ?? null);
  $showMonthEndBuyerWrap = ($meSupplierVal !== null && $meSupplierVal !== '' && (int) $meSupplierVal !== 0);
@endphp

<div class="mx-2">
  <div class="row mx-0 my-2">
    <h2>{{ __('Edit consumable') }}</h2>
  </div>

  <form method="POST" action="{{ url('/consumables/view/'.$consumable->id) }}">
    @csrf

    <div class="form-group">
      {{-- Basic information --}}
      <h6 class="text-muted text-uppercase small mb-3 border-bottom pb-2">{{ __('Basic information') }}</h6>
      <div class="row">
        <div class="col-md-8 mb-3">
          <label class="control-label">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="name" required="required" value="{{ old('name', $consumable->name) }}" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('name') }}</p>
          @endif
        </div>
      </div>
      <div class="row">
        <div class="col-12 mb-3">
          <label class="control-label">{{ __('Description') }} <span class="text-muted">({{ __('optional') }})</span></label>
          <textarea class="form-control" name="description" rows="3">{{ old('description', $consumable->description ?? '') }}</textarea>
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('description') }}</p>
          @endif
        </div>
      </div>

      {{-- Codes & unit --}}
      <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Codes & unit') }}</h6>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="control-label">{{ __('HSN') }}</label>
          <input class="form-control" name="EAN" type="text" style="text-transform: uppercase;" value="{{ old('EAN', $consumable->EAN) }}" />
        </div>
        <div class="col-md-4 mb-3">
          <label class="control-label">{{ __('SKU') }}</label>
          <input class="form-control" name="SKU" type="text" style="text-transform: uppercase;" value="{{ old('SKU', $consumable->SKU) }}" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('SKU') }}</p>
          @endif
        </div>
        <div class="col-md-4 mb-3">
          <label class="control-label">{{ __('Unit type') }} <span class="text-danger">*</span></label>
          <a href="{{ url('/unitType') }}" class="float-end small" target="_blank"> (+{{ __('New') }})</a>
          <select class="selectpicker form-control" data-live-search="true" name="unit_type_id" required="required">
            <option value="" selected disabled>{{ __('Select unit type') }}</option>
            @if(isset($unitType))
              @foreach($unitType as $unit)
                <option value="{{ $unit->id }}"
                  data-unit-data-type="{{ $unit->data_type ?? '' }}"
                  {{ (string) old('unit_type_id', $consumable->unit_type_id) === (string) $unit->id ? 'selected' : '' }}>
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
          <input class="form-control" name="rate" step="any" required="required" value="{{ old('rate', $consumable->rate) }}" />
        </div>
        <div class="col-md-4 mb-3">
          <label class="control-label">{{ __('GST slab (%)') }} <span class="text-danger">*</span></label>
          <select class="selectpicker form-control" data-live-search="true" name="gst" required="required">
            @foreach (['0', '5', '12', '18', '28'] as $g)
              <option value="{{ $g }}" {{ (string) old('gst', $consumable->gst) === (string) $g ? 'selected' : '' }}>{{ $g }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label class="control-label">{{ __('Payment terms') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="payment_terms" required="required" value="{{ old('payment_terms', $consumable->payment_terms) }}" />
        </div>
      </div>
      @hasrole('admin')
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="control-label">{{ __('Quantity') }} <span class="text-danger">*</span></label>
          <input type="number" class="form-control" name="quantity" step=".1" required="required" value="{{ old('quantity', $consumable->quantity) }}" />
        </div>
      </div>
      @else
      <input type="hidden" name="quantity" value="{{ old('quantity', $consumable->quantity) }}" />
      @endhasrole

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
              {{ (string) $hwMeStr === '1' ? 'checked' : '' }}>
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
          <select class="selectpicker form-control" data-live-search="true" name="supplier[]" multiple required>
            <option value="" disabled>{{ __('Select supplier') }}</option>
            @if(isset($supplier))
              @foreach($supplier as $sup)
                @php
                  $isSelected = in_array($sup->id, $selectedSuppliers);
                  $label = $isSelected ? '&#10003; ' . $sup->c_name : $sup->c_name;
                @endphp
                <option value="{{ $sup->id }}"
                  {{ $isSelected ? 'selected' : '' }}
                  data-content="{{ $label }}">
                  {{ $sup->c_name }}
                </option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="control-label">{{ __('Month end PO supplier') }}</label>
          <a href="{{ url('/supplier/create') }}" class="float-end small" target="_blank"> (+{{ __('New') }})</a>
          <select class="selectpicker form-control" data-live-search="true" name="monthEndpo_supplier" id="monthEndpo_supplier_select">
            <option value="" disabled {{ ! $showMonthEndBuyerWrap ? 'selected' : '' }}>{{ __('Select supplier') }}</option>
            @if(isset($supplier))
              @foreach($supplier as $sup)
                <option value="{{ $sup->id }}"
                  {{ (string) old('monthEndpo_supplier', $consumable->monthEndpo_supplier) === (string) $sup->id ? 'selected' : '' }}>
                  {{ $sup->c_name }}
                </option>
              @endforeach
            @endif
          </select>
        </div>
      </div>
      <div class="row" id="month_end_po_buyer_wrap" style="{{ $showMonthEndBuyerWrap ? '' : 'display:none;' }}">
        <div class="col-md-6 mb-3 offset-md-6">
          <label class="control-label">{{ __('Buyer') }} <span class="text-danger" id="month_end_buyer_required_mark">*</span></label>
          <select class="form-control" name="monthEndpo_buyer" id="monthEndpo_buyer_select">
            <option value="" disabled {{ ! $showMonthEndBuyerWrap || $mbeForSelect === null || $mbeForSelect === '' ? 'selected' : '' }}>{{ __('Select') }}</option>
            <option value="1" {{ (string) $mbeForSelect === '1' ? 'selected' : '' }}>{{ __('UK-18') }}</option>
            <option value="2" {{ (string) $mbeForSelect === '2' ? 'selected' : '' }}>{{ __('Non UK-18') }}</option>
            <option value="3" {{ (string) $mbeForSelect === '3' ? 'selected' : '' }}>{{ __('Common') }}</option>
          </select>
          <small class="text-muted">{{ __('Same as Small Hardware: one buyer class for month-end PO.') }}</small>
        </div>
      </div>

      {{-- MOQ --}}
      <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Minimum order quantity (MOQ)') }}</h6>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="control-label">{{ __('Has MOQ') }}</label>
          <select class="form-control" name="is_moq" id="is_moq">
            <option value="0" {{ $isMoqSelected ? '' : 'selected' }}>{{ __('No') }}</option>
            <option value="1" {{ $isMoqSelected ? 'selected' : '' }}>{{ __('Yes') }}</option>
          </select>
        </div>
        <div class="col-md-4 mb-3" id="moq_qty_div" style="{{ $isMoqSelected ? '' : 'display:none;' }}">
          <label class="control-label">{{ __('MOQ quantity') }}</label>
          <input type="number" class="form-control" name="moq_qty" id="moq_qty" step="any" min="0"
            value="{{ old('moq_qty', $consumable->moq_qty) }}"
            {{ $isMoqSelected ? 'required' : '' }} />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('moq_qty') }}</p>
          @endif
        </div>
      </div>

      {{-- Container --}}
      <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Container') }}</h6>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="control-label">{{ __('Is container') }}</label>
          @php
            $isContEdit = old('is_container', $consumable->is_container === null ? '' : (string) $consumable->is_container);
          @endphp
          <select class="form-control" name="is_container" id="is_container">
            <option value="">{{ __('Select') }}</option>
            <option value="1" {{ (string) $isContEdit === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
            <option value="0" {{ (string) $isContEdit === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
          </select>
        </div>
        <div class="col-md-4 mb-3" id="container_qty_div"
          style="{{ (string) $isContEdit === '1' ? '' : 'display:none;' }}">
          <label class="control-label">{{ __('Container quantity') }}</label>
          <input type="number" class="form-control" name="container_quantity" step="any"
            value="{{ old('container_quantity', $consumable->container_quantity) }}"
            {{ (string) $isContEdit === '1' ? 'required' : '' }} />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-12">
          <button type="submit" class="btn btn-primary px-4">{{ __('Update consumable') }}</button>
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
