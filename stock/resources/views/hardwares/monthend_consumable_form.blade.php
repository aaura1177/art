@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Create Consumable From Hardware</h2>
        </div>

        <div class="alert alert-info">
            Hardware: <strong>{{ $hardware->name }}</strong> | Rate: <strong>{{ $hardware->rate }}</strong> |
            Supplier: <strong>{{ optional($hardware->hardwareSuppliers)->name }}</strong>
        </div>

        <form method="POST" action="{{ route('hardwares.monthend.onboarding.store_consumable', ['hardware' => $hardware->id]) }}"
            enctype="multipart/form-data">
            @csrf
            @php
                $gstVal = old('gst', '0');
                $isContVal = old('is_container', '0');
            @endphp

            <div class="form-group">
                <h6 class="text-muted text-uppercase small mb-3 border-bottom pb-2">{{ __('Basic information') }}</h6>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="control-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required="required"
                            value="{{ old('name', $hardware->name) }}" />
                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('name') }}</p>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="control-label">{{ __('Description') }} <span class="text-muted">({{ __('optional') }})</span></label>
                        <textarea class="form-control" name="description" rows="3"
                            placeholder="{{ __('Optional notes for this item') }}">{{ old('description') }}</textarea>
                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('description') }}</p>
                        @endif
                    </div>
                </div>

                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Codes & unit') }}</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('HSN') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" style="text-transform: uppercase;" name="EAN" required=""
                            value="{{ old('EAN') }}" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('SKU') }}</label>
                        <input type="text" class="form-control" style="text-transform: uppercase;" name="SKU"
                            value="{{ old('SKU') }}" />
                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('SKU') }}</p>
                        @endif
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('Unit type') }} <span class="text-danger">*</span></label>
                        <a href="{{ url('/unitType') }}" class="float-end small" target="_blank"> (+{{ __('New') }})</a>
                        <select class="selectpicker form-control" data-live-search="true" name="unit_type_id" required="required">
                            <option value="" selected disabled>{{ __('Select unit type') }}</option>
                            @foreach ($unitType as $unit)
                                <option value="{{ $unit->id }}" data-unit-data-type="{{ $unit->data_type ?? '' }}"
                                    {{ (string) old('unit_type_id') === (string) $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Pricing & terms') }}</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('Rate') }} <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="rate" step="any" required="required"
                            value="{{ old('rate', $hardware->rate) }}" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('GST slab (%)') }} <span class="text-danger">*</span></label>
                        <select class="selectpicker form-control" data-live-search="true" name="gst" required="required">
                            @foreach (['0', '5', '12', '18', '28'] as $g)
                                <option value="{{ $g }}" {{ (string) $gstVal === (string) $g ? 'selected' : '' }}>
                                    {{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="control-label">{{ __('Payment terms') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="payment_terms" required="required"
                            value="{{ old('payment_terms') }}" />
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Schema::hasColumn('consumables', 'hardware_monthend_po_product'))
                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Month-end type') }}</h6>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="hidden" name="hardware_monthend_po_product" value="0">
                            <input class="form-check-input" type="checkbox" name="hardware_monthend_po_product"
                                id="hardware_monthend_po_product" value="1"
                                {{ (string) old('hardware_monthend_po_product', '1') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="hardware_monthend_po_product">
                                {{ __('Hardware month-end PO product') }}
                            </label>
                            <small class="text-muted d-block">{{ __('If checked, uses the hardware month-end PO flow (no regular month-end buyer).') }}</small>
                        </div>
                    </div>
                </div>
                @endif

                <h6 class="text-muted text-uppercase small mb-3 mt-2 border-bottom pb-2">{{ __('Suppliers') }}</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="control-label">{{ __('Supplier') }} <span class="text-danger">*</span></label>
                        <a href="{{ url('/supplier/create') }}" class="float-end small" target="_blank"> (+{{ __('New') }})</a>
                        <select class="selectpicker form-control" data-live-search="true" name="supplier[]" multiple
                            required="required">
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ in_array((string) $sup->id, array_map('strval', (array) old('supplier', [])), true) ? 'selected' : '' }}>
                                    {{ $sup->c_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="control-label">{{ __('Month end PO supplier') }} <span class="text-danger">*</span></label>
                        <select class="selectpicker form-control" data-live-search="true" name="monthEndpo_supplier"
                            id="monthEndpo_supplier_select" required>
                            <option value="" selected disabled>{{ __('Select supplier') }}</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ (string) old('monthEndpo_supplier', '') === (string) $sup->id ? 'selected' : '' }}>
                                    {{ $sup->c_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

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
                        <input type="number" class="form-control" name="moq_qty" id="moq_qty" step="any" min="0"
                            value="{{ old('moq_qty') }}" />
                    </div>
                </div>

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
                        <input type="number" class="form-control" name="container_quantity" step="any"
                            value="{{ old('container_quantity') }}" />
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-4">{{ __('Create consumable & link hardware') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            function syncContainerQtyStepFromUnitType() {
                var el = document.querySelector('select[name="unit_type_id"]');
                if (!el) return;
                var opt = el.options[el.selectedIndex];
                var dt = opt ? opt.getAttribute('data-unit-data-type') : '';
                var $inp = $('input[name="container_quantity"]');
                if (!$inp.length) return;
                $inp.attr('step', dt === 'int' ? '1' : 'any');
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

            $('#is_container').on('change', toggleContainerQtyRequirement);
            $('#is_moq').on('change', toggleMoqQty);
            $('select[name="unit_type_id"]').on('changed.bs.select', syncContainerQtyStepFromUnitType);
            toggleContainerQtyRequirement();
            toggleMoqQty();
            syncContainerQtyStepFromUnitType();
        });
    </script>

@endsection

