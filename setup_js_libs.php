<?php
// Ensure directory exists
$dir = __DIR__ . '/public/js';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
    echo "Created directory: $dir\n";
}

// URLs
$libs = [
    'xlsx.full.min.js' => 'https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js',
    'mammoth.browser.min.js' => 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.21/mammoth.browser.min.js'
];

foreach ($libs as $name => $url) {
    echo "Downloading $name...\n";
    $content = file_get_contents($url);
    if ($content) {
        file_put_contents("$dir/$name", $content);
        echo "Success: $name saved.\n";
    } else {
        echo "Error: Failed to download $name from $url\n";
    }
}
?>
