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

namespace Ampache\Module\Api;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * NOTE: only covers normalizeAction()/normalizeType(), which are pure
 * functions of their arguments -- handle() itself needs a real PSR-7
 * request/Gatekeeper flow that isn't in scope here.
 */
class ApiHandlerTest extends TestCase
{
    private ApiHandler $subject;

    /**
     * The REST rewrite hands over (action, type, hasFilter) triples; these are the album_disk paths
     *
     * @return array<string, array{0: string, 1: string|null, 2: bool, 3: string}>
     */
    public static function albumDiskPathProvider(): array
    {
        return [
            // `albums/{album_id}/disks`
            'album disks' => ['disks', 'album', true, 'album_disks'],
            // `album-disks/{album_disk_id}` -- must not resolve to the album-scoped listing
            'single album disk' => ['album_disks', null, true, 'album_disk'],
            // `album-disks/{album_disk_id}/songs`
            'album disk songs' => ['songs', 'album_disk', true, 'album_disk_songs'],
            // `album_disks` with no id keeps the album-scoped listing action
            'album disk listing' => ['album_disks', null, false, 'album_disks'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function albumDiskTypeAliasProvider(): array
    {
        return [
            'plural' => ['album_disks'],
            'dashed plural' => ['album-disks'],
            'dashed singular' => ['album-disk'],
            'canonical' => ['album_disk'],
        ];
    }

    /**
     * REST path suffix => the `task` the REST applications derive from it
     *
     * @return array<string, array{0: string}>
     */
    public static function catalogTaskProvider(): array
    {
        return [
            'add => add_to_catalog' => ['add'],
            'clean => clean_catalog' => ['clean'],
            'update => update_catalog' => ['update'],
            'verify => verify_catalog' => ['verify'],
        ];
    }

    /**
     * REST spells multi-word names with a dash; a single rule folds them, so these need no aliases.
     * Guards the alias arms that rule replaced.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function dashedActionProvider(): array
    {
        return [
            'add-song' => ['add-song', 'add_song'],
            'bookmark-create' => ['bookmark-create', 'bookmark_create'],
            'delete-all' => ['delete-all', 'delete_all'],
            'deleted-songs' => ['deleted-songs', 'deleted_songs'],
            'deleted-videos' => ['deleted-videos', 'deleted_videos'],
            'deleted-podcast-episodes' => ['deleted-podcast-episodes', 'deleted_podcast_episodes'],
            'friends-timeline' => ['friends-timeline', 'friends_timeline'],
            'get-art' => ['get-art', 'get_art'],
            'last-shouts' => ['last-shouts', 'last_shouts'],
            'live-streams' => ['live-streams', 'live_streams'],
            'now-playing' => ['now-playing', 'now_playing'],
            'playlist-create' => ['playlist-create', 'playlist_create'],
            'playlist-generate' => ['playlist-generate', 'playlist_generate'],
            'playlists-generate' => ['playlists-generate', 'playlist_generate'],
            'podcast-episodes' => ['podcast-episodes', 'podcast_episodes'],
            'record-play' => ['record-play', 'record_play'],
            'remove-song' => ['remove-song', 'remove_song'],
            'search-songs' => ['search-songs', 'search_songs'],
            'system-preferences' => ['system-preferences', 'system_preferences'],
            'update-art' => ['update-art', 'update_art'],
            'url-to-song' => ['url-to-song', 'url_to_song'],
            'volume-down' => ['volume-down', 'volume_down'],
            'volume-mute' => ['volume-mute', 'volume_mute'],
            'volume-up' => ['volume-up', 'volume_up'],
            'get-similar_artists' => ['get-similar_artists', 'get_similar'],
            'get-similar_songs' => ['get-similar_songs', 'get_similar'],
            // renames whose dashed REST spelling does not match the RPC name at all
            'fetch-info' => ['fetch-info', 'update_artist_info'],
            'fetch-metadata' => ['fetch-metadata', 'get_external_metadata'],
            'update-tags' => ['update-tags', 'update_from_tags'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function dashedTypeProvider(): array
    {
        return [
            'album-artists' => ['album-artists', 'album_artist'],
            'album-artist' => ['album-artist', 'album_artist'],
            'live-streams' => ['live-streams', 'live_stream'],
            'live-stream' => ['live-stream', 'live_stream'],
            'podcast-episodes' => ['podcast-episodes', 'podcast_episode'],
            'podcast-episode' => ['podcast-episode', 'podcast_episode'],
            'song-artists' => ['song-artists', 'song_artist'],
            'song-artist' => ['song-artist', 'song_artist'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function deletedAliasProvider(): array
    {
        return [
            'songs' => ['deleted-songs', 'deleted_songs'],
            'videos' => ['deleted-videos', 'deleted_videos'],
            'podcast episodes' => ['deleted-podcast-episodes', 'deleted_podcast_episodes'],
        ];
    }

    public static function shareableTypeProvider(): array
    {
        return [
            'album' => ['album'],
            'artist' => ['artist'],
            'playlist' => ['playlist'],
            'smartlist' => ['smartlist'],
            'podcast' => ['podcast'],
            'podcast_episode' => ['podcast_episode'],
            'song' => ['song'],
            'video' => ['video'],
        ];
    }

    #[DataProvider(methodName: 'dashedActionProvider')]
    public function testNormalizeActionFoldsTheDashedRestSpelling(string $action, string $expected): void
    {
        static::assertSame(
            $expected,
            $this->subject->normalizeAction($action, null, false)
        );
    }

    public function testNormalizeActionLeavesShareUnchangedForNonShareableTypes(): void
    {
        static::assertSame(
            'share',
            $this->subject->normalizeAction('share', 'label', true)
        );
    }

    public function testNormalizeActionLeavesShareUnchangedWithoutType(): void
    {
        static::assertSame(
            'share',
            $this->subject->normalizeAction('share', null, true)
        );
    }

    public function testNormalizeActionLeavesUpdateUnchangedWithoutType(): void
    {
        // plain `update` (no type) is the system_update alias and must not be remapped
        static::assertSame(
            'update',
            $this->subject->normalizeAction('update', null, false)
        );
    }

    #[DataProvider(methodName: 'albumDiskPathProvider')]
    public function testNormalizeActionResolvesAlbumDiskPaths(
        string $action,
        ?string $type,
        bool $hasFilter,
        string $expected,
    ): void {
        static::assertSame(
            $expected,
            $this->subject->normalizeAction($action, $type, $hasFilter)
        );
    }

    #[DataProvider(methodName: 'catalogTaskProvider')]
    public function testNormalizeActionRoutesCatalogTaskShortcutsToCatalogAction(string $action): void
    {
        static::assertSame(
            'catalog_action',
            $this->subject->normalizeAction($action, 'catalog', true)
        );
    }

    /**
     * The canonical REST path is `{type}/deleted`, but the flat hyphenated resource name is a plausible client
     * guess and every other hyphenated alias is accepted, so it has to resolve to the same action.
     */
    #[DataProvider(methodName: 'deletedAliasProvider')]
    public function testNormalizeActionRoutesHyphenatedDeletedAliases(string $action, string $expected): void
    {
        static::assertSame(
            $expected,
            $this->subject->normalizeAction($action, null, false)
        );
    }

    #[DataProvider(methodName: 'shareableTypeProvider')]
    public function testNormalizeActionRoutesRestShareToShareCreate(string $type): void
    {
        static::assertSame(
            'share_create',
            $this->subject->normalizeAction('share', $type, true)
        );
    }

    public function testNormalizeActionStillRoutesCatalogAddWithoutFilterToCatalogCreate(): void
    {
        static::assertSame(
            'catalog_create',
            $this->subject->normalizeAction('add', 'catalog', false)
        );
    }

    public function testNormalizeActionStillRoutesCatalogCreateToCatalogCreate(): void
    {
        static::assertSame(
            'catalog_create',
            $this->subject->normalizeAction('create', 'catalog', true)
        );
    }

    public function testNormalizeActionStillRoutesLegacyCreateToShareCreate(): void
    {
        // preserved in case anything still relies on the literal 'create' segment
        static::assertSame(
            'share_create',
            $this->subject->normalizeAction('create', 'album', true)
        );
    }

    #[DataProvider(methodName: 'dashedTypeProvider')]
    public function testNormalizeTypeFoldsTheDashedRestSpelling(string $type, string $expected): void
    {
        static::assertSame(
            $expected,
            $this->subject->normalizeType($type)
        );
    }

    #[DataProvider(methodName: 'albumDiskTypeAliasProvider')]
    public function testNormalizeTypeResolvesAlbumDiskAliases(string $type): void
    {
        static::assertSame(
            'album_disk',
            $this->subject->normalizeType($type)
        );
    }

    protected function setUp(): void
    {
        $this->subject = new ApiHandler(
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ConfigContainerInterface::class),
            $this->createMock(NetworkCheckerInterface::class),
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(ContainerInterface::class),
        );
    }
}
