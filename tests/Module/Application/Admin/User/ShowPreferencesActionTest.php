<?php

declare(strict_types=1);

/**
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
 *
 */

namespace Ampache\Module\Application\Admin\User;

use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ShowPreferencesActionTest extends TestCase
{
    private UiInterface&MockObject $ui;

    private ModelFactoryInterface&MockObject $modelFactory;

    private PreferenceRepositoryInterface&MockObject $preferenceRepository;

    private ShowPreferencesAction $subject;

    protected function setUp(): void
    {
        $this->ui                   = $this->createMock(UiInterface::class);
        $this->modelFactory         = $this->createMock(ModelFactoryInterface::class);
        $this->preferenceRepository = $this->createMock(PreferenceRepositoryInterface::class);

        $this->subject = new ShowPreferencesAction(
            $this->ui,
            $this->modelFactory,
            $this->preferenceRepository,
        );
    }

    public function testRunErrorsIfUserWasNotFound(): void
    {
        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $user       = $this->createMock(User::class);

        $userId = 666;

        static::expectException(ObjectNotFoundException::class);

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->willReturn(true);

        $request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['user_id' => (string) $userId]);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with($userId)
            ->willReturn($user);

        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->subject->run($request, $gatekeeper);
    }

    public function testShowsPreferences(): void
    {
        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $user       = $this->createMock(User::class);

        $userId      = 666;
        $preferences = ['some-preferences'];

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->willReturn(true);

        $request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['user_id' => (string) $userId]);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with($userId)
            ->willReturn($user);

        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('show')
            ->with(
                'show_user_preferences.inc.php',
                [
                    'ui' => $this->ui,
                    'preferences' => $preferences,
                    'client' => $user
                ]
            );
        $this->ui->expects(static::once())
            ->method('showFooter');

        $this->preferenceRepository->expects(static::once())
            ->method('getAll')
            ->with($user)
            ->willReturn($preferences);

        static::assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
