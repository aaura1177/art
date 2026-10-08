@extends('layouts.app')

@section('content')
@include('supplier_terms._styles')

<div class="container-fluid py-4 px-3 px-md-4">
    @include('supplier_terms._document', [
        'terms' => $terms,
        'bodyHtml' => $bodyHtml,
        'showPdfDownload' => true,
    ])
</div>
@endsection
