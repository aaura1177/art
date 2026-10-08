@include('layouts.modal')

<body>

  
  <header>
      <h1 class="text-center">Corner and L Bill</h1>
  </header>

  <div class="container">
	<!--<div class="row box-space">
        <div class="col-6">
          <h3 style="text-decoration: underline;">BILL TO</h3><br/>
          @if(isset($companyDetails))
            <img src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image">
            <address>
              <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
              <p>State Name: {{$companyDetails->state}}</p>
              <p>Company PAN: {{$companyDetails->pan}}</p>
              <p>GSTIN/UIN: {{$companyDetails->gstin}}</p>
              <p>E-mail: po@artisanfurniture.net</p>
            </address> 
            @endif
        </div>
        
    </div>

    <div class="row box-space">
      <div class="col-6">
          <address>
            <table width="100%" cellspacing="0">
              <tbody>
                <tr>
                  <td><h3 style="text-decoration: underline;">DISPATCH TO</h3></td>
                </tr>
                <tr>
                  <td><p>Global Vision Direct (P) Ltd</p></td>
                </tr>
                <tr>
                  <td><p>Plot# 1216/2, Mahapura Road,</p></td>
                </tr>
                <tr>
                  <td><p>Jaipur - Mumbai National Highway,</p></td>
                </tr>
                <tr>
                  <td><p>Bhankrota, Jaipur, Rajasthan - 08</p></td>
                </tr>
                <tr>
                  <td><p>GSTIN/UIN: {{$companyDetails->gstin}}</p></td>
                </tr>
              </tbody>
            </table>
          </address>
      </div>
      
    </div>-->
    <!-- Header second row -->
    <div class="row mt-3">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
			<?php 
				$grand_total_corner_quantity = 0;
				$grand_total_corner_amount = 0;
				
				$grand_total_l_quantity = 0;
				$grand_total_l_amount = 0;
			?>
			@if(isset($products)) @foreach($products as $key => $product)
				@if(count($product))
				  <thead>
					<tr class="border-less-row">
					  
					  <th colspan="11">{{$key}}</th>
					</tr>
					<tr>
					  <th>Product</th>
					  <th>Quantity</th>
					  <th>Corners per piece</th>
					  <th>Total Corners</th>
					  <th>Corner Amount</th>
					  <th>L per piece</th>
					  <th>Total L</th>
					  <th>L Amount</th>
					</tr>
				  </thead>
				  <tbody>
					<?php 
						$total_corner_quantity = 0;
						$total_corner_amount = 0;
						
						$total_l_quantity = 0;
						$total_l_amount = 0;
					?>
					@foreach($product as $k => $b)
					<tr>
						@php
						
						$amount  = 0.50;
						$corner_amount = $b->corners*$amount*$b->product_quantity;
						$l_amount = $b->l*$amount*$b->product_quantity;
						$total_corner_quantity 	= 	$total_corner_quantity + ($b->corners*$b->product_quantity);
						$total_l_quantity 		= 	$total_l_quantity + ($b->l*$b->product_quantity);
						$total_corner_amount	= 	$total_corner_amount + $corner_amount;
						$total_l_amount			= 	$total_l_amount + $l_amount;
						@endphp
						<td>{{$b->product->code}} - {{$b->product->name}}</td>
						<td>{{$b->product_quantity}}</td>
						<td>{{$b->corners}}</td>
						<td>{{$b->total_corners}}</td>
						<td>{{$b->total_corners_amount}}</td>
						<td>{{$b->l}}</td>
						<td>{{$b->total_l}}</td>
						<td>{{$b->total_l_amount}}</td>
					</tr>
					@endforeach
					<tr>
						<td><b></b></td>
						<td><b>Total</b></td>
						<td></td>
						<td><b>{{$total_corner_quantity}}</b></td>
						<td><b>{{$total_corner_amount}}</b></td>
						<td></td>
						<td><b>{{$total_l_quantity}}</b></td>
						<td><b>{{$total_l_amount}}</b></td>
					</tr>
					<tr><td colspan="11"></td></tr>
					@php
						$grand_total_corner_quantity 	= 	$total_corner_quantity + $grand_total_corner_quantity;
						$grand_total_corner_amount	 	= 	$total_corner_amount + $grand_total_corner_amount;
						
						$grand_total_l_quantity 	= 	$total_l_quantity + $grand_total_l_quantity;
						$grand_total_l_amount	 	= 	$total_l_amount + $grand_total_l_amount;
					@endphp
				@endif
			@endforeach @endif
					<tr><td colspan="11"></td></tr>
					<tr><td colspan="11"></td></tr>
					<tr><td colspan="11"></td></tr>
					<tr>
						<td><b></b></td>
						<td><b>Grand Total</b></td>
						<td></td>
						<td><b>{{$grand_total_corner_quantity}}</b></td>
						<td><b>{{$grand_total_corner_amount}}</b></td>
						<td></td>
						<td><b>{{$grand_total_l_quantity}}</b></td>
						<td><b>{{$grand_total_l_amount}}</b></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
  </div>
<script>
$(function(){
	$('')
});
</script>
 </body>