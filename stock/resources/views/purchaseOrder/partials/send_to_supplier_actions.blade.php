@php
    $sent = (int) ($purchaseOrder->send_to_supplier_status ?? 1) === 1;
    $canceled = (int) ($purchaseOrder->status ?? 0) === 2;
@endphp
@if (!$canceled)
    @if ($sent)
        <span class="badge bg-success">Sent to supplier</span>
    @elseif (!empty($sendToSupplierUrl))
        <form method="POST" action="{{ $sendToSupplierUrl }}" class="d-inline-block m-0"
            onsubmit="return confirm('Send this PO to the supplier dashboard?');">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm" title="Send to Supplier" data-bs-toggle="tooltip" data-bs-placement="top">
                <i class="fa fa-paper-plane"></i>
            </button>
        </form>
    @endif
@endif
