@extends('layouts.modal')
<body>
<style>
	.table th {
		font-size: 12px !important;
	}
	.table td {
		font-size: 11px !important;
	}
</style>
     <div class="container">

    <!-- Header row -->
    
		<div style="text-align:center;">
			<img src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image" width='100px' />
		  <h1>Stock Valuation</h1>
		  <h5 style="font-size:12px;" >As on {{ (isset($from)?date('d F Y',strtotime($from)):date('d F Y')) }}</h5>
		</div>
		<div class="row box-space" id="clogo">
		
		  <table class="table table-bordered" width="100%" cellspacing="0">
			<thead>
			  <tr>
				<th>Code</th>
				<th>Name</th>
				<th>Quantity</th>
				<th>Rate</th>
				<th>Total Value</th>
			  </tr>
			</thead>
			<tbody>
				@if(isset($products)) @foreach($products as $key => $product)
			  <tr>
				<?php
				if($product->remaining_stock < 0){
					$remaining =	0;
				}else{
					$remaining =	$product->remaining_stock;
				}
				?>
				<td>{{$product->code}}</td>
				<td>{{$product->name}}</td>
				<td>{{$remaining}}</td>
				<td>
					<?php
					
					$ins		=	[];
					$rates		=	[];
					$last 			=	0;
					foreach($product['pbs'] as $inn){
						$ins[$inn->id]['qty']		=	$inn->receiveqty;
						$ins[$inn->id]['rate']		=	$inn->actual_rate;
					}
					krsort($ins);
					$qty = 0;
					$amount = 0;
					$remq = $product->remaining_stock;
					foreach($ins as $in){
						$qty = $qty+$in['qty'];
						if($qty > $product->remaining_stock){
							$amount = $amount + $in['rate']*$remq;
							break;
						}else{
							$remq = $remq - $in['qty'];
							$amount = $amount + ($in['rate']*$in['qty']);
						}
					}
				?>
				{{ ($product->remaining_stock > 0)?$amount/$product->remaining_stock:0 }}
				</td>
				<td>{{$amount}}</td>
			  </tr>
			  @endforeach @endif
			</tbody>
		  </table>
		  </div>
    </div>
</body>