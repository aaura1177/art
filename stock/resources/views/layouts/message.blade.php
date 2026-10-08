@if(Session::has('danger') || Session::has('success'))
	<div class="alert alert-dismissible fade show alert-{{ Session::has('danger') ? 'danger' : 'success' }}" role="alert">
		{{ Session::has('danger') ? Session::get('danger') : Session::get('success') }}
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>
@endif

<!-- @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif -->

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        
        @if(is_array(session('error')))
            <ul class="mb-0">
                @foreach(session('error') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        @else
            {{ session('error') }}
        @endif

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('import_report_download_url'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <a href="{{ session('import_report_download_url') }}" class="btn btn-sm btn-primary">
            Download import-result-report.csv
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
