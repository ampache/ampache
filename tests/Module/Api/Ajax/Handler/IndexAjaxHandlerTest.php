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

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\Sidebar\SidebarViewFactoryInterface;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\SlideshowInterface;
use Ampache\Module\Wanted\WantedManagerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

class IndexAjaxHandlerTest extends TestCase
{
    // the page swaps the box by fragment, so its html must come back in the response, not printed before it
    public function testRefreshNowPlayingReturnsTheBoxAsItsFragment(): void
    {
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnMap([
            [PrivilegeCheckerInterface::class, $this->createStub(PrivilegeCheckerInterface::class)],
            [LoggerInterface::class, $this->createStub(LoggerInterface::class)],
        ]);
        $GLOBALS['dic'] = $dic;

        $xml = $this->handle('refresh_now_playing');

        self::assertCount(1, $xml->xpath('//content[@div="now_playing"]'));
    }

    /** The slideshow no longer ships a script to run, the page fades through plain images on its own */
    public function testSlideshowSendsOnlyImagesWithTheFirstShowing(): void
    {
        $slideshow = $this->createMock(SlideshowInterface::class);
        $slideshow->method('getCurrentSlideshow')
            ->willReturn([
                ['title' => new SimpleXMLElement('<t/>'), 'url' => 'https://live.staticflickr.com/1/a.jpg'],
                ['title' => new SimpleXMLElement('<t/>'), 'url' => 'https://live.staticflickr.com/2/b.jpg'],
                ['title' => new SimpleXMLElement('<t/>'), 'url' => 'https://x.test/"onerror="alert(1)'],
            ]);

        $xml = $this->handle('slideshow', $slideshow);

        self::assertCount(1, $xml->xpath('//content'));
        $html = (string) $xml->xpath('//content[@div="fslider"]')[0];

        self::assertSame(3, substr_count($html, '<img '));
        self::assertSame(1, substr_count($html, 'class="active"'));
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('"onerror=', $html, 'a url cannot break out of its src attribute');
    }

    private function handle(string $action, ?SlideshowInterface $slideshow = null): SimpleXMLElement
    {
        $requestParser = $this->createMock(RequestParserInterface::class);
        $requestParser->method('getFromRequest')
            ->with('action')
            ->willReturn($action);

        $subject = new IndexAjaxHandler(
            $requestParser,
            $slideshow ?? $this->createStub(SlideshowInterface::class),
            $this->createStub(AlbumRepositoryInterface::class),
            $this->createStub(LabelRepositoryInterface::class),
            $this->createStub(SongRepositoryInterface::class),
            $this->createStub(WantedRepositoryInterface::class),
            $this->createStub(VideoRepositoryInterface::class),
            $this->createStub(WantedManagerInterface::class),
            $this->createStub(GatekeeperFactoryInterface::class),
            $this->createStub(GuiFactoryInterface::class),
            $this->createStub(BrowseFactoryInterface::class),
            $this->createStub(SidebarViewFactoryInterface::class),
        );

        ob_start();
        $subject->handle($this->createStub(User::class));

        return new SimpleXMLElement((string) ob_get_clean());
    }
}
