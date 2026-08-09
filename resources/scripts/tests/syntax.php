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

// Cross-platform replacement for syntax.sh: parse every *.php file and report syntax errors.
// Runs on Windows and *nix so it can be wired into `composer qa`. Uses the in-process tokenizer

$root = dirname(__DIR__, 3);

$skipDirs = ['vendor', 'node_modules', '.git', 'build', '.idea'];

echo "\033[1;34mChecking syntax error\033[0m\n";

$directory = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
$filter    = new RecursiveCallbackFilterIterator(
    $directory,
    static function (SplFileInfo $current) use ($skipDirs): bool {
        if ($current->isDir()) {
            return !in_array($current->getFilename(), $skipDirs, true);
        }

        return in_array(strtolower($current->getExtension()), ['php', 'phtml'], true);
    }
);

$errors = [];
foreach (new RecursiveIteratorIterator($filter) as $file) {
    /** @var SplFileInfo $file */
    $path   = $file->getPathname();
    $source = @file_get_contents($path);
    if ($source === false) {
        $errors[] = 'Unable to read ' . $path;
        continue;
    }

    try {
        token_get_all($source, TOKEN_PARSE);
    } catch (\CompileError|\ParseError $error) {
        $errors[] = $path . ': ' . $error->getMessage() . ' on line ' . $error->getLine();
    }
}

if ($errors !== []) {
    fwrite(STDERR, "\033[0;31mPlease check files syntax\033[0m\n");
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "\033[1;32mSyntax is OK\033[0m\n";
exit(0);
