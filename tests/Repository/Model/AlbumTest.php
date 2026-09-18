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

namespace Ampache\Repository\Model;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Psr\Container\ContainerInterface;

class AlbumTest extends MockeryTestCase
{
    /**
     * Withdrawing a release takes its songs with it, so the cascade is part of the promise rather than a
     * convenience: enabling a song back on its own stays possible, re-enabling the album does not happen by itself
     */
    public function testUpdateEnabledCarriesTheStateDownToTheSongs(): void
    {
        $albumRepository = $this->mock(AlbumRepositoryInterface::class);
        $albumRepository->shouldReceive('setField')
            ->with(666, AlbumFieldEnum::ENABLED, 0)
            ->once();
        $albumRepository->shouldReceive('setSongsEnabled')
            ->with(666, false)
            ->once();

        $this->bootDic($albumRepository, true);

        Album::update_enabled(false, 666);
    }

    /**
     * The whole feature rests on this level, and nothing else stops a listener who posts the field by hand:
     * the guard is the only thing between them and a takedown they can undo
     */
    public function testUpdateEnabledWritesNothingBelowManager(): void
    {
        $albumRepository = $this->mock(AlbumRepositoryInterface::class);
        // the tell that the guard fired: neither the album nor its songs are touched
        $albumRepository->shouldNotReceive('setField');
        $albumRepository->shouldNotReceive('setSongsEnabled');

        $this->bootDic($albumRepository, false);

        Album::update_enabled(false, 666);
    }

    private function bootDic(AlbumRepositoryInterface $albumRepository, bool $isManager): void
    {
        $privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, null)
            ->andReturn($isManager);

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->with(PrivilegeCheckerInterface::class)->andReturn($privilegeChecker);
        $dic->shouldReceive('get')->with(AlbumRepositoryInterface::class)->andReturn($albumRepository);

        $GLOBALS['dic'] = $dic;
    }
}
