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
 * A `refresh` that names no url reloads the page it is on, for ever.
 */
class TemplateMetaRefreshTest extends TestCase
{
    public function testEveryMetaRefreshSendsThePageSomewhereElse(): void
    {
        foreach ($this->getTemplates() as $file) {
            $contents = (string) file_get_contents((string) $file->getRealPath());

            preg_match_all('/<meta\s+http-equiv="refresh"\s+content="([^"]*)"/i', $contents, $matches);

            foreach ($matches[1] as $content) {
                self::assertMatchesRegularExpression(
                    '/^\d+\s*;\s*URL=/i',
                    $content,
                    sprintf(
                        '%s refreshes to `%s`, which reloads the page it is on. A cache directive belongs on `cache-control`, `expires` or `pragma`',
                        $this->relativePath((string) $file->getRealPath()),
                        $content
                    )
                );
            }
        }
    }

    /**
     * @return list<SplFileInfo>
     */
    private function getTemplates(): array
    {
        $templates = [];
        $iterator  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../../resources/templates', FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'phtml') {
                $templates[] = $file;
            }
        }

        usort($templates, static fn(SplFileInfo $a, SplFileInfo $b): int => strcmp((string) $a->getRealPath(), (string) $b->getRealPath()));

        return $templates;
    }

    private function relativePath(string $path): string
    {
        $at = strpos($path, 'resources/templates');

        return ($at === false) ? $path : substr($path, $at);
    }
}
