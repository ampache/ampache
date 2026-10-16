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
 * `NO_SESSION` makes `Session::auth()` skip cookie validation entirely and hand back an anonymous user
 * instead of throwing `RequireAuthException` -- so a file defining it is reachable with no login at all.
 */
class NoSessionBypassTest extends TestCase
{
    use EntryPointFileScanningTrait;

    /**
     * Every file that must keep defining `NO_SESSION`, grouped by why it needs to be cookie-session-free.
     *
     * @var list<string>
     */
    private const array EXPECTED_BYPASS_FILES = [
        // pages that must render for a user who isn't authenticated yet, or never will be
        'public/login.php',
        'public/oidc/index.php',
        'public/register.php',
        'public/lostpassword.php',
        'public/error.php',
        'public/opensearch.php',
        // browser pages served without a cookie session for a specific technical reason
        'public/now_playing.php',
        'public/graph.php',
        'public/image.php',
        'public/rss.php',
        // stream/download/queue endpoints driven by a token or api session, not a cookie
        'public/batch.php',
        'public/play/index.php',
        'public/share.php',
        // legacy XML/JSON RPC + REST API, Subsonic/OpenSubsonic, Jellyfin -- all token/key authenticated
        'public/server/xml.server.php',
        'public/server/json.server.php',
        'public/server/xml.rest.php',
        'public/server/json.rest.php',
        'public/rest/index.php',
        'public/jellyfin/index.php',
        // other media-server protocols with their own auth: DAAP, UPnP, WebDAV
        'public/daap/index.php',
        'public/upnp/index.php',
        'public/upnp/control-reply.php',
        'public/upnp/event-reply.php',
        'public/upnp/cm-control-reply.php',
        'public/upnp/cm-event-reply.php',
        'public/upnp/MediaServerServiceDesc.php',
        'public/upnp/ssdp.php',
        'public/webdav/index.php',
        // every CLI command authenticates (or not) its own way, never via a browser cookie
        'bin/cli',
    ];

    /**
     * `image.php` is the only entry point pairing `NO_SESSION` with `NO_SESSION_UPDATE`: serving art to an
     * unauthenticated crawler must not also write a fresh session row on every fetch.
     */
    public function testOnlyImageAlsoSuppressesSessionWrites(): void
    {
        self::assertSame(
            ['public/image.php'],
            $this->getEntryPointFilesDefining('NO_SESSION_UPDATE')
        );
    }

    public function testOnlyTheKnownEntryPointsBypassCookieSessionAuth(): void
    {
        $expected = self::EXPECTED_BYPASS_FILES;
        sort($expected);

        self::assertSame(
            $expected,
            $this->getEntryPointFilesDefining('NO_SESSION'),
            'A file started or stopped bypassing cookie-session auth (Session::auth()). If deliberate, update '
            . 'EXPECTED_BYPASS_FILES with a one-line reason; otherwise a page lost its required auth exemption '
            . 'or gained an unreviewed one.'
        );
    }
}
