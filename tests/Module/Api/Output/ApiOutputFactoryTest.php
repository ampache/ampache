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

namespace Ampache\Module\Api\Output;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Override;

class ApiOutputFactoryTest extends MockeryTestCase
{
    private ApiOutputFactory $subject;

    public function testCreateJsonOutputReturnsInstance(): void
    {
        $this->assertInstanceOf(
            JsonOutput::class,
            $this->subject->createJsonOutput()
        );
    }

    public function testCreateXmlOutputReturnsInstance(): void
    {
        $this->assertInstanceOf(
            XmlOutput::class,
            $this->subject->createXmlOutput()
        );
    }

    #[Override]
    protected function setUp(): void
    {
        // the formatters are final, so the factory gets real ones built from mocked repositories
        $this->subject = new ApiOutputFactory(
            new Json5_Data(
                $this->mock(AlbumRepositoryInterface::class),
                $this->mock(BookmarkRepositoryInterface::class),
                $this->mock(LabelRepositoryInterface::class),
                $this->mock(LicenseRepositoryInterface::class),
                $this->mock(PodcastRepositoryInterface::class),
                $this->mock(SongRepositoryInterface::class),
            ),
            new Xml5_Data(
                $this->mock(AlbumRepositoryInterface::class),
                $this->mock(BookmarkRepositoryInterface::class),
                $this->mock(LabelRepositoryInterface::class),
                $this->mock(LicenseRepositoryInterface::class),
                $this->mock(PodcastRepositoryInterface::class),
                $this->mock(SongRepositoryInterface::class),
            )
        );
    }
}
