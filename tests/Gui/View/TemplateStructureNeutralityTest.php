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
 * `resources/templates/` is the one part of the tree no structure transform rewrites.
 *
 * The `client` layout serves its pages from `public/client/`, and `client-ampache8.py` appends the
 * web-root suffix by rewriting `getWebPath()` -> `getWebPath('/client')` across `src/` and the old
 * per-layout template directories only. A `.phtml` that resolves the web path itself is never rewritten
 * and builds urls missing `/client` on that branch -- with nothing in this repo to catch it, because the
 * transform lives in a different repository.
 *
 * So a template asks its view for the path, and the view is in `src/Gui`, which is rewritten.
 */
class TemplateStructureNeutralityTest extends TestCase
{
    /**
     * Calls that resolve a layout-dependent path, and what a template should use instead.
     */
    private const array FORBIDDEN = [
        'AmpConfig::get_web_path(' => 'take the path from the view (`$this->getWebPath()`)',
        'AmpConfig::get_web_path (' => 'take the path from the view (`$this->getWebPath()`)',
        '$this->configContainer->getWebPath(' => 'take the path from the view (`$this->getWebPath()`)',
    ];

    /**
     * A `public/` literal in a template would be wrong on `squashed`, which flattens that directory away.
     */
    public function testNoTemplateHardCodesTheWebRoot(): void
    {
        $violations = [];
        foreach ($this->getTemplates() as $template) {
            $contents = (string) file_get_contents((string) $template->getRealPath());
            if (preg_match('#[\'"/]public/#', $contents) === 1) {
                $violations[] = $this->getRelativePath($template);
            }
        }

        static::assertSame([], $violations, 'templates hard-coding a `public/` path');
    }

    public function testNoTemplateResolvesTheWebPathItself(): void
    {
        $violations = [];
        foreach ($this->getTemplates() as $template) {
            $contents = (string) file_get_contents((string) $template->getRealPath());
            foreach (self::FORBIDDEN as $needle => $advice) {
                if (str_contains($contents, $needle)) {
                    $violations[] = $this->getRelativePath($template) . ' uses ' . $needle . ') -- ' . $advice;
                }
            }
        }

        static::assertSame([], array_values(array_unique($violations)));
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', (string) $file->getRealPath());
        $at   = strpos($path, 'resources/templates/');

        return ($at === false) ? $path : substr($path, $at);
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
}
