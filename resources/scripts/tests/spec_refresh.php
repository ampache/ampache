<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

// Record a re-audit of the OpenSubsonic implementation: re-pin `OpenSubsonicSpecVersionTest::SPEC_SHA256` to the
// committed spec and stamp the compliance date in docs/API-subsonic.md.
//
// This is the bookkeeping half of a re-audit, NOT the audit. Run it only once you have actually compared the new
// spec against the implementation, because the pin is what forces that comparison to happen at all.
//
//   composer spec:refresh            write the new hash and today's date
//   composer spec:refresh -- --check report what would change and exit 1 if anything would (for CI)
//   composer spec:refresh -- --date=2026-08-07
//
// Refreshing the spec itself is a separate step, done in the open-subsonic-api repo:
//   npm run bundle:openapi && cp openapi-opensubsonic.json <ampache>/docs/openapi-opensubsonic.json

$root      = dirname(__DIR__, 3);
$specPath  = $root . '/docs/openapi-opensubsonic.json';
$testPath  = $root . '/tests/Module/Api/OpenSubsonicSpecVersionTest.php';
$docPath   = $root . '/docs/API-subsonic.md';
$changePath = $root . '/docs/CHANGELOG.md';

$check = in_array('--check', $argv, true);
$date  = date('Y-m-d');
foreach ($argv as $arg) {
    if (str_starts_with((string) $arg, '--date=')) {
        $date = substr((string) $arg, 7);
    }
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    fwrite(STDERR, "\033[1;31mNot a date: {$date} (expected YYYY-MM-DD)\033[0m\n");

    exit(1);
}

foreach ([$specPath, $testPath, $docPath] as $required) {
    if (!is_readable($required)) {
        fwrite(STDERR, "\033[1;31mMissing: {$required}\033[0m\n");

        exit(1);
    }
}

// The spec is `text=auto`, so a windows checkout holds CRLF while the commit holds LF. Hash the normalised
// content, exactly as OpenSubsonicSpecVersionTest does, or the two disagree on line endings alone.
$spec = str_replace("\r\n", "\n", (string) file_get_contents($specPath));
$hash = hash('sha256', $spec);

/** @var array<int, string> $changes */
$changes = [];

// SPEC_SHA256
$test = (string) file_get_contents($testPath);
if (!preg_match("/SPEC_SHA256 = '([0-9a-f]{64})'/", $test, $match)) {
    fwrite(STDERR, "\033[1;31mCould not find SPEC_SHA256 in {$testPath}\033[0m\n");

    exit(1);
}
if ($match[1] !== $hash) {
    $changes[] = sprintf('SPEC_SHA256  %s -> %s', substr($match[1], 0, 12) . '...', substr($hash, 0, 12) . '...');
    if (!$check) {
        file_put_contents($testPath, str_replace("SPEC_SHA256 = '{$match[1]}'", "SPEC_SHA256 = '{$hash}'", $test));
    }
}

// The endpoint count is pinned separately and is deliberately left alone: a changed count means endpoints came or
// went, which is a bigger review than a re-hash, so it should be looked at by hand.
$paths = json_decode($spec, true);
if (
    is_array($paths)
    && preg_match('/SPEC_PATH_COUNT = (\d+)/', $test, $countMatch)
    && count($paths['paths'] ?? []) !== (int) $countMatch[1]
) {
    printf(
        "\033[1;33mSPEC_PATH_COUNT is %d but the spec now has %d paths — endpoints were added or removed, review "
        . "that by hand before updating the constant.\033[0m\n",
        (int) $countMatch[1],
        count($paths['paths'] ?? [])
    );
}

// Compliance date
$doc = (string) file_get_contents($docPath);
if (!preg_match('/\*\*Audited against the OpenSubsonic specification on (\d{4}-\d{2}-\d{2})\.\*\*/', $doc, $dateMatch)) {
    fwrite(STDERR, "\033[1;31mCould not find the compliance date in {$docPath}\033[0m\n");

    exit(1);
}
if ($dateMatch[1] !== $date) {
    $changes[] = sprintf('compliance date  %s -> %s', $dateMatch[1], $date);
    if (!$check) {
        file_put_contents($docPath, str_replace($dateMatch[0], str_replace($dateMatch[1], $date, $dateMatch[0]), $doc));
    }
}

// The changelog carries the same date in its release notes. It is prose about a release rather than a pin, so it is
// reported instead of rewritten.
if (is_readable($changePath)) {
    $changelog = (string) file_get_contents($changePath);
    if (
        preg_match('/audited against the published specification on (\d{4}-\d{2}-\d{2})/', $changelog, $logMatch)
        && $logMatch[1] !== $date
    ) {
        printf("\033[1;33mdocs/CHANGELOG.md still says %s — update it by hand if this audit belongs to it.\033[0m\n", $logMatch[1]);
    }
}

if ($changes === []) {
    echo "\033[1;32mAlready in step with docs/openapi-opensubsonic.json\033[0m\n";

    exit(0);
}

foreach ($changes as $change) {
    echo ($check ? "\033[1;33mwould update\033[0m  " : "\033[1;32mupdated\033[0m  ") . $change . "\n";
}

if ($check) {
    echo "\033[1;33mRun `composer spec:refresh` once the implementation has been re-audited.\033[0m\n";

    exit(1);
}

exit(0);
