@extends('layouts.app')

@section('content')

    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
      <h2>Permission Management</h2>
      <div class="col"><a class="btn btn-success float-end" style="color: #fff;" href="{{ route('permission-management.index')}}"><i class="fa fa-arrow-left"></i> Go Back</a></div>
    </div>
      
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">  
                    <form action="{{ route('permission-management.store') }}" method="POST" data-parsley-validate>
                        @csrf
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Permission Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" 
                            required  data-parsley-required-message="This field is required." placehodler="Enter Module Name">
                            @error('name')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                      
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('footer')



@endsection
