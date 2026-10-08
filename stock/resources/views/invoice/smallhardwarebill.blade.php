@extends('layouts.modal')

<body>

  <!-- next page -->
  <header>
      <h1 class="text-center">Small Hardware Bill - {{$supplier->c_name}}</h1>
  </header>
  <div class="container">
	

    <form method="POST" action="{{ url('/invoice/smallhardwarebillfinal') }}">
	  @csrf
		<div class="modal-body">
			<div class="row">
				<div class="col-6">
					<input name="po_no" type="hidden" class="form-control" value="{{$purchaseOrderNo}}" />
				</div>
				<div class="col-6">
					<input name="uk18" type="hidden" class="form-control" value="{{implode(',',$uk18)}}" />
				</div>
				<div class="col-6">
					<input name="date" type="hidden" class="form-control" value="{{date('d-M-Y',strtotime($date))}}" />
				</div>
			</div>
			<div class="row">
				<input id="iifhb" name="invoice_id" type="hidden" value="{{implode(',',$invoice_ids)}}">
				<input id="hardware_supplier" name="smallhardware_supplier" type="hidden" value="{{$hardware_supplier}}">
			</div>
		</div>
		<div class="modal-footer">
		  <button type="submit" onclick="return submitHardwareBill()" class="btn btn-success">Create PO</button>
		</div>
	  </form>
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
                  
                  <th colspan="11">{{$invoice->buyerorderno}}</th>
                </tr>
                <tr>
                  <th>#</th>
                  <th>Product Description</th>
                  <th>QTY</th>
                  <th>Small Hardware</th>
                  <th>Total Small Hardware</th>
                  <th>Name</th>
                  <th>Rate</th>
                  <th>Value</th>
                </tr>
              </tbody>

              <tbody>
                @if(isset($invoiceTable[$invoice->id])) @foreach($invoiceTable[$invoice->id] as $key => $invt) @if(isset($pricings[$invt->product_id])) @foreach($pricings[$invt->product_id] as $pricing)
					<?php
						if(isset($pricing->quantity)){
							$totsmh = $pricing->quantity * $invt->quantity;
						}else{
							$totsmh = 0;
						}
						if(isset($smhcounts[$pricing->smallhardwares->name])){
							$smhcounts[$pricing->smallhardwares->name] = $smhcounts[$pricing->smallhardwares->name] + $totsmh;
						}else{
							$smhcounts[$pricing->smallhardwares->name] = $totsmh;
						}
					?>
					<tr>
						<td>{{++$k}}</td>
						<td>{{$invt->product->code}} - {{$invt->product->name}}<br>
							<span style="font-style: italic; white-space: pre-line;">{{$invt->descriptionBox}}</span></td>
						<td>{{$invt->quantity}}</td>
						<td>
							@if(isset($pricing->quantity))
								{{$pricing->quantity}}  
							@else
								0
							@endif
						</td>
						<td>
							@if(isset($pricing->quantity))
								{{$pricing->quantity * $invt->quantity}}  
							@else
								0
							@endif
						</td>
						<td>
							@if(isset($pricing->smallhardwares->name))
								{{$pricing->smallhardwares->name}}  
							@endif
						</td>
						<td>
							@if(isset($pricing->price) && $pricing->price != '' && $pricing->price != NULL)
								{{$pricing->price}}
							@else
								@if(isset($pricing->smallhardwares->rate))
									{{$pricing->smallhardwares->rate}}  
								@else
									0
								@endif
							@endif
						</td>
						<td>
							@php
							if(isset($pricing->price) && $pricing->price != '' && $pricing->price != NULL){
								$value = $pricing->price * $invt->quantity * $pricing->quantity;
							}else{
								if(isset($pricing->smallhardwares->rate)){
									$value = $pricing->smallhardwares->rate * $invt->quantity * $pricing->quantity;
								}else{
									$value = 0;
								}
							}
							$totalValue = $totalValue + $value;
							@endphp
							{{$value}}
						</td>
				  	</tr>
				@endforeach 
				
				@else

				<tr>
					<td>{{++$k}}</td>
					<td>{{$invt->product->code}} - {{$invt->product->name}}<br>
						<span style="font-style: italic; white-space: pre-line;">{{$invt->descriptionBox}}</span></td>
					<td>{{$invt->quantity}}</td>
					<td>
						0
					</td>
					<td>
						0
					</td>
					<td>
						
					</td>
					<td>
						0
					</td>
					<td>
						0
					</td>
				  </tr>

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
              </tbody>
			  @endforeach @endif
			  <tbody>
				
				<tr>
					<td colspan="4"></td>
					<td colspan="3"><strong>Total</strong></td>
					<td><strong>{{$grandValue}}</strong></td>
				</tr>
				<tr>
					<td colspan="11"></td>	
				</tr>
				<tr>
					<td colspan="11"><strong>Total Small Hardwares</strong></td>	
				</tr>
				@php 
				$i = 1;
				@endphp
				@if(isset($smhcounts)) @foreach($smhcounts as $name=>$smhcount)
				<tr>
					<td>{{$i++}}</td>
					<td colspan="3"><strong>{{$name}}</strong></td>
					<td colspan="8">{{$smhcount}}</td>
				</tr>
				@endforeach @endif	
				
				{{-- <tr>
					<td colspan="4"></td>
					<td colspan="3"><strong>Grand Total</strong></td>
					<td><strong>{{$grandTotalValue}}</strong></td>
				</tr> --}}
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