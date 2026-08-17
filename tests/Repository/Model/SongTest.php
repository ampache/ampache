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

namespace Ampache\Repository\Model;

use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

class SongTest extends MockeryTestCase
{
    /**
     * @return list<array{string, string}>
     */
    public static function customPlayArgProvider(): array
    {
        return [
            ['/music/Björk - Jóga [2019].flac', '/music/Björk - Jóga [2019].flac'],
            ['ogg;id;', 'oggid'],
            ['Grapevine"; id; #', 'Grapevine id '],
            ['/music/`id`.mp3', '/music/id.mp3'],
            ['/music/$(id).mp3', '/music/id.mp3'],
            ["Grapevine\nid", 'Grapevineid'],
            ['back\\slash', 'backslash'],
        ];
    }

    /**
     * A genre a user set by hand maps a second time, and a file can name the same genre twice, so the two lists held the
     * same set of genres while the scan reported a difference on every pass
     *
     * @return list<array{list<string>, list<string>}>
     */
    public static function duplicateTagProvider(): array
    {
        return [
            'file repeats a genre' => [
                ['EBM', 'Electro', 'Electronic', 'Industrial'],
                ['EBM', 'Electro', 'Electronic', 'Industrial', 'Industrial'],
            ],
            'file repeats a genre in another case' => [
                ['EBM', 'Electro', 'Electronic', 'Industrial'],
                ['EBM', 'Electro', 'Electronic', 'Industrial', 'industrial'],
            ],
            'a user mapped a genre the file also carries' => [
                ['EBM', 'Electro', 'Electronic', 'Industrial', 'Industrial'],
                ['EBM', 'Electro', 'Electronic', 'Industrial'],
            ],
        ];
    }

    /**
     * @param list<string> $tags
     * @param list<string> $new_tags
     */
    #[DataProvider('duplicateTagProvider')]
    public function testCompareSongInformationIgnoresRepeatedTags(array $tags, array $new_tags): void
    {
        $song        = new Song();
        $song->title = 'Grapevine';
        $song->time  = 195;
        $song->tags  = $this->tagRows($tags);

        $new_song        = new Song();
        $new_song->title = 'Grapevine';
        $new_song->time  = 195;
        $new_song->tags  = $this->tagRows($new_tags);

        $result = Song::compare_song_information($song, $new_song);

        $this->assertFalse($result['change']);
        $this->assertSame([], $result['element']);
    }

    public function testCompareSongInformationIgnoresWhitespaceOnlyStringChanges(): void
    {
        $song          = new Song();
        $song->title   = ' Grapevine ';
        $song->comment = "a  padded\tcomment";
        $song->time    = 195;

        $new_song          = new Song();
        $new_song->title   = 'Grapevine';
        $new_song->comment = 'a padded comment';
        $new_song->time    = 195;

        $result = Song::compare_song_information($song, $new_song);

        $this->assertFalse($result['change']);
        $this->assertSame([], $result['element']);
    }

    /**
     * `time` used to be compared as a string, so update_from_tags died on the first song whose length changed with
     * _clean_string_field_value(): Argument #1 ($value) must be of type ?string, int given
     */
    public function testCompareSongInformationReportsNumericFieldChange(): void
    {
        $song        = new Song();
        $song->title = 'I Heard It Through the Grapevine';
        $song->time  = 195;

        $new_song        = new Song();
        $new_song->title = 'I Heard It Through the Grapevine';
        $new_song->time  = 201;

        $result = Song::compare_song_information($song, $new_song);

        $this->assertTrue($result['change']);
        $this->assertSame(['time' => 'OLD:195 --> 201'], $result['element']);
    }

    public function testCompareSongInformationReportsStringFieldChange(): void
    {
        $song        = new Song();
        $song->title = 'Grapevine';
        $song->time  = 195;

        $new_song        = new Song();
        $new_song->title = 'I Heard It Through the Grapevine';
        $new_song->time  = 195;

        $result = Song::compare_song_information($song, $new_song);

        $this->assertTrue($result['change']);
        $this->assertSame(['title' => 'OLD: Grapevine --> I Heard It Through the Grapevine'], $result['element']);
    }

    public function testCompareSongInformationReportsTagChange(): void
    {
        $song        = new Song();
        $song->title = 'Grapevine';
        $song->time  = 195;
        $song->tags  = $this->tagRows(['EBM', 'Electro']);

        $new_song        = new Song();
        $new_song->title = 'Grapevine';
        $new_song->time  = 195;
        $new_song->tags  = $this->tagRows(['EBM', 'Electro', 'Industrial']);

        $result = Song::compare_song_information($song, $new_song);

        $this->assertTrue($result['change']);
        $this->assertSame(['tags' => 'OLD: EBM Electro --> EBM Electro Industrial'], $result['element']);
    }

    public function testIsCodecNameRejectsAnythingButABareWord(): void
    {
        $method = new ReflectionMethod(Song::class, '_is_codec_name');

        $this->assertTrue($method->invoke(null, 'ogg'));
        $this->assertTrue($method->invoke(null, 'pcm_s16le'));
        $this->assertFalse($method->invoke(null, 'ogg;id;'));
        $this->assertFalse($method->invoke(null, 'ogg id'));
        $this->assertFalse($method->invoke(null, ''));
    }

    public function testRunCustomPlayActionIgnoresAnIndexOutsideTheActionList(): void
    {
        $song = new Song();

        $this->assertSame([], $song->run_custom_play_action(-1));
        $this->assertSame([], $song->run_custom_play_action(0));
        $this->assertSame([], $song->run_custom_play_action(1));
    }

    #[DataProvider('customPlayArgProvider')]
    public function testScrubCustomPlayArgDropsShellMetacharacters(string $value, string $expected): void
    {
        $method = new ReflectionMethod(Song::class, '_scrub_custom_play_arg');

        $this->assertSame($expected, $method->invoke(null, $value));
    }

    /**
     * @param list<string> $names
     * @return list<array{id: int, name: string, is_hidden: int, count: int}>
     */
    private function tagRows(array $names): array
    {
        $rows = [];
        foreach ($names as $index => $name) {
            $rows[] = [
                'id' => $index + 1,
                'name' => $name,
                'is_hidden' => 0,
                'count' => 0,
            ];
        }

        return $rows;
    }
}
