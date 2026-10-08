@extends('layouts.modal')

<body>

  <!-- next page -->
  <header>
      <h1 class="text-center">Products</h1>
  </header>
  <div class="container">

    <!-- Header second row -->
    <div class="row mt-3">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
				<thead>
					<tr>
						<th>Code</th>
						<th>Image</th>
						<th>Name</th>
						<th>Category</th>
						<th>SubCategory</th>
						<th>Quantity</th>
						<th>Location</th>
					</tr>
				</thead>
				<tbody>
					@if(isset($products)) @foreach($products as $key => $product)
					<tr>
						<td>{{$product->code}}</td>
						<td><img width="100px" src="{{ asset('uploads/') }}/image.php?image=/allproducts/{{ $product->code . '/' . $product->code .  '-1.jpg' }}&height=100px&width=100px" alt="No Image available" /></td>
						<td>{{$product->name}}</td>
						<td>{{$product->category->name}}</td>
						<td>{{$product->subCategory->name}}</td>
						<td>{{$product->quantity}}</td>
						<td>{{$product->location}}</td>
					<tr>
					@endforeach @endif
				</tbody>
			</table>
		</div>
	</div>
   </div>
</body>
<script type="text/javascript">
  document.ready = window.print();
</script>