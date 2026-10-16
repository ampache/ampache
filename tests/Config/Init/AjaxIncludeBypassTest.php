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

namespace Ampache\Config\Init;

use PHPUnit\Framework\TestCase;

/**
 * `AJAX_INCLUDE` marks the server-side ajax fragment endpoints (`Ajax::action()`, `Stream.php`,
 * `AuthenticationManager`, `BrowseAjaxHandler`) so they return a fragment rather than a full page/redirect.
 */
class AjaxIncludeBypassTest extends TestCase
{
    use EntryPointFileScanningTrait;

    /** @var list<string> */
    private const array EXPECTED_BYPASS_FILES = [
        'public/server/ajax.server.php',
        'public/server/edit.server.php',
        'public/server/fs.ajax.php',
        'public/server/refresh_reordered.server.php',
    ];

    public function testOnlyTheKnownAjaxEndpointsDefineAjaxInclude(): void
    {
        $expected = self::EXPECTED_BYPASS_FILES;
        sort($expected);

        self::assertSame(
            $expected,
            $this->getEntryPointFilesDefining('AJAX_INCLUDE'),
            'A file started or stopped identifying itself as an ajax fragment endpoint. If deliberate, update '
            . 'EXPECTED_BYPASS_FILES; otherwise this changes whether Ajax::action() and BrowseAjaxHandler treat '
            . 'the request as a fragment.'
        );
    }
}
