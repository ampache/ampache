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

namespace Ampache\Module\Api\Output;

use Ampache\MockeryTestCase;
use Ampache\Module\Util\XmlWriterInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;

class ApiOutputFactoryTest extends MockeryTestCase
{
    /** @var ApiOutputFactory|null */
    private ApiOutputFactory $subject;

    public function setUp(): void
    {
        $this->subject = new ApiOutputFactory(
            $this->mock(ModelFactoryInterface::class),
            $this->mock(AlbumRepositoryInterface::class),
            $this->mock(SongRepositoryInterface::class),
            $this->mock(XmlWriterInterface::class)
        );
    }

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
}
