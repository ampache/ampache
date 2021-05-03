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
 *
 */

declare(strict_types=1);

namespace Ampache\Repository\Model;

use Ampache\MockeryTestCase;
use Ampache\Module\Playback\PlaybackFactoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;

class ModelFactoryTest extends MockeryTestCase
{
    /** @var MockInterface|ContainerInterface */
    private MockInterface $dic;

    private ModelFactory $subject;

    public function setUp(): void
    {
        $this->dic = $this->mock(ContainerInterface::class);

        $this->subject = new ModelFactory(
            $this->dic
        );
    }

    public function testCreateLicenseReturnsLicense(): void
    {
        $this->dic->shouldReceive('get')
            ->with(LicenseRepositoryInterface::class)
            ->once()
            ->andReturn($this->mock(LicenseRepositoryInterface::class));

        $this->assertInstanceOf(
            License::class,
            $this->subject->createLicense()
        );
    }

    public function testCreateShareReturnsShare(): void
    {
        $this->dic->shouldReceive('get')
            ->with(ShareRepositoryInterface::class)
            ->once()
            ->andReturn($this->mock(ShareRepositoryInterface::class));
        $this->dic->shouldReceive('get')
            ->with(PlaybackFactoryInterface::class)
            ->once()
            ->andReturn($this->mock(PlaybackFactoryInterface::class));

        $this->assertInstanceOf(
            Share::class,
            $this->subject->createShare(666)
        );
    }
}
