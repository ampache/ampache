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

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\Wanted\MissingArtistRetrieverInterface;
use Ampache\Plugin\PluginSongPreviewInterface;
use Ampache\Repository\SongPreviewRepositoryInterface;

class Song_Preview extends database_object implements Media, displayable_item, container_item
{
    protected const string DB_TABLENAME = 'song_preview';

    public ?string $album_mbid  = null;
    public ?int $artist         = null; // artist.id (Int)
    public ?string $artist_mbid = null;
    public ?int $disk           = null;
    public bool $enabled        = true;
    public ?string $file        = null;
    public int $id              = 0;
    public ?string $link        = null;
    public ?string $mbid        = null; // MusicBrainz ID
    public string $mime;
    public ?string $session = null;
    public ?string $title   = null;
    public ?int $track      = null;
    public string $type;
    private ?string $f_album      = null;
    private ?string $f_album_link = null;
    private ?string $f_link       = null;

    /**
     * Constructor
     *
     * Song Preview class
     */
    public function __construct(
        ?int $object_id = 0,
        ?string $album_name = null,
    ) {
        if (!$object_id) {
            return;
        }

        $info = $this->has_info($object_id);
        if ($info === []) {
            return;
        }

        $this->album_mbid  = $info['album_mbid'] ?? null;
        $this->artist      = isset($info['artist']) ? (int) $info['artist'] : null;
        $this->artist_mbid = $info['artist_mbid'] ?? null;
        $this->disk        = isset($info['disk']) ? (int) $info['disk'] : null;
        $this->enabled     = (bool) ($info['enabled'] ?? true);
        $this->file        = $info['file'] ?? null;
        $this->id          = (int) ($info['id'] ?? 0);
        $this->link        = $info['link'] ?? null;
        $this->mbid        = $info['mbid'] ?? null;
        $this->session     = $info['session'] ?? null;
        $this->title       = $info['title'] ?? null;
        $this->track       = isset($info['track']) ? (int) $info['track'] : null;

        if ($this->file) {
            $data       = pathinfo($this->file);
            $this->type = (isset($data['extension']))
                ? strtolower($data['extension'])
                : 'mp3';
            $this->mime = Song::type_to_mime($this->type);
        }

        if ($album_name) {
            $this->f_album = $album_name;
        }
    }

