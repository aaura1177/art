@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2 align-items-center">
        <div class="col">
            <h2 class="mb-1">Supplier terms &amp; conditions</h2>
            <p class="text-muted mb-0">Single global document (one record). Shown on the supplier dashboard and via the supplier API.</p>
        </div>
        <div class="col-auto">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.supplier_terms.view') }}">Preview (same as suppliers)</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.supplier_terms.update') }}" id="supplier-terms-form">
        @csrf
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $terms->title) }}" maxlength="255">
            @error('title')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        <div class="form-group">
            <label for="body">Terms body</label>
            <p class="small text-muted">Use the rich-text editor for headings, lists, tables, and horizontal rules. Content is saved as HTML and cleaned on save. If you paste plain text only (no paragraphs from the editor), you can still use section lines with only <strong>⸻</strong> or <strong>---</strong> between blocks when not using HTML.</p>
            <textarea class="form-control" id="body" name="body" rows="16">{{ old('body', $terms->body) }}</textarea>
            @error('body')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>

    @if ($terms->updated_at)
        <p class="text-muted mt-3 small">Last updated {{ $terms->updated_at->format('Y-m-d H:i') }}
            @if ($terms->updatedBy)
                by {{ $terms->updatedBy->firstname }} {{ $terms->updatedBy->lastname }}
            @endif
        </p>
    @endif
</div>

<script src="{{ asset('ui-vendor/ckeditor/ckeditor.js') }}"></script>
<script>
(function () {
    var el = document.querySelector('#body');
    if (!el || typeof ClassicEditor === 'undefined') {
        return;
    }

    var toolbar = [
        'heading', '|',
        'bold', 'italic', 'underline', '|',
        'bulletedList', 'numberedList', '|',
        'blockQuote', 'horizontalLine', 'insertTable', '|',
        'undo', 'redo'
    ];

    ClassicEditor.create(el, {
        toolbar: toolbar,
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
            ]
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        }
    }).then(function (editor) {
        window.supplierTermsEditor = editor;
        var form = document.getElementById('supplier-terms-form');
        if (form) {
            form.addEventListener('submit', function () {
                editor.updateSourceElement();
            });
        }
    }).catch(function (err) {
        console.error(err);
    });
})();
</script>
@endsection
