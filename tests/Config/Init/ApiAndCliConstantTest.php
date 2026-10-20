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
 * `API`/`CLI` tell `Ui::update_text()`, `Ui::check_ticker()`, `LegacyLogger` and every `Catalog*`/`Beets`
 * progress call site to stay machine-parseable -- no incidental HTML/JS/SSE chatter in the response body.
 */
class ApiAndCliConstantTest extends TestCase
{
    use EntryPointFileScanningTrait;

    private const string API_HANDLER_FILE = __DIR__ . '/../../../src/Module/Api/ApiHandler.php';

    /**
     * Every non-HTML entry point defines `API` at the top of the file, before `Config/Init.php` runs --
     * relying on `ApiHandler::handle()` alone left a gap, because `Init.php`'s own bootstrap chain runs
     * before `handle()` is ever reached, so a log-write failure during bootstrap leaked the warning into
     * otherwise strict XML/JSON/binary output (see testApiHandlerStillDefinesApiAtRuntime below for why
     * `ApiHandler::handle()` still defines it too, as a fallback for any entry point that forgets).
     *
     * @var list<string>
     */
    private const array EXPECTED_ENTRY_POINT_API_FILES = [
        'public/daap/index.php',
        'public/graph.php',
        'public/image.php',
        'public/jellyfin/index.php',
        'public/opensearch.php',
        'public/play/index.php',
        'public/rest/index.php',
        'public/server/json.rest.php',
        'public/server/json.server.php',
        'public/server/xml.rest.php',
        'public/server/xml.server.php',
        'public/stream.php',
        'public/upnp/MediaServerServiceDesc.php',
        'public/upnp/cm-control-reply.php',
        'public/upnp/cm-event-reply.php',
        'public/upnp/control-reply.php',
        'public/upnp/event-reply.php',
        'public/upnp/index.php',
        'public/upnp/playstatus.php',
        'public/waveform.php',
        'public/webdav/index.php',
    ];

    /**
     * A fallback for any entry point that forgets its own define -- the entry points above no longer
     * depend on this alone (see EXPECTED_ENTRY_POINT_API_FILES above for why).
     */
    public function testApiHandlerStillDefinesApiAtRuntime(): void
    {
        $contents = (string) file_get_contents(self::API_HANDLER_FILE);

        self::assertStringContainsString(
            "define('API', true);",
            $contents,
            'ApiHandler::handle() no longer defines API, so an entry point that forgot its own define would '
            . 'start leaking catalog-update HTML/ticker output into its response.'
        );
    }

    /**
     * `bin/cli` is the only CLI entry point today; a second one (e.g. a split admin tool) that forgets this
     * would silently get HTML/JS progress output written to a terminal instead of plain text.
     */
    public function testOnlyCliDefinesCli(): void
    {
        self::assertSame(
            ['bin/cli'],
            $this->getEntryPointFilesDefining('CLI')
        );
    }

    public function testOnlyTheKnownNonHtmlEntryPointsDefineApi(): void
    {
        $expected = self::EXPECTED_ENTRY_POINT_API_FILES;
        sort($expected);

        self::assertSame(
            $expected,
            $this->getEntryPointFilesDefining('API'),
            'A file started or stopped defining API at the entry point. If deliberate (the file only ever '
            . 'serves XML/JSON/binary output, never an HTML page), update EXPECTED_ENTRY_POINT_API_FILES; '
            . 'otherwise this reopens the bootstrap-phase gap that let log-write warnings leak into non-HTML '
            . 'responses (see EXPECTED_ENTRY_POINT_API_FILES above).'
        );
    }
}
