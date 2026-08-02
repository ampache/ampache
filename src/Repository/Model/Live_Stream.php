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
use Ampache\Module\Art\Art;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\database_object;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Repository\LiveStreamRepositoryInterface;

/**
 * Radio Class
 *
 * This handles the internet radio stuff, that is inserted into live_stream
 * this can include podcasts or what-have-you
 *
 */
class Live_Stream extends database_object implements Media, displayable_item, container_item, CatalogItemInterface, ModelInterface
{
    protected const string DB_TABLENAME = 'live_stream';

    public int $catalog;
    public ?string $codec = null;
    public int $genre;

    /* DB based variables */
    public int $id           = 0;
    public ?string $link     = null;
    public ?string $name     = null;
    public ?string $site_url = null;
    public ?string $url      = null;
    private ?string $f_link  = null;

    /**
     * Constructor
     * This takes a flagged. id and then pulls in the information for said flag entry
     */
    public function __construct(?int $stream_id = 0)
    {
        if (!$stream_id) {
            return;
        }

        $info           = $this->get_info($stream_id, static::DB_TABLENAME);
        $this->id       = (int) ($info['id'] ?? 0);
        $this->catalog  = (int) ($info['catalog'] ?? 0);
        $this->genre    = (int) ($info['genre'] ?? 0);
        $this->name     = $info['name'] ?? null;
        $this->codec    = $info['codec'] ?? null;
        $this->site_url = $info['site_url'] ?? null;
        $this->url      = $info['url'] ?? null;
    }

    /**
     * create
     * This is a static function that takes a key'd array for input
     * and if everything is good creates the object.
     */
    public static function create(array $data): ?string
    {
        // Make sure we've got a name and codec
        if ((string) $data['name'] === '') {
            AmpError::add('name', T_('Name is required'));
        }

        if ((string) $data['codec'] === '') {
            AmpError::add('codec', T_('Codec is required (e.g. MP3, OGG...)'));
        }

        $allowed_array = [
            'https',
            'http',
            'mms',
            'mmsh',
            'mmsu',
            'mmst',
            'rtsp',
            'rtmp',
        ];

        $elements = explode(":", (string) $data['url']);

        if (!in_array($elements['0'], $allowed_array)) {
            AmpError::add('url', T_('URL is invalid, must be http:// or https://'));
        }

        if (!empty($data['site_url'])) {
            $elements = explode(":", (string) $data['site_url']);
            if (!in_array($elements['0'], $allowed_array)) {
                AmpError::add('site_url', T_('URL is invalid, must be http:// or https://'));
            }
        }

        // Make sure it's a real catalog
        $catalog = Catalog::create_from_id((int) $data['catalog']);
        if ($catalog === null) {
            AmpError::add('catalog', T_('Catalog is invalid'));

            return null;
        }

        if (AmpError::occurred()) {
            return null;
        }

        // If we've made it this far everything must be ok... I hope
        $liveStream           = new Live_Stream();
        $liveStream->name     = $data['name'];
        $liveStream->site_url = $data['site_url'];
        $liveStream->url      = $data['url'];
        $liveStream->catalog  = $catalog->id;
        $liveStream->codec    = strtolower((string) $data['codec']);

        $insert_id = self::getLiveStreamRepository()->persist($liveStream);
        if (!$insert_id) {
            return null;
        }

        return (string) $insert_id;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getLiveStreamRepository(): LiveStreamRepositoryInterface
    {
        global $dic;

        return $dic->get(LiveStreamRepositoryInterface::class);
    }

    public function check_play_history(int $user, string $agent, int $date): bool
    {
        // Do nothing
        unset($user, $agent, $date);

        return false;
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('live_stream', $this->id, (string) $this->get_fullname(), $size, $this->get_link());
        }
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
     * Get item get_f_album_disk_link.
     */
    public function get_f_album_disk_link(): string
    {
        return '';
    }

    /**
     * Get item get_f_album_link.
     */
    public function get_f_album_link(): string
    {
        return '';
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\">" . scrub_out($title ?? $this->get_fullname()) . "</a>";
        }

        return $this->f_link;
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        return null;
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->name;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
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
            $web_path = AmpConfig::get_web_path();

            $this->link = $web_path . '/radio.php?action=show&radio=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if ($filter_type === null || $filter_type === 'live_stream') {
            $medias[] = ['object_type' => LibraryItemEnum::LIVE_STREAM, 'object_id' => $this->id];
        }

        return $medias;
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    public function get_parent_fullname(): string
    {
        return '';
    }

    /**
     * get_stream_name
     */
    public function get_stream_name(): string
    {
        return (string) $this->get_fullname();
    }

    /**
     * get_stream_types
     * This is needed by the media interface
     * @return list<string>
     */
    public function get_stream_types(?string $player = null): array
    {
        return ['native'];
    }

    /**
     * get_transcode_settings
     *
     * This will probably never be implemented
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
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
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
        return LibraryItemEnum::LIVE_STREAM;
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
        return Art::has_db($this->id, 'live_stream');
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * play_url
     * This is needed by the media interface
     */
    public function play_url(string $additional_params = '', string $player = '', bool $local = false, ?string $sid = '', ?string $force_http = ''): string
    {
        $user_id = (Core::get_global('user') instanceof User)
            ? (string) Core::get_global('user')->getId()
            : '-1';

        // the station is proxied rather than handed over, so the client only ever sees this server
        $url = Stream::get_base_url($local) . 'type=live_stream&oid=' . $this->id . '&uid=' . $user_id . '&name=' . rawurlencode((string) $this->name);

        return Stream_Url::format($url . $additional_params);
    }

    public function remove(): bool
    {
        return true;
    }

    /**
     * Persists the current state, inserting the item when it is new
     */
    public function save(): void
    {
        $insert_id = $this->getLiveStreamRepository()->persist($this);

        if ($insert_id !== null) {
            $this->id = $insert_id;
        }

        // memory_cache is on by default, so the row this object just wrote has to leave the request cache
        self::remove_from_cache('live_stream', $this->id);
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
     * update
     * This is a static function that takes a key'd array for input
     * it depends on a ID element to determine which radio element it
     * should be updating
     */
    public function update(array $data): ?int
    {
        if (!$data['name']) {
            AmpError::add('general', T_('Name is required'));
        }

        $allowed_array = [
            'https',
            'http',
            'mms',
            'mmsh',
            'mmsu',
            'mmst',
            'rtsp',
            'rtmp',
        ];

        $elements = explode(":", (string) $data['url']);

        if (!in_array($elements['0'], $allowed_array)) {
            AmpError::add('general', T_('URL is invalid, must be mms://, https:// or http://'));
        }

        if (!empty($data['site_url'])) {
            $elements = explode(":", (string) $data['site_url']);
            if (!in_array($elements['0'], $allowed_array)) {
                AmpError::add('site_url', T_('URL is invalid, must be http:// or https://'));
            }
        }

        if (AmpError::occurred()) {
            return null;
        }

        $this->name     = $data['name'] ?? $this->name;
        $this->site_url = $data['site_url'] ?? null;
        $this->url      = $data['url'] ?? $this->url;
        $this->codec    = strtolower((string) $data['codec']);

        $this->save();

        return $this->id;
    }
}
