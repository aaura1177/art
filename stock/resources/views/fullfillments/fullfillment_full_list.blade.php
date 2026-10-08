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
        <h2>Fulfillment List</h2>
        <div class="col">
            <div class="btn-group float-end" role="group" style="color: #fff; margin-left: 5px;">
                <a class=""
                    href="{{ url('export-fulfillment?sku_id=' . $sku_name . '&customer_name=' . $customer_name . '&customer_email=' . $customer_email) }}">
                    <button id="" type="button" class="btn btn-success" aria-expanded="false">
                        Download
                    </button></a>&nbsp;
                <a href="javascript:window.history.back();"> <button id="" type="button"
                        class="btn btn-secondary">
                        Go Back
                    </button>
                </a>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <form method="GET" id="search_submit" action="{{ url('fulfillment/list') }}">

            <div class="row mt-3 align-table">
                <div class="col-2">
                    <input type="text" class="form-control search_data" name="sku" placeholder="SKU"
                        value="{{ $sku_name }}">
                </div>
                <div class="col-2">
                    <input type="text" class="form-control search_data" name="customer_name" placeholder="Customer Name"
                        value="{{ $customer_name }}">
                </div>

                <div class="col-2">
                    <input type="text" class="form-control search_data" name="customer_email"
                        placeholder="Customer Email" value="{{ $customer_email }}">
                    <input type="hidden" class="form-control search_data" name="per_page" value="{{ $per_page }}">
                </div>

                <div class="col-2">
                    <input type="submit" class="form-control btn btn-primary"value="Search">
                </div>
                <div class="col-2">
                    <a href="{{ url('/fulfillment/list') }}"> <button id="" type="button" class="btn btn-warning">
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
                                <th>Sku</th>
                                <th>Customer Name</th>
                                <th>Customer Email</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (isset($data) && count($data) > 0)
                                @foreach ($data as $key => $datas)
                                    <tr>
                                        <td>{{ $datas->sku }}</td>
                                        <td>{{ $datas->wp_customer_name }}</td>
                                        <td> <a
                                                href="{{ url('fulfillment/list?customer_email=' . $datas->wp_customer_email) }}">
                                                {{ $datas->wp_customer_email }}</a></td>
                                        <td>{{ $datas->qty }}</td>
                                        <td><span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Fulfillment Logs">
                                                <a class="btn btn-primary"
                                                    href="{{ url('fulfillment/logs?sku=' . $datas->sku . '&customer_email=' . $datas->wp_customer_email) }}">
                                                    <i class="fa fa-history"></i>
                                                </a>
                                            </span></td>
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
                                    {{ count($data) > 0 ? ($countResult - $perPage == 0 ? 1 : $countResult - $perPage) : 0 }}
                                    to
                                    {{ COUNT($data) % $perPage == 0 ? $countResult : $count }}
                                    of
                                    {{ $count }} entries</div>
                            </div>
                            {{-- /////////////////// --}}
                            <div class="col-md-4">
                                {{ $data->appends(['customer_name' => $customer_name, 'customer_email' => $customer_email, 'sku' => $sku_name, 'per_page' => $per_page])->links() }}
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
            var urlData = "{{ url('fulfillment/list?per_page=') }}";
            var customer_name = '';
            var customer_email = '';
            var sku = '';
            if ('{{ $customer_name }}') {
                customer_name = "&customer_name=" + '{{ $customer_name }}'
            }
            if ('{{ $customer_email }}') {
                customer_email = "&customer_email=" + '{{ $customer_email }}'
            }
            if ('{{ $sku_name }}') {
                sku = "&sku=" + '{{ $sku_name }}'
            }
            var perPageLink = urlData + value + customer_name + customer_email + sku;
            window.location.href = perPageLink;
        }
    </script>
@endsection
