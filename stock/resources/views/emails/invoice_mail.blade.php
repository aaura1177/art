<p>Hello Good morning!</p>
<?php echo 'Please see the link below for the commercial invoice number <b>'. $invoice->invoiceno .'</b> dated '. date('d M Y',strtotime($invoice->date)) .' for shipment number '. $invoice->buyerorderno; ?><br><br>
<a href="https://stock.artisanadmin.net/invoice/printinv/{{$invoice->id}}" target="_blank">Invoice</a><br><br>
<a href="https://stock.artisanadmin.net/invoice/modalpackingsheet/{{$invoice->id}}" target="_blank">Packing List</a><br><br>
Thanks & kind Regards,<br>Anu Jain<br>General Manager