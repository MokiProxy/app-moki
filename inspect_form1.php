<?php

$path = 'E:\Software-Dev\magang\app-moki\app\Services\Form1ImportExportService.php';
$lines = file($path);

echo "===== RiskScoreLevel references =====".PHP_EOL;
foreach ($lines as $i => $line) {
    if (stripos($line, 'RiskScoreLevel') !== false) {
        echo ($i + 1).": ".trim($line).PHP_EOL;
    }
}

echo PHP_EOL."===== resolveRiskScoreValue / resolveLevel methods =====".PHP_EOL;
$show = false;
foreach ($lines as $i => $line) {
    if (preg_match('/function\s+(resolveRiskScoreValue|resolveRiskScoreLevel|resolveScoreLevel|resolveLevel|resolveScore)/', $line)) {
        $show = true;
    }
    if ($show) {
        echo ($i + 1).": ".rtrim($line).PHP_EOL;
        if (trim($line) === '}') {
            $show = false;
            echo "----".PHP_EOL;
        }
    }
}
