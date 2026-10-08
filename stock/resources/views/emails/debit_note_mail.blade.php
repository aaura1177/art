<p>A Debit Note has been created.</p>
<p>Debit Note Details - </p>
<p><strong>Product Name:</strong> {{$request['product_name']}}</p>
<p><strong>Supplier Name:</strong> {{$request['supplier_name']}}</p>
<p><strong>Supplier Invoice:</strong> {{$request['supplier_inv_no']}}</p>
<?php if($request['rejectRepair']->status == 1){
    $status = 'Reject';
    }elseif($request['rejectRepair']->status == 2){
        $status = 'Repair'; 
    }elseif($request['rejectRepair']->status == 0){
        $status = 'Complete';
    }
 ?>
<p><strong>Status:</strong> {{$status}}</p>
<p><strong>Remarks:</strong> {{$request['rejectRepair']->remarks}}</p>
