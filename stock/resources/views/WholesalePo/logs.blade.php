@extends('layouts.app')

@section('content')
<div class="row mx-1 my-2">
    <div class="col"><h2>Activity log — {{ $shipment->buyer_orderno }}</h2></div>
    <div class="col">
        <a class="btn btn-secondary float-end" style="color:#fff;" href="{{ route('wholesale-po.show', $shipment->id) }}"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>When</th>
                    <th>Who</th>
                    <th>Action</th>
                    <th>Message</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->user_name ?: ('User#'.$log->user_id) }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->message }}</td>
                        <td>{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No log entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $logs->links() }}
    </div>
</div>
@endsection
