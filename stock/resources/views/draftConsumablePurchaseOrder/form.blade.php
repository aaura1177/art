@php
    $isEdit = !empty($draft);
    $formAction = $isEdit ? url('/draftConsumablePurchaseOrder/edit/' . $draft->id) : url('/draftConsumablePurchaseOrder/create');
    $pageTitle = $isEdit ? 'Edit Consumable Draft PO' : 'Add Consumable Draft PO';
    $submitLabel = $isEdit ? 'Update Draft PO' : 'Create Draft PO';
    $draftPonoDisplay = $isEdit ? $draft->draft_pono : ($nextDraftPono ?? 'CDRAFT/1');
    $selectedMonths = $isEdit && $draft->month ? explode(',', $draft->month) : [date('m-Y')];
@endphp
@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>{{ $pageTitle }}</h2>
    </div>

    <form id="draftConsumablePoForm" method="POST" action="{{ $formAction }}">
        @csrf

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">Draft PO No.</label>
                <input type="text" class="form-control" readonly value="{{ $draftPonoDisplay }}" />
            </div>
            <div class="col-4">
                <label class="control-label">Supplier</label>
                <select class="selectpicker" data-live-search="true" name="supplier_id" required id="supplier_id" onchange="handleSelectSupplier(this)">
                    <option value="" disabled {{ !$isEdit ? 'selected' : '' }}>Select Supplier</option>
                    @foreach ($supplier as $s)
                        <option value="{{ $s->id }}" data-gst="{{ $s->gst }}"
                            {{ ($isEdit && (int) $draft->supplier_id === (int) $s->id) ? 'selected' : '' }}>
                            {{ $s->c_name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" id="supplier_gst" value="{{ $isEdit ? optional($draft->supplier)->gst : '' }}" />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">Date of PO</label>
                <input type="date" class="form-control" name="podate" required value="{{ $isEdit ? $draft->podate : date('Y-m-d') }}" />
            </div>
            <div class="col-4">
                <label class="control-label">Delivery Date</label>
                <input type="date" class="form-control" name="del_date" required value="{{ $isEdit ? $draft->del_date : date('Y-m-d', strtotime('+3 days')) }}" />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">Supplier Month</label>
                <select class="selectpicker" name="month[]" required multiple>
                    @php
                        $monthOptions = [
                            date('m-Y') => date('F Y'),
                            date('m-Y', strtotime('previous month')) => date('F Y', strtotime('previous month')),
                        ];
                    @endphp
                    @foreach ($monthOptions as $val => $label)
                        <option value="{{ $val }}" {{ in_array($val, $selectedMonths, true) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4">
                <label class="control-label">Buyer Order Number</label>
                <input type="text" class="form-control toUpperCase" name="buyer_orderno" required value="{{ $isEdit ? $draft->buyer_orderno : '' }}" />
            </div>
            <div class="col-4">
                <label class="control-label">Address to</label>
                <select name="address_option" class="form-control">
                    <option value="2" {{ ($isEdit && (string) $draft->address_option === '2') || !$isEdit ? 'selected' : '' }}>Factory</option>
                    <option value="1" {{ $isEdit && (string) $draft->address_option === '1' ? 'selected' : '' }}>Office</option>
                </select>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">Terms of Payment</label>
                <select class="form-control" name="payterms">
                    @foreach (['30-45 Days', '30 Days', '45 Days'] as $term)
                        <option value="{{ $term }}" {{ ($isEdit && $draft->payterms === $term) || (!$isEdit && $term === '30-45 Days') ? 'selected' : '' }}>{{ $term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-8">
                <label class="control-label">Remarks</label>
                <textarea class="form-control" name="remarks">{{ $isEdit ? $draft->remarks : "1. Goods must be delivered to our Factory Address.\n2. Invoice and E waybill should be attached at the time of delivery.\n3. All rates including freight charges." }}</textarea>
            </div>
        </div>

        <div class="row mt-5 my-3"><div class="col-6"><h5>Products List</h5></div></div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="min-width:250px;">Consumable</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Rate/Item (₹)</th>
                        <th>Amount (₹)</th>
                        <th>GST %</th>
                        <th>GST (₹)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="productTable"></tbody>
            </table>
        </div>

        <div class="row mt-2"><div class="col"><input type="button" id="addProduct" class="btn btn-primary" value="Add Product" /></div></div>

        <div class="row mt-3">
            <div class="col-4">
                <label>Total GST (₹)</label>
                <input type="text" class="form-control" name="tgst" id="totalgst" readonly value="{{ $isEdit ? $draft->tgst : '' }}" />
            </div>
            <div class="col-4">
                <label>Total Quantity</label>
                <input type="number" class="form-control" name="tquantity" id="tquantity" readonly value="{{ $isEdit ? $draft->tquantity : '' }}" />
            </div>
            <div class="col-4">
                <label>Sub Total Amount (₹)</label>
                <input type="number" class="form-control" name="subtotalamount" id="subtotalamount" readonly value="{{ $isEdit ? $draft->subTotal : '' }}" />
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-4">
                <label>Total Amount (₹)</label>
                <input type="number" class="form-control" name="tamount" id="totalamount" readonly value="{{ $isEdit ? $draft->tamount : '' }}" />
            </div>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-primary mt-3">{{ $submitLabel }}</button>
        <a href="{{ url('/draftConsumablePurchaseOrder') }}" class="btn btn-secondary mt-3 ms-2">Cancel</a>
    </form>
</div>
@endsection

@section('footer')
@php
    $initialLinesJson = ($lines ?? collect())->map(function ($line) {
        return [
            'consumable_id' => $line->consumable_id,
            'quantity' => $line->quantity,
            'unit' => $line->unit,
            'rate' => $line->rate,
            'amount' => $line->amount,
            'gstslab' => $line->gstslab,
            'gstamount' => $line->gstamount,
            'description' => $line->description,
        ];
    })->values();
@endphp
@include('draftConsumablePurchaseOrder.partials.form_scripts', ['initialLinesJson' => $initialLinesJson])
@include('draftPurchaseOrder.partials.draft_monthly_amount_confirm', [
    'draftPoKind' => 'consumable',
    'draftFormSelector' => '#draftConsumablePoForm',
    'excludeFurnitureDraftId' => null,
    'excludeConsumableDraftId' => $isEdit ? $draft->id : null,
])
@endsection
