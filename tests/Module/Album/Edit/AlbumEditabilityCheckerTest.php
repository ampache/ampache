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

namespace Ampache\Module\Album\Edit;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Mockery\MockInterface;
use Override;

class AlbumEditabilityCheckerTest extends MockeryTestCase
{
    private ConfigContainerInterface&MockInterface $configContainer;
    private PrivilegeCheckerInterface&MockInterface $privilegeChecker;
    private AlbumEditabilityChecker $subject;

    public function testCheckReturnsFalseIfAlbumHasNoAlbumArtist(): void
    {
        $album = $this->mock(Album::class);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $album->shouldReceive('getAlbumArtist')
            ->withNoArgs()
            ->once()
            ->andReturn(0);

        $this->assertFalse(
            $this->subject->check($this->mock(GuiGatekeeperInterface::class), $album)
        );
    }

    public function testCheckReturnsFalseIfUploadEditingIsDisabled(): void
    {
        $album = $this->mock(Album::class);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $album->shouldReceive('getAlbumArtist')
            ->withNoArgs()
            ->once()
            ->andReturn(123);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->subject->check($this->mock(GuiGatekeeperInterface::class), $album)
        );
    }

    public function testCheckReturnsFalseIfUploaderIsSomeoneElse(): void
    {
        $album      = $this->mock(Album::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $album->shouldReceive('getAlbumArtist')->withNoArgs()->once()->andReturn(123);
        $album->shouldReceive('get_user_owner')->withNoArgs()->once()->andReturn(42);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('getUserId')->withNoArgs()->once()->andReturn(666);

        $this->assertFalse($this->subject->check($gatekeeper, $album));
    }

    public function testCheckReturnsTrueForAContentManager(): void
    {
        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->check($this->mock(GuiGatekeeperInterface::class), $this->mock(Album::class))
        );
    }

    public function testCheckReturnsTrueForTheUploaderOfAnAlbumDisk(): void
    {
        $albumDisk               = $this->mock(AlbumDisk::class);
        $gatekeeper              = $this->mock(GuiGatekeeperInterface::class);
        $albumDisk->album_artist = 123;

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            ->once()
            ->andReturnTrue();

        $albumDisk->shouldReceive('get_user_owner')->withNoArgs()->once()->andReturn(42);
        $gatekeeper->shouldReceive('getUserId')->withNoArgs()->once()->andReturn(42);

        $this->assertTrue($this->subject->check($gatekeeper, $albumDisk));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);

        $this->subject = new AlbumEditabilityChecker(
            $this->privilegeChecker,
            $this->configContainer
        );
    }
}
