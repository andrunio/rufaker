<?php

declare(strict_types=1);

// Reads line coverage out of a Clover report and fails the build when it sits below the floor.
$report = $argv[1] ?? 'coverage.xml';
$floor = (float)($argv[2] ?? 95);
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

if ($percent < $floor) {
    fwrite(STDERR, "::error::Line coverage $percent% is below the floor of $floor%.\n");

    exit(1);
}

echo "Line coverage: $percent% ($covered of $statements statements), the floor is $floor%.\n";
