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

namespace Ampache\Gui\Partial;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Sidebar\SidebarViewFactoryInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The tab title names the object the page shows. A list the viewer may not see must not name itself
 * there either, or the title says what the page itself refuses to.
 */
class HeaderViewTest extends TestCase
{
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private ?string $script    = null;
    private ?string $siteTitle = null;

    public function testAPrivateListTheViewerCannotSeeStaysAnonymous(): void
    {
        $playlist = $this->list(Playlist::class, 'private', false);
        // the tell that the guard fired: the name is never even read, let alone shown to anyone at all
        $playlist->expects(static::never())
            ->method('get_fullname');

        self::assertSame('Some site', $this->title('playlist', 'playlist_id'));
    }

    public function testAPrivateListTheViewerCollaboratesOnNamesItself(): void
    {
        $playlist = $this->list(Playlist::class, 'private', true);
        $playlist->method('get_fullname')
            ->willReturn('Shared list');

        self::assertSame('Shared list - Some site', $this->title('playlist', 'playlist_id'));
    }

    public function testAPrivateSearchTheViewerCannotSeeStaysAnonymous(): void
    {
        $search = $this->list(Search::class, 'private', false);
        $search->expects(static::never())
            ->method('get_fullname');

        self::assertSame('Some site', $this->title('smartplaylist', 'playlist_id'));
    }

    public function testAPublicListNamesItself(): void
    {
        $playlist = $this->list(Playlist::class, 'public', false);
        $playlist->method('get_fullname')
            ->willReturn('Electro cool');

        self::assertSame('Electro cool - Some site', $this->title('playlist', 'playlist_id'));
    }

    public function testAVisitorWithNoAccountSeesNoPrivateName(): void
    {
        $playlist = $this->list(Playlist::class, 'private', false);
        $playlist->expects(static::never())
            ->method('get_fullname');

        self::assertSame('Some site', $this->title('playlist', 'playlist_id'));
    }

    protected function setUp(): void
    {
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);
        $this->script            = $_SERVER['SCRIPT_NAME'] ?? null;
        $this->siteTitle         = (string) AmpConfig::get('site_title');
        AmpConfig::set('site_title', 'Some site', true);
        AmpConfig::set('page_title_icons', '0', true);
    }

    protected function tearDown(): void
    {
        AmpConfig::set('site_title', $this->siteTitle, true);
        unset($_GET['playlist_id']);
        if ($this->script === null) {
            unset($_SERVER['SCRIPT_NAME']);
        } else {
            $_SERVER['SCRIPT_NAME'] = $this->script;
        }
    }

    /**
     * @param class-string $className
     */
    private function list(string $className, string $type, bool $collaborates): MockObject
    {
        $item = $this->createMock($className);
        $item->method('isVisible')
            ->willReturn($type === 'public' || $collaborates);

        $this->libraryItemLoader->method('load')
            ->willReturn($item);

        return $item;
    }

    private function title(string $script, string $param, ?User $user = null): string
    {
        $_SERVER['SCRIPT_NAME'] = '/' . $script . '.php';
        $_GET[$param]           = '42';

        return new HeaderView(
            '',
            '',
            $this->createMock(EnvironmentInterface::class),
            $this->createMock(AjaxUriRetrieverInterface::class),
            $this->createMock(CollectionRepositoryInterface::class),
            $this->libraryItemLoader,
            $this->createMock(PlaylistLoaderInterface::class),
            $this->createMock(PrivateMessageRepositoryInterface::class),
            $this->createMock(ZipHandlerInterface::class),
            $this->createMock(SidebarViewFactoryInterface::class),
            $user,
            '',
            false,
            false,
            false,
            false,
        )->getPageTitle();
    }
}
