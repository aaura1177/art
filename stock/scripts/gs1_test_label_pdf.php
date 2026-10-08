<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Gs1\Models\Gs1Catalog;
use App\Gs1\Models\Gs1Country;
use App\Gs1\Services\LabelChromePdfService;
use App\Gs1\Services\LabelService;

$c = Gs1Catalog::query()->where('gtin', '8904350034277')->first()
    ?: Gs1Catalog::query()->whereNotNull('erp_product_id')->where('barcode_status', 'ok')->first()
    ?: Gs1Catalog::query()->whereNotNull('erp_product_id')->first();
if (!$c) {
    fwrite(STDERR, "no linked catalog\n");
    exit(1);
}
$country = 'UK';
if (!Gs1Country::query()->where('code', $country)->where('active', true)->exists()) {
    $country = Gs1Country::query()->where('active', true)->orderBy('sort_order')->value('code') ?: 'CA';
}
echo "gtin={$c->gtin} sku={$c->erp_sku} country={$country}\n";

$payload = app(LabelService::class)->buildPdfPayload($c, $country, null, 1);
echo 'pages=' . count($payload['pages']) . ' paper=' . json_encode($payload['paper']) . "\n";
echo 'icons recycled=' . ($payload['icons']['recycled'] ? 'yes' : 'no')
    . ' seal=' . ($payload['icons']['seal'] ? 'yes' : 'no') . "\n";
$p0 = $payload['pages'][0];
echo 'lang=' . $p0['language_code']
    . ' eanLbl=' . ($p0['t']['labels']['ean'] ?? '')
    . ' size=' . ($p0['d']['size_text'] ?? '') . "\n";

$bytes = app(LabelChromePdfService::class)->render($payload);
$out = storage_path('app/gs1-label-test.pdf');
file_put_contents($out, $bytes);
$pub = '/home/u271258263/domains/auraprojects.store/public_html/service/gs1-label-UK-8904350034277-3.pdf';
@copy($out, $pub);
echo "pdf={$out} bytes=" . strlen($bytes) . "\n";
echo "copied={$pub} bytes=" . (@filesize($pub) ?: 0) . "\n";
