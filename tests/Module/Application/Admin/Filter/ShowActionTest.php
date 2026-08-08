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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CatalogFilterRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends TestCase
{
    protected ShowAction $subject;
    private CatalogFilterRepositoryInterface&MockObject $catalogFilterRepository;
    private ConfigContainerInterface&MockObject $configContainer;
    private UiInterface&MockObject $ui;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testRunRenders(): void
    {
        $this->catalogFilterRepository->method('findGroups')->willReturn(new \ArrayIterator([]));

        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showBoxTop')
            ->with('Show Catalog Filters', 'box box_manage_filter');
        $this->ui->expects(static::once())
            ->method('showQueryStats');

        ob_start();

        try {
            $result = $this->subject->run($request, $gatekeeper);
        } finally {
            $output = (string) ob_get_clean();
        }

        self::assertNull($result);
        self::assertNotSame('', $output);
    }

    public function testRunThrowsIfAccessIsDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(false);

        $this->subject->run($request, $gatekeeper);
    }

    protected function setUp(): void
    {
        $this->ui                      = $this->createMock(UiInterface::class);
        $this->configContainer         = $this->createMock(ConfigContainerInterface::class);
        $this->catalogFilterRepository = $this->createMock(CatalogFilterRepositoryInterface::class);
        $this->userRepository          = $this->createMock(UserRepositoryInterface::class);

        $this->subject = new ShowAction(
            $this->ui,
            $this->configContainer,
            $this->catalogFilterRepository,
            $this->userRepository
        );
    }
}
