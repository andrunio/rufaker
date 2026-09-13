<?php

declare(strict_types=1);

// Cuts the section of one version out of the changelog: the release says what the file says.
$tag = $argv[1] ?? '';
$path = $argv[2] ?? 'CHANGELOG.md';

if (preg_match('/^v(\d+\.\d+\.\d+)$/', $tag, $parsed) !== 1) {
    fwrite(STDERR, "The tag $tag is not a version tag such as v1.2.3.\n");

    exit(1);
}

$version = $parsed[1];
$changelog = @file_get_contents($path);

if ($changelog === false) {
    fwrite(STDERR, "Cannot read the changelog at $path.\n");

    exit(1);
}

// The section runs until the next one or until the link block at the foot of the file.
$section = sprintf(
    '/^## \[%s] - \d{4}-\d{2}-\d{2}$(.*?)(?=^## |^\[[^]]+]: |\z)/ms',
    preg_quote($version, '/'),
);

if (preg_match($section, $changelog, $found) !== 1) {
    $undated = preg_match('/^## \[' . preg_quote($version, '/') . ']$/m', $changelog) === 1;

    fwrite(STDERR, $undated
        ? "The section for $version carries no date. Keep a Changelog wants ## [$version] - YYYY-MM-DD.\n"
        : "The changelog has no section for $version. Is it still called Unreleased?\n");

    exit(1);
}

$notes = trim($found[1]);

if ($notes === '') {
    fwrite(STDERR, "The section for $version is empty.\n");

    exit(1);
}

// A missing link at the foot of the file means the release was closed halfway.
if (preg_match('/^\[' . preg_quote($version, '/') . ']: \S+$/m', $changelog) !== 1) {
    fwrite(STDERR, "The changelog has no link for $version at the foot of the file.\n");

    exit(1);
}

fwrite(STDERR, "Release notes for $version: " . substr_count($notes, "\n") . " lines.\n");

echo "$notes\n";
