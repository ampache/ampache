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

namespace Ampache\Module\Application\Admin\Filter;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ConfirmDeleteActionTest extends TestCase
{
    // a filter decides which catalogs users see, so a plain link must not be able to delete one
    public function testADeleteWithoutAValidFormTokenIsRefused(): void
    {
        $ui = $this->createMock(UiInterface::class);
        $ui->expects(self::never())->method('showConfirmation');

        $requestParser = $this->createMock(RequestParserInterface::class);
        $requestParser->method('verifyForm')->with('delete_catalog_filter')->willReturn(false);

        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $gatekeeper->method('mayAccess')->willReturn(true);

        $subject = new ConfirmDeleteAction($ui, $this->createMock(ConfigContainerInterface::class), $requestParser);

        $this->expectException(AccessDeniedException::class);

        $subject->run($this->createMock(ServerRequestInterface::class), $gatekeeper);
    }
}
