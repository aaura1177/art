@php
    $embedded = !empty($embedded);
    $showHeader = !$embedded && empty($hideDocumentHeader);
@endphp
<div class="supplier-terms-document {{ $embedded ? 'supplier-terms-document--embedded' : 'card shadow-sm' }}">
    @if ($showHeader)
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="mb-0 pe-3">{{ $termsTitle ?? ($terms && $terms->title ? $terms->title : 'Supplier terms & conditions') }}</h1>
            @if (!empty($showPdfDownload) && !empty($bodyHtml))
                <a href="{{ route('supplier.terms.pdf') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
            @endif
        </div>
    @endif
    <div class="{{ $showHeader ? 'card-body' : '' }}">
        @if (!empty($bodyHtml))
            <div class="terms-rendered">
                {!! $bodyHtml !!}
            </div>
        @else
            <p class="text-muted mb-0">Terms have not been published yet. Please check back later.</p>
        @endif
    </div>
</div>
