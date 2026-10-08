@extends('layouts.app')
@section('content')
    <style>
        .align-table {
            padding-left: 1.25rem;
            align-items: flex-end;
        }

        .align-table input {
            outline: none !important;
            box-shadow: none !important;
        }

        .labelFix {
            white-space: nowrap;
            margin: 0;
        }

        .dataTables_length {
            padding: 5px 1.25rem;
        }
    </style>
    <div class="row mx-3 my-2">
        <h2>Fulfillment Logs</h2>
        <div class="col">
            <div class="btn-group float-end" role="group" style="color: #fff; margin-left: 5px;">
                <a class=""
                    href="{{ url('/export/fulfillmentLogs?customer_name=' . $customer_name . '&customer_email=' . $customer_email . '&sku_id=' . $sku . '&order_id=' . $order_id) }}"><button
                        id="" type="button" class="btn btn-success">
                        Download
                    </button></a>&nbsp;

                <a href="javascript:window. history. back();"> <button id="" type="button"
                        class="btn btn-secondary">
                        Go Back
                    </button>
                </a>

            </div>
        </div>

    </div>
    <div class="card mb-3">
        <form method="GET" id="search_submit" action="{{ url('fulfillment/logs') }}">
            <div class="row mt-3 align-table">
                <div class="col-2">
                    <input type="text" class="form-control search_data" name="sku" placeholder="SKU"
                        value="{{ $sku }}">
                </div>
                <div class="col-2">
                    <input type="text" class="form-control search_data" name="order_id" placeholder="Order ID"
                        value="{{ $order_id }}">
                </div>
                <div class="col-2">
                    <input type="text" class="form-control search_data" name="customer_name" placeholder="Customer Name"
                        value="{{ $customer_name }}">
                </div>
                <div class="col-2">
                    <input type="text" class="form-control search_data" name="customer_email"
                        placeholder="Customer Email" value="{{ $customer_email }}">
                    <input type="hidden" class="form-control search_data" name="per_page" placeholder="Customer Email"
                        value="{{ $per_page }}">
                </div>

                <div class="col-2">
                    <input type="submit" class="form-control btn btn-primary"value="Search">
                </div>
                <div class="col-2">
                    <a href="{{ url('/fulfillment/logs') }}"> <button id="" type="button" class="btn btn-warning">
                            Clear Search
                        </button></a> &nbsp;
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-responsive">
                <div class="ajax_search">
                    <table class="table table-bordered" id="" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Customer Name</th>
                                <th>Customer Email</th>
                                <th>Quantity</th>
                                <th>Remaining Stock</th>
                                <th>OrderType</th>
                                <th>Type</th>
                                <th>Order ID</th>
                                <th>Time Stamp</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (isset($fulllogs) && count($fulllogs) > 0)
                                @foreach ($fulllogs as $key => $logs)
                                    <tr>
                                        <td>{{ $logs->sku }}</td>
                                        <td>{{ $logs->wp_customer_name }}</td>
                                        <td>{{ $logs->wp_customer_email }}</td>

                                        <td>{{ $logs->qty }}</td>
                                        <td>{{ $logs->remaining_stock }}</td>
                                        <td>{{ $logs->order_type }}</td>
                                        <td>{{ $logs->type }}</td>
                                        <td>{{ $logs->order_id }}</td>
                                        <td>{{ date('d M Y H:i:s', strtotime($logs->created_at . ' + 5 hours 30 minutes')) }}
                                        </td>
                                        <td>{{ $logs->remark }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="12" class="text-center">No data available</td>
                                </tr>
                            @endif

                        </tbody>
                    </table>
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                {{-- //////////////////// --}}
                                <div class="dataTables_length" id="dataTables_length">
                                    <label class="labelFix">Show
                                        <select name="dataTables_length" aria-controls="dataTables"
                                            style="width: fit-content;"
                                            class="form-select form-select-sm form-control form-control-sm"
                                            id="dataTables_page" onchange="changePageCount(this.value)">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        entries</label>
                                </div>
                            </div>
                            {{-- /////////////////// --}}
                            <div class="col-md-4">
                                <div class="" id="" role="status" aria-live="polite">Showing
                                    {{ count($fulllogs) > 0 ? ($countResult - $perPage == 0 ? 1 : $countResult - $perPage) : 0 }}
                                    to
                                    {{ COUNT($fulllogs) % $perPage == 0 ? $countResult : $count }}
                                    of
                                    {{ $count }} entries</div>
                            </div>
                            <div class="col-md-4">
                                {{ $fulllogs->appends(['customer_name' => $customer_name, 'customer_email' => $customer_email, 'sku' => $sku, 'order_id' => $order_id, 'per_page' => $per_page])->links() }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection

@section('footer')
    <script>
        $(document).ready(function() {
            $("#dataTables_page").val('{{ $per_page }}');
            if (!'{{ $per_page }}') {
                $("#dataTables_page").val('10');
            }

        });

        function changePageCount(value) {
            var urlData = "{{ url('fulfillment/logs?per_page=') }}";
            var customer_name = '';
            var customer_email = '';
            var order_id = '';
            var sku = '';
            if ('{{ $customer_name }}') {
                customer_name = "&customer_name=" + '{{ $customer_name }}'
            }
            if ('{{ $customer_email }}') {
                customer_email = "&customer_email=" + '{{ $customer_email }}'
            }
            if ('{{ $order_id }}') {
                order_id = "&order_id=" + '{{ $order_id }}'
            }
            if ('{{ $sku }}') {
                sku = "&sku=" + '{{ $sku }}'
            }
            var perPageLink = urlData + value + customer_name + customer_email + order_id + sku;
            window.location.href = perPageLink;
        }
    </script>
@endsection
