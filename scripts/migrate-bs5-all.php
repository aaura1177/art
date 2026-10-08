<?php
/**
 * Single-pass BS5 migration for all Blade views (low process footprint).
 */
$root = realpath(__DIR__ . '/..');
$views = $root . '/stock/resources/views';

$cdnRemove = [
    '/^\s*<link[^>]+href="https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@[^"]+"[^>]*\/?>\s*$/m',
    '/^\s*<script[^>]+src="https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@[^"]+"[^>]*><\/script>\s*$/m',
    '/^\s*<script[^>]+src="https:\/\/code\.jquery\.com\/jquery[^"]+"[^>]*><\/script>\s*$/m',
    '/^\s*<link[^>]+href="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/bootstrap-select\/[^"]+"[^>]*>\s*$/m',
    '/^\s*<script[^>]+src="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/bootstrap-select\/[^"]+"[^>]*><\/script>\s*$/m',
    '/^\s*<script[^>]+src="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/moment\.js\/[^"]+"[^>]*><\/script>\s*$/m',
    '/^\s*<script[^>]+src="https:\/\/cdn\.ckeditor\.com\/ckeditor5\/[^"]+"[^>]*><\/script>\s*$/m',
    '/^\s*<link[^>]+href="https:\/\/cdn\.datatables\.net\/buttons\/[^"]+"[^>]*>\s*$/m',
    '/^\s*<script[^>]+src="https:\/\/cdn\.datatables\.net\/buttons\/[^"]+"[^>]*><\/script>\s*$/m',
    '/^\s*<script[^>]+src="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/jszip\/[^"]+"[^>]*><\/script>\s*$/m',
    '/^.*https:\/\/cdn\.jsdelivr\.net.*\R/m',
];

$cdnReplace = [
    ['/<link[^>]+href="https:\/\/cdn\.jsdelivr\.net\/npm\/select2@[^"]+"[^>]*\/?>\s*$/m', "@push('styles')\n<link href=\"{{ asset('ui-vendor/select2/css/select2.min.css') }}\" rel=\"stylesheet\">\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.jsdelivr\.net\/npm\/select2@[^"]+"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/select2/js/select2.min.js') }}\"></script>\n@endpush"],
    ['/<link rel="stylesheet" href="https:\/\/cdn\.jsdelivr\.net\/npm\/parsleyjs@[^"]+"[^>]*>\s*$/m', "@push('styles')\n<link href=\"{{ asset('ui-vendor/parsley/css/parsley.css') }}\" rel=\"stylesheet\">\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.jsdelivr\.net\/npm\/parsleyjs@[^"]+"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/parsley/js/parsley.min.js') }}\"></script>\n@endpush"],
    ['/<link rel="stylesheet" href="https:\/\/cdn\.jsdelivr\.net\/npm\/flatpickr\/dist\/flatpickr\.min\.css"[^>]*>\s*$/m', "@push('styles')\n<link href=\"{{ asset('ui-vendor/flatpickr/flatpickr.min.css') }}\" rel=\"stylesheet\">\n@endpush"],
    ['/<link rel="stylesheet" href="https:\/\/cdn\.jsdelivr\.net\/npm\/flatpickr\/dist\/plugins\/monthSelect\/style\.css"[^>]*>\s*$/m', "@push('styles')\n<link href=\"{{ asset('ui-vendor/flatpickr/plugins/monthSelect/style.css') }}\" rel=\"stylesheet\">\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.jsdelivr\.net\/npm\/flatpickr\/dist\/plugins\/monthSelect\/index\.js"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/flatpickr/plugins/monthSelect/index.js') }}\"></script>\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.jsdelivr\.net\/npm\/flatpickr"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/flatpickr/flatpickr.min.js') }}\"></script>\n@endpush"],
    ['/<link rel="stylesheet" href="https:\/\/code\.jquery\.com\/ui\/[^"]+\/themes\/base\/jquery-ui\.css"[^>]*>\s*$/m', "@push('styles')\n<link href=\"{{ asset('ui-vendor/jquery-ui/css/jquery-ui.min.css') }}\" rel=\"stylesheet\">\n@endpush"],
    ['/<script[^>]+src="https:\/\/code\.jquery\.com\/ui\/[^"]+\/jquery-ui\.js"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/jquery-ui/js/jquery-ui.min.js') }}\"></script>\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.ckeditor\.com\/ckeditor5\/[^"]+\/classic\/ckeditor\.js"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/ckeditor/ckeditor.js') }}\"></script>\n@endpush"],
    ['/<link[^>]+href="https:\/\/cdn\.datatables\.net\/buttons\/[^"]+\/css\/buttons\.dataTables\.min\.css"[^>]*>\s*$/m', "@push('styles')\n<link href=\"{{ asset('ui-vendor/datatables/buttons/buttons.dataTables.min.css') }}\" rel=\"stylesheet\">\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.datatables\.net\/buttons\/[^"]+\/js\/dataTables\.buttons\.min\.js"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/datatables/buttons/dataTables.buttons.min.js') }}\"></script>\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.datatables\.net\/buttons\/[^"]+\/js\/buttons\.html5\.min\.js"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/datatables/buttons/buttons.html5.min.js') }}\"></script>\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.datatables\.net\/buttons\/[^"]+\/js\/buttons\.print\.min\.js"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/datatables/buttons/buttons.print.min.js') }}\"></script>\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdn\.datatables\.net\/buttons\/[^"]+\/js\/buttons\.colVis\.min\.js"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/datatables/buttons/buttons.colVis.min.js') }}\"></script>\n@endpush"],
    ['/<script[^>]+src="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/jszip\/[^"]+"[^>]*><\/script>\s*$/m', "@push('scripts')\n<script src=\"{{ asset('ui-vendor/datatables/buttons/jszip.min.js') }}\"></script>\n@endpush"],
];

