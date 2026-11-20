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

use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Share\ShareUiLinkRendererInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class BrowseAjaxHandlerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function ownerKeyProvider(): iterable
    {
        yield 'pvmsg user' => ['pvmsg', 'user'];
        yield 'pvmsg to_user' => ['pvmsg', 'to_user'];
        yield 'pvmsg from_user' => ['pvmsg', 'from_user'];
        yield 'share user' => ['share', 'user'];
    }

    // the owning page sets these filters server-side, so a filter box request pointing them at another user reads their rows
    #[DataProvider('ownerKeyProvider')]
    #[RunInSeparateProcess]
    public function testFilterBoxCannotRepointTheOwnerOfPrivateRows(string $type, string $key): void
    {
        $browse = $this->filterBoxBrowse($type, $key);

        $browse->expects(self::never())
            ->method('set_filter')
            ->with($key);

        $this->handle($browse);
    }

    #[RunInSeparateProcess]
    public function testFilterBoxStillSetsTheUserFilterOnOtherLists(): void
    {
        $browse = $this->filterBoxBrowse('catalog', 'user');

        $browse->expects(self::once())
            ->method('set_filter')
            ->with('user', 2);

        $this->handle($browse);
    }

    private function filterBoxBrowse(string $type, string $key): Browse
    {
        if (!defined('AJAX_INCLUDE')) {
            define('AJAX_INCLUDE', 1);
        }

        $_REQUEST = ['action' => 'browse', 'browse_id' => '1', 'key' => $key, 'value' => '2'];

        $browse = $this->createMock(Browse::class);
        $browse->method('get_type')->willReturn($type);
        $browse->method('get_content_div')->willReturn('browse_content');

        return $browse;
    }

    private function handle(Browse $browse): void
    {
        $requestParser = $this->createMock(RequestParserInterface::class);
        $requestParser->method('getFromRequest')->willReturn('browse');
        $browseFactory = $this->createMock(BrowseFactoryInterface::class);
        $browseFactory->method('create')->willReturn($browse);

        $subject = new BrowseAjaxHandler(
            $requestParser,
            $this->createMock(ModelFactoryInterface::class),
            $browseFactory,
            $this->createMock(LiveStreamRepositoryInterface::class),
            $this->createMock(ShareUiLinkRendererInterface::class),
            $this->createMock(CatalogRepositoryInterface::class),
        );

        ob_start();
        $subject->handle($this->createMock(User::class));
        ob_end_clean();
    }
}
