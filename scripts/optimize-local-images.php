<?php

$jobs = [
    ['public/images/Ai_logo.png', 'public/images/ai_logo.webp', 640, 640, 82],
    ['public/images/logo.png', 'public/images/logo.webp', 320, 320, 82],
    ['public/images/google_logo.png', 'public/images/google_logo.webp', 96, 96, 82],
    ['public/images/categorey.png', 'public/images/categorey.webp', 300, 300, 80],
    ['public/images/hero.png', 'public/images/hero.webp', 800, 800, 80],
];

foreach ($jobs as [$sourcePath, $targetPath, $maxWidth, $maxHeight, $quality]) {
    $contents = file_get_contents($sourcePath);
    $source = $contents !== false ? imagecreatefromstring($contents) : false;

    if ($source === false) {
        fwrite(STDERR, "Unable to read {$sourcePath}\n");
        continue;
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $scale = min(1, $maxWidth / $width, $maxHeight / $height);
    $targetWidth = max(1, (int) round($width * $scale));
    $targetHeight = max(1, (int) round($height * $scale));
    $target = imagecreatetruecolor($targetWidth, $targetHeight);

    imagealphablending($target, false);
    imagesavealpha($target, true);
    $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
    imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
    imagecopyresampled(
        $target,
        $source,
        0,
        0,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $width,
        $height
    );
    imagewebp($target, $targetPath, $quality);

    imagedestroy($source);
    imagedestroy($target);

    echo "{$targetPath} ".filesize($targetPath)." bytes\n";
}
