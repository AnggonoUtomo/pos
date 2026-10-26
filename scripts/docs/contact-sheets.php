<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$temporary = $root.'/storage/framework/testing/user-guide';
$reports = json_decode(file_get_contents($temporary.'/pages-report.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($reports as $report) {
    foreach (array_chunk($report['pages'], 6) as $sheet => $pages) {
        $width = 360;
        $height = 535;
        $canvas = imagecreatetruecolor($width * 2, $height * (int) ceil(count($pages) / 2));
        $background = imagecolorallocate($canvas, 222, 231, 228);
        $text = imagecolorallocate($canvas, 20, 50, 45);
        imagefill($canvas, 0, 0, $background);
        foreach ($pages as $position => $page) {
            $source = imagecreatefrompng($temporary.'/'.$report['name'].'/page-'.sprintf('%03d', $page['page']).'.png');
            $x = ($position % 2) * $width;
            $y = intdiv($position, 2) * $height;
            imagestring($canvas, 3, $x + 10, $y + 5, $report['name'].' / '.$page['page'], $text);
            imagecopyresampled($canvas, $source, $x + 10, $y + 25, 0, 0, 340, 481, imagesx($source), imagesy($source));
            imagedestroy($source);
        }
        $path = $temporary.'/'.$report['name'].'-sheet-'.($sheet + 1).'.png';
        imagepng($canvas, $path);
        imagedestroy($canvas);
        echo $path.PHP_EOL;
    }
}
