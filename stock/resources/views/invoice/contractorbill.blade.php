@extends('layouts.app')

@section('content')
<script src="{{ asset('ui-vendor/jquery.floatThead.js') }}"></script>
<script>
	$(function() {
		$('.table').each(function() {
			var $table = $(this);
			$(this).floatThead({
				position: 'fixed',
				responsiveContainer: function() {
					return $table.closest('.table-responsive');
				}
			});
		});

	});
</script>
<style>
	th {
		background: white;
	}
</style>
<script>
	$(function() {
		$("[id^='frate']").change(function() {
			info = $(this).attr('id').split("_");
			invoice_id = info[1];
			product_id = info[2];
			frate = $(this).val();
			pquant = $("#quant_" + invoice_id + "_" + product_id).val();
			$("#total_" + invoice_id + "_" + product_id).val(frate * pquant);
			$("[id^='camount_" + invoice_id + "_" + product_id + "_']").each(function() {
				cid = $(this).attr('id').split("_");
				cid = cid[3];
				quant = $("#cquant_" + invoice_id + "_" + product_id + "_" + cid).val();
				$(this).val(quant * frate);
				submitRecord(invoice_id, product_id, cid, quant, frate * quant, frate);
			});
		});

		$("[id^='frate']").each(function() {
			info = $(this).attr('id').split("_");
			invoice_id = info[1];
			product_id = info[2];
			frate = $(this).val();
			pquant = $("#quant_" + invoice_id + "_" + product_id).val();
			$("#total_" + invoice_id + "_" + product_id).val(frate * pquant);
		});

		$("[id^='cquant']").change(function() {
			info = $(this).attr('id').split("_");
			invoice_id = info[1];
			product_id = info[2];
			cid = info[3];
			frate = $("#frate_" + invoice_id + "_" + product_id).val();
			quant = $(this).val();
			$("#camount_" + invoice_id + "_" + product_id + "_" + cid).val(frate * quant);
			total_quant = 0;
			$("[id^='cquant_" + invoice_id + "_" + product_id + "_']").each(function() {
				total_quant = parseInt(total_quant) + parseInt($(this).val());
			});
			pquant = parseInt($("#quant_" + invoice_id + "_" + product_id).val());

			total_quant = parseInt(total_quant);
			$("#balance_" + invoice_id + "_" + product_id).val(pquant - total_quant);
			submitRecord(invoice_id, product_id, cid, quant, frate * quant, frate);
		});

		$("[id^='cquant']").each(function() {
			info = $(this).attr('id').split("_");
			invoice_id = info[1];
			product_id = info[2];
			cid = info[3];
			frate = $("#frate_" + invoice_id + "_" + product_id).val();
			quant = $(this).val();
			$("#camount_" + invoice_id + "_" + product_id + "_" + cid).val(frate * quant);
			total_quant = 0;
			$("[id^='cquant_" + invoice_id + "_" + product_id + "_']").each(function() {
				total_quant = parseInt(total_quant) + parseInt($(this).val());
			});
			pquant = parseInt($("#quant_" + invoice_id + "_" + product_id).val());

			total_quant = parseInt(total_quant);
			$("#balance_" + invoice_id + "_" + product_id).val(pquant - total_quant);
		});

		// $("[id^='company_']").change(function(){
		// info         =       $(this).attr('id').split("_");
		// invoice_id   =       info[1];
		// product_id   =       info[2];
		// total_quant = 0;
		// $("[id^='cquant_"+invoice_id+"_"+product_id+"_']").each(function(){
		// total_quant =        parseInt(total_quant) + parseInt($(this).val()) + parseInt($("#company_"+invoice_id+"_"+product_id).val());
		// pquant               =       $("#quant_"+invoice_id+"_"+product_id).val();
		// $("#balance_"+invoice_id+"_"+product_id).val(pquant - total_quant);
		// });
		// finishing_rate       =       $("#frate_"+invoice_id+"_"+product_id).val();
		// total_amount =       finishing_rate*$("#company_"+invoice_id+"_"+product_id).val();
		// submitRecord(invoice_id,product_id,0,$("#company_"+invoice_id+"_"+product_id).val(),total_amount,finishing_rate);
		// });

		$("[id^='finishing_']").change(function() {
			info = $(this).attr('id').split("_");
			invoice_id = info[1];
			product_id = info[2];
			frate = $("#frate_" + invoice_id + "_" + product_id).val();
			$("[id^='camount_" + invoice_id + "_" + product_id + "_']").each(function() {
				cid = $(this).attr('id').split("_");
				cid = cid[3];
				quant = $("#cquant_" + invoice_id + "_" + product_id + "_" + cid).val();
				$(this).val(quant * frate);
				submitRecord(invoice_id, product_id, cid, quant, frate * quant, frate);
			});
		});

		function submitRecord(invoice_id, product_id, contractor_id, quantity, total_amount, finishing_rate) {
			finish = $("#finishing_" + invoice_id + "_" + product_id).val();
			token = $("[name^='_token']").val();
			$.ajax({
				'url': "{{ url('/invoice/createContractorBill') }}",
				'method': 'POST',
				'data': {
					"_token": token,
					"product_id": product_id,
					"invoice_id": invoice_id,
					"contractor_id": contractor_id,
					"quantity": quantity,
					"amount": total_amount,
					"finishing_rate": finishing_rate,
					"finishing": finish
				},
				success: function(r) {

				}
			});
		}
		// --- 2. NEW BULK SAVE LOGIC ---
		$('#save_all_btn').click(function(e) {
			e.preventDefault();

			let allData = [];
			let token = $("[name^='_token']").first().val();
			let btn = $(this);

			// Loop through every row (tr) inside any tbody
			$('tbody tr').each(function() {
				let row = $(this);

				// We find the 'product quantity' input to extract invoice_id and product_id
				let quantInput = row.find('input[id^="quant_"]');
				if (quantInput.length === 0) return; // Skip if row doesn't have product data

				let idParts = quantInput.attr('id').split('_');
				let invoice_id = idParts[1];
				let product_id = idParts[2];

				// Get row-level values (Finishing Rate & Finishing Type)
				let finishing_rate = row.find('#frate_' + invoice_id + '_' + product_id).val();
				let finishing = row.find('#finishing_' + invoice_id + '_' + product_id).val();

				// Loop through each contractor quantity input in this specific row
				row.find('input[id^="cquant_"]').each(function() {
					let cInput = $(this);
					let cParts = cInput.attr('id').split('_');
					let contractor_id = cParts[3];
					let quantity = cInput.val();

					// Calculate amount based on the inputs (to ensure sync)
					let amount = row.find('#camount_' + invoice_id + '_' + product_id + '_' + contractor_id).val();

					// Add to our payload
					allData.push({
						invoice_id: invoice_id,
						product_id: product_id,
						contractor_id: contractor_id,
						quantity: quantity,
						amount: amount,
						finishing_rate: finishing_rate,
						finishing: finishing
					});
				});
			});

			if (allData.length === 0) {
				alert('No data to save.');
				return;
			}

			// Change button text to indicate loading
			let originalText = btn.text();
			btn.text('Saving...').prop('disabled', true);

			$.ajax({
				url: "{{ url('/invoice/createContractorBillbulk') }}", // Uses your NEW route
				method: 'POST', // Note: Usually updates are POST, but your route might be GET/POST. Recommend POST.
				data: {
					_token: token,
					bill_data: allData
				},
				success: function(response) {
					alert('All records updated successfully!');
					btn.text(originalText).prop('disabled', false);
				},
				error: function(xhr, status, error) {
					console.error(error);
					alert('Something went wrong while saving.');
					btn.text(originalText).prop('disabled', false);
				}
			});
		});
	});
