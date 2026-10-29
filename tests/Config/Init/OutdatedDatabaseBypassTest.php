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
 * An entry point defines `OUTDATED_DATABASE_OK` before requiring `Config/Init.php` to stay reachable with a
 * pending database update; this locks the known set so a silent removal or addition fails here, not in the field.
 */
class OutdatedDatabaseBypassTest extends TestCase
{
    use EntryPointFileScanningTrait;


    /**
     * Every file that must keep defining `OUTDATED_DATABASE_OK`, with the one-line reason it needs to.
     *
     * @var list<string>
     */
    private const array EXPECTED_BYPASS_FILES = [
        // admin-only; only this bootstrap guard is waived, every action still requires a real admin session
        'public/update.php',
        // unauthenticated descriptor, served before login is even reachable
        'public/opensearch.php',
        // serves art for an already-valid session/API token; nothing it reads depends on the schema being current
        'public/image.php',
        // the update redirect targets login.php when auth is required, so login must stay reachable too
        'public/login.php',
        // oidc login must stay reachable as it may be the primary auth method method
        'public/oidc/index.php',
        // stream/download only (see the file's own `action` guard); create/delete stay fully gated
        'public/share.php',
        // play URLs rely on an already-issued session/token, not a fresh login
        'public/play/index.php',
        // legacy XML/JSON RPC API -- a non-browser client can't follow an html redirect to update.php
        'public/server/xml.server.php',
        'public/server/json.server.php',
        // REST routing in front of the same ApiHandler as the RPC API above
        'public/server/xml.rest.php',
        'public/server/json.rest.php',
        // Subsonic/OpenSubsonic clients can't follow an html redirect either
        'public/rest/index.php',
        // Jellyfin-compatible clients, same reasoning
        'public/jellyfin/index.php',
        // admin:updateDatabase has to be reachable from the CLI on the outdated database it is about to fix
        'bin/cli',
    ];

    public function testOnlyTheKnownEntryPointsBypassTheOutdatedDatabaseCheck(): void
    {
        $expected = self::EXPECTED_BYPASS_FILES;
        sort($expected);

        self::assertSame(
            $expected,
            $this->getEntryPointFilesDefining('OUTDATED_DATABASE_OK'),
            'A file started or stopped bypassing InitializationHandlerDatabaseUpdate. If deliberate, update '
            . 'EXPECTED_BYPASS_FILES with a one-line reason; otherwise this is the recurring bug where an entry '
            . 'point loses (or silently gains) the outdated-database bypass.'
        );
    }
}
