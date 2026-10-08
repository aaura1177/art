<?php

$ref = '/tmp/in308-p1-600.png';
$outSeal = '/home/u271258263/domains/auraprojects.store/public_html/service/images/gs1/labels/seal.png';
$outPublic = '/home/u271258263/domains/auraprojects.store/public_html/service/stock/public/gs1/labels/seal.png';
$preview = '/tmp/seal-preview.png';

$im = imagecreatefrompng($ref);
$w = imagesx($im);
$h = imagesy($im);

$minx = $w;
$miny = $h;
$maxx = 0;
$maxy = 0;
$n = 0;
$step = 3;
for ($y = (int) ($h * 0.74); $y < $h - 40; $y += $step) {
    for ($x = (int) ($w * 0.74); $x < $w - 20; $x += $step) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 255;
        $g = ($rgb >> 8) & 255;
        $b = $rgb & 255;
        // dark green ring / tree
        if ($g > 35 && $g < 150 && $r < $g && $b < $g && ($g - $r) > 12) {
            $minx = min($minx, $x);
            $miny = min($miny, $y);
            $maxx = max($maxx, $x);
            $maxy = max($maxy, $y);
            $n++;
        }
    }
}

echo "green {$minx},{$miny}-{$maxx},{$maxy} n={$n}\n";
$cx = (int) round(($minx + $maxx) / 2);
// Outer ring bottom + left/right give diameter; center sits above bottom.
$diam = max($maxx - $minx, $maxy - $miny);
$rad = (int) round($diam / 2) + 24;
$cy = (int) round($maxy - ($maxx - $minx) / 2);
$side = $rad * 2;
$x0 = max(0, $cx - $rad);
$y0 = max(0, $cy - $rad);
if ($x0 + $side > $w) {
    $side = $w - $x0;
}
if ($y0 + $side > $h) {
    $side = min($side, $h - $y0);
}
// Force square
$side = min($side, $w - $x0, $h - $y0);

$crop = imagecrop($im, ['x' => $x0, 'y' => $y0, 'width' => $side, 'height' => $side]);
imagepng($crop, $preview, 1);
echo "crop {$side}x{$side} at {$x0},{$y0} cy={$cy} cx={$cx}\n";

$target = 1000;
if (extension_loaded('imagick')) {
    $img = new Imagick($preview);
    // Mild sharpen after resize for print clarity
    $img->resizeImage($target, $target, Imagick::FILTER_LANCZOS, 1, true);
    $img->unsharpMaskImage(0, 0.6, 0.8, 0.02);
    $img->setImageFormat('png');
    $blob = $img->getImageBlob();
    file_put_contents($outSeal, $blob);
    @mkdir(dirname($outPublic), 0755, true);
    file_put_contents($outPublic, $blob);
    // also keep a copy under uploads for invoices if useful
    $uploads = '/home/u271258263/domains/auraprojects.store/public_html/service/uploads/vriksh-seal.png';
    file_put_contents($uploads, $blob);
    echo 'wrote ' . $img->getImageWidth() . 'x' . $img->getImageHeight() . ' bytes=' . strlen($blob) . "\n";
    exit(0);
}

imagepng($crop, $outSeal, 1);
@mkdir(dirname($outPublic), 0755, true);
imagepng($crop, $outPublic, 1);
echo "wrote crop only\n";