</script>
<div class="mx-2">
	<div class="row mx-0 my-2">
		<h2>Contractor Bill</h2>
	</div>


	<!-- Form Starts -->
	<div class="form-group">
		<div>
			<button id="save_all_btn" class="btn btn-primary ">Save All Changes</button>
		</div>
		<br>
		<div>
			@foreach($contractors as $c)
			<input class="contractor_checkbox" type="checkbox" id="{{$c->id}}" checked="checked"> {{$c->name}}<br>
			@endforeach
		</div>
		<!-- Product Details-->
		@foreach($products as $inv => $pro)
		<div class="row mt-5 my-3">
			<div class="col-6">
				<h5>{{$inv}}</h5>
			</div>
		</div>

		<div class="table-responsive">
			<table class="table table-hover">
				<thead id="thead_{{$inv}}">
					<tr id="mytable">
						<th scope="col" style="min-width: 200px;">Product</th>
						<th scope="col" style="min-width: 70px;">QTY</th>
						<th scope="col" style="min-width: 120px;">Finish</th>
						<th scope="col" style="min-width: 120px;">Finishing Rate</th>
						@foreach($contractors as $c)
						<th class="th_{{$c->id}}" scope="col" style="min-width: 120px;">{{$c->name}}</th>
						<th class="th_amount_{{$c->id}}" scope="col" style="min-width: 120px;">{{$c->name}} Amount</th>
						@endforeach
						<th scope="col" style="min-width: 120px;">Balance</th>
						<th scope="col" style="min-width: 120px;">Total</th>
					</tr>
				</thead>
				<tbody>
					@foreach($pro as $key => $p)
					<?php
						// Before settings "new finishing rate from" = old_finishing rate; on/after = current rate
						$invoice = $invoices[$p->invoice_id] ?? null;
						$useOldRate = $invoice && \Carbon\Carbon::parse($invoice->date)->lt($contractorFinishingNewRateFrom);
						if ($useOldRate) {
							$extraFinishing = 0;
							if (! empty($p->product->finshing_extra_price_id)) {
								$fep = \App\FinshingExtraPrice::find($p->product->finshing_extra_price_id);
								if ($fep) {
									$extraFinishing = (float) $fep->price;
								}
							}
							$fr = $finishRates[$p->product->finishing] ?? null;
							if ($fr && $fr->old_rate !== null) {
								$vol = ($p->product->height * $p->product->width * $p->product->depth) / 1000000;
								$fp = round(($fr->old_rate / 60) * $vol);
								$rem = $fp % 5;
								if ($rem < 3) { $fp -= $rem; } else { $fp += (5 - $rem); }
								$effectiveFrate = $fp + $extraFinishing;
							} else {
								$effectiveFrate = $p->product->finishing_price;
							}
						} else {
							$effectiveFrate = $p->product->finishing_price;
						}
					?>
					<form id='createContractorBill{{$p->product_id}}' method="POST" action="{{ url('/invoice/createContractorBill') }}">
						@csrf
						<tr>
							<td scope="col">{{$p->product->code}} - {{$p->product->name}}</td>
							<td scope="col"><input id="quant_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" name="quant[{{$p->invoice_id}}][{{$p->product_id}}]" value="{{$p->quantity}}" readonly /></td>
							<td scope="col"><input id="finishing_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" type="text" name="finishing[{{$p->invoice_id}}][{{$p->product_id}}]" value="{{$p->product->finishing}}" /></td>
							
                                                        <td scope="col"><input id="frate_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" type="number" name="frate[{{$p->invoice_id}}][{{$p->product_id}}]" /></td>
							<?php
