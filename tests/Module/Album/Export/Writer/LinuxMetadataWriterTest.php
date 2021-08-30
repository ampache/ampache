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

namespace Ampache\Module\Album\Export\Writer;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Album;
use org\bovigo\vfs\vfsStream;

class LinuxMetadataWriterTest extends MockeryTestCase
{
    private ?LinuxMetadataWriter $subject;

    public function setUp(): void
    {
        $this->subject = new LinuxMetadataWriter();
    }

    public function testWriteWritesData(): void
    {
        $album = $this->mock(Album::class);

        $dir  = vfsStream::setup();
        $file = vfsStream::newFile('.directory');

        $dir->addChild($file);

        $dirName      = $dir->url();
        $iconFileName = 'some-file-name';
        $f_name       = 'some-full-name';

        $album->f_name = $f_name;

        $this->subject->write(
            $album,
            $dirName,
            $iconFileName
        );

        $this->assertSame(
            sprintf(
                "Name=%s\nIcon=%s",
                $f_name,
                $iconFileName
            ),
            $file->getContent()
        );
    }
}
