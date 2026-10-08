@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h2 class="mb-0">Sustainability Stage-0</h2>
        </div>
    </div>

    <div class="card mb-3 ">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tree Plantation and Carbon Sequestration Impact</h5>
        </div>
        <div class="card-body">
            <!-- Filter For Records -->
            <div class="row align-items-end mb-3 pt-3">
                <div class="col-md-8">
                    <form class="row g-2">
                        <!-- <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Keyword" class="form-control"/>
                        </div> -->

                        <div class="col-md-3">
                            <label for="inputtypelabel" class="form-label">From</label>
                            <div class="input-group">
                                <input type="date" name="date-from" id="date-from" value="{{ request('date-from') }}" class="form-control ui-autocomplete-input"/>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="inputtypelabel" class="form-label">To</label>
                            <div class="input-group">
                                <input type="date" name="date-to" id="date-to" value="{{ request('date-to') }}" class="form-control ui-autocomplete-input"/>
                            </div>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-info me-2"><i class="fa fa-filter"></i> Filter</button>
                            <a href="{{ route('sustainability.stage0.index') }}" class="btn btn-danger me-2">
                            <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                        </div>
                    </form>
                </div>

                @if(!empty(@$records[0]))
                    <div class="col-md-4 d-flex justify-content-end">
                        <form action="{{ route('sustainability.stage0.export') }}" method="post" class="d-flex">
                            @csrf
                            <!-- <input type="hidden" name="search" value="{{ request('search') }}"> -->
                            <input type="hidden" name="date-from" value="{{ request('date-from') }}">
                            <input type="hidden" name="date-to" value="{{ request('date-to') }}">
                            <button type="submit" class="btn btn-success"> <i class="fa fa-file-excel"></i> Export</button>
                        </form>
                    </div>
                @endif
            </div>
            <!-- End Filter  Records -->
           
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>Month-Year</th>
                        <th>Total Qty. Delivered By Containers</th>
                        <th>Total Weight Delivered In Containers</th>
                        <th>Carbon Sequestration</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ date("M - Y", strtotime($record->month_year)) }}</td>
                            <td>{{ $record->total_qty_delivered_by_containers }}</td>
                            <td>{{ $record->total_weight_delivered_in_containers }}</td>
                            <td>{{ $record->carbon_sequestration }}</td>
                            <td>
                                <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#viewModal" onclick="getDetails(`{{ date('M - Y', strtotime($record->month_year)) }}`,`{{ date('Y-m', strtotime($record->month_year)) }}`)">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    @if($records->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center">No records found</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <td colspan="2" class="text-center">Total</td>
                        <td>{{ $records->sum('total_qty_delivered_by_containers') }}</td>
                        <td>{{ $records->sum('total_weight_delivered_in_containers') }}</td>
                        <td>{{ $records->sum('carbon_sequestration') }}</td>
                        <td></td>
                    </tr>
                    <!-- <tr>
                        <td colspan="4" class="text-center">Avg. Total</td>
                        <td>{{ round($records->sum('carbon_sequestration')/12, 5) }}</td>
                    </tr> -->
                </tfoot>
            </table>
            <div class="d-flex justify-content-center mt-3">
                {{ $records->links() }}
            </div>

        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">
                        Detailed Summary of Containers for <span id="selectedMonth" class="text-danger"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="detailViewTable" class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Sr.No</th>
                                <th>Country</th>
                                <th>Invoice Number</th>
                                <th>Container Number</th>
                                <th>Port</th>
                                <th>Total Quantities</th>
                                <th>Total Weight</th>
                                <th>Total Gross Weight</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="detailViewTableBody">
                            <!-- Loader -->
                            <div id="loader" style="display: none; text-align: center; padding: 20px;">
                                <span>Loading...</span>
                            </div>
                        </tbody>
                        <tfoot id="tfoot" class="table-danger" style="display:none">

                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('footer')

<script>
function getDetails(date, date2) {
    $("#selectedMonth").html(date);

    let csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ route('sustainability.stage0.getContainerDetails') }}",
        type: 'POST',
        data: {
            _token: csrfToken,
            date: date2
        },
        beforeSend: function() {
            $('#loader').show(); // Show loader
            $('#detailViewTableBody').empty(); // Optionally clear old data
            $('#tfoot').empty(); // Optionally clear old data
        },
        success: function(response) {
            if (response.status === 1) {
                let jsonResponse = response.data; // Already parsed

                let html = "";
                let counter = 0;
                let totalDeliveredQty = totalDeliveredWt = totalDeliveredGrossWt = 0;
                jsonResponse.forEach(function(res) {
                    counter++;
                    totalDeliveredQty = totalDeliveredQty + res.totalquantity;
                    totalDeliveredWt = totalDeliveredWt + res.totalwt;
                    totalDeliveredGrossWt = totalDeliveredGrossWt + res.totalgrosswt;
                    html += `<tr>
                                <td>${counter}</td>
                                <td>${res.country}</td>
                                <td>${res.invoiceno}</td>
                                <td>${res.containerno}</td>
                                <td>${res.discharge}</td>
                                <td>${res.totalquantity}</td>
                                <td>${res.totalwt}</td>
                                <td>${res.totalgrosswt}</td>
                                <td>${res.date}</td>
                             </tr>`;
                });

                var footer = `tr>
                                <td colspan='5' class='text-end fw-bold'> Total</td>
                                <td class='fw-bold'>${totalDeliveredQty}</td>
                                <td class='fw-bold'>${totalDeliveredWt}</td>
                                <td class='fw-bold'>${totalDeliveredGrossWt}</td>
                                <td></td>
                            </tr>`;
                $("#tfoot").html(footer);
                $("#detailViewTableBody").html(html); // replace with actual tbody ID
            } else {
                console.error("Server responded with status 0:", response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", error);
        },
        complete: function() {
            $('#loader').hide(); // Hide loader when done
            $('#tfoot').css("display","contents"); 
        }
    });
}


</script>
@endsection