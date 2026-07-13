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
 */

namespace Ampache\Module\Art\Export\Writer;

use Ampache\Repository\Model\Art;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class MetadataWriterTest extends TestCase
{
    private MetadataWriter $subject;

    public function testWriteCreatesButLeavesEmptyFileWhenArtHasNoRawData(): void
    {
        $root     = vfsStream::setup();
        $art      = $this->createMock(Art::class);
        $art->raw = null;

        $filePath = $root->url() . '/art.jpg';

        $this->subject->write($art, $filePath, 'unused-file-name.jpg');

        static::assertTrue($root->hasChild('art.jpg'));
        static::assertSame('', file_get_contents($filePath));
    }

    public function testWriteWritesRawArtToDirNamePath(): void
    {
        $root     = vfsStream::setup();
        $art      = $this->createMock(Art::class);
        $art->raw = 'some-raw-bytes';

        $filePath = $root->url() . '/art.jpg';

        $this->subject->write($art, $filePath, 'unused-file-name.jpg');

        static::assertTrue($root->hasChild('art.jpg'));
        static::assertSame('some-raw-bytes', file_get_contents($filePath));
    }

    protected function setUp(): void
    {
        $this->subject = new MetadataWriter();
    }
}
