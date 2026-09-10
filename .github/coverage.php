<?php

declare(strict_types=1);

// Reads line coverage out of a Clover report and prints it as GitHub Actions step outputs.
$report = $argv[1] ?? 'coverage.xml';
$clover = @simplexml_load_file($report);

if ($clover === false) {
    fwrite(STDERR, "Cannot read the coverage report at $report.\n");

    exit(1);
}

$statements = (int)$clover->project->metrics['statements'];
$covered = (int)$clover->project->metrics['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, "The coverage report at $report counts no statements at all.\n");

    exit(1);
}

$percent = round($covered / $statements * 100, 2);

// The scale shields.io applies to coverage badges of its own.
$color = 'red';

foreach ([95 => 'brightgreen', 90 => 'green', 75 => 'yellow', 60 => 'orange'] as $floor => $name) {
    if ($percent >= $floor) {
        $color = $name;

        break;
    }
}

fwrite(STDERR, "Line coverage: $percent% ($covered of $statements statements).\n");

echo "percent=$percent\n";
echo "color=$color\n";