    /**
     * build_cache
     *
     * This attempts to reduce queries by asking for everything in the
     * browse all at once and storing it in the cache, this can help if the
     * db connection is the slow point.
     * @param array<int|string> $song_ids
     */
    public static function build_cache(array $song_ids): bool
    {
        if (empty($song_ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        $artists = [];
        foreach (self::getSongPreviewRepository()->getRows(array_values($song_ids)) as $row) {
            parent::add_to_cache('song_preview', $row['id'], $row);
            if ($row['artist']) {
                $artists[] = (int) $row['artist'];
            }
        }

        Artist::build_cache($artists);

        return true;
    }

    /**
     * garbage_collection
     */
    public static function garbage_collection(): void
    {
        self::getSongPreviewRepository()->collectGarbage();
    }

    /**
     * get_song_previews
     * @return Song_Preview[]
     */
    public static function get_song_previews(string $album_mbid): array
    {
        $songs = [];
        foreach (self::getSongPreviewRepository()->findIdsBySession((string) session_id(), $album_mbid) as $previewId) {
            $songs[] = new Song_Preview($previewId);
        }

        return $songs;
    }

    /**
     * insert
     *
     * This inserts the song preview described by the passed array
     * @param array<string, mixed> $results
     */
    public static function insert(array $results): ?int
    {
        if ((int) $results['disk'] == 0) {
            $results['disk'] = Album::sanitize_disk($results['disk']);
        }

        if ((int) $results['track'] == 0) {
            $results['disk']  = Album::sanitize_disk($results['track'][0]);
            $results['track'] = substr((string) $results['track'], 1);
        }

        return self::getSongPreviewRepository()->insert($results);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getSongPreviewRepository(): SongPreviewRepositoryInterface
    {
        global $dic;

        return $dic->get(SongPreviewRepositoryInterface::class);
    }

    public function check_play_history(int $user, string $agent, int $date): bool
    {
        // Do nothing
        unset($user, $agent, $date);

        return false;
    }

    public function display_art(array $size, bool $force = false): void
    {
        // Do nothing, song previews don't have art}
        unset($size, $force);
    }

    public function get_default_art_kind(): string
    {
        return 'default';
    }

    public function get_description(): string
    {
        return '';
    }

    /**
     * Get item get_f_album_link.
     */
    public function get_f_album_link(): ?string
    {
        if ($this->f_album_link === null && $this->f_album !== null) {
            $this->f_album_link = "<a href=\"" . AmpConfig::get_web_path() . "/albums.php?action=show_missing&mbid=" . $this->album_mbid . "&;artist=" . $this->artist . "\" title=\"" . $this->f_album . "\">" . $this->f_album . "</a>";
        }

        return $this->f_album_link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . scrub_out($this->get_link()) . "\" title=\"" . scrub_out($this->get_parent_fullname()) . " - " . scrub_out($this->title) . "\"> " . scrub_out($title ?? $this->title) . "</a>";
        }

        return $this->f_link;
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        if ($this->artist) {
            return "<a href=\"" . AmpConfig::get_web_path() . "/artists.php?action=show&artist=" . $this->artist . "\" title=\"" . scrub_out($this->get_parent_fullname()) . "\"> " . scrub_out($this->get_parent_fullname()) . "</a>";
        }
        $wartist = $this->getMissingArtistRetriever()->retrieve((string) $this->artist_mbid);

        return $wartist['link'] ?? '';
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * Get item fullname.
     */
    public function get_fullname(): ?string
    {
        return $this->title;
    }

    public function get_keywords(): array
    {
        return [];
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $this->link = "#";
        }

        return $this->link ?? '';
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if ($filter_type === null || $filter_type === 'song_preview') {
            $medias[] = ['object_type' => LibraryItemEnum::SONG_PREVIEW, 'object_id' => $this->id];
        }

        return $medias;
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        // Wanted album is not part of the library, cannot return it.
        return null;
    }

    /**
     * get_parent_fullname
     * gets the name of $this->artist, allows passing of id
     */
    public function get_parent_fullname(): string
    {
        if ($this->artist) {
            return (string) (new Artist($this->artist)->get_fullname());
        }
        $wartist = $this->getMissingArtistRetriever()->retrieve((string) $this->artist_mbid);

        return $wartist['name'] ?? '';
    }

    /**
     * get_stream_name
     */
    public function get_stream_name(): string
    {
        return (string) $this->title;
    }

    /**
     * get_stream_types
     * @return list<string>
     */
    public function get_stream_types(?string $player = null): array
    {
        return ['native'];
    }

    /**
     * get_transcode_settings
     *
     * FIXME: Song Preview transcoding is not implemented
     * @param array{bitrate?: float|int, maxbitrate?: int, subtitle?: string, resolution?: string, quality?: int, frame?: float, duration?: float} $options
     * @return array{format?: string, command?: string}
     */
    public function get_transcode_settings(?string $target = null, ?string $player = null, array $options = []): array
    {
        return [];
    }

    public function get_user_owner(): ?int
    {
        return null;
    }

    /**
     * Returns the filename of the media-item
     */
    public function getFileName(): string
    {
        return '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::SONG_PREVIEW;
    }

    /**
     * getYear
     */
    public function getYear(): string
    {
        return '';
    }

    public function has_art(): bool
    {
        return false;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * stream URL taking into account the downsampling mojo and everything
     * else, this is the true function
     */
    public function play_url(string $additional_params = '', string $player = '', bool $local = false): string
    {
        $user_id = (Core::get_global('user') instanceof User)
            ? (string) Core::get_global('user')->getId()
            : '-1';
        $type      = $this->type;
        $song_name = rawurlencode($this->get_parent_fullname() . " - " . $this->title . "." . $type);
        $url       = Stream::get_base_url($local) . "type=song_preview&oid=" . $this->id . "&uid=" . $user_id . "&name=" . $song_name;

        return Stream_Url::format($url . $additional_params);
    }

    public function remove(): bool
    {
        return true;
    }

    /**
     * @param array{
     *     latitude?: float,
     *     longitude?: float,
     *     name?: string
     * } $location
     */
    public function set_played(int $user_id, string $agent, array $location, int $date): bool
    {
        // Do nothing
        unset($user_id, $agent, $location, $date);

        return false;
    }

    /**
     * stream
     */
    public function stream(): void
    {
        $user = Core::get_global('user');
        if (!$user instanceof User || empty($this->file)) {
            return;
        }

        foreach (Plugin::get_plugins(PluginTypeEnum::SONG_PREVIEW_STREAM_PROVIDER) as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->_plugin instanceof PluginSongPreviewInterface && $plugin->load($user)) {
                $plugin->_plugin->stream_song_preview($this->file);
            }
        }
    }

    public function update(array $data): ?int
    {
        return null;
    }

    /**
     * @deprecated inject dependency
     */
    private function getMissingArtistRetriever(): MissingArtistRetrieverInterface
    {
        global $dic;

        return $dic->get(MissingArtistRetrieverInterface::class);
    }

    /**
     * has_info
     */
    private function has_info(?int $preview_id = 0): array
    {
        if ($preview_id === null) {
            return [];
        }

        if (parent::is_cached('song_preview', $preview_id)) {
            return parent::get_from_cache('song_preview', $preview_id);
        }

        $repository = self::getSongPreviewRepository();
        $results    = $repository->getRow($preview_id);
        if (!empty($results['id'])) {
            if (empty($results['artist_mbid'])) {
                $results['artist_mbid'] = $repository->findArtistMbid((int) $results['artist']);
            }

            parent::add_to_cache('song_preview', $preview_id, $results);

            return $results;
        }

        return [];
    }
}
