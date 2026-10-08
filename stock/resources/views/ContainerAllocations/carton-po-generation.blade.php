<?php
use App\Helpers\Common;
?>
@extends('layouts.app')

@section('content')

     <style>
        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            line-height: 20px;;
        }
    </style>

    <div class="row mx-1 my-2">
      <h2>Carton PO Generation</h2>
      <div class="col">
        <a class="btn btn-secondary float-end" style="color: #fff;" href="{{ route('allocation-management.view',$record->id)}}"><i class="fa fa-arrow-left"></i> Go Back</a>
       </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Accepted Container Suppliers</h3>
        </div>
        <div class="card-body">
             <ul>
                <li class="fw-bold"> Buyer Order number : {{ $record->buyer_order_number }}</li>
                <li class="fw-bold"> Planned Date : {{ $record->planned_date }}</li>
                <li class="fw-bold"> Total Container Volume Consumed : {{ $record->containerAllocationItems->sum('drop_ship_volume') }}</li>
                <li class="fw-bold"> Total Physical Volume Consumed: {{ $record->containerAllocationItems->sum('physical_volume') }}</li>
            </ul>

            <form action="{{ route('allocation-management.generateCartonPo', $record->id) }}" method="POST">
            @csrf

                <!-- <div class="row mb-3">
                    <label for="inputtypelabel" class="form-label">Delivery Date</label>
                    <div class="input-group">
                        <?php
                         $del_date = date('Y-m-d', strtotime($record->planned_date . ' - 10 days'));
                        ?>
                        <input type="date" name="delivery_date" value="{{ $del_date }}" class="form-control ui-autocomplete-input"/>
                    </div>
                </div> -->
               @php
                // Decode skus once, then pluck total_qty keyed by sku_code
                $bySupplier = $record->containerAllocationDetails->groupBy('supplier_id');
                @endphp

                @foreach($bySupplier as $supplierId => $products)
                    @if($supplierId==0)
                         <div class="sku-block mb-4 d-none">
                            <table class="table table-bordered">
                                <caption class="bg-info-subtle fw-bold" style="caption-side:top">
                                <h3 class="px-2">
                                    IN Stock
                                </h3>
                                </caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:30%">Product Name</th>
                                        <th>SKU</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        @php
                                        $code     = $product->productInfo->code;

                                        @endphp
                                        <tr>
                                            <td style="width:30%">{{ $product->productInfo->name }}</td>
                                            <td>{{ $code }}</td>
                                            <td>{{ $product->asked_quantity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        @php
                            $supplier = $products->first()->supplierInfo;
                            $purchase_order = $products->first();
                            $po_is_empty = true;
                            $is_po_generated = true;
                            foreach($products as $product)
                            {
                                $po_is_empty = $product->po_consumable_id ? false : true;
                                if($product->status != 'po_generated')
                                {
                                   $is_po_generated = false;
                                }
                            }
                        @endphp



                        <div class="sku-block mb-4">
                            <table class="table table-bordered">
                                <caption class="bg-info-subtle fw-bold" style="caption-side:top">
                                <h3 class="px-2">

                                    Supplier: {{ @$supplier->c_name}}
                                </h3>
                                </caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:30%">Product Name</th>
                                        <th>SKU</th>
                                        <th>Unit Price</th>
                                        <th>Asked Qty</th>
                                        <th>PO Status</th>
                                        <th>Supplier Invoice Status</th>
                                        <th>Purchase Bill Status</th>
                                        <th>Carton PO Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($products as $product)
                                    @php
                                    $commonClass = new Common();
                                    $supplier_query = $commonClass->checkSupplierInvoice(@$product->purchase_order_id);
                                    $purchaseBill_query = $commonClass->checkPurchaseBill(@$product->purchase_order_id);

                                    $code     = $product->productInfo->code;
                                    $rate     = optional(
                                                    App\supplierProduct::where('supplier_id', $supplierId)
                                                    ->where('product_id', $product->product_id)
                                                    ->first()
                                                )->rate ?? '—';
                                    @endphp
                                    <tr>

                                        <td style="width:30%">{{ $product->productInfo->name }}</td>
                                        <td>{{ $code }}</td>
                                        <td>{{ $rate }}</td>
                                        <td>{{ $product->asked_quantity }}</td>
                                        <td>
                                            <span class="text-success fw-bold">{{ ($product->status == 'po_generated') ? ' ( PO Generated - '.$product->purchaseOrderInfo->pono.' )' : 'Pending' }}
                                                @if($product->status == "po_generated")
                                                    <a href="{{ URL::to('/purchaseOrder/modal/'.$product->purchase_order_id)}}" class="btn btn-sm btn btn-danger" target="_blank">
                                                    <i class="fa fa-eye"></i></a>
                                                @endif
                                            </span>
                                        </td>

                                        <td>
                                            <span class="text-success fw-bold">
                                                @if($product->status == "po_generated" && $supplier_query['exists'] == true)
                                                    <a href="{{ URL::to('/supplierInvoice/modal/'.$supplier_query['invoice_id'])}}" class="btn btn-sm btn btn-danger" target="_blank">
                                                    <i class="fa fa-eye"></i> </a>
                                                    {{ ($supplier_query['is_approved'] == true) ? ' Approved' : 'Pending' }}
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-success fw-bold">
                                                @if($product->status == "po_generated" && $supplier_query['exists'] == true && $purchaseBill_query['exists'] == true)
                                                    <a href="{{ URL::to('/purchaseBill/modal/'.$purchaseBill_query['bill_id'])}}" class="btn btn-sm btn btn-danger" target="_blank">
                                                    <i class="fa fa-eye"></i> </a>
                                                    {{ ($purchaseBill_query['is_checked'] == true) ? ' Verified' : 'Pending' }}
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-success fw-bold">{{ ($product->po_consumable_id != '') ? ' ( PO Generated - '.$product->po_consumable_id.' )' : 'Pending' }}
                                                @if($product->po_consumable_id != "")
                                                    <a href="{{ URL::to('purchaseOrder/viewCartonPo/'.$product->po_consumable_id)}}" class="btn btn-sm btn btn-danger" target="_blank">
                                                    <i class="fa fa-eye"></i></a>
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endforeach

                @if($pendingCount!=0)

                    @if($po_is_empty==true && $is_po_generated == true)
                        <label class="fw-bold"> Select Supplier </label>
                        <select name="supplier_id" class="form-control mb-2" required >
                            <option value="">--Select Supplier--</option>
                            @foreach($packaging_suppliers as $packing_supplier)
                                <option value="{{ $packing_supplier->id }}">{{ $packing_supplier->c_name }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-success">Generate Carton PO Of Selected Supplier</button>

                    @endif

                @endif
            </form>

        </div>
    </div>
@endsection
@section('footer')


@endsection
