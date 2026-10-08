@extends('layouts.app')

@section('content')

<div class="row mx-3 my-2">
  <h2>Temporary Products List</h2>

  @hasrole('admin')
  <div class="col">
    <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/temporaryProduct/create') }}">Add Temporary Product</a>
  </div>
  @endhasrole

</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th>Code</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Sub-Category</th>
            @hasrole('admin')
            <th>Options</th>
            @endhasrole
          </tr>
        </thead>
        <tbody>
          @if(isset($tempProducts)) @foreach($tempProducts as $key => $tempProduct)
          <tr>
            <td>{{$tempProduct->code}}</td>

            <td>
            @php
                $imageName = $tempProduct->imageURL; // directly the name like 'image1.jpg'
            @endphp

            @if(array_key_exists($imageName, $fileMap))
                <img src="{{ $fileMap[$imageName] }}" alt="Product Image" width="50px">
            @else
                <p>Image not found</p>
            @endif
        </td>

          

            <td>{{$tempProduct->name}}</td>
            <td>{{$tempProduct->category->name}}</td>
            <td>{{$tempProduct->subCategory->name}}</td>
            @hasrole('admin')
            <td>
              <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                <button class="btn btn-info" onclick="updateTempProduct('{{$tempProduct->id}}')">
                  <i class="fa fa-edit"></i>
                </button>
              </span>
            </td>
            @endhasrole
          </tr>
          @endforeach @endif
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('footer')
<script type="text/javascript">
  $(function() {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteTempProduct(id) {
    location.href = "{{ url('/temporaryProduct/delete') }}" + '/' + id;
  }

  function updateTempProduct(id) {
    location.href = "{{ url('/temporaryProduct/view') }}" + '/' + id;
  }
</script>
@endsection