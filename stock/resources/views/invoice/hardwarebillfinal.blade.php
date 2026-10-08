@extends('layouts.modal')

<body>

  <!-- next page -->
  <header>
      <h1 class="text-center">Hardware Purchase Order</h1>
  </header>
  <div class="container">
	<div class="row box-space">
        <div class="col-6">
          <h3 style="text-decoration: underline;">BILL TO</h3><br/>
          @if(isset($companyDetails))
            <img width="450px" src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image" style="width:100px;">
            <p style="font-size: 24px;"><strong>{{ $companyDetails->c_name }}</strong></p>
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
		
		<div class="col-6">
            <div class="pull-right" id="supplier">
              @if(isset($supplier))
				<h1>PO No. {{$purchaseOrderNo}}</h1>
                <p>PO Date: {{date('d-M-Y',strtotime($date))}}</p><br>
                <address>
                <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
                <p>{{$supplier->c_name}}</p>
                <p>{{$supplier->address1}}</p>
                <p>{{$supplier->address2}}</p>
                <p>{{$supplier->city}} - {{$supplier->postcode}}</p>
                <p>State Name: {{$supplier->state}}</p>
                <p>GSTIN/UIN: <span style="font-weight: 600; font-size: 16px;" id="gstin">{{$supplier->gstin}}</span></p>
                <p>Phone: {{$supplier->phone1}}</p>
                <p>E-mail: {{$supplier->email}}</p>
              </address>
			  @endif
            </div>
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
      
    </div>
    <!-- Header second row -->
    <div class="row mt-3">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
			@php
			$grandValue = 0;
			$grandTotalValue = 0;
			@endphp
			@if(isset($invoice)) @foreach($invoice as $key => $invoice)
				@php
				$totalValue = 0; 
				$k = 0;
				@endphp
              <tbody>
                <tr class="border-less-row">
                  
                  <td colspan="11"><b>{{$invoice->buyerorderno}}</b></td>
                </tr>
                <tr>
                  <th>#</th>
                  <th>Product Description</th>
                  <th>QTY</th>
                  <th>Hardware</th>
                  <th>Total Hardware</th>
                  <th>Name</th>
                  <th>Rate</th>
                  <th>Value</th>
                </tr>
              </tbody>

              <tbody>
                @if(isset($invoiceTable[$invoice->id])) @foreach($invoiceTable[$invoice->id] as $key => $invt)
					@if(isset($pricing[$invt->product_id]))
						@php
						$showProd =	0;
						@endphp
						@if(!empty($pricing[$invt->product_id]))
							@for($h = 1;$h < 6;$h++)
								@php 
								$hardvar = "hardware".$h;
								$hardware_quantity = "hardware".$h."_quantity";
								$hardware_cost = "hardwareCost".$h;
								@endphp
								@if($pricing[$invt->product_id]->$hardvar != '')
									@foreach($hardwares as $hardware)
										@if($hardware->id == $pricing[$invt->product_id]->$hardvar)
											@php
											$showProd =	1;
											@endphp
											<tr>
												<td>{{++$k}}</td>
												<td>{{$invt->product->code}} - {{$invt->product->name}}<br>
													<span style="font-style: italic; white-space: pre-line;">{{$invt->descriptionBox}}</span>
												</td>
												<td>{{$invt->quantity}}</td>
												<td>{{$pricing[$invt->product_id]->$hardware_quantity}}</td>
												<td>{{$invt->quantity * $pricing[$invt->product_id]->$hardware_quantity}}</td>
											  
												<td>{{$hardware->name}}</td>
												<td>
												@php
												if($hardware->rate){
													$hardwarerate 		= $hardware->rate; 
												}else{
													if($pricing[$invt->product_id]->$hardware_quantity > 0){
														$hardwarerate 		= $pricing[$invt->product_id]->$hardware_cost/$pricing[$invt->product_id]->$hardware_quantity; 
													}else{
														$hardwarerate 		= 0; 
													}
												}
												@endphp
												{{$hardwarerate}}
												</td>
												@php
												if($hardware->rate){
													$value 		= $hardware->rate * $invt->quantity * $pricing[$invt->product_id]->$hardware_quantity; 
												}else{
													$value 		= $pricing[$invt->product_id]->$hardware_cost * $invt->quantity * $pricing[$invt->product_id]->$hardware_quantity; 
												}
												$totalValue = $totalValue + $value; 
												@endphp
												<td>{{$value}}</td>
											</tr>
										@endif
									@endforeach
								@endif
							@endfor
							@if(!$showProd)
								@php
								$showProd =	1;
								@endphp
							<!--<tr>
								<td></td>
								<td>{{$invt->product->code}} - {{$invt->product->name}}<br>
									<span style="font-style: italic; white-space: pre-line;">{{$invt->descriptionBox}}</span>
								</td>
								<td>{{$invt->quantity}}</td>
								<td>0</td>
								<td>0</td>
							  
								<td>--</td>
								<td>0</td>
								<td>0</td>
							</tr>-->
							@endif
						@else
							@if(!$showProd)
								@php
								$showProd =	1;
								@endphp
							<!--<tr>
								<td></td>
								<td>{{$invt->product->code}} - {{$invt->product->name}}<br>
									<span style="font-style: italic; white-space: pre-line;">{{$invt->descriptionBox}}</span>
								</td>
								<td>{{$invt->quantity}}</td>
								<td>0</td>
								<td>0</td>
							  
								<td>--</td>
								<td>0</td>
								<td>0</td>
							</tr>-->
							@endif
						@endif
					@endif			
                @endforeach @endif
                <tr>
                  <td colspan="4"></td>
                  <td colspan="3"><strong>Total</strong></td>
                  <td><strong>{{$totalValue}}</strong></td>
				  <?php
					$grandValue	=	$grandValue + $totalValue;
				  ?>
                </tr>
				<tr>
					<td colspan="11"></td>	
				</tr>
				<tr>
					<td colspan="11"></td>
				</tr>
				<tr>
					<td colspan="11"></td>	
				</tr>
              </tbody>
			  @endforeach @endif
			  <tbody>
				
				<tr>
					<td colspan="4"></td>
					<td colspan="3"><strong>Total</strong></td>
					<td><strong>{{$grandValue}}</strong></td>
				</tr>
				@if($supplier->gst)
					@php
					$gstpercent = $supplier->gstpercent/2;
					$grandTotalValue = $grandValue + ($grandValue*$supplier->gstpercent)/100;
					@endphp
				<tr>
					<td colspan="4"></td>
					<td colspan="3"><strong>CGST</strong></td>
					<td><strong>{{($grandValue*$gstpercent)/100}}</strong></td>
				</tr>
				<tr>
					<td colspan="4"></td>
					<td colspan="3"><strong>SGST</strong></td>
					<td><strong>{{($grandValue*$gstpercent)/100}}</strong></td>
				</tr>
				@endif
				
				@if($supplier->tds)
					@php
					$grandTotalValue = $grandTotalValue - ($grandValue*$supplier->tdspercent)/100;
					@endphp
				<tr>
					<td colspan="4"></td>
					<td colspan="3"><strong>TDS</strong></td>
					<td><strong>{{($grandValue*$supplier->tdspercent)/100}}</strong></td>
				</tr>
				@endif
				<tr>
					<td colspan="4"></td>
					<td colspan="3"><strong>Grand Total</strong></td>
					<td><strong>{{$grandTotalValue}}</strong></td>
				</tr>
			  </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Script start for Print Invoice-->
@if(isset($print) && $print==1)
    <script type="text/javascript">
      document.ready = window.print();
    </script>
@endif
<!-- Script end -->
