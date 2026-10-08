@extends('layouts.app')

@section('content')
@php use App\Support\CourierSnapshotPresenter as Present; @endphp

<div class="mx-3 my-3">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h2 class="mb-1">Courier change history</h2>
            <p class="text-muted mb-0">Pick a courier to see when it was changed and what was updated.</p>
        </div>
        <div class="col-auto">
            <a class="btn btn-secondary" href="{{ url('/courier') }}">← Back to couriers</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered mb-0" id="dataTables" width="100%">
                <thead>
                    <tr>
                        <th>Courier</th>
                        <th>Country</th>
                        <th>Last changed</th>
                        <th style="width:140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matrix as $row)
                        @php $c = $row['courier']; @endphp
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->country }}</td>
                            <td>{{ Present::friendlyWhen($row['last_snapshot_at'] ?? null) }}</td>
                            <td>
                                <a class="btn btn-sm btn-primary" href="{{ url('/courier/history/'.$c->id) }}">
                                    View history
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No couriers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
