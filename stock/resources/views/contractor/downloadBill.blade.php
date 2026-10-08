@extends('layouts.modal')

<body>

  <div class="img-fluid text-center mx-auto mt-5">
        <img src="{{ asset('/images/gvllogo.png')}}" alt="Global Vision Direct (P) Ltd">
        <h3 class="text-center">Global Vision Direct (P) Ltd</h3>
    </div>
  <header>
      <h1 class="text-center">Contractor Polish Detail - {{$contractor->name}} (<?php echo date('F Y',strtotime($month[1].'-'.$month[0].'-1')); ?>)</h1>
  </header>

  <div class="container">

    <!-- Header second row -->
    <div class="row mt-3">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
			<?php 
				$grand_total_quantity = 0;
				$grand_total_amount = 0;
			?>
			@if(isset($bills)) @foreach($bills as $key => $bill)
				@if(count($bill))
				  <tbody>
				  	
					<tr class="border-less-row">
					  
					  <th colspan="11">{{$key}}</th>
					</tr>
					<tr>
					  <th>Product</th>
					  <th>Finish</th>
					  <th>Rate</th>
					  <th>Quantity</th>
					  <th>Amount</th>
					</tr>
				  </tbody>
				  <tbody>
					<?php 
						$total_quantity = 0;
						$total_amount = 0;
					?>
					@foreach($bill as $k => $b)
					<tr>
						@php
						($b->finishing != "")?$finishing	=	$b->finishing:$finishing	=	$b->product->finishing;
						($b->finishing_rate != "")?$finishing_rate	=	$b->finishing_rate:$finishing_rate	=	$b->product->finishing_price;
						($b->amount != 0)?$amount	=	$b->amount:$amount	=	$b->product->finishing_price*$b->quantity;
						
						$total_quantity 	= 	$total_quantity + $b->quantity;
						$total_amount	 	= 	$total_amount + $amount;
						@endphp
						<td>{{$b->product->code}}</td>
						<td>{{$finishing}}</td>
						<td>{{$finishing_rate}}</td>
						<td>{{$b->quantity}}</td>
						<td>{{$amount}}</td>
					</tr>
					@endforeach
					<tr>
						<td></td>
						<td></td>
						<td><b>Total</b></td>
						<td><b>{{$total_quantity}}</b></td>
						<td><b>{{$total_amount}}</b></td>
					</tr>
					@php
						$grand_total_quantity 	= 	$total_quantity + $grand_total_quantity;
						$grand_total_amount	 	= 	$total_amount + $grand_total_amount;
					@endphp
				@endif
			@endforeach @endif
					<tr><td colspan="11"></td></tr>
					<tr><td colspan="11"></td></tr>
					<tr><td colspan="11"></td></tr>
					<tr>
						<td></td>
						<td></td>
						<td><b>Total</b></td>
						<td><b>{{$grand_total_quantity}}</b></td>
						<td><b>{{$grand_total_amount}}</b></td>
					</tr>
					@php
					$grand_amount = $grand_total_amount;
					@endphp
					@if($contractor->gst)
						@php
						$gstpercent = $contractor->gstpercent/2;
						$grand_amount = $grand_total_amount + ($grand_total_amount*$contractor->gstpercent)/100;
						@endphp
					<!--<tr>
						<td></td>
						<td></td>
						<td><b>CGST</b></td>
						<td></td>
						<td><b>{{($grand_total_amount*$gstpercent)/100}}</b></td>
					</tr>
					<tr>
						<td></td>
						<td></td>
						<td><b>SGST</b></td>
						<td></td>
						<td><b>{{($grand_total_amount*$gstpercent)/100}}</b></td>
					</tr>-->
					@endif
					@if($contractor->tds)
						@php
						$grand_amount = $grand_amount - ($grand_total_amount*$contractor->tdspercent)/100;
						@endphp
					<!--<tr>
						<td></td>
						<td></td>
						<td><b>TDS</b></td>
						<td></td>
						<td><b>{{($grand_total_amount*$contractor->tdspercent)/100}}</b></td>
					</tr>-->
					@endif
					<!--<tr>
						<td></td>
						<td></td>
						<td><b>Total</b></td>
						<td></td>
						<td><b>{{$grand_amount}}</b></td>
					</tr>-->
				</tbody>
			</table>
		</div>
	</div>
  </div>
  
 </body>