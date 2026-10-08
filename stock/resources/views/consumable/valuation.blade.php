@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Consumable Valuation</h2>

        @hasrole('admin')
            <div class="col">
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#mydateModal" href="{{ url('/consumable/valuationPdfView') }}" onclick="updateForm(this)">Download PDF</a>
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#mydateModal" href="{{ url('/consumable/exportValuationExcel') }}" onclick="updateForm(this)">Download CSV</a>
            </div>
        @endhasrole

        @hasrole('factory')
            <div class="col">
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#mydateModal" href="{{ url('/consumable/valuationPdfView') }}" onclick="updateForm(this)">Download PDF</a>
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#mydateModal" href="{{ url('/consumable/exportValuationExcel') }}" onclick="updateForm(this)">Download CSV</a>
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
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>Total Value</th>
                            <th>Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($valuationRows as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['quantity'] }}</td>
                                <td>{{ $row['rate'] }}</td>
                                <td>{{ $row['total_value'] }}</td>
                                <td>
                                    @if (!empty($row['age_lines']))
                                        {!! implode('<br>', $row['age_lines']) !!}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mydateModal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Enter Date</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="date_filter" method="POST" action="{{ url('/consumable/exportValuationExcel') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6">
                                <input min="2020-10-15" class="form-control" type="date" name="from" id="fsd" value="" />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Download</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <script type="text/javascript">
        function updateForm(obj) {
            const url = $(obj).attr('href');
            $('#date_filter').attr('action', url);
        }
    </script>
@endsection
