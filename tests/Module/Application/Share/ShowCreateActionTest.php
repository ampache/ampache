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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ShowCreateActionTest extends TestCase
{
    // the artist page offers the advanced share, so its form has to load an artist instead of staying empty
    public function testTheFormLoadsAnArtist(): void
    {
        $configContainer = $this->createStub(ConfigContainerInterface::class);
        $configContainer->method('isFeatureEnabled')
            ->willReturn(true);

        $requestParser = $this->createStub(RequestParserInterface::class);
        $requestParser->method('getFromRequest')
            ->willReturnMap([['type', 'artist'], ['id', '7']]);

        $libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);
        $libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::ARTIST, 7, self::containsEqual(Artist::class))
            ->willReturn(null);

        new ShowCreateAction(
            $requestParser,
            $configContainer,
            $this->createStub(UiInterface::class),
            $this->createStub(PasswordGeneratorInterface::class),
            $this->createStub(ZipHandlerInterface::class),
            $libraryItemLoader,
        )->run($this->createStub(ServerRequestInterface::class), $this->createStub(GuiGatekeeperInterface::class));
    }
}
