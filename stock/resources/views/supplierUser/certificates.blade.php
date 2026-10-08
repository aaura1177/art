@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Certificates</h2>
        <div class="col">
            <button type="button" class="btn btn-success float-end" data-bs-toggle="modal" data-bs-target="#addCertificateModal">
                Upload Certificate
            </button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Certificate No</th>
                            <th>File</th>
                            <th style="min-width:120px;">Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($certificates))
                            @foreach($certificates as $certificate)
                                <tr>
                                    <td>{{$certificate->id}}</td>
                                    <td>{{$certificate->name}}</td>
                                    @php
                                        $imageName = $certificate->file_path;
                                    @endphp

                                    @if (array_key_exists($imageName, $fileMap))
                                        <td><a href="{{ $fileMap[$imageName] }}" target="_blank">View Certificate</a></td>
                                    @else
                                        <td>Image not found</td>
                                    @endif
                                    <td>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editCertificateModal{{ $certificate->id }}">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger" onclick="deleteCertificate({{$certificate->id}})"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editCertificateModal{{ $certificate->id }}" tabindex="-1" role="dialog" aria-labelledby="editCertificateModalLabel{{ $certificate->id }}" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editCertificateModalLabel{{ $certificate->id }}">Edit Certificate</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="{{ url('/certificates/' . $certificate->id) }}" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="name">Certificate No:</label>
                                                        <input type="text" class="form-control" id="name" name="name" value="{{ $certificate->name }}" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="certificate">Upload New Certificate (Optional):</label>
                                                        <input type="file" class="form-control-file" id="certificate" name="certificate">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCertificateModal" tabindex="-1" role="dialog" aria-labelledby="addCertificateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCertificateModalLabel">Upload Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ url('/certificates') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Certificate No:</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="certificate">Upload Certificate:</label>
                            <input type="file" class="form-control-file" id="certificate" name="certificate" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });

        function deleteCertificate(id) {
            if (confirm("Are you sure you want to delete this certificate?")) {
                $.ajax({
                    url: "{{ url('/certificates') }}" + '/' + id,
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        alert(response.message);
                        location.reload();
                    },
                    error: function(xhr) {
                        alert('Error deleting certificate.');
                    }
                });
            }
        }
    </script>
@endsection

      