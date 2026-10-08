@php
    $isEdit = !empty($draft);
    $formAction = $isEdit ? url('/draftPurchaseOrder/edit/' . $draft->id) : url('/draftPurchaseOrder/create');
    $pageTitle = $isEdit ? 'Edit Draft PO' : 'Add Draft PO';
    $submitLabel = $isEdit ? 'Update Draft PO' : 'Create Draft PO';
    $draftPonoDisplay = $isEdit ? $draft->draft_pono : ($nextDraftPono ?? 'DRAFT/1');
@endphp
@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>{{ $pageTitle }}</h2>
    </div>

    <form id="draftPoForm" method="POST" action="{{ $formAction }}">
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
                <input type="date" class="form-control" name="del_date" required value="{{ $isEdit ? $draft->del_date : '' }}" />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">Supplier Ref. No.</label>
                <input type="text" class="form-control toUpperCase" name="ref_supplier" required value="{{ $isEdit ? $draft->ref_supplier : '' }}" />
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
                <textarea class="form-control" name="payterms">{{ $isEdit ? $draft->payterms : "30-45 Days" }}</textarea>
            </div>
            <div class="col-8">
                <label class="control-label">Remarks</label>
                <textarea class="form-control" name="remarks">{{ $isEdit ? $draft->remarks : "1. Wood must be seasoned & chemically treated.\n2. Timber MUST be sourced from regulated & legal plantations only." }}</textarea>
            </div>
        </div>

        <div class="row mt-5 my-3"><div class="col-6"><h5>Products List</h5></div></div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="min-width:250px;">Product</th>
                        <th>EAN</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Rate/Item (₹)</th>
                        <th>Amount (₹)</th>
                        <th>GST %</th>
                        <th>GST (₹)</th>
                        <th>Discount Type</th>
                        <th>Discount</th>
                        <th>Priority</th>
                        <th>Delivery Point</th>
                        <th>Legs</th>
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
            <div class="col-4">
                <label>Total Discount (₹)</label>
                <input type="number" class="form-control" name="totaldiscount" id="totaldiscount" readonly value="{{ $isEdit ? $draft->totaldiscount : '' }}" />
            </div>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-primary mt-3">{{ $submitLabel }}</button>
        <a href="{{ url('/draftPurchaseOrder') }}" class="btn btn-secondary mt-3 ms-2">Cancel</a>
    </form>
</div>
@endsection

@section('footer')
@php
    $initialLinesJson = ($lines ?? collect())->map(function ($line) {
        return [
            'product_id' => $line->product_id,
            'EAN' => $line->EAN,
            'quantity' => $line->quantity,
            'unit' => $line->unit,
            'rate' => $line->rate,
            'amount' => $line->amount,
            'gstslab' => $line->gstslab,
            'gstamount' => $line->gstamount,
            'discount_type' => $line->discount_type,
            'discount' => $line->discount,
            'priority' => $line->priority,
            'delivery_point' => $line->delivery_point,
            'legs' => $line->legs,
            'description' => $line->description,
        ];
    })->values();
@endphp
@include('draftPurchaseOrder.partials.form_scripts', ['initialLinesJson' => $initialLinesJson])
@include('draftPurchaseOrder.partials.draft_monthly_amount_confirm', [
    'draftPoKind' => 'furniture',
    'draftFormSelector' => '#draftPoForm',
    'excludeFurnitureDraftId' => $isEdit ? $draft->id : null,
    'excludeConsumableDraftId' => null,
])
@endsection
