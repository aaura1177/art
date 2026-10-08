<?php 
        $total_inward_quantity = 0;
        $total_inward_value = 0;
        $total_outward_quantity = 0;
        $total_outward_value = 0;
        ?>
<table>
    <thead>
        <tr>
            <th rowspan="2">Date</th>
            <th rowspan="2" style="width:50px;">Particulars</th>
            <th rowspan="2" style="width:50px;">Vch Type</th>
            <th rowspan="2" style="width:50px;">Vch No.</th>
            <th colspan="2" style="text-align:center;"><b>Inward</b></th>
            <th colspan="2" style="text-align:center;"><b>Outward</b></th>
            <th colspan="2" style="text-align:center;"><b>Closing</b></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Quantity</td>
            <td>Value</td>
            <td>Quantity</td>
            <td>Value</td>
            <td>Quantity</td>
            <td>Value</td>
        </tr>
        <tr>
            <td>{{date('d-M-y', strtotime($newfsd))}}</td>
            <td>Opening Balance</td>
            <td></td>
            <td></td>
            <td>{{$open_balance}}</td>
            <td>{{$open_balance * $open_rate}}</td>
            <td></td>
            <td></td>
            <td>{{$open_balance}}</td>
            <td>{{$open_balance * $open_rate}}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        
    @foreach($stocklog as $sl)
        <tr>
            <?php
            $rate = 0;
            $vch_type = 'Internal';
            $supplier = '';
            
            if($sl->type == 1){
                $pb = \App\pbTable::where('product_id',$sl->product_id)->where('created_at',$sl->created_at)->first();
                if(!isset($pb->id)){
                    $pb = \App\pbTable::where('product_id',$sl->product_id)->whereDate('created_at',date('Y-m-d', strtotime($sl->created_at)))->first();
                }
                if(isset($pb->id) && !str_contains($sl->ref_no, 'SWAP') && !str_contains($sl->ref_no, 'Stock Correction')){
                    $purchaseBill = \App\purchaseBill::find($pb->purchasebill_id);
            
                    $vch_type = 'Purchase';

                    $supplier = $purchaseBill->purchaseOrder->supplier->c_name;
                    if(isset($pb->id)){
                        $rate = $pb->rate;
                    }
                }
            }
            if($sl->type == 2){
                if($sl->ref_no != 'Reject/Repair'){
                    $pb = [];
                    $stockoutTable = \App\stockoutTable::where('product_id',$sl->product_id)->whereDate('created_at','<=',$sl->created_at)->orderBy('created_at','desc')->first();
                    if(!isset($stockoutTable->id)){
                        $stockoutTable = \App\stockoutTable::where('product_id',$sl->product_id)->whereDate('created_at','>',date('Y-m-d', strtotime($sl->created_at)))->orderBy('created_at','asc')->first();
                    }
                    
                    $stockout = \App\stockout::find($stockoutTable->stock_id);

                    $invoice = \App\invoice::find($stockout->invoice_id);
                    if(isset($invoice->id)){
                        $pb = \App\invoiceTable::where('invoice_id',$invoice->id)->where('product_id',$sl->product_id)->first();
                        $vch_type = 'Pound Sale';
                        if($invoice->currency == '₹'){
                            $vch_type = 'INR Sale';
                        }
                        if($invoice->currency == '£'){
                            $vch_type = 'Pound Sale';
                        }
                        if($invoice->currency == '$'){
                            $vch_type = 'Dollar Sale';
                        }
                        if($invoice->currency == '€'){
                            $vch_type = 'Euro Sale';
                        }
                    }

                    
                    
                    $supplier = 'Global Vision Direct Ltd.';

                    if(isset($pb->id)){
                        $rate = $pb->rate * $invoice->conrate;
                    }
                }else{
                    $supplier = 'Global Vision Direct Ltd.';
                    $rate = 0;
                    $vch_type = 'Internal(Reject/Repair)';
                }
            }

            if(str_contains($sl->ref_no, 'SWAP')){
                $vch_type = 'Internal(Swapping)';
            }
            if(str_contains($sl->ref_no, 'Stock Correction')){
                $vch_type = $sl->ref_no;
            }

            if($sl->type == 1){ $inward_quantity = $sl->quantity; }else{ $inward_quantity = 0; }
            if($sl->type == 1){ $inward_value = $rate * $sl->quantity; }else{ $inward_value = 0; }
            if($sl->type == 2){ $outward_quantity = $sl->quantity; }else{ $outward_quantity = 0; }
            if($sl->type == 2){ $outward_value = $rate * $sl->quantity; }else{ $outward_value = 0; }
            $closing_quantity = $sl->remaining_stock;
            $closing_value = $rate * $sl->remaining_stock;

            $total_inward_quantity = $total_inward_quantity + $inward_quantity;
            $total_inward_value = $total_inward_value + $inward_value;
            $total_outward_quantity = $total_outward_quantity + $outward_quantity;
            $total_outward_value = $total_outward_value + $outward_value;
            ?>
            <td>{{ date('d-M-y', strtotime($sl->created_at)) }}</td>
            <td>{{ $supplier }}</td>
            <td>{{ $vch_type }}</td>
            <td>{{ $sl->voucher_no }}</td>
            <td>{{ ($sl->type == 1)?$sl->quantity:0 }}</td>
            <td>{{ ($sl->type == 1)?$rate * $sl->quantity:0 }}</td>
            <td>{{ ($sl->type == 2)?$sl->quantity:0 }}</td>
            <td>{{ ($sl->type == 2)?$rate * $sl->quantity:0 }}</td>
            <td>{{ $sl->remaining_stock }}</td>
            <td>{{ $rate * $sl->remaining_stock }}</td>
        </tr>
    @endforeach
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td><b>Total</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td>{{ $total_inward_quantity }}</td>
            <td>{{ $total_inward_value }}</td>
            <td>{{ $total_outward_quantity }}</td>
            <td>{{ $total_outward_value }}</td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
</table>