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

namespace Ampache\Module\Preference;

use Ampache\MockeryTestCase;
use Ampache\Repository\PreferenceRepositoryInterface;
use Ampache\Repository\UserPreferenceRepositoryInterface;
use Mockery\MockInterface;

class UserPreferenceRetrieverTest extends MockeryTestCase
{
    /** @var PreferenceRepositoryInterface|MockInterface|null */
    private MockInterface $preferenceRepository;

    /** @var UserPreferenceRepositoryInterface|MockInterface|null */
    private MockInterface $userPreferenceRepository;

    private UserPreferenceRetriever $subject;

    public function setUp(): void
    {
        $this->preferenceRepository     = $this->mock(PreferenceRepositoryInterface::class);
        $this->userPreferenceRepository = $this->mock(UserPreferenceRepositoryInterface::class);

        $this->subject = new UserPreferenceRetriever(
            $this->preferenceRepository,
            $this->userPreferenceRepository
        );
    }

    public function testRetrieveReturnsValue(): void
    {
        $userId         = 666;
        $preferenceId   = 42;
        $preferenceName = 'some-name';
        $value          = 'some-value';

        $this->preferenceRepository->shouldReceive('getIdByName')
            ->with($preferenceName)
            ->once()
            ->andReturn($preferenceId);

        $this->userPreferenceRepository->shouldReceive('getByUserAndPreference')
            ->with($userId, $preferenceId)
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->retrieve($userId, $preferenceName)
        );
    }
}
