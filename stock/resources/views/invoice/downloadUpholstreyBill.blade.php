@extends('layouts.modal')

<body>

  <div class="img-fluid text-center mx-auto mt-5">
        <img src="{{ asset('/images/gvllogo.png')}}" alt="Global Vision Direct (P) Ltd">
        <h3 class="text-center">Global Vision Direct (P) Ltd</h3>
    </div>
  <header>
      <h1 class="text-center">Upholstery Bill - {{$contractor->name}} (<?php echo date('F Y',strtotime($month[1].'-'.$month[0].'-'.date('d'))); ?>)</h1>
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
				  <thead>
					<tr class="border-less-row">
					  
					  <th colspan="11">{{$key}}</th>
					</tr>
					<tr>
					  <th>Product</th>
					  <th>Rate</th>
					  <th>Quantity</th>
					  <th>Amount</th>
					</tr>
				  </thead>
				  <tbody>
					<?php 
						$total_quantity = 0;
						$total_amount = 0;
					?>
					@foreach($bill as $k => $b)
					<tr>
						@php
						($b->upholestry_rate != "")?$upholestry_rate	=	$b->upholestry_rate:$upholestry_rate	=	$b->product->upholstry;
						($b->amount != 0)?$amount	=	$b->amount:$amount	=	$b->product->upholstry*$b->quantity;
						
						$total_quantity 	= 	$total_quantity + $b->quantity;
						$total_amount	 	= 	$total_amount + $amount;
						@endphp
						<td>{{$b->product->code}}</td>
						<td>{{$upholestry_rate}}</td>
						<td>{{$b->quantity}}</td>
						<td>{{$amount}}</td>
					</tr>
					@endforeach
					<tr>
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
						<td><b>Grand Total</b></td>
						<td><b>{{$grand_total_quantity}}</b></td>
						<td><b>{{$grand_total_amount}}</b></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
  </div>
  
 </body>