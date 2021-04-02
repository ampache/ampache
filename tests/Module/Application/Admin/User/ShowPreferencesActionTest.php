<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Application\Admin\User;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowPreferencesActionTest extends MockeryTestCase
{
    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var PreferenceRepositoryInterface|MockInterface|null */
    private MockInterface $preferenceRepository;

    private ?ShowPreferencesAction $subject;

    public function setUp(): void
    {
        $this->ui                   = $this->mock(UiInterface::class);
        $this->preferenceRepository = $this->mock(PreferenceRepositoryInterface::class);

        $this->subject = new ShowPreferencesAction(
            $this->ui,
            $this->preferenceRepository
        );
    }

    public function testHandleReturnsNull(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->mock(User::class);

        $userId      = 666;
        $preferences = ['some-preference'];

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->preferenceRepository->shouldReceive('getAll')
            ->with($userId)
            ->once()
            ->andReturn($preferences);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_user_preferences.inc.php',
                [
                    'client' => $user,
                    'preferences' => $preferences,
                    'ui' => $this->ui,
                ]
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }
}
