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

namespace Ampache\Module\Application\Search;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class SaveAsSmartPlaylistActionTest extends TestCase
{
    // the name comes raw from the request and the confirmation renders html, so a crafted link runs script
    public function testTheConfirmationEscapesTheSmartlistName(): void
    {
        $smartlist       = $this->createMock(Smartlist::class);
        $smartlist->name = '<img src=x onerror=alert(1)>';

        $modelFactory = $this->createMock(ModelFactoryInterface::class);
        $modelFactory->method('createSmartlist')->willReturn($smartlist);

        $ui = $this->createMock(UiInterface::class);
        $ui->expects(self::once())
            ->method('showConfirmation')
            ->with(
                self::anything(),
                self::logicalAnd(
                    self::stringContains('&lt;img src=x onerror=alert(1)&gt;'),
                    self::logicalNot(self::stringContains('<img'))
                ),
                self::anything()
            );

        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $gatekeeper->method('mayAccess')->willReturn(true);

        $subject = new SaveAsSmartPlaylistAction($ui, $this->createMock(ConfigContainerInterface::class), $modelFactory);

        $subject->run($this->createMock(ServerRequestInterface::class), $gatekeeper);
    }
}
