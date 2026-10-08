@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2 align-items-center">
        <div class="col">
            <h2 class="mb-1">Supplier terms acceptance status</h2>
            <p class="text-muted mb-0">Shows whether each supplier has accepted the current supplier terms &amp; conditions.</p>
        </div>
        <div class="col-auto">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.supplier_terms.edit') }}">Edit terms</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.supplier_terms.acceptance_status') }}" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label for="q" class="mb-1">Search supplier</label>
                <input
                    type="text"
                    class="form-control"
                    id="q"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Name / short name / email"
                    autocomplete="off"
                >
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit">Search</button>
                <a class="btn btn-link" href="{{ route('admin.supplier_terms.acceptance_status') }}">Clear</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Supplier</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Accepted?</th>
                            <th>Accepted at</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $s)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $s->c_name ?: ($s->name ?: '—') }}</div>
                                    @if ($s->short_name)
                                        <div class="text-muted small">{{ $s->short_name }}</div>
                                    @endif
                                </td>
                                <td>{{ $s->type ?: '—' }}</td>
                                <td>{{ $s->email ?: '—' }}</td>
                                <td>
                                    @if ($s->terms_accepted)
                                        <span class="badge bg-success">Accepted</span>
                                    @else
                                        <span class="badge bg-secondary">Not accepted</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($s->terms_accepted_at)
                                        {{ $s->terms_accepted_at->format('d-m-Y h:i A') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No suppliers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if (method_exists($suppliers, 'links'))
            <div class="card-footer">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

