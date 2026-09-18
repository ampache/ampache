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

namespace Ampache\Gui\AlbumDisk;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Override;

/**
 * A disk row is the only thing the artist page shows when albums are not grouped, so this is where a manager
 * either learns the release has been withdrawn or does not.
 */
class AlbumDiskViewAdapterTest extends MockeryTestCase
{
    private MockInterface&AlbumDisk $albumDisk;
    private MockInterface&Browse $browse;
    private MockInterface&ConfigContainerInterface $configContainer;
    private MockInterface&FunctionCheckerInterface $functionChecker;
    private MockInterface&GuiGatekeeperInterface $gatekeeper;
    private MockInterface&ModelFactoryInterface $modelFactory;
    private AlbumDiskViewAdapter $subject;
    private MockInterface&ZipHandlerInterface $zipHandler;

    public function testIsDisabledFollowsTheAlbumBehindTheDisk(): void
    {
        $this->albumDisk->shouldReceive('isEnabled')->once()->andReturnFalse();

        self::assertTrue($this->subject->isDisabled());
    }

    public function testIsDisabledIsFalseWhileTheAlbumIsOnTheShelves(): void
    {
        $this->albumDisk->shouldReceive('isEnabled')->once()->andReturnTrue();

        self::assertFalse($this->subject->isDisabled());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->zipHandler      = $this->mock(ZipHandlerInterface::class);
        $this->functionChecker = $this->mock(FunctionCheckerInterface::class);
        $this->gatekeeper      = $this->mock(GuiGatekeeperInterface::class);
        $this->browse          = $this->mock(Browse::class);
        $this->albumDisk       = $this->mock(AlbumDisk::class);

        $this->subject = new AlbumDiskViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $this->zipHandler,
            $this->functionChecker,
            $this->gatekeeper,
            $this->browse,
            $this->albumDisk,
        );
    }
}