$totalQty = 0;

foreach ($contractors as $cc) {
    if (isset($cbills[$p->invoice_id][$p->product_id][$cc->id])) {
        $totalQty += $cbills[$p->invoice_id][$p->product_id][$cc->id]->quantity;
    }
}
?>
                                                        @foreach($contractors as $c)
							<?php
							if (isset($cbills[$p->invoice_id][$p->product_id][$c->id])) {
								$bill           =       $cbills[$p->invoice_id][$p->product_id][$c->id];
								$quantity       =       $bill->quantity;
								$amount         =       $bill->amount;
								if ($totalQty == 0) {
									$frate  =   $effectiveFrate;
								} else {
									$frate  =   $bill->finishing_rate;
								}

								$finish     =   $bill->finishing;
							} else {
								$quantity       =       0;
								$amount         =       0;
								$frate          =       $effectiveFrate;
								$finish         =       $p->product->finishing;
							}
							if ($frate == 0) {
								$frate          =       $effectiveFrate;
							}
							if ($finish == "") {
								$finish         =       $p->product->finishing;
							}
							?>
							<script>
								$("#frate_" + <?php echo $p->invoice_id; ?> + "_" + <?php echo $p->product_id; ?>).val(<?php echo $frate; ?>);
								$("#finishing_" + <?php echo $p->invoice_id; ?> + "_" + <?php echo $p->product_id; ?>).val("<?php echo $finish; ?>");
							</script>
							<td class="td_{{$c->id}}" scope="col"><input max="{{$p->quantity}}" id="cquant_{{$p->invoice_id}}_{{$p->product_id}}_{{$c->id}}" class="form-control" type="number" name="cquant[{{$p->invoice_id}}][{{$p->product_id}}][{{$c->id}}]" value="{{$quantity}}" /></td>
							<td class="td_amount_{{$c->id}}" scope="col"><input id="camount_{{$p->invoice_id}}_{{$p->product_id}}_{{$c->id}}" class="form-control" type="number" name="camount[{{$p->invoice_id}}][{{$p->product_id}}][{{$c->id}}]" value="{{$amount}}" readonly /></td>
							@endforeach
							<?php
							if (isset($cbills[$p->invoice_id][$p->product_id][0])) {
								$bill           =       $cbills[$p->invoice_id][$p->product_id][0];
								$quantity       =       $bill->quantity;
							} else {
								$quantity       =       0;
							}
							?>
							<td scope="col"><input id="balance_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" type="number" name="balance[{{$p->invoice_id}}][{{$p->product_id}}]" readonly /></td>
							<td scope="col"><input id="total_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" type="number" name="total[{{$p->invoice_id}}][{{$p->product_id}}]" readonly /></td>
						</tr>
					</form>
					@endforeach
				</tbody>
			</table>
		</div>
		@endforeach
	</div>

</div>
<script type="text/javascript">
	$(function() {
		$(document).on("change", ".contractor_checkbox", function(e) {
			id = $(this).attr('id');
			if (this.checked) {
				$('.td_' + id).show();
				$('.td_amount_' + id).show();
				$('.th_' + id).show();
				$('.th_amount_' + id).show();
			} else {
				$('.td_' + id).hide();
				$('.td_amount_' + id).hide();
				$('.th_' + id).hide();
				$('.th_amount_' + id).hide();
			}
		});
	})
</script>
@endsection

@section('footer')
@endsection