@extends('layouts.app')

@section('content')
<div class="mx-3 my-2">
    <h2>Reverse carton supplier invoice</h2>
    <p class="text-muted">This will delete the supplier invoice and purchase bill, restore PO remaining quantities, and remove inward packaging stock.</p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th>PO No.</th>
                    <td>{{ $purchaseOrder->pono ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Supplier invoice no.</th>
                    <td>{{ $supplierInvoice->supplier_invoice_number }}</td>
                </tr>
                <tr>
                    <th>Internal invoice no.</th>
                    <td>{{ $supplierInvoice->internal_invoice_number ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Purchase bill id</th>
                    <td>{{ $bill->id }}</td>
                </tr>
            </table>

            <h5 class="mt-3">Lines to undo</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Product id</th>
                            <th>Box 1 qty</th>
                            <th>Box 2 qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $line)
                        <tr>
                            <td>{{ $line->product_id }}</td>
                            <td>{{ $line->receiveqty }}</td>
                            <td>{{ $line->receiveqty2 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ url('/supplierInvoice/carton/'.$supplierInvoice->id.'/reverse') }}">
        @csrf
        <div class="form-group">
            <label for="reason">Reason (optional)</label>
            <textarea name="reason" id="reason" class="form-control" rows="2" maxlength="500">{{ old('reason') }}</textarea>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" name="confirm" id="confirm" value="1" required>
            <label class="form-check-label" for="confirm">I understand this cannot be undone from the UI.</label>
        </div>
        <button type="submit" class="btn btn-danger" onclick="return confirm('Permanently reverse this carton supplier invoice?');">Reverse invoice</button>
        <a href="{{ url('/supplierInvoice/carton') }}" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>
@endsection
