@extends('layouts.app')

@section('content')

    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
      <h2>Role Management</h2>
      <div class="col"><a class="btn btn-success float-end" style="color: #fff;" href="{{ route('role-management.index')}}"><i class="fa fa-arrow-left"></i> Go Back</a></div>
    </div>
      
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">  
                    <form action="{{ route('role-management.update',$role->id) }}" method="POST" data-parsley-validate>
                        @csrf
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Role Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $role->name }}" 
                            required  data-parsley-required-message="This field is required." readonly placeholder="Enter Role Name">
                            @error('name')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row mt-3" id="permission-div">
                            <div class="col-6">
                                @foreach($permission as $value)
                                    <label>
                                        <input type="checkbox" name="permission[]" value="{{ $value->id }}" class="name"
                                            {{ in_array($value->id, $rolePermissions) ? 'checked' : '' }}>
                                        {{ $value->name }}
                                    </label>
                                    <br/>
                                @endforeach
                            </div>
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
