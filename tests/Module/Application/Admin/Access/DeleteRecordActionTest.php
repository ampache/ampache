<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AccessRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class DeleteRecordActionTest extends TestCase
{
    private UiInterface&MockObject $ui;

    private AccessRepositoryInterface&MockObject $accessRepository;

    private RequestParserInterface&MockObject $requestParser;

    private DeleteRecordAction $subject;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    private ServerRequestInterface&MockObject $request;

    protected function setUp(): void
    {
        $this->ui               = $this->createMock(UiInterface::class);
        $this->requestParser    = $this->createMock(RequestParserInterface::class);
        $this->accessRepository = $this->createMock(AccessRepositoryInterface::class);

        $this->subject = new DeleteRecordAction(
            $this->ui,
            $this->accessRepository,
            $this->requestParser
        );

        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $this->request    = $this->createMock(ServerRequestInterface::class);
    }

    public function testRunThrowsIfAccessIsDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunThrowsIfFormVerificationFails(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->willReturn(true);

        $this->requestParser->expects(static::once())
            ->method('verifyForm')
            ->with('delete_access')
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunDeletes(): void
    {
        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->willReturn(true);

        $this->requestParser->expects(static::once())
            ->method('verifyForm')
            ->with('delete_access')
            ->willReturn(true);

        $aclId = 666;

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['access_id' => (string) $aclId]);

        $this->accessRepository->expects(static::once())
            ->method('delete')
            ->with($aclId);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showConfirmation')
            ->with(
                'No Problem',
                'Your Access List entry has been removed',
                'admin/access.php',
            );
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }
}
