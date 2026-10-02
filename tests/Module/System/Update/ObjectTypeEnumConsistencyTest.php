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

namespace Ampache\Module\System\Update;

use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\ObjectTypeEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// guards against the drift in docs/OBJECT-TYPE-ENUM-PLAN.md: a SQL object_type enum going stale, or a duplicate column
class ObjectTypeEnumConsistencyTest extends TestCase
{
    /** values written as literals, not sourced from either PHP enum - see docs/OBJECT-TYPE-ENUM-PLAN.md. @var list<string> */
    private const array NON_ENUM_LITERAL_VALUES = [
        'catalog',
    ];

    /** shared by bookmark/tmp_playlist/tmp_playlist_data/playlist_data - see migration 810025. @var list<string> */
    private const array PLAYABLE_MEDIA_TYPES = [
        'broadcast',
        'democratic',
        'live_stream',
        'podcast_episode',
        'song',
        'song_preview',
        'video',
    ];
    private const string SEED_FILE = __DIR__ . '/../../../../resources/sql/ampache.sql';

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function objectTypeExactListProvider(): array
    {
        $libraryItems    = array_map(static fn(LibraryItemEnum $case): string => $case->value, LibraryItemEnum::cases());
        $genericTaggable = ['album', 'album_disk', 'artist', 'catalog', 'collection', 'folder', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video'];

        return [
            'album_map' => ['album_map', ['album', 'song']],
            'artist_map' => ['artist_map', ['album', 'song']],
            'catalog_map' => ['catalog_map', ['album', 'album_disk', 'artist', 'song_artist', 'album_artist', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'video']],
            'folder_map' => ['folder_map', ['folder', 'song', 'podcast_episode', 'video']],
            'collection_map' => ['collection_map', ['album', 'album_disk', 'artist', 'folder', 'genre', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'video']],
            'collection' => ['collection', ['album', 'album_disk', 'artist', 'folder', 'genre', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'video']],
            'playlist_folder_map' => ['playlist_folder_map', ['playlist', 'search', 'collection']],
            'recommendation' => ['recommendation', ['song', 'artist']],
            'user_shout' => ['user_shout', $libraryItems],
            'bookmark' => ['bookmark', self::PLAYABLE_MEDIA_TYPES],
            'tmp_playlist' => ['tmp_playlist', self::PLAYABLE_MEDIA_TYPES],
            'tmp_playlist_data' => ['tmp_playlist_data', self::PLAYABLE_MEDIA_TYPES],
            'playlist_data' => ['playlist_data', self::PLAYABLE_MEDIA_TYPES],
            'mood_map' => ['mood_map', ['album', 'album_disk', 'artist', 'podcast', 'podcast_episode', 'song', 'video']],
            'share' => ['share', ['album', 'album_disk', 'artist', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'video']],
            'user_playlist' => ['user_playlist', ['song', 'live_stream', 'video', 'podcast_episode']],
            'cache_object_count' => ['cache_object_count', $genericTaggable],
            'cache_object_count_run' => ['cache_object_count_run', $genericTaggable],
            'object_count' => ['object_count', $genericTaggable],
            'rating' => ['rating', $genericTaggable],
            'user_flag' => ['user_flag', $genericTaggable],
            'image' => ['image', [...$genericTaggable, 'wanted']],
            'tag_map' => ['tag_map', [...array_diff($genericTaggable, ['collection']), 'broadcast']],
            'user_activity' => ['user_activity', array_diff($genericTaggable, ['collection'])],
        ];
    }

    public function testEveryObjectTypeEnumValueIsAKnownType(): void
    {
        $known = [
            ...array_map(static fn(LibraryItemEnum $case): string => $case->value, LibraryItemEnum::cases()),
            ...array_map(static fn(ObjectTypeEnum $case): string => $case->value, ObjectTypeEnum::cases()),
            ...self::NON_ENUM_LITERAL_VALUES,
        ];

        foreach ($this->parseTables() as $table => $columns) {
            foreach ($columns as $column) {
                if ($column['name'] !== 'object_type') {
                    continue;
                }

                foreach ($this->enumValues($column['type']) ?? [] as $value) {
                    self::assertContains(
                        $value,
                        $known,
                        sprintf(
                            '`%s`.`object_type` lists `%s`, which is not a case of LibraryItemEnum or '
                            . 'ObjectTypeEnum and is not in NON_ENUM_LITERAL_VALUES - either it is a dead '
                            . 'value (like `tvshow`/`tvshow_season` were) or this allowlist needs updating',
                            $table,
                            $value
                        )
                    );
                }
            }
        }
    }

    public function testNoTableDeclaresAColumnMoreThanOnce(): void
    {
        foreach ($this->parseTables() as $table => $columns) {
            $names  = array_map(static fn(array $column): string => $column['name'], $columns);
            $counts = array_count_values($names);
            $dupes  = array_keys(array_filter($counts, static fn(int $count): bool => $count > 1));

            self::assertSame(
                [],
                $dupes,
                sprintf('`%s` declares these columns more than once: %s', $table, implode(', ', $dupes))
            );
        }
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('objectTypeExactListProvider')]
    public function testObjectTypeEnumMatchesVerifiedList(string $table, array $expected): void
    {
        $columns = $this->parseTables()[$table] ?? null;
        self::assertNotNull($columns, sprintf('`%s` is not declared in the schema seed', $table));

        $actual = null;
        foreach ($columns as $column) {
            if ($column['name'] === 'object_type') {
                $actual = $this->enumValues($column['type']);
            }
        }

        self::assertNotNull(
            $actual,
            sprintf('`%s`.`object_type` is not declared as an `enum` in the schema seed', $table)
        );

        sort($expected);
        sort($actual);

        self::assertSame(
            $expected,
            $actual,
            sprintf(
                '`%s`.`object_type`\'s enum no longer matches its verified value list - see docs/OBJECT-TYPE-ENUM-PLAN.md. '
                . 'If this is a deliberate change, update both the migration that adds the new value everywhere it belongs '
                . 'and this test.',
                $table
            )
        );
    }

    /**
     * @return ?list<string>
     */
    private function enumValues(string $columnType): ?array
    {
        if (!preg_match('/^enum\(([^)]*)\)/', $columnType, $match)) {
            return null;
        }

        preg_match_all("/'([^']*)'/", $match[1], $values);

        return $values[1];
    }

    /**
     * @return array<string, list<array{name: string, type: string}>>
     */
    private function parseTables(): array
    {
        $tables = [];
        $table  = null;

        foreach (file(self::SEED_FILE, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match('/^CREATE TABLE IF NOT EXISTS `(\w+)` \(/', $line, $created)) {
                $table          = $created[1];
                $tables[$table] = [];
                continue;
            }

            if ($table === null) {
                continue;
            }

            if (preg_match('/^\) ENGINE=/', $line)) {
                $table = null;
                continue;
            }

            // a column line always starts with a backtick identifier; KEY/PRIMARY KEY/CONSTRAINT lines don't
            if (preg_match('/^\s*`(\w+)`\s+(.+?),?$/', $line, $declared)) {
                $tables[$table][] = ['name' => $declared[1], 'type' => $declared[2]];
            }
        }

        return $tables;
    }
}
