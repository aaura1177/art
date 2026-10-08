@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
      <h2>Role Management</h2>
      <div class="col">
        <a class="btn btn-primary float-end" style="color: #fff;" href="{{ route('role-management.create')}}">Add New</a>
        <a class="btn btn-success float-end me-2" style="color: #fff;" href="{{ route('permission-management.index')}}">Permission Management</a>
    </div>
    </div>
      
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Roles List</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>Role Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $key => $role)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $role->name }}</td>
                            <td>
                                <a href="{{ route('role-management.edit', $role->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                
                                <form action="{{ route('role-management.delete', $role->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if($roles->isEmpty())
                        <tr>
                            <td colspan="3" class="text-center">No records found</td>
                        </tr>
                    @endif
                </tbody>
               
            </table>
           
        </div>
    </div>
@endsection
@section('footer')

@endsection
