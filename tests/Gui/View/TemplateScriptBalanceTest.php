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

namespace Ampache\Gui\View;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * A literal `</script>` in a template ends the block wherever it sits, comments included.
 *
 * Which is how a comment explaining that very hazard, written inside a script block, ended it early and
 * printed the rest of the page as text.
 */
class TemplateScriptBalanceTest extends TestCase
{
    public function testEveryScriptBlockIsClosedExactlyOnce(): void
    {
        $checked = 0;

        foreach ($this->getTemplates() as $file) {
            // what the browser sees: the php blocks never reach it
            $markup = (string) preg_replace('/<\?php.*?\?>/s', '', (string) file_get_contents((string) $file->getRealPath()));

            $opened = preg_match_all('/<script\b/', $markup);
            $closed = preg_match_all('/<\/script\s*>/', $markup);
            if ($opened === 0 && $closed === 0) {
                continue;
            }

            $checked++;
            self::assertSame(
                $opened,
                $closed,
                sprintf(
                    '%s opens %d script blocks and closes %d: a stray closing tag ends the block early and the rest of the page prints as text',
                    $this->relativePath((string) $file->getRealPath()),
                    $opened,
                    $closed
                )
            );
        }

        self::assertGreaterThan(30, $checked, 'the scan found almost no script blocks, so it is broken');
    }

    /**
     * @return list<SplFileInfo>
     */
    private function getTemplates(): array
    {
        $templates = [];
        foreach (['/../../../resources/templates', '/../../../public/templates'] as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__ . $root, FilesystemIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (in_array($file->getExtension(), ['phtml', 'php'], true)) {
                    $templates[] = $file;
                }
            }
        }

        usort($templates, static fn(SplFileInfo $a, SplFileInfo $b): int => strcmp((string) $a->getRealPath(), (string) $b->getRealPath()));

        return $templates;
    }

    private function relativePath(string $path): string
    {
        $at = strpos($path, 'templates');

        return ($at === false) ? $path : substr($path, $at);
    }
}
