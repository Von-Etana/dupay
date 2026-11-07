<?php

$files = [
    __DIR__ . '/../../resources/views/admin/security_alerts/index.blade.php',
    __DIR__ . '/../../resources/views/admin/security_alerts/show.blade.php',
];

$allGood = true;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "FAIL: File not found: $file\n";
        $allGood = false;
        continue;
    }

    $content = file_get_contents($file);
    echo "PASS: File exists (" . strlen($content) . " bytes): " . basename($file) . "\n";

    // Check directive matching (@if/@endif, @can/@endcan, @section/@endsection, etc.)
    $directives = ['if', 'can', 'section', 'forelse'];
    foreach ($directives as $dir) {
        preg_match_all('/@' . $dir . '\b/i', $content, $opens);
        preg_match_all('/@end' . $dir . '\b/i', $content, $closes);
        $openCount = count($opens[0]);
        $closeCount = count($closes[0]);
        if ($openCount !== $closeCount) {
            echo "  FAIL: Directive mismatch for @$dir in " . basename($file) . " ($openCount opens, $closeCount closes)\n";
            $allGood = false;
        } else {
            echo "  PASS: Directive @$dir balanced ($openCount)\n";
        }
    }
}

if ($allGood) {
    echo "\nAll blade templates verified successfully!\n";
    exit(0);
} else {
    echo "\nBlade template verification failed!\n";
    exit(1);
}
