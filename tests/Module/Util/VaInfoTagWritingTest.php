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

namespace Ampache\Module\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the formats tags can be written back into.
 *
 * Embedding a cover reads a file, builds its new tag block and hands it to the writer. The writer only knows four
 * extensions and silently ignores the rest, so a caller that does not ask first spends a full read on a file it
 * cannot tag — and, on a container that keeps its tags anywhere but id3v2, hands the writer nothing at all.
 */
class VaInfoTagWritingTest extends TestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function unwritableFiles(): array
    {
        return [
            ['/music/track.m4a'],
            ['/music/track.mp4'],
            ['/music/track.wma'],
            ['/music/track.ape'],
            ['/music/track.opus'],
            ['/music/track'],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function writableFiles(): array
    {
        return [
            ['/music/track.mp3'],
            ['/music/track.flac'],
            ['/music/track.oga'],
            ['/music/track.ogg'],
        ];
    }

    public function testAFileKeepingItsTagsInAnotherBlockOffersNoneToTheWriter(): void
    {
        $this->assertSame(
            [],
            VaInfo::existingTags(['fileformat' => 'mp4', 'tags' => ['quicktime' => ['title' => ['Hello']]]]),
            'a quicktime block cannot be written back as id3v2'
        );
    }

    public function testAFileWithNoTagAtAllOffersNoneToTheWriter(): void
    {
        $this->assertSame([], VaInfo::existingTags(['fileformat' => 'mp3']));
        $this->assertSame([], VaInfo::existingTags(['fileformat' => 'mp3', 'tags' => []]));
        $this->assertSame([], VaInfo::existingTags([]), 'an unreadable file reports no format either');
    }

    #[DataProvider('unwritableFiles')]
    public function testAFormatTheWriterIgnoresIsRefused(string $filename): void
    {
        $this->assertFalse(VaInfo::canWriteTags($filename));
    }

    #[DataProvider('writableFiles')]
    public function testAFormatTheWriterKnowsIsAccepted(string $filename): void
    {
        $this->assertTrue(VaInfo::canWriteTags($filename));
    }

    public function testTheExtensionIsReadWithoutRegardToCase(): void
    {
        $this->assertTrue(VaInfo::canWriteTags('/music/TRACK.MP3'));
        $this->assertTrue(VaInfo::canWriteTags('/music/Track.Flac'));
    }

    public function testTheTagsAlreadyOnTheFileAreTheOnesHandedBack(): void
    {
        $id3v2 = ['title' => ['Hello'], 'artist' => ['Someone']];
        $this->assertSame($id3v2, VaInfo::existingTags(['fileformat' => 'mp3', 'tags' => ['id3v2' => $id3v2]]));

        $vorbis = ['title' => ['Hello']];
        foreach (['flac', 'ogg'] as $fileformat) {
            $this->assertSame(
                $vorbis,
                VaInfo::existingTags(['fileformat' => $fileformat, 'tags' => ['vorbiscomment' => $vorbis]]),
                sprintf('%s keeps its tags in a vorbis comment', $fileformat)
            );
        }
    }
}
