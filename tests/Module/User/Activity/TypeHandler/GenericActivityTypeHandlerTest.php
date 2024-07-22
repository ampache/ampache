<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

class GenericActivityTypeHandlerTest extends MockeryTestCase
{
    /** @var MockInterface|UserActivityRepositoryInterface|null */
    private MockInterface $userActivityRepository;

    private ?GenericActivityTypeHandler $subject;

    protected function setUp(): void
    {
        $this->userActivityRepository = $this->mock(UserActivityRepositoryInterface::class);

        $this->subject = new GenericActivityTypeHandler(
            $this->userActivityRepository
        );
    }

    public function testRegisterActivityRegistersGenericEntry(): void
    {
        $objectId   = 666;
        $objectType = 'some-type';
        $action     = 'some-action';
        $userId     = 42;
        $date       = 123;

        $this->userActivityRepository->shouldReceive('registerGenericEntry')
            ->with(
                $userId,
                $action,
                $objectType,
                $objectId,
                $date
            )
            ->once();

        $this->subject->registerActivity(
            $objectId,
            $objectType,
            $action,
            $userId,
            $date
        );
    }
}
