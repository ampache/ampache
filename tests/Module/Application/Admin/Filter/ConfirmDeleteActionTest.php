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
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CatalogFilterRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class ConfirmDeleteActionTest extends TestCase
{
    // filter_name comes from the query string and the confirmation renders html, so a link can run script
    public function testTheConfirmationEscapesTheFilterName(): void
    {
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnMap([
            [UserRepositoryInterface::class, $this->createMock(UserRepositoryInterface::class)],
        ]);
        $GLOBALS['dic'] = $dic;

        $catalogFilterRepository = $this->createMock(CatalogFilterRepositoryInterface::class);
        $catalogFilterRepository->method('deleteGroup')->willReturn(true);

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

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['filter_id' => '2', 'filter_name' => '<img src=x onerror=alert(1)>']);

        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $gatekeeper->method('mayAccess')->willReturn(true);

        $subject = new ConfirmDeleteAction($ui, $this->createMock(ConfigContainerInterface::class), $catalogFilterRepository);

        $subject->run($request, $gatekeeper);
    }
}
