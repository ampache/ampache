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

// Finds a Python 3 interpreter and forwards the remaining arguments to it, exiting with its status.
// Debian names the binary python3 and ships no python at all, while other platforms only provide python.

$arguments = array_slice($argv, 1);
if ($arguments === []) {
    fwrite(STDERR, "usage: python.php <script> [arguments...]\n");

    exit(2);
}

// py -3 first on Windows: the launcher resolves a real Python 3 even when python.exe is the Store stub.
$candidates = (PHP_OS_FAMILY === 'Windows')
    ? [['py', '-3'], ['python'], ['python3']]
    : [['python3'], ['python']];

/**
 * Confirms the command exists and is Python 3 rather than an end-of-life Python 2.
 *
 * @param list<string> $command
 */
function isPython3(array $command): bool
{
    $output = [];
    $status = 1;
    exec(implode(' ', array_map('escapeshellarg', $command)) . ' --version 2>&1', $output, $status);

    return $status === 0 && str_starts_with(implode(' ', $output), 'Python 3');
}

foreach ($candidates as $candidate) {
    if (!isPython3($candidate)) {
        continue;
    }

    $status = 1;
    passthru(implode(' ', array_map('escapeshellarg', [...$candidate, ...$arguments])), $status);

    exit($status);
}

fwrite(STDERR, "No Python 3 interpreter found. Tried: " . implode(', ', array_map(static fn (array $candidate): string => implode(' ', $candidate), $candidates)) . "\n");

exit(1);
