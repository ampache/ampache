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
     * Only Jellyfin and Subsonic/OpenSubsonic define `API` at the entry point -- they never touch
     * `ApiHandlerInterface`. The legacy XML/JSON RPC and REST entry points get it from
     * `ApiHandler::handle()` instead (see testApiHandlerStillDefinesApiAtRuntime below), not from
     * themselves, so they correctly do NOT appear in this list.
     *
     * @var list<string>
     */
    private const array EXPECTED_ENTRY_POINT_API_FILES = [
        'public/jellyfin/index.php',
        'public/rest/index.php',
    ];

    /**
     * The legacy RPC/REST API entry points rely on this define, not one of their own, to suppress GUI output.
     */
    public function testApiHandlerStillDefinesApiAtRuntime(): void
    {
        $contents = (string) file_get_contents(self::API_HANDLER_FILE);

        self::assertStringContainsString(
            "define('API', true);",
            $contents,
            'ApiHandler::handle() no longer defines API, so the legacy xml/json server+rest entry points would '
            . 'start leaking catalog-update HTML/ticker output into their XML/JSON responses.'
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

    public function testOnlyJellyfinAndSubsonicDefineApiAtTheEntryPoint(): void
    {
        $expected = self::EXPECTED_ENTRY_POINT_API_FILES;
        sort($expected);

        self::assertSame(
            $expected,
            $this->getEntryPointFilesDefining('API'),
            'A file started or stopped defining API at the entry point. If this is one of the legacy RPC/REST '
            . 'files (xml.server.php, json.server.php, xml.rest.php, json.rest.php), it should rely on '
            . 'ApiHandler::handle() instead -- check that define is still there rather than adding this one back.'
        );
    }
}
