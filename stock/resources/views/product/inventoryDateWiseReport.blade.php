<table>
    <thead>
        <tr>
            <th colspan="15" style="text-align:center;">Date Wise Report - {{$start_date}} - {{$end_date}}</th>
        </tr>
        <tr>
            <th rowspan="3" style="width:60px;">Particulars</th>
            <th colspan="14" style="text-align:center;">Global Vision Direct (P) Ltd.</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="3" style="text-align:center;"><b>Opening Balance</b></td>
            <td colspan="3" style="text-align:center;"><b>Inwards</b></td>
            <td style="text-align:center;"><b>Swap In</b></td>
            <td colspan="3" style="text-align:center;"><b>Outwards</b></td>
            <td style="text-align:center;"><b>Swap Out</b></td>
            <td colspan="3" style="text-align:center;"><b>Closing Balance</b></td>
        </tr>
        <tr>
            <td>Quantity</td>
            <td>Rate</td>
            <td>Value</td>
            <td>Quantity</td>
            <td>Rate</td>
            <td>Value</td>
            <td>Quantity</td>
            <td>Quantity</td>
            <td>Rate</td>
            <td>Value</td>
            <td>Quantity</td>
            <td>Quantity</td>
            <td>Rate</td>
            <td>Value</td>
        </tr>
    @foreach($pall as $id=>$p)
        <?php
        $opening_balance = 0;
        $closing_balance = 0;
        $open_rate = 0;
        $close_rate = 0;
        $out_rate_final = 0;
        ?>
        @if(!empty($p['stocklog_first']))
            <?php
            $opening_balance = $p['stocklog_first']['remaining_stock'];
            ?>
        @else
            @if(!empty($p['stocklog_first_2']))
                <?php
                $opening_balance = $p['stocklog_first_2']['opening_balance'];
                ?>
            @endif
        @endif
        @if(!empty($p['stocklog_last']))
            <?php
            $closing_balance = $p['stocklog_last']['remaining_stock'];
            $open_rate = 0;
            $close_rate = 0;

            if($opening_balance > 0){                    
                $ins        =   [];
                foreach($p['pbTable_open'] as $inn){
                    if(isset($inn['id'])){
                        $ins[$inn['id']]['qty']       =   $inn['receiveqty'];
                        $ins[$inn['id']]['rate']      =   $inn['actual_rate'];
                    }
                }
                krsort($ins);
                $qty = 0;
                $amount = 0;
                $remq = $opening_balance;
                foreach($ins as $in){
                    $qty = $qty+$in['qty'];
                    if($qty > $opening_balance){
                        $amount = $amount + $in['rate']*$remq;
                        break;
                    }else{
                        $remq = $remq - $in['qty'];
                        $amount = $amount + ($in['rate']*$in['qty']);
                    }
                }
                if($amount > 0){
                    $open_rate = $amount/$opening_balance;
                }
            }

            if($closing_balance > 0){                    
                $ins        =   [];
                foreach($p['pbTable_close'] as $inn){
                    if(isset($inn['id'])){
                        $ins[$inn['id']]['qty']       =   $inn['receiveqty'];
                        $ins[$inn['id']]['rate']      =   $inn['actual_rate'];
                    }
                }
                krsort($ins);
                $qty = 0;
                $amount = 0;
                $remq = $closing_balance;
                foreach($ins as $in){
                    $qty = $qty+$in['qty'];
                    if($qty > $closing_balance){
                        $amount = $amount + $in['rate']*$remq;
                        break;
                    }else{
                        $remq = $remq - $in['qty'];
                        $amount = $amount + ($in['rate']*$in['qty']);
                    }
                }
                if($amount > 0){
                    $close_rate = $amount/$closing_balance;
                }
            }


            $out_rate = 0;
            $count_stockoutTable = count($p['stockoutTable']);
            $count_pbTable = count($p['pbTable']);
            $out_rate_final = 0;
            foreach($p['stockoutTable'] as $st){
                $stock_out = \App\stockout::find($st['stock_id']);
                $invoice = \App\invoice::where('id',$stock_out->invoice_id)->first();
                $invoiceTable = \App\invoiceTable::where('invoice_id',$stock_out->invoice_id)->where('product_id',$id)->first();
                if(isset($invoiceTable->rate)){
                    $out_rate = $out_rate + ($invoiceTable->rate * $invoice->conrate);
                }
            }
            if($count_stockoutTable > 0){
                $out_rate_final = $out_rate/$count_stockoutTable;
            }
            
            // if($p['stocklog_first']['type'] == 1){
            //     if(isset($p['pbTable'][0])){
            //         $open_rate = $p['pbTable'][0]['rate'];
            //     }else{
            //         $open_rate = 0;
            //     }
            // }else{
            //     if(isset($p['stockoutTable'][0])){
            //         $stockoutTable_first = $p['stockoutTable'][0];

            //         $stockout = \App\stockout::find($stockoutTable_first['stock_id']);
            //         $invoice = \App\invoice::where('id',$stockout->invoice_id)->first();
            //         $pb = \App\invoiceTable::where('invoice_id',$stockout->invoice_id)->where('product_id',$id)->first();
            //         if(isset($pb->rate)){
            //             $open_rate = $pb->rate * $invoice->conrate;
            //         }
            //     }else{
            //         $open_rate = 0;
            //     }
                
                
            // }
            // if($p['stocklog_last']['type'] == 1){
            //     if($count_pbTable > 0 && isset($p['pbTable'][$count_pbTable - 1])){
            //         $close_rate = $p['pbTable'][$count_pbTable - 1]['rate'];
            //     }else{
            //         $close_rate = 0;
            //     }
            // }else{
            //     if($count_stockoutTable > 0 && isset($p['stockoutTable'][$count_stockoutTable - 1])){
            //         $stockoutTable_last = $p['stockoutTable'][$count_stockoutTable - 1];

            //         $stockout_last = \App\stockout::find($stockoutTable_last['stock_id']);
            //         $invoice = \App\invoice::where('id',$stockout_last->invoice_id)->first();
            //         $pbl = \App\invoiceTable::where('invoice_id',$stockout_last->invoice_id)->where('product_id',$id)->first();
            //         if(isset($pbl->rate)){
            //             $close_rate = $pbl->rate * $invoice->conrate;
            //         }
            //     }else{
            //         $close_rate = 0;
            //     }
            // }
            ?>
        
        @endif

        @if($opening_balance > 0 || $p['inward'] > 0 || $p['outward'] > 0 || $closing_balance > 0)
        <tr>
            <td>{{ $p['product']->code }}</td>
            <td>{{ $opening_balance }}</td>
            <td>{{ number_format($open_rate,2,'.','') }}</td>
            <td>{{ number_format($open_rate * $opening_balance,2,'.','') }}</td>
            <td>{{ $p['inward'] }}</td>
            <td>{{ number_format($p['inward_rate'],2,'.','') }}</td>
            <td>{{ number_format($p['inward'] * $p['inward_rate'],2,'.','') }}</td>
            <td>{{ $p['inward_swap'] }}</td>
            <td>{{ $p['outward'] }}</td>
            <td>{{ number_format($out_rate_final,2,'.','') }}</td>
            <td>{{ number_format($p['outward'] * $out_rate_final,2,'.','') }}</td>
            <td>{{ $p['outward_swap'] }}</td>
            <td>{{ $closing_balance }}</td>
            <td>{{ number_format($close_rate,2,'.','') }}</td>
            <td>{{ number_format($close_rate * $closing_balance,2,'.','') }}</td>
        </tr>
        @endif
    @endforeach
    </tbody>
</table>