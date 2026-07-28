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
use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;

class Video extends database_object implements
    Media,
    displayable_item,
    container_item,
    GarbageCollectibleInterface,
    CatalogItemInterface
{
    protected const string DB_TABLENAME = 'video';

    public ?int $addition_time  = null;
    public ?string $audio_codec = null;
    public ?int $bitrate        = null;
    public int $catalog;
    public ?int $channels  = null;
    public ?int $display_x = null;
    public ?int $display_y = null;
    public int $enabled;
    public ?string $file      = null;
    public ?float $frame_rate = null;
    public int $id            = 0;
    public ?int $last_played  = null; // When this was last streamed, as a unix timestamp; null until it has been played.
    public ?string $link      = null;
    public ?string $mime      = null;
    public ?string $mode      = null;
    public bool $played;
    public ?int $release_date = null;
    public int $resolution_x;
    public int $resolution_y;
    public int $size;
    public int $time;
    public ?string $title   = null;
    public int $total_count = 0;
    public int $total_skip  = 0;
    public string $type;
    public ?int $update_time             = null;
    public int|float|null $video_bitrate = null;
    public ?string $video_codec          = null;
    private ?string $f_display           = null;
    private ?string $f_link              = null;
    private ?string $f_resolution        = null;
    private ?bool $has_art               = null;

    /** @var array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags */
    private ?array $tags = null;

    /**
     * Constructor
     * This pulls the information from the database and returns
     * a constructed object
     */
    public function __construct(?int $video_id = 0)
    {
        if (!$video_id) {
            return;
        }

        $info = $this->get_info($video_id, 'video');
        if ($info === []) {
            return;
        }

        $this->id            = (int) ($info['id'] ?? 0);
        $this->catalog       = (int) ($info['catalog'] ?? 0);
        $this->title         = $info['title'] ?? null;
        $this->file          = $info['file'] ?? null;
        $this->size          = (int) ($info['size'] ?? 0);
        $this->time          = (int) ($info['time'] ?? 0);
        $this->mime          = $info['mime'] ?? null;
        $this->enabled       = (int) ($info['enabled'] ?? 0);
        $this->played        = (bool) ($info['played'] ?? false);
        $this->update_time   = isset($info['update_time']) ? (int) $info['update_time'] : null;
        $this->addition_time = isset($info['addition_time']) ? (int) $info['addition_time'] : null;
        $this->bitrate       = isset($info['bitrate']) ? (int) $info['bitrate'] : null;
        $this->mode          = $info['mode'] ?? null;
        $this->channels      = isset($info['channels']) ? (int) $info['channels'] : null;
        $this->resolution_x  = (int) ($info['resolution_x'] ?? 0);
        $this->resolution_y  = (int) ($info['resolution_y'] ?? 0);
        $this->display_x     = isset($info['display_x']) ? (int) $info['display_x'] : null;
        $this->display_y     = isset($info['display_y']) ? (int) $info['display_y'] : null;
        $this->video_codec   = $info['video_codec'] ?? null;
        $this->audio_codec   = $info['audio_codec'] ?? null;
        $this->frame_rate    = isset($info['frame_rate']) ? (float) $info['frame_rate'] : null;
        $this->video_bitrate = isset($info['video_bitrate']) ? (float) $info['video_bitrate'] : null;
        $this->release_date  = isset($info['release_date']) ? (int) $info['release_date'] : null;
        $this->last_played   = isset($info['last_played']) ? (int) $info['last_played'] : null;
        $this->total_count   = (int) ($info['total_count'] ?? 0);
        $this->total_skip    = (int) ($info['total_skip'] ?? 0);

        $this->type = ($this->file) ? strtolower(pathinfo($this->file, PATHINFO_EXTENSION)) : '';
    }

    /**
     * build_cache
     * Build a cache based on the array of ids passed, saves lots of little queries
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        foreach (self::getVideoRepository()->getRowsByIds($ids) as $row) {
            parent::add_to_cache('video', $row['id'], $row);
        }

        return true;
    }

    /**
     * compare_video_information
     * this compares the new ID3 tags of a file against
     * the ones in the database to see if they have changed
     * it returns false if nothing has changes, or the true
     * if they have. Static because it doesn't need this
     * @return array{
     *     change: bool,
     *     element: array<string, string>
     * }
     */
    public static function compare_video_information(Video $video, Video $new_video): array
    {
        $string_array = [
            'title',
            'tags',
        ];

        // Skip some stuff we don't care about
        $skip_array = [
            'addition_time',
            'catalog',
            'disabledMetadataFields',
            'enabled',
            'file',
            'id',
            'mime',
            'played',
            'tag_id',
            'total_count',
            'total_skip',
            'type',
            'update_time',
        ];

        return Song::compare_media_information($video, $new_video, $string_array, $skip_array);
    }

    /**
     * garbage_collection
     *
     * Cleans up the inherited object tables
     */
    public static function garbage_collection(): void
    {
        self::getVideoRepository()->collectGarbage();
    }

    /**
     * generate_preview
     * Generate video preview image from a video file
     */
    public static function generate_preview(int $video_id, bool $overwrite = false): void
    {
        if ($overwrite || !Art::has_db($video_id, 'video', 'preview')) {
            $artp  = new Art($video_id, 'video', 'preview');
            $video = new Video($video_id);
            $image = Stream::get_image_preview($video);
            if ($image) {
                $artp->insert($image, 'image/png');
            }
        }
    }

    /**
     * get_deleted
     * get items from the deleted_videos table
     * @return array<int, array{
     *     id: int,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string,
     *     file: string,
     *     catalog: int,
     *     total_count: int,
     *     total_skip: int
     * }>
     */
    public static function get_deleted(): array
    {
        $deleted = [];
        foreach (self::getVideoRepository()->getDeletedRows() as $row) {
            $deleted[] = [
                'id' => (int) $row['id'],
                'addition_time' => (int) $row['addition_time'],
                'delete_time' => (int) $row['delete_time'],
                'title' => $row['title'],
                'file' => $row['file'],
                'catalog' => (int) $row['catalog'],
                'total_count' => (int) $row['total_count'],
                'total_skip' => (int) $row['total_skip'],
            ];
        }

        return $deleted;
    }

    /**
     * Insert new video.
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $options
     */
    public static function insert(array $data, ?array $options = []): int
    {
        $check_file = Catalog::get_id_from_file($data['file'], 'video');
        if ($check_file > 0) {
            return $check_file;
        }

        $bitrate      = (int) $data['bitrate'];
        $mode         = $data['mode'];
        $rezx         = $data['resolution_x'];
        $rezy         = $data['resolution_y'];
        $release_date = $data['release_date'] ?? null;
        // No release date, then release date = production year
        if (!$release_date && array_key_exists('year', $data)) {
            $release_date = strtotime($data['year'] . '-01-01');
        }

        $tags          = $data['genre'] ?? null;
        $channels      = (int) $data['channels'];
        $disx          = (int) $data['display_x'];
        $disy          = (int) $data['display_y'];
        $frame_rate    = (float) $data['frame_rate'];
        $video_bitrate = Catalog::check_int($data['video_bitrate'], PHP_INT_MAX, 0);

        $params = [
            $data['file'],
            $data['catalog'],
            $data['title'],
            $data['video_codec'],
            $data['audio_codec'],
            $rezx,
            $rezy,
            $data['size'],
            $data['time'],
            $data['mime'],
            $release_date,
            time(),
            $bitrate,
            $mode,
            $channels,
            $disx,
            $disy,
            $frame_rate,
            $video_bitrate,
        ];
        $video_id = self::getVideoRepository()->insert($params);

        Catalog::update_map((int) $data['catalog'], 'video', $video_id);

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string) $tag);
                if ($tag !== '' && $tag !== '0') {
                    Tag::add('video', $video_id, $tag);
                }
            }
        }

        if (
            $data['art']
            && $options !== null
            && $options !== []
            && $options['gather_art']
        ) {
            $art = new Art($video_id, 'video');
            $art->insert_url($data['art']);
        }

        return $video_id;
    }

    /**
     * type_to_mime
     *
     * Returns the mime type for the specified file extension/type
     */
    public static function type_to_mime(string $type): string
    {
        // FIXME: This should really be done the other way around.
        // Store the mime type in the database, and provide a function
        // to make it a human-friendly type.
        return match ($type) {
            'avi' => 'video/avi',
            'ogg', 'ogv' => 'application/ogg',
            'wmv' => 'audio/x-ms-wmv',
            'mp4', 'm4v' => 'video/mp4',
            'mkv' => 'video/x-matroska',
            'mov' => 'video/quicktime',
            'divx' => 'video/x-divx',
            'webm' => 'video/webm',
            'flv' => 'video/x-flv',
            'ts' => 'video/mp2t',
            default => 'video/mpeg',
        };
    }

    /**
     * update_played
     * sets the played flag
     */
    public static function update_played(bool $new_played, int $video_id): void
    {
        if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
            return;
        }

        self::getVideoRepository()->setPlayed($video_id, $new_played);
    }

    /**
     * update_utime
     * sets a new update time
     */
    public static function update_utime(int $video_id, int $time = 0): void
    {
        if (!$time) {
            $time = time();
        }

        self::getVideoRepository()->setUpdateTime($video_id, $time);
    }

    public static function update_video(int $video_id, Video $new_video): void
    {
        self::getVideoRepository()->updateFromTags($video_id, $new_video, time());
    }

    /**
     * update_video_counts
     */
    public static function update_video_counts(int $video_id): void
    {
        if ($video_id > 0) {
            self::getVideoRepository()->updateCounts($video_id);
        }
    }

    /**
     * @deprecated inject dependency
     */
    private static function getVideoRepository(): VideoRepositoryInterface
    {
        global $dic;

        return $dic->get(VideoRepositoryInterface::class);
    }

    public function check_play_history(int $user, string $agent, int $date): bool
    {
        return Stats::has_played_history('video', $this, $user, $agent, $date);
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if (Art::has_db($this->id, 'video') || $force) {
            Art::display('video', $this->id, (string) $this->get_fullname(), $size, $this->get_link());
        }
    }

    /**
     * Get default art kind for this item.
     */
    public function get_default_art_kind(): string
    {
        return 'preview';
    }

    /**
     * get_description
     */
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
     * get_f_display
     */
    public function get_f_display(): ?string
    {
        if (!$this->f_display && ($this->display_x || $this->display_y)) {
            $this->f_display = $this->display_x . 'x' . $this->display_y;
        }

        return $this->f_display;
    }

    /**
     * Get item link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $link_text    = scrub_out($title ?? $this->get_fullname());
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . $link_text . "\"> " . $link_text . "</a>";
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
     * get_f_resolution
     */
    public function get_f_resolution(): ?string
    {
        if (!$this->f_resolution && ($this->resolution_x || $this->resolution_y)) {
            $this->f_resolution = $this->resolution_x . 'x' . $this->resolution_y;
        }

        return $this->f_resolution;
    }

    /**
     * Get item f_tags.
     */
    public function get_f_tags(): string
    {
        return Tag::get_display($this->get_tags(), true, 'video');
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(?bool $hours = false): string
    {
        $min = floor($this->time / 60);
        $sec = sprintf("%02d", ($this->time % 60));
        if (!$hours) {
            return $min . ":" . $sec;
        }

        $hour  = sprintf("%02d", floor($min / 60));
        $min_h = sprintf("%02d", ($min % 60));

        return $hour . ":" . $min_h . ":" . $sec;
    }

    /**
     * Get item fullname.
     */
    public function get_fullname(): ?string
    {
        return $this->title;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array{title: array{important: true, label: string, value: string|null}}
     */
    public function get_keywords(): array
    {
        return [
            'title' => [
                'important' => true,
                'label' => T_('Title'),
                'value' => $this->get_fullname()
            ]
        ];
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path = AmpConfig::get_web_path();

            $this->link = $web_path . "/video.php?action=show_video&video_id=" . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * Get all childrens and sub-childrens medias.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        if ($filter_type === null || $filter_type === 'video') {
            $medias[] = ['object_type' => LibraryItemEnum::VIDEO, 'object_id' => $this->id];
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
     * Get stream name.
     */
    public function get_stream_name(): string
    {
        return (string) $this->title;
    }

    /**
     * Get stream types.
     * @return list<string>
     */
    public function get_stream_types(?string $player = null): array
    {
        return Stream::get_stream_types_for_type($this->type, $player);
    }

    /**
     * Get subtitle file from language code.
     */
    public function get_subtitle_file(string $lang_code): string
    {
        $subtitle = '';
        if ($this->file && ($lang_code == '__' || $this->get_language_name($lang_code))) {
            $pinfo    = pathinfo($this->file);
            $subtitle = ($pinfo['dirname']) . DIRECTORY_SEPARATOR . $pinfo['filename'];
            if ($lang_code != '__') {
                $subtitle .= '.' . $lang_code;
            }

            $subtitle .= '.srt';
        }

        return $subtitle;
    }

    /**
     * get_subtitles
     * Get existing subtitles list for this video
     * @return array<array{
     *     file: string,
     *     lang_code: string,
     *     lang_name: string
     * }>
     */
    public function get_subtitles(): array
    {
        $subtitles = [];
        $filter    = '';
        if ($this->file) {
            $pinfo  = pathinfo($this->file);
            $filter = ($pinfo['dirname']) . DIRECTORY_SEPARATOR . $pinfo['filename'] . '*.srt';
        }

        foreach (glob($filter) ?: [] as $srt) {
            $psrt      = explode('.', $srt);
            $lang_code = '__';
            $lang_name = T_('Unknown');
            if (count($psrt) >= 2) {
                $lang_code = $psrt[count($psrt) - 2];
                if (strlen($lang_code) == 2) {
                    $lang_name = $this->get_language_name($lang_code);
                }
            }

            $subtitles[] = [
                'file' => ($pinfo['dirname'] ?? '') . DIRECTORY_SEPARATOR . $srt,
                'lang_code' => $lang_code,
                'lang_name' => $lang_name
            ];
        }

        return $subtitles;
    }

    /**
     * Get item tags.
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_tags(): array
    {
        if ($this->tags === null) {
            $this->tags = Tag::get_top_tags('video', $this->id);
        }

        return $this->tags ?? [];
    }

    /**
     * get_transcode_settings
     * @param array{bitrate?: float|int, maxbitrate?: int, subtitle?: string, resolution?: string, quality?: int, frame?: float, duration?: float} $options
     * @return array{format?: string, command?: string}
     */
    public function get_transcode_settings(?string $target = null, ?string $player = null, array $options = []): array
    {
        return Stream::get_transcode_settings_for_media($this->type, $target, $player, 'video', $options);
    }

    /**
     * Get item's owner.
     */
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
        return $this->get_fullname() . '.' . $this->type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::VIDEO;
    }

    /**
     * getYear
     */
    public function getYear(): string
    {
        return '';
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->id, 'video');
        }

        return $this->has_art ?? false;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * play_url
     * This returns a "PLAY" url for the video in question here, this currently feels a little
     * like a hack, might need to adjust it in the future
     */
    public function play_url(string $additional_params = '', string $player = '', bool $local = false, int|string|null $uid = null, ?string $streamToken = null): string
    {
        if ($this->isNew()) {
            return '';
        }

        if (!$uid) {
            // No user in the case of upnp. Set to 0 instead. required to fix database insertion errors
            $uid = Core::get_global('user')?->getId() ?? 0;
        }

        // set no use when using auth
        if (!AmpConfig::get('use_auth') && !AmpConfig::get('require_session')) {
            $uid = -1;
        }

        $media_name = $this->get_stream_name() . "." . $this->type;
        $media_name = (string) preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
        $media_name = (AmpConfig::get('stream_beautiful_url'))
            ? urlencode($media_name)
            : rawurlencode($media_name);

        $url = Stream::get_base_url($local, $streamToken) . "type=video&oid=" . $this->id . "&uid=" . $uid . $additional_params;
        if ($player !== '') {
            $url .= "&player=" . $player;
        }

        $url .= "&name=" . $media_name;

        return Stream_Url::format($url);
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        $deleted = !$this->file || !file_exists($this->file) || unlink($this->file);
        if ($deleted) {
            // keep details about deletions
            $deleted = self::getVideoRepository()->delete($this);
            if ($deleted) {
                $this->getArtCleanup()->collectGarbageForObject('video', $this->id);
                Userflag::garbage_collection('video', $this->id);
                Rating::garbage_collection('video', $this->id);
                $this->getShoutRepository()->collectGarbage('video', $this->id);
                $this->getUseractivityRepository()->collectGarbage('video', $this->id);
            }
        } else {
            debug_event(self::class, 'Cannot delete ' . $this->file . ' file. Please check permissions.', 1);
        }

        return $deleted;
    }

    /**
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param array{
     *     latitude?: float,
     *     longitude?: float,
     *     name?: string
     * } $location
     */
    public function set_played(int $user_id, string $agent, array $location, int $date): bool
    {
        // ignore duplicates or skip the last track
        if (!$this->check_play_history($user_id, $agent, $date)) {
            return false;
        }

        Stats::insert('video', $this->id, $user_id, $agent, $location, 'stream', $date);

        if ($this->played) {
            return true;
        }

        /* If it hasn't been played, set it! */
        self::update_played(true, $this->id);

        return true;
    }

    /**
     * update
     * This takes a key'd array of data as input and updates a video entry
     */
    public function update(array $data): int
    {
        $this->title = $data['title'] ?? $this->title;

        // don't require a release date when updating a video
        $with_release_date = isset($data['release_date']);
        if ($with_release_date) {
            $this->release_date = strtotime((string) $data['release_date']) ?: null;
        }

        self::getVideoRepository()->update($this, $with_release_date);

        if (isset($data['edit_tags'])) {
            Tag::update_tag_list((string) $data['edit_tags'], 'video', $this->id, true);
        }

        return $this->id;
    }

    /**
     * Get language name from code.
     */
    protected function get_language_name(string $code): string
    {
        $languageCodes = [
            "aa" => T_("Afar"),
            "ab" => T_("Abkhazian"),
            "ae" => T_("Avestan"),
            "af" => T_("Afrikaans"),
            "ak" => T_("Akan"),
            "am" => T_("Amharic"),
            "an" => T_("Aragonese"),
            "ar" => T_("Arabic"),
            "as" => T_("Assamese"),
            "av" => T_("Avaric"),
            "ay" => T_("Aymara"),
            "az" => T_("Azerbaijani"),
            "ba" => T_("Bashkir"),
            "be" => T_("Belarusian"),
            "bg" => T_("Bulgarian"),
            "bh" => T_("Bihari"),
            "bi" => T_("Bislama"),
            "bm" => T_("Bambara"),
            "bn" => T_("Bengali"),
            "bo" => T_("Tibetan"),
            "br" => T_("Breton"),
            "bs" => T_("Bosnian"),
            "ca" => T_("Catalan"),
            "ce" => T_("Chechen"),
            "ch" => T_("Chamorro"),
            "co" => T_("Corsican"),
            "cr" => T_("Cree"),
            "cs" => T_("Czech"),
            "cu" => T_("Church Slavic"),
            "cv" => T_("Chuvash"),
            "cy" => T_("Welsh"),
            "da" => T_("Danish"),
            "de" => T_("German"),
            "dv" => T_("Divehi"),
            "dz" => T_("Dzongkha"),
            "ee" => T_("Ewe"),
            "el" => T_("Greek"),
            "en" => T_("English"),
            "eo" => T_("Esperanto"),
            "es" => T_("Spanish"),
            "et" => T_("Estonian"),
            "eu" => T_("Basque"),
            "fa" => T_("Persian"),
            "ff" => T_("Fulah"),
            "fi" => T_("Finnish"),
            "fj" => T_("Fijian"),
            "fo" => T_("Faroese"),
            "fr" => T_("French"),
            "fy" => T_("Western Frisian"),
            "ga" => T_("Irish"),
            "gd" => T_("Scottish Gaelic"),
            "gl" => T_("Galician"),
            "gn" => T_("Guarani"),
            "gu" => T_("Gujarati"),
            "gv" => T_("Manx"),
            "ha" => T_("Hausa"),
            "he" => T_("Hebrew"),
            "hi" => T_("Hindi"),
            "ho" => T_("Hiri Motu"),
            "hr" => T_("Croatian"),
            "ht" => T_("Haitian"),
            "hu" => T_("Hungarian"),
            "hy" => T_("Armenian"),
            "hz" => T_("Herero"),
            "ia" => T_("Interlingua (International Auxiliary Language Association)"),
            "id" => T_("Indonesian"),
            "ie" => T_("Interlingue"),
            "ig" => T_("Igbo"),
            "ii" => T_("Sichuan Yi"),
            "ik" => T_("Inupiaq"),
            "io" => T_("Ido"),
            "is" => T_("Icelandic"),
            "it" => T_("Italian"),
            "iu" => T_("Inuktitut"),
            "ja" => T_("Japanese"),
            "jv" => T_("Javanese"),
            "ka" => T_("Georgian"),
            "kg" => T_("Kongo"),
            "ki" => T_("Kikuyu"),
            "kj" => T_("Kwanyama"),
            "kk" => T_("Kazakh"),
            "kl" => T_("Kalaallisut"),
            "km" => T_("Khmer"),
            "kn" => T_("Kannada"),
            "ko" => T_("Korean"),
            "kr" => T_("Kanuri"),
            "ks" => T_("Kashmiri"),
            "ku" => T_("Kurdish"),
            "kv" => T_("Komi"),
            "kw" => T_("Cornish"),
            "ky" => T_("Kirghiz"),
            "la" => T_("Latin"),
            "lb" => T_("Luxembourgish"),
            "lg" => T_("Ganda"),
            "li" => T_("Limburgish"),
            "ln" => T_("Lingala"),
            "lo" => T_("Lao"),
            "lt" => T_("Lithuanian"),
            "lu" => T_("Luba-Katanga"),
            "lv" => T_("Latvian"),
            "mg" => T_("Malagasy"),
            "mh" => T_("Marshallese"),
            "mi" => T_("Maori"),
            "mk" => T_("Macedonian"),
            "ml" => T_("Malayalam"),
            "mn" => T_("Mongolian"),
            "mr" => T_("Marathi"),
            "ms" => T_("Malay"),
            "mt" => T_("Maltese"),
            "my" => T_("Burmese"),
            "na" => T_("Nauru"),
            "nb" => T_("Norwegian Bokmal"),
            "nd" => T_("North Ndebele"),
            "ne" => T_("Nepali"),
            "ng" => T_("Ndonga"),
            "nl" => T_("Dutch"),
            "nn" => T_("Norwegian Nynorsk"),
            "no" => T_("Norwegian"),
            "nr" => T_("South Ndebele"),
            "nv" => T_("Navajo"),
            "ny" => T_("Chichewa"),
            "oc" => T_("Occitan"),
            "oj" => T_("Ojibwa"),
            "om" => T_("Oromo"),
            "or" => T_("Oriya"),
            "os" => T_("Ossetian"),
            "pa" => T_("Panjabi"),
            "pi" => T_("Pali"),
            "pl" => T_("Polish"),
            "ps" => T_("Pashto"),
            "pt" => T_("Portuguese"),
            "qu" => T_("Quechua"),
            "rm" => T_("Raeto-Romance"),
            "rn" => T_("Kirundi"),
            "ro" => T_("Romanian"),
            "ru" => T_("Russian"),
            "rw" => T_("Kinyarwanda"),
            "sa" => T_("Sanskrit"),
            "sc" => T_("Sardinian"),
            "sd" => T_("Sindhi"),
            "se" => T_("Northern Sami"),
            "sg" => T_("Sango"),
            "si" => T_("Sinhala"),
            "sk" => T_("Slovak"),
            "sl" => T_("Slovenian"),
            "sm" => T_("Samoan"),
            "sn" => T_("Shona"),
            "so" => T_("Somali"),
            "sq" => T_("Albanian"),
            "sr" => T_("Serbian"),
            "ss" => T_("Swati"),
            "st" => T_("Southern Sotho"),
            "su" => T_("Sundanese"),
            "sv" => T_("Swedish"),
            "sw" => T_("Swahili"),
            "ta" => T_("Tamil"),
            "te" => T_("Telugu"),
            "tg" => T_("Tajik"),
            "th" => T_("Thai"),
            "ti" => T_("Tigrinya"),
            "tk" => T_("Turkmen"),
            "tl" => T_("Tagalog"),
            "tn" => T_("Tswana"),
            "to" => T_("Tonga"),
            "tr" => T_("Turkish"),
            "ts" => T_("Tsonga"),
            "tt" => T_("Tatar"),
            "tw" => T_("Twi"),
            "ty" => T_("Tahitian"),
            "ug" => T_("Uighur"),
            "uk" => T_("Ukrainian"),
            "ur" => T_("Urdu"),
            "uz" => T_("Uzbek"),
            "ve" => T_("Venda"),
            "vi" => T_("Vietnamese"),
            "vo" => T_("Volapuk"),
            "wa" => T_("Walloon"),
            "wo" => T_("Wolof"),
            "xh" => T_("Xhosa"),
            "yi" => T_("Yiddish"),
            "yo" => T_("Yoruba"),
            "za" => T_("Zhuang"),
            "zh" => T_("Chinese"),
            "zu" => T_("Zulu"),
        ];

        return $languageCodes[$code];
    }

    /**
     * @deprecated inject dependency
     */
    private function getArtCleanup(): ArtCleanupInterface
    {
        global $dic;

        return $dic->get(ArtCleanupInterface::class);
    }

    /**
     * @deprecated
     */
    private function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
