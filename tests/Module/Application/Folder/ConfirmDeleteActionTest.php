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

namespace Ampache\Module\Application\Folder;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Folder\Deletion\FolderDeleterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\FolderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ConfirmDeleteActionTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private FolderDeleterInterface&MockObject $folderDeleter;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private GuiGatekeeperInterface&MockObject $gatekeeper;
    private ServerRequestInterface&MockObject $request;
    private RequestParserInterface&MockObject $requestParser;
    private ConfirmDeleteAction $subject;
    private UiInterface&MockObject $ui;

    public function testRunReturnsNullInDemoMode(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(true);
        $this->ui->expects(static::once())->method('showHeader');
        $this->ui->expects(static::once())->method('showQueryStats');
        $this->ui->expects(static::once())->method('showFooter');
        $this->requestParser->expects(static::never())
            ->method('verifyForm');

        self::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunThrowsIfFormTokenIsInvalid(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(false);

        $this->requestParser->expects(static::once())
            ->method('verifyForm')
            ->with('delete_folder')
            ->willReturn(false);
        $this->requestParser->expects(static::never())
            ->method('getFromRequest');
        $this->request->expects(static::never())
            ->method('getQueryParams');

        $this->subject->run($this->request, $this->gatekeeper);
    }

    protected function setUp(): void
    {
        $this->configContainer  = $this->createMock(ConfigContainerInterface::class);
        $this->ui               = $this->createMock(UiInterface::class);
        $this->requestParser    = $this->createMock(RequestParserInterface::class);
        $this->folderDeleter    = $this->createMock(FolderDeleterInterface::class);
        $this->folderRepository = $this->createMock(FolderRepositoryInterface::class);
        $this->gatekeeper       = $this->createMock(GuiGatekeeperInterface::class);
        $this->request          = $this->createMock(ServerRequestInterface::class);

        $this->subject = new ConfirmDeleteAction(
            $this->configContainer,
            $this->ui,
            $this->requestParser,
            $this->folderDeleter,
            $this->folderRepository
        );
    }
}
