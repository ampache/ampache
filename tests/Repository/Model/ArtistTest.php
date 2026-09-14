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
use Ampache\Repository\ArtistRepositoryInterface;
use Psr\Container\ContainerInterface;

class ArtistTest extends MockeryTestCase
{
    /**
     * Withdrawing an artist reaches their albums and the songs under them, and the stored counts are brought
     * back in the same breath because every page reads the count rather than counting again
     */
    public function testUpdateEnabledCarriesTheStateDownAndRefreshesTheCounts(): void
    {
        $artistRepository = $this->mock(ArtistRepositoryInterface::class);
        $artistRepository->shouldReceive('setField')
            ->with(666, ArtistFieldEnum::ENABLED, 0)
            ->once();
        $artistRepository->shouldReceive('setChildrenEnabled')
            ->with(666, false)
            ->once();
        $artistRepository->shouldReceive('updateCounts')
            ->with(666)
            ->once();

        $this->bootDic($artistRepository, true);

        Artist::update_enabled(false, 666);
    }

    /**
     * The whole feature rests on this level, and nothing else stops a listener who posts the field by hand:
     * the guard is the only thing between them and a takedown they can undo
     */
    public function testUpdateEnabledWritesNothingBelowManager(): void
    {
        $artistRepository = $this->mock(ArtistRepositoryInterface::class);
        // the tell that the guard fired: neither the artist, nor its children, nor the counts are touched
        $artistRepository->shouldNotReceive('setField');
        $artistRepository->shouldNotReceive('setChildrenEnabled');
        $artistRepository->shouldNotReceive('updateCounts');

        $this->bootDic($artistRepository, false);

        Artist::update_enabled(false, 666);
    }

    private function bootDic(ArtistRepositoryInterface $artistRepository, bool $isManager): void
    {
        $privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, null)
            ->andReturn($isManager);

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->with(PrivilegeCheckerInterface::class)->andReturn($privilegeChecker);
        $dic->shouldReceive('get')->with(ArtistRepositoryInterface::class)->andReturn($artistRepository);

        $GLOBALS['dic'] = $dic;
    }
}
