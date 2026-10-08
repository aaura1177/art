<p>Company Interest -</p>
<?php
$product_type = '';
if(isset($request->product_type) && !empty($request->product_type)){
    $product_type = implode(",",$request->product_type);
}
?>
<p><strong>Company Name:</strong> {{ $request->company_name }}</p>
<p><strong>Establishment date:</strong> {{ $request->establishment_date }}</p>
<p><strong>Contact name:</strong>  {{ $request->contact_name }}</p>
<p><strong>Email:</strong> {{ $request->email }}</p>
<p><strong>Phone:</strong> {{ $request->phone }}</p>
<p><strong>Website link:</strong> {{ $request->website_link }}</p>
<p><strong>Location:</strong> {{ $request->location }}</p>
<p><strong>Work with companies:</strong> {{ $request->work_with_companies_name }}</p>
<p><strong>Product type:</strong> {{ $product_type }}</p>
