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

namespace Ampache\Module\User\Activity\TypeHandler;

use Ampache\MockeryTestCase;
use Ampache\Repository\UserActivityRepositoryInterface;
use Mockery\MockInterface;
use Override;

class ActivityTypeHandlerMapperTest extends MockeryTestCase
{
    private ?ActivityTypeHandlerMapper $subject;
    private UserActivityRepositoryInterface|MockInterface|null $userActivityRepository;

    public function testMapReturnsGenericEntry(): void
    {
        $this->assertInstanceOf(
            GenericActivityTypeHandler::class,
            $this->subject->map('foobar')
        );
    }

    public function testMapReturnsSpecificEntry(): void
    {
        $this->assertInstanceOf(
            SongActivityTypeHandler::class,
            $this->subject->map(ActivityTypeEnum::TYPE_SONG)
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->userActivityRepository = $this->mock(UserActivityRepositoryInterface::class);

        $this->subject = new ActivityTypeHandlerMapper(
            $this->userActivityRepository
        );
    }
}
