<?php
/**
 * Generate retail label footer icons (recycle + placeholder seal if missing).
 * Run: php scripts/gs1_make_label_icons.php
 */
$dirs = [
    dirname(__DIR__) . '/images/gs1/labels',
    __DIR__ . '/../stock/public/gs1/labels',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function saveAll(array $dirs, string $name, $im): void
{
    foreach ($dirs as $dir) {
        imagepng($im, $dir . '/' . $name);
    }
}

// --- Recycle Möbius (green) ---
$s = 160;
$im = imagecreatetruecolor($s, $s);
imagesavealpha($im, true);
$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefill($im, 0, 0, $transparent);
$green = imagecolorallocate($im, 46, 139, 48);
// Three thick arcs approximating chasing arrows
imagesetthickness($im, 14);
$cx = $s / 2;
$cy = $s / 2;
$r = 52;
for ($a = 0; $a < 3; $a++) {
    $start = $a * 120 + 20;
    $end = $start + 80;
    imagearc($im, (int) $cx, (int) $cy, $r * 2, $r * 2, $start, $end, $green);
    // arrow tip
    $rad = deg2rad($end);
    $x = (int) ($cx + $r * cos($rad));
    $y = (int) ($cy + $r * sin($rad));
    $rad2 = deg2rad($end + 25);
    $x2 = (int) ($cx + ($r - 18) * cos($rad2));
    $y2 = (int) ($cy + ($r - 18) * sin($rad2));
    $rad3 = deg2rad($end - 10);
    $x3 = (int) ($cx + ($r + 18) * cos($rad3));
    $y3 = (int) ($cy + ($r + 18) * sin($rad3));
    imagefilledpolygon($im, [$x, $y, $x2, $y2, $x3, $y3], $green);
}
saveAll($dirs, 'recycled.png', $im);
imagedestroy($im);

// Prefer seal cropped from reference if present; otherwise skip overwrite
$srcPng = '/tmp/label-ref/in308-1.png';
if (is_file($srcPng)) {
    $src = imagecreatefrompng($srcPng);
    $minx = 9999;
    $miny = 9999;
    $maxx = 0;
    $maxy = 0;
    for ($y = 1240; $y < 1480; $y++) {
        for ($x = 880; $x < 1180; $x++) {
            $rgb = imagecolorat($src, $x, $y);
            $r = ($rgb >> 16) & 255;
            $g = ($rgb >> 8) & 255;
            $b = $rgb & 255;
            if (($g > 60 && $g > $r + 15) || ($r + $g + $b) < 90) {
                $minx = min($minx, $x);
                $miny = min($miny, $y);
                $maxx = max($maxx, $x);
                $maxy = max($maxy, $y);
            }
        }
    }
    if ($maxx > $minx) {
        $pad = 4;
        $seal = imagecrop($src, [
            'x' => max(0, $minx - $pad),
            'y' => max(0, $miny - $pad),
            'width' => $maxx - $minx + 2 * $pad,
            'height' => $maxy - $miny + 2 * $pad,
        ]);
        if ($seal) {
            saveAll($dirs, 'seal.png', $seal);
            imagedestroy($seal);
        }
    }
    imagedestroy($src);
}

echo "Icons written to:\n";
foreach ($dirs as $dir) {
    echo "  $dir\n";
    foreach (['recycled.png', 'seal.png'] as $f) {
        $p = $dir . '/' . $f;
        echo '    ' . $f . (is_file($p) ? ' ' . filesize($p) . "b\n" : " MISSING\n");
    }
}