$subs = [
    'data-toggle=' => 'data-bs-toggle=', 'data-target=' => 'data-bs-target=',
    'data-dismiss=' => 'data-bs-dismiss=', 'data-placement=' => 'data-bs-placement=',
    'navbar-brand mr-1' => 'navbar-brand me-1', 'navbar-nav ml-auto mr-0' => 'navbar-nav ms-auto me-0',
    'float-right' => 'float-end', 'float-left' => 'float-start', 'text-right' => 'text-end', 'text-left' => 'text-start',
    'font-weight-bold' => 'fw-bold', 'font-italic' => 'fst-italic', 'badge-pill' => 'rounded-pill', 'custom-select' => 'form-select',
    'ml-1' => 'ms-1', 'ml-2' => 'ms-2', 'ml-3' => 'ms-3', 'ml-4' => 'ms-4', 'ml-5' => 'ms-5',
    'mr-1' => 'me-1', 'mr-2' => 'me-2', 'mr-3' => 'me-3', 'mr-4' => 'me-4', 'mr-5' => 'me-5',
    'pl-1' => 'ps-1', 'pl-2' => 'ps-2', 'pl-3' => 'ps-3', 'pr-1' => 'pe-1', 'pr-2' => 'pe-2', 'pr-3' => 'pe-3',
];

$closePatterns = [
    '/<button type="button" class="close" data-bs-dismiss="modal"(?: aria-label="Close")?>\s*(?:<span aria-hidden="true">[×&times;]<\/span>|<i aria-hidden="true" class="la la-remove"><\/i>)\s*<\/button>/s'
        => '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>',
    '/<button type="submit" class="close" data-bs-dismiss="modal"(?: aria-label="Close")?>\s*(?:<span aria-hidden="true">[×&times;]<\/span>)?\s*<\/button>/s'
        => '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>',
    '/<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;<\/button>/'
        => '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>',
    '/<button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">\s*<span aria-hidden="true">&times;<\/span>\s*<\/button>/s'
        => '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
    '/<button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">\s*<span aria-hidden="true">[×&times;]<\/span>\s*<\/button>/s'
        => '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>',
];

$changed = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views)) as $file) {
    if (!str_ends_with($file->getFilename(), '.blade.php')) continue;
    $text = file_get_contents($file->getPathname());
    $orig = $text;
    foreach ($cdnRemove as $p) $text = preg_replace($p, '', $text);
    foreach ($cdnReplace as [$p, $r]) $text = preg_replace($p, $r, $text);
    foreach ($subs as $o => $n) $text = str_replace($o, $n, $text);
    foreach ($closePatterns as $p => $r) $text = preg_replace($p, $r, $text);
    if ($text !== $orig) {
        file_put_contents($file->getPathname(), $text);
        $changed++;
    }
}
echo "Migrated {$changed} blade files\n";
