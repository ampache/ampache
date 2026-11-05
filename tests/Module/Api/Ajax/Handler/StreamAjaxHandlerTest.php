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
 */

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StreamAjaxHandlerTest extends TestCase
{
    private RequestParserInterface&MockObject $requestParser;
    private StreamAjaxHandler $subject;
    private UiInterface&MockObject $ui;
    private User&MockObject $user;

    /**
     * `custom_play_action` reaches the iframe reload target unmodified, unlike its sibling `object_type`
     * just above it in the same handler, which already goes through the scrubbing `Core::get_request()`.
     */
    public function testDirectplayScrubsCustomPlayAction(): void
    {
        AmpConfig::set('web_path', 'https://music.example', true);

        $this->requestParser->method('getFromRequest')
            ->with('action')
            ->willReturn('directplay');

        $_REQUEST['object_type']        = 'song';
        $_GET['object_id']              = '42';
        $_REQUEST['custom_play_action'] = '<script>alert(1)</script>&evil=1';

        ob_start();
        $this->subject->handle($this->user);
        ob_get_clean();

        $this->assertStringNotContainsString('<script>', $_SESSION['iframe']['target']);
        $this->assertStringContainsString('&amp;evil=1', $_SESSION['iframe']['target']);
    }

    protected function setUp(): void
    {
        $this->requestParser = $this->createMock(RequestParserInterface::class);
        $this->ui            = $this->createMock(UiInterface::class);
        $this->user          = $this->createMock(User::class);

        $this->subject = new StreamAjaxHandler($this->requestParser, $this->ui);
    }

    protected function tearDown(): void
    {
        // AmpConfig is a static store backupGlobals cannot reach, so a web_path set here outlives the test
        AmpConfig::set('web_path', '', true);
    }
}
