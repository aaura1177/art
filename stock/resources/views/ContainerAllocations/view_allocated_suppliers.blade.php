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
      <h2>Allocated Suppliers</h2>
      <div class="col">
        <a class="btn btn-secondary float-end" style="color: #fff;" href="{{ route('allocation-management.view',$record->id)}}"> <i class="fa fa-arrow-left"></i> Go Back </a>
        <a class="btn btn-info float-end me-2" style="color: #fff;" href="{{ route('allocation-management.print-negative-item-list',$record->id)}}"><i class="fa fa-print"></i> Print Negative Item List</a>
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

             @php

                $bySupplier = $record->containerAllocationDetails->groupBy('supplier_id');
                @endphp

                @foreach($bySupplier as $supplierId => $products)
                    @php
                    $supplier = $products->first()->supplierInfo;
                    $po_info = $products->first()->purchaseOrderInfo;
                    $rem_qty = collect(@$po_info->poTable)->sum('remqty');
                    @endphp

                    <div class="sku-block mb-4">
                        <table class="table table-bordered">
                            @php
                                $shouldSendReminder = $rem_qty > 0 && now()->toDateString() >= $po_info->del_date;
                            @endphp

                            <caption class="bg-warning-subtle fw-bold p-2" style="caption-side:top">
                                <div class="mb-2">
                                    <h3 class="fw-bold mb-1">{{ ($supplierId == 0) ? 'IN STOCK' : 'Supplier: '.@$supplier->c_name }}</h3>
                                    <h5 class="mb-1">PO Date: {{ (@$po_info->podate!="") ? date('d M Y',strtotime(@$po_info->podate)) : "NA" }}</h5>
                                    <h5 class="mb-1 @if($shouldSendReminder) text-danger @endif">Delivery Date: {{ (@$po_info->del_date!="" ) ? date('d M Y',strtotime(@$po_info->del_date)) : "NA" }}</h5>
                                </div>

                                @if($shouldSendReminder)
                                    <a href="{{ route('allocation-management.sendReminderEmails', $po_info->id) }}" class="btn btn-warning btn-sm">
                                        Send Reminder Email
                                    </a>
                                @endif
                            </caption>
                            <thead class="table-dark">
                                <tr>
                                    <th style="width:30%">Product Name</th>
                                    <th>SKU</th>
                                    <th>Unit Price</th>
                                    <th>Asked Qty</th>
                                    <th>Remaining Qty</th>
                                    <th>Delivered Qty</th>
                                    <!-- <th>Total Item Qty to Deliver</th> -->
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($products as $product)
                                @php
                                $code     = $product->productInfo->code;
                                $rate     = optional(
                                                App\supplierProduct::where('supplier_id', $supplierId)
                                                ->where('product_id', $product->product_id)
                                                ->first()
                                            )->rate ?? '—';
                                
                                if($supplierId == 0)
                                {
                                    $remQty = 0;
                                    $deliverd_qty = $product->asked_quantity;
                                }
                                else
                                {
                                    $remQty = $product->asked_quantity;
                                    if(@$po_info->id)
                                    {
                                        $query = App\poTable::where('poid', @$po_info->id)
                                                ->where('product_id', $product->product_id)
                                                ->first();
                                        if($query)
                                        {
                                            $remQty = $query->remqty; 
                                        }
                                    }
                                    
                                    $deliverd_qty = $product->asked_quantity - $remQty;
                                }
                               
                                @endphp
                                <tr @if($remQty!=0) class="table-danger" @endif>
                                    <td style="width:30%">{{ $product->productInfo->name }}</td>
                                    <td>{{ $code }}</td>
                                    <td>{{ $rate }}</td>
                                    <td>{{ $product->asked_quantity }}</td>
                                    <td>
                                       @if($remQty!=0) 
                                            <span class="badge badge-danger">{{ $remQty }}</span>
                                        @else
                                            {{ $remQty }}
                                       @endif
                                    </td>
                                    <td> {{ $deliverd_qty }} </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
        </div>
    </div>
@endsection
@section('footer')

@endsection
