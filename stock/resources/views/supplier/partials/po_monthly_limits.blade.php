@php
    $supplierModel = $supplier ?? null;
    $supplierType = old('type', optional($supplierModel)->type ?? 'Furniture');
    $isMergedChecked = (int) old('is_merged', optional($supplierModel)->is_merged ?? 0) === 1;
    $doPack = old('do_packaging', optional($supplierModel)->do_packaging ?? 'No');
@endphp

<h6 class="text-muted text-uppercase small mb-3 mt-3 border-bottom pb-2">{{ __('Supplier type & PO monthly limits') }}</h6>

<div class="card border mb-3" id="supplier-po-limits-card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="control-label">{{ __('Type') }} <span class="text-danger">*</span></label>
                <select name="type" id="supplier_type" class="selectpicker form-control" required>
                    <option value="Furniture" {{ $supplierType === 'Furniture' ? 'selected' : '' }}>Furniture</option>
                    <option value="Consumable" {{ $supplierType === 'Consumable' ? 'selected' : '' }}>Consumable</option>
                    <option value="Service" {{ $supplierType === 'Service' ? 'selected' : '' }}>Service</option>
                    <option value="Both" {{ $supplierType === 'Both' ? 'selected' : '' }}>Both</option>
                    @if ($supplierType === 'Packaging')
                        <option value="Packaging" selected>Packaging</option>
                    @endif
                </select>
                <small class="text-muted d-block mt-1">{{ __('Choose type, then set limits below.') }}</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="control-label">{{ __('Do Packaging') }}</label>
                <select name="do_packaging" class="selectpicker form-control">
                    <option value="Yes" {{ $doPack === 'Yes' ? 'selected' : '' }}>Yes</option>
                    <option value="No" {{ $doPack === 'No' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end" id="supplier-is-merged-row">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="is_merged" id="is_merged" value="1"
                        {{ $isMergedChecked ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_merged">
                        {{ __('Merged PO limit') }}
                        <small class="d-block text-muted">{{ __('One cap for furniture + consumable POs (not carton)') }}</small>
                    </label>
                </div>
            </div>
        </div>

        <div id="po-limit-furniture">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="control-label">{{ __('PO monthly limit (Furniture)') }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="po_monthly_limit_furniture"
                        value="{{ old('po_monthly_limit_furniture', optional($supplierModel)->po_monthly_limit_furniture ?? '') }}"
                        placeholder="{{ __('Optional') }}" />
                </div>
                <div class="col-md-4 mb-3">
                    <label class="control-label">{{ __('PO limit start date (Furniture)') }}</label>
                    <input type="date" class="form-control" name="po_limit_start_date_furniture"
                        value="{{ old('po_limit_start_date_furniture', optional($supplierModel)->po_limit_start_date_furniture ? \Carbon\Carbon::parse($supplierModel->po_limit_start_date_furniture)->format('Y-m-d') : '') }}" />
                </div>
            </div>
        </div>

        <div id="po-limit-consumable">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="control-label">{{ __('PO monthly limit (Consumable)') }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="po_monthly_limit_consumable"
                        value="{{ old('po_monthly_limit_consumable', optional($supplierModel)->po_monthly_limit_consumable ?? '') }}"
                        placeholder="{{ __('Optional') }}" />
                </div>
                <div class="col-md-4 mb-3">
                    <label class="control-label">{{ __('PO limit start date (Consumable)') }}</label>
                    <input type="date" class="form-control" name="po_limit_start_date_consumable"
                        value="{{ old('po_limit_start_date_consumable', optional($supplierModel)->po_limit_start_date_consumable ? \Carbon\Carbon::parse($supplierModel->po_limit_start_date_consumable)->format('Y-m-d') : '') }}" />
                </div>
            </div>
        </div>

        <div id="po-limit-merged">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="control-label">{{ __('PO monthly limit (Furniture + Consumable)') }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="po_monthly_limit_merged"
                        value="{{ old('po_monthly_limit_merged', optional($supplierModel)->po_monthly_limit_merged ?? '') }}"
                        placeholder="{{ __('Optional') }}" />
                </div>
                <div class="col-md-4 mb-3">
                    <label class="control-label">{{ __('PO limit start date (Merged)') }}</label>
                    <input type="date" class="form-control" name="po_limit_start_date_merged"
                        value="{{ old('po_limit_start_date_merged', optional($supplierModel)->po_limit_start_date_merged ? \Carbon\Carbon::parse($supplierModel->po_limit_start_date_merged)->format('Y-m-d') : '') }}" />
                </div>
            </div>
        </div>

        <div id="po-limit-none-msg" class="alert alert-light border small mb-0" style="display:none;">
            {{ __('PO monthly limits do not apply to Service or Packaging suppliers.') }}
        </div>
    </div>
</div>
