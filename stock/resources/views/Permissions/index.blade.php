@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
      <h2>Permission Management</h2>
      <div class="col">
        <a class="btn btn-primary float-end" style="color: #fff;" href="{{ route('permission-management.create')}}">Add New</a>
        </div>
    </div>
      
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Permission List</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>Permission Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $record->name }}</td>
                            <td>
                                <!-- <a href="{{ route('permission-management.edit', $record->id) }}" class="btn btn-primary btn-sm">Edit</a> -->
                                <form action="{{ route('permission-management.delete', $record->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if($records->isEmpty())
                        <tr>
                            <td colspan="4" class="text-center">No records found</td>
                        </tr>
                    @endif
                </tbody>
               
            </table>
           
        </div>
    </div>
@endsection
@section('footer')

@endsection
