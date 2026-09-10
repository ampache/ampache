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

namespace Ampache\Module\Statistics;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Database\database_object;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Repository\UserflagRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use ReflectionProperty;

class UserflagTest extends MockeryTestCase
{
    private UserActivityPosterInterface&MockInterface $activityPoster;
    private UserflagRepositoryInterface&MockInterface $userflagRepository;

    public function testSetFlagClampsAFutureDateToNow(): void
    {
        // a client-supplied date in 2106 used to pin the favourite to the top of every newest list forever
        $this->primeFlag('album', 42, 7, false);

        $isNow = Mockery::on(static fn(int $date): bool => $date <= time() && $date > time() - 5);

        $this->activityPoster->shouldReceive('post')
            ->with(7, 'userflag', 'album', 42, $isNow)
            ->once();
        $this->userflagRepository->shouldReceive('adjustWeight')
            ->with('album', 42, 1)
            ->once();
        $this->userflagRepository->shouldReceive('setFlag')
            ->with(42, 'album', 7, $isNow)
            ->once();

        $flag = new Userflag(42, 'album');

        self::assertTrue($flag->set_flag(true, 7, 4294967295));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->userflagRepository = $this->mock(UserflagRepositoryInterface::class);
        $this->activityPoster     = $this->mock(UserActivityPosterInterface::class);

        $logger = new NullLogger();

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')
            ->andReturnUsing(fn(string $id) => match ($id) {
                UserflagRepositoryInterface::class => $this->userflagRepository,
                UserActivityPosterInterface::class => $this->activityPoster,
                default => $logger,
            });

        $GLOBALS['dic'] = $dic;
    }

    private function primeFlag(string $type, int $objectId, int $userId, bool $value): void
    {
        AmpConfig::set('memory_cache', true, true);
        new ReflectionProperty(database_object::class, '_enabled')->setValue(null, null);
        Userflag::add_to_cache('userflag_' . $type . '_user' . $userId, $objectId, [$value]);
    }
}
