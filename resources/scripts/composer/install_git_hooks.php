<?php

// Keeps .git/hooks/pre-commit in sync with the tracked copy. Never fatal: a packaged
// release has no .git directory at all, and a broken copy here must not break `composer install`.

try {
    $root   = dirname(__DIR__, 3);
    $source = $root . '/resources/git-hooks/pre-commit';
    $hooksDir = $root . '/.git/hooks';

    if (!is_dir($root . '/.git') || !is_file($source)) {
        exit;
    }

    if (!is_dir($hooksDir) && !mkdir($hooksDir, 0755, true) && !is_dir($hooksDir)) {
        exit;
    }

    if (copy($source, $hooksDir . '/pre-commit')) {
        chmod($hooksDir . '/pre-commit', 0755);
    }
} catch (Throwable) {
    // best-effort only; a contributor can still commit without the hook installed
}
