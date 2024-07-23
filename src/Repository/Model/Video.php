<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

class Video extends database_object implements
    Media,
    library_item,
    GarbageCollectibleInterface,
    CatalogItemInterface
{
    protected const DB_TABLENAME = 'video';

    public int $id = 0;

    public string $file;

    public int $catalog;

    public ?string $title = null;

    public ?string $video_codec = null;

    public ?string $audio_codec = null;

    public int $resolution_x;

    public int $resolution_y;

    public int $time;

    public int $size;

    public ?string $mime = null;

    public int $addition_time;

    public ?int $update_time = null;

    public int $enabled;

    public bool $played;

    public ?int $release_date = null;

    public ?int $channels = null;

    public ?int $bitrate = null;

    public ?int $video_bitrate = null;

    public ?int $display_x = null;

    public ?int $display_y = null;

    public ?float $frame_rate = null;

    public ?string $mode = null;

    public int $total_count;

    public int $total_skip;

    public ?string $link = null;

    /** @var string $type */
    public $type;

    /** @var array $tags */
    public $tags;

    /** @var null|string $f_name */
    public $f_name;

    /** @var null|string $f_full_title */
    public $f_full_title;

    /** @var null|string $f_time */
    public $f_time;

    /** @var null|string $f_time_h */
    public $f_time_h;

    /** @var null|string $f_size */
    public $f_size;

    /** @var null|string $f_link */
    public $f_link;

    /** @var null|string $f_codec */
    public $f_codec;

    /** @var null|string $f_resolution */
    public $f_resolution;

    /** @var null|string $f_display */
    public $f_display;

    /** @var null|string $f_bitrate */
    public $f_bitrate;

    /** @var null|string $f_video_bitrate */
    public $f_video_bitrate;

    /** @var null|string $f_frame_rate */
    public $f_frame_rate;

    /** @var null|string $f_tags */
    public $f_tags;

    /** @var null|string $f_length */
    public $f_length;

    /** @var null|string $f_release_date */
    public $f_release_date;

    private ?bool $has_art = null;

    /**
     * Constructor
     * This pulls the information from the database and returns
     * a constructed object
     * @param int|null $video_id
     */
    public function __construct($video_id = 0)
    {
        if (!$video_id) {
            return;
        }

        $info = $this->get_info($video_id, 'video');
        if ($info === []) {
            return;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        $this->type        = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * Create a video strongly typed object from its id.
     * @param int $video_id
     */
    public static function create_from_id($video_id): Video
    {
        foreach (ObjectTypeToClassNameMapper::VIDEO_TYPES as $dtype) {
            $sql        = "SELECT `id` FROM `" . strtolower($dtype->value) . "` WHERE `id` = ?";
            $db_results = Dba::read($sql, [$video_id]);
            $results    = Dba::fetch_assoc($db_results);
            if (array_key_exists('id', $results)) {
                $className = ObjectTypeToClassNameMapper::map(strtolower($dtype->value));

                return new $className($video_id);
            }
        }

        return new Video($video_id);
    }

    /**
     * build_cache
     * Build a cache based on the array of ids passed, saves lots of little queries
     * @param int[] $ids
     */
    public static function build_cache($ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = 'SELECT * FROM `video` WHERE `video`.`id` IN ' . $idlist;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('video', $row['id'], $row);
        }

        return true;
    }

    /**
     * format
     * This formats a video object so that it is human readable
     */
    public function format(?bool $details = true): void
    {
        $this->get_f_link();
        $this->f_codec = $this->video_codec . ' / ' . $this->audio_codec;
        if ($this->resolution_x || $this->resolution_y) {
            $this->f_resolution = $this->resolution_x . 'x' . $this->resolution_y;
        }

        if ($this->display_x || $this->display_y) {
            $this->f_display = $this->display_x . 'x' . $this->display_y;
        }

        // Format the Bitrate
        $this->f_bitrate       = (int) ($this->bitrate / 1024) . "-" . strtoupper((string) $this->mode);
        $this->f_video_bitrate = (string) (int) ($this->video_bitrate / 1024);
        if ($this->frame_rate) {
            $this->f_frame_rate = $this->frame_rate . ' fps';
        }

        // Format the Time
        $min            = floor($this->time / 60);
        $sec            = sprintf("%02d", ($this->time % 60));
        $this->f_time   = $min . ":" . $sec;
        $hour           = sprintf("%02d", floor($min / 60));
        $min_h          = sprintf("%02d", ($min % 60));
        $this->f_time_h = $hour . ":" . $min_h . ":" . $sec;

        // Format the size
        $this->f_size = Ui::format_bytes($this->size);

        if ($details) {
            // Get the top tags
            $this->tags   = Tag::get_top_tags('video', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'video');
        }

        $this->f_length = floor($this->time / 60) . ' ' . T_('minutes');
        if ($this->release_date) {
            $this->f_release_date = get_datetime((int) $this->release_date, 'short', 'none');
        }
    }

    /**
     * Returns the filename of the media-item
     */
    public function getFileName(): string
    {
        return $this->get_fullname() . '.' . $this->type;
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->id, 'video');
        }

        return $this->has_art;
    }

    /**
     * Get item keywords for metadata searches.
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
     * Get item fullname.
     */
    public function get_fullname(): ?string
    {
        if ($this->f_name === null) {
            $this->f_name = $this->title;
        }

        return $this->f_name;
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path   = AmpConfig::get('web_path');
            $this->link = $web_path . "/video.php?action=show_video&video_id=" . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item link.
     */
    public function get_f_link(): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $link_text    = scrub_out($this->get_fullname());
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . $link_text . "\"> " . $link_text . "</a>";
        }

        return $this->f_link;
    }

    /**
     * get_f_artist_link
     */
    public function get_f_artist_link(): ?string
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
     * Get item get_f_album_disk_link.
     */
    public function get_f_album_disk_link(): string
    {
        return '';
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    /**
     * Get item childrens.
     */
    public function get_childrens(): array
    {
        return [];
    }

    /**
     * Search for direct children of an object
     * @param string $name
     */
    public function get_children($name): array
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return [];
    }

    /**
     * Get all childrens and sub-childrens medias.
     *
     * @return list<array{object_type: LibraryItemEnum, object_id: int}>
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
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    /**
     * Get item's owner.
     */
    public function get_user_owner(): ?int
    {
        return null;
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
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        if (Art::has_db($this->id, 'video') || $force) {
            Art::display('video', $this->id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * garbage_collection
     *
     * Cleans up the inherited object tables
     */
    public static function garbage_collection(): void
    {
        // delete files matching catalog_ignore_pattern
        $ignore_pattern = AmpConfig::get('catalog_ignore_pattern');
        if ($ignore_pattern) {
            Dba::write("DELETE FROM `video` WHERE `file` REGEXP ?;", [$ignore_pattern]);
        }

        // clean up missing catalogs
        Dba::write("DELETE FROM `video` WHERE `video`.`catalog` NOT IN (SELECT `id` FROM `catalog`);");
        // clean up sub-tables of videos
        Movie::garbage_collection();
        TVShow_Episode::garbage_collection();
        TVShow_Season::garbage_collection();
        TvShow::garbage_collection();
        Personal_Video::garbage_collection();
        Clip::garbage_collection();
    }

    /**
     * Get stream types.
     * @param string $player
     */
    public function get_stream_types($player = null): array
    {
        return Stream::get_stream_types_for_type($this->type, $player);
    }

    /**
     * play_url
     * This returns a "PLAY" url for the video in question here, this currently feels a little
     * like a hack, might need to adjust it in the future
     * @param string $additional_params
     * @param string $player
     * @param bool $local
     * @param int|string $uid
     * @param null|string $streamToken
     */
    public function play_url($additional_params = '', $player = '', $local = false, $uid = false, $streamToken = null): string
    {
        if ($this->isNew()) {
            return '';
        }

        if (!$uid) {
            // No user in the case of upnp. Set to 0 instead. required to fix database insertion errors
            $uid = Core::get_global('user')->id ?? 0;
        }

        // set no use when using auth
        if (!AmpConfig::get('use_auth') && !AmpConfig::get('require_session')) {
            $uid = -1;
        }

        $media_name = $this->get_stream_name() . "." . $this->type;
        $media_name = (string)preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
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
     * Get stream name.
     */
    public function get_stream_name(): string
    {
        return (string)$this->title;
    }

    /**
     * get_transcode_settings
     * @param string $target
     * @param array $options
     * @param string $player
     */
    public function get_transcode_settings($target = null, $player = null, $options = []): array
    {
        return Stream::get_transcode_settings_for_media($this->type, $target, $player, 'video', $options);
    }

    /**
     * getYear
     */
    public function getYear(): string
    {
        return '';
    }

    /**
     * type_to_mime
     *
     * Returns the mime type for the specified file extension/type
     * @param string $type
     */
    public static function type_to_mime($type): string
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
     * Insert new video.
     */
    public static function insert(array $data, ?array $gtypes = [], ?array $options = []): int
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
        $video_bitrate = Catalog::check_int($data['video_bitrate'], 4294967294, 0);

        $sql    = "INSERT INTO `video` (`file`, `catalog`, `title`, `video_codec`, `audio_codec`, `resolution_x`, `resolution_y`, `size`, `time`, `mime`, `release_date`, `addition_time`, `bitrate`, `mode`, `channels`, `display_x`, `display_y`, `frame_rate`, `video_bitrate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
        Dba::write($sql, $params);
        $video_id = (int) Dba::insert_id();

        Catalog::update_map((int)$data['catalog'], 'video', $video_id);

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string) $tag);
                if ($tag !== '' && $tag !== '0') {
                    Tag::add('video', $video_id, $tag, false);
                }
            }
        }

        if (
            $data['art'] &&
            $options !== null &&
            $options !== [] &&
            $options['gather_art']
        ) {
            $art = new Art($video_id, 'video');
            $art->insert_url($data['art']);
        }

        $data['id'] = $video_id;

        return self::insert_video_type($data, $gtypes, $options);
    }

    /**
     * Insert video for derived type.
     */
    private static function insert_video_type(array $data, ?array $gtypes = [], ?array $options = []): int
    {
        if (is_array($gtypes) && $gtypes !== []) {
            $gtype = $gtypes[0];
            switch ($gtype) {
                case 'tvshow':
                    return TVShow_Episode::insert($data, $gtypes, $options);
                case 'movie':
                    return Movie::insert($data, $gtypes, $options);
                case 'clip':
                    return Clip::insert($data, $gtypes, $options);
                case 'personal_video':
                    return Personal_Video::insert($data, $gtypes, $options);
            }
        }

        return $data['id'];
    }

    /**
     * update
     * This takes a key'd array of data as input and updates a video entry
     */
    public function update(array $data): int
    {
        $sql    = "UPDATE `video` SET `title` = ?";
        $title  = $data['title'] ?? $this->title;
        $params = [$title];
        // don't require a release date when updating a video
        if (isset($data['release_date'])) {
            $f_release_date     = (string) $data['release_date'];
            $release_date       = strtotime($f_release_date);
            $this->release_date = $release_date ?: null;
            $sql .= ", `release_date` = ?";
            $params[] = $release_date;
        }

        $sql .= " WHERE `id` = ?";
        $params[] = $this->id;

        Dba::write($sql, $params);

        if (isset($data['edit_tags'])) {
            Tag::update_tag_list($data['edit_tags'], 'video', $this->id, true);
        }

        $this->title = $title;

        return $this->id;
    }

    /**
     * @param int $video_id
     */
    public static function update_video($video_id, Video $new_video): void
    {
        $update_time  = time();
        $release_date = is_numeric($new_video->release_date) ? $new_video->release_date : null;

        $sql = "UPDATE `video` SET `title` = ?, `bitrate` = ?, `size` = ?, `time` = ?, `video_codec` = ?, `audio_codec` = ?, `resolution_x` = ?, `resolution_y` = ?, `release_date` = ?, `channels` = ?, `display_x` = ?, `display_y` = ?, `frame_rate` = ?, `video_bitrate` = ?, `update_time` = ? WHERE `id` = ?";

        Dba::write($sql, [
            $new_video->title,
            $new_video->bitrate,
            $new_video->size,
            $new_video->time,
            $new_video->video_codec,
            $new_video->audio_codec,
            $new_video->resolution_x,
            $new_video->resolution_y,
            $release_date,
            $new_video->channels,
            $new_video->display_x,
            $new_video->display_y,
            $new_video->frame_rate,
            $new_video->video_bitrate,
            $update_time,
            $video_id,
        ]);
    }

    /**
     * update_video_counts
     *
     * @param int $video_id
     */
    public static function update_video_counts($video_id): void
    {
        if ($video_id > 0) {
            $params = [$video_id];
            $sql    = "UPDATE `video` SET `total_count` = 0 WHERE `total_count` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream');";
            Dba::write($sql, $params);
            $sql = "UPDATE `video` SET `total_skip` = 0 WHERE `total_skip` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream');";
            Dba::write($sql, $params);
            $sql = "UPDATE `video` SET `video`.`played` = 0 WHERE `video`.`played` = 1 AND `video`.`id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_type` = 'video' AND `count_type` = 'stream');";
            Dba::write($sql, $params);
            $sql = "UPDATE `video` SET `video`.`played` = 1 WHERE `video`.`played` = 0 AND `video`.`id` IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_type` = 'video' AND `count_type` = 'stream');";
            Dba::write($sql, $params);
        }
    }

    /**
     * Get release item art.
     */
    public function get_release_item_art(): array
    {
        return [
            'object_type' => 'video',
            'object_id' => $this->id
        ];
    }

    /**
     * generate_preview
     * Generate video preview image from a video file
     * @param int $video_id
     * @param bool $overwrite
     */
    public static function generate_preview($video_id, $overwrite = false): void
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
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param int $user_id
     * @param string $agent
     * @param array $location
     * @param int $date
     */
    public function set_played($user_id, $agent, $location, $date): bool
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
        Video::update_played(true, $this->id);

        return true;
    }

    /**
     * @param int $user
     * @param string $agent
     * @param int $date
     */
    public function check_play_history($user, $agent, $date): bool
    {
        return Stats::has_played_history('video', $this, $user, $agent, $date);
    }

    /**
     * get_subtitles
     * Get existing subtitles list for this video
     */
    public function get_subtitles(): array
    {
        $subtitles = [];
        $pinfo     = pathinfo($this->file);
        $filter    = $pinfo['dirname'] . DIRECTORY_SEPARATOR . $pinfo['filename'] . '*.srt';

        foreach (glob($filter) as $srt) {
            $psrt      = explode('.', $srt);
            $lang_code = '__';
            $lang_name = T_('Unknown');
            if (count($psrt) >= 2) {
                $lang_code = $psrt[count($psrt) - 2];
                if (strlen($lang_code) == 2) {
                    $lang_name = $this->get_language_name($lang_code);
                }
            }

            $subtitles[] = ['file' => $pinfo['dirname'] . DIRECTORY_SEPARATOR . $srt, 'lang_code' => $lang_code, 'lang_name' => $lang_name];
        }

        return $subtitles;
    }

    /**
     * Get language name from code.
     * @param string $code
     */
    protected function get_language_name($code): string
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
     * Get subtitle file from language code.
     * @param string $lang_code
     */
    public function get_subtitle_file($lang_code): string
    {
        $subtitle = '';
        if ($lang_code == '__' || $this->get_language_name($lang_code)) {
            $pinfo    = pathinfo($this->file);
            $subtitle = $pinfo['dirname'] . DIRECTORY_SEPARATOR . $pinfo['filename'];
            if ($lang_code != '__') {
                $subtitle .= '.' . $lang_code;
            }

            $subtitle .= '.srt';
        }

        return $subtitle;
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        $deleted = file_exists($this->file) ? unlink($this->file) : true;

        if ($deleted) {
            // keep details about deletions
            $params = [$this->id];
            $sql    = "REPLACE INTO `deleted_video` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip` FROM `video` WHERE `id` = ?;";
            Dba::write($sql, $params);

            $sql     = "DELETE FROM `video` WHERE `id` = ?";
            $deleted = (Dba::write($sql, $params) !== false);
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
     * update_utime
     * sets a new update time
     * @param int $video_id
     * @param int $time
     */
    public static function update_utime($video_id, $time = 0): void
    {
        if (!$time) {
            $time = time();
        }

        $sql = "UPDATE `video` SET `update_time` = ? WHERE `id` = ?;";
        Dba::write($sql, [$time, $video_id]);
    }

    /**
     * update_played
     * sets the played flag
     * @param bool $new_played
     * @param int $song_id
     */
    public static function update_played($new_played, $song_id): void
    {
        self::_update_item('played', ($new_played ? 1 : 0), $song_id, AccessLevelEnum::USER);
    }

    /**
     * _update_item
     * This is a private function that should only be called from within the video class.
     * It takes a field, value video id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param string|int $value
     * @param int $video_id
     */
    private static function _update_item($field, $value, $video_id, AccessLevelEnum $level): bool
    {
        /* Check them Rights! */
        if (!Access::check(AccessTypeEnum::INTERFACE, $level)) {
            return false;
        }

        /* Can't update to blank */
        if (trim((string) $value) === '') {
            return false;
        }

        $sql = sprintf('UPDATE `video` SET `%s` = ? WHERE `id` = ?', $field);
        Dba::write($sql, [$value, $video_id]);

        return true;
    }

    /**
     * get_deleted
     * get items from the deleted_videos table
     * @return int[]
     */
    public static function get_deleted(): array
    {
        $deleted    = [];
        $sql        = "SELECT * FROM `deleted_video`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $deleted[] = $row;
        }

        return $deleted;
    }

    /**
     * compare_video_information
     * this compares the new ID3 tags of a file against
     * the ones in the database to see if they have changed
     * it returns false if nothing has changes, or the true
     * if they have. Static because it doesn't need this
     */
    public static function compare_video_information(Video $video, Video $new_video): array
    {
        // Remove some stuff we don't care about
        unset($video->catalog, $video->played, $video->enabled, $video->addition_time, $video->update_time, $video->type);
        $string_array = [
            'title',
            'tags',
        ];
        $skip_array   = [
            'id',
            'tag_id',
            'mime',
            'total_count',
            'disabledMetadataFields',
        ];

        return Song::compare_media_information($video, $new_video, $string_array, $skip_array);
    }

    public function get_artist_fullname(): string
    {
        return '';
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::VIDEO;
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

    /**
     * @deprecated inject dependency
     */
    private function getArtCleanup(): ArtCleanupInterface
    {
        global $dic;

        return $dic->get(ArtCleanupInterface::class);
    }
}
