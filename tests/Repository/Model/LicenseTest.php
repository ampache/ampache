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
use Ampache\Repository\LicenseRepositoryInterface;
use Mockery\MockInterface;

class LicenseTest extends MockeryTestCase
{
    /** @var MockInterface|LicenseRepositoryInterface */
    private MockInterface $licenseRepository;

    public function setUp(): void
    {
        $this->licenseRepository = $this->mock(LicenseRepositoryInterface::class);
    }

    public function testIsNewReturnsFalseIfNotNew(): void
    {
        $licenseId = 666;

        $this->licenseRepository->shouldReceive('getDataById')
            ->with($licenseId)
            ->once()
            ->andReturn(['id' => $licenseId]);

        $this->assertFalse(
            $this->createInstance($licenseId)->isNew()
        );
    }

    public function testIsNewReturnsTrueIfIsNew(): void
    {
        $licenseId = 666;

        $this->licenseRepository->shouldReceive('getDataById')
            ->with($licenseId)
            ->once()
            ->andReturn([]);

        $this->assertTrue(
            $this->createInstance($licenseId)->isNew()
        );
    }

    public function testGetLinkFormattedReturnsNameIfLinkisMissing(): void
    {
        $licenseId = 666;
        $name      = 'some-value';

        $this->licenseRepository->shouldReceive('getDataById')
            ->with($licenseId)
            ->once()
            ->andReturn(['name' => $name]);

        $this->assertSame(
            $name,
            $this->createInstance($licenseId)->getLinkFormatted()
        );
    }

    public function testGetLinkFormattedReturnsValue(): void
    {
        $licenseId = 666;
        $name      = 'some-value';
        $link      = 'some-value';

        $this->licenseRepository->shouldReceive('getDataById')
            ->with($licenseId)
            ->once()
            ->andReturn(['name' => $name, 'external_link' => $link]);

        $this->assertSame(
            sprintf(
                '<a href="%s">%s</a>',
                $link,
                $name
            ),
            $this->createInstance($licenseId)->getLinkFormatted()
        );
    }

    public function testGetNameReturnsValue(): void
    {
        $licenseId = 666;
        $name      = 'some-value';

        $this->licenseRepository->shouldReceive('getDataById')
            ->with($licenseId)
            ->once()
            ->andReturn(['name' => $name]);

        $this->assertSame(
            $name,
            $this->createInstance($licenseId)->getName()
        );
    }

    public function testGetLinkReturnsValue(): void
    {
        $licenseId = 666;
        $value     = 'some-value';

        $this->licenseRepository->shouldReceive('getDataById')
            ->with($licenseId)
            ->once()
            ->andReturn(['external_link' => $value]);

        $this->assertSame(
            $value,
            $this->createInstance($licenseId)->getLink()
        );
    }

    public function testGetDscriptionReturnsValue(): void
    {
        $licenseId = 666;
        $value     = 'some-value';

        $this->licenseRepository->shouldReceive('getDataById')
            ->with($licenseId)
            ->once()
            ->andReturn(['description' => $value]);

        $this->assertSame(
            $value,
            $this->createInstance($licenseId)->getDescription()
        );
    }

    private function createInstance(int $id): License
    {
        return new License(
            $this->licenseRepository,
            $id
        );
    }
}
