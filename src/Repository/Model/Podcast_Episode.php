<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Module\Podcast\PodcastEpisodeStateEnum;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Module\Authorization\Access;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;

class Podcast_Episode extends database_object implements
    Media,
    library_item,
    GarbageCollectibleInterface,
    CatalogizedItemInterface
{
    protected const DB_TABLENAME = 'podcast_episode';

    public int $id = 0;
    public ?string $title;
    public ?string $guid;
    public int $podcast;
    public ?string $state;
    public ?string $file;
    public ?string $source;
    public int $size;
    public int $time;
    public ?string $website;
    public ?string $description;
    public ?string $author;
    public ?string $category;
    public bool $played;
    public bool $enabled;
    public int $pubdate;
    public int $addition_time;
    public int $total_count;
    public int $total_skip;
    public int $catalog;
    public int $bitrate;
    public int $rate;
    public ?string $mode;
    public ?int $channels;
    public ?string $waveform;

    public ?string $link = null;
    public $type;
    public $mime;
    public $f_name;
    public $f_file;
    public $f_size;
    public $f_time;
    public $f_time_h;
    public $f_description;
    public $f_author;
    public $f_artist_full;
    public $f_bitrate;
    public $f_category;
    public $f_website;
    public $f_pubdate;
    public $f_state;
    public $f_link;
    public $f_podcast;
    public $f_podcast_link;

    private ?bool $has_art = null;

    /**
     * Constructor
     *
     * Podcast Episode class
     * @param int|null $episode_id
     */
    public function __construct($episode_id = 0)
    {
        if (!$episode_id) {
            return;
        }

        $info = $this->get_info($episode_id, static::DB_TABLENAME);
        if (empty($info)) {
            return;
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
        $this->id = $episode_id;
        if (!empty($this->file)) {
            $this->type    = strtolower((string)pathinfo($this->file, PATHINFO_EXTENSION));
            $this->mime    = Song::type_to_mime($this->type);
            $this->enabled = true;
        }
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
     * garbage_collection
     *
     * Cleans up the podcast_episode table
     */
    public static function garbage_collection(): void
    {
        Dba::write("DELETE FROM `podcast_episode` USING `podcast_episode` LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`id` IS NULL;");
    }

    /**
     * Returns the id of the catalog the item is associated to
     */
    public function getCatalogId(): int
    {
        return $this->catalog;
    }

    /**
     * format
     * this function takes the object and formats some values
     *
     * @param bool $details
     */
    public function format($details = true): void
    {
        if ($this->isNew()) {
            return;
        }
        $this->f_description = scrub_out($this->description);
        $this->f_category    = scrub_out($this->category);
        $this->f_author      = scrub_out($this->author);
        $this->f_artist_full = $this->f_author;
        $this->f_website     = scrub_out($this->website);
        $this->f_pubdate     = date("c", (int)$this->pubdate);
        switch ($this->state) {
            case PodcastEpisodeStateEnum::SKIPPED:
                $this->f_state = T_('skipped');
                break;
            case PodcastEpisodeStateEnum::PENDING:
                $this->f_state = T_('pending');
                break;
            case PodcastEpisodeStateEnum::COMPLETED:
                $this->f_state = T_('completed');
                break;
            default:
                $this->f_state = '';
        }
        // format the file
        if (!empty($this->file)) {
            $this->type    = strtolower((string)pathinfo($this->file, PATHINFO_EXTENSION));
            $this->mime    = Song::type_to_mime($this->type);
            $this->enabled = true;
        }

        // Format the Bitrate
        $this->f_bitrate = (int)($this->bitrate / 1024) . "-" . strtoupper((string)$this->mode);

        // Format the Time
        $min            = floor($this->time / 60);
        $sec            = sprintf("%02d", ($this->time % 60));
        $this->f_time   = $min . ":" . $sec;
        $hour           = sprintf("%02d", floor($min / 60));
        $min_h          = sprintf("%02d", ($min % 60));
        $this->f_time_h = $hour . ":" . $min_h . ":" . $sec;
        // Format the Size
        $this->f_size = Ui::format_bytes($this->size);
        $this->f_file = $this->get_fullname() . '.' . $this->type;

        $this->get_f_link();

        if ($details) {
            $this->get_f_podcast();
            $this->get_f_podcast_link();
            $this->f_file = $this->get_f_podcast() . ' - ' . $this->f_file;
        }
        if (AmpConfig::get('show_played_times')) {
            $this->total_count = (int) $this->total_count;
        }
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->podcast, 'podcast');
        }

        return $this->has_art;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords            = array();
        $keywords['podcast'] = array(
            'important' => true,
            'label' => T_('Podcast'),
            'value' => $this->get_f_podcast()
        );
        $keywords['title'] = array(
            'important' => true,
            'label' => T_('Title'),
            'value' => $this->get_fullname()
        );

        return $keywords;
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        if (!isset($this->f_name)) {
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
            $this->link = $web_path . '/podcast_episode.php?action=show&podcast_episode=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): string
    {
        // don't do anything if it's formatted
        if (!isset($this->f_link)) {
            $this->f_link = '<a href="' . $this->get_link() . '" title="' . scrub_out($this->get_fullname()) . '">' . scrub_out($this->get_fullname()) . '</a>';
        }

        return $this->f_link;
    }

    /**
     * get_f_podcast
     */
    public function get_f_podcast(): string
    {
        if (!isset($this->f_podcast)) {
            $podcast         = new Podcast($this->podcast);
            $this->f_podcast = $podcast->get_fullname();
        }

        return $this->f_podcast;
    }

    /**
     * get_f_podcast_link
     */
    public function get_f_podcast_link(): string
    {
        if (!isset($this->f_podcast_link)) {
            $podcast              = new Podcast($this->podcast);
            $this->f_podcast_link = $podcast->get_f_link();
        }

        return $this->f_podcast_link;
    }

    /**
     * get_f_artist_link
     */
    public function get_f_artist_link(): ?string
    {
        return $this->get_f_podcast_link();
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
        return array(
            'object_type' => 'podcast',
            'object_id' => $this->podcast
        );
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * Search for direct children of an object
     * @param string $name
     * @return array
     */
    public function get_children($name)
    {
        debug_event(self::class, 'get_children ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'podcast_episode') {
            $medias[] = array(
                'object_type' => 'podcast_episode',
                'object_id' => $this->id
            );
        }

        return $medias;
    }

    /**
     * @return int|null
     */
    public function get_user_owner(): ?int
    {
        return null;
    }

    /**
     * get_default_art_kind
     */
    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * get_description
     */
    public function get_description(): string
    {
        if (!isset($this->f_description)) {
            $this->f_description = scrub_out($this->description ?? '');
        }

        return $this->f_description;
    }

    public function getSource(): string
    {
        return (string) $this->source;
    }

    public function getFile(): string
    {
        return (string) $this->file;
    }

    public function getPodcastId(): int
    {
        return $this->podcast;
    }

    /**
     * display_art
     * @param int $thumb
     * @param bool $force
     */
    public function display_art($thumb = 2, $force = false): void
    {
        $episode_id = null;
        $type       = null;

        if (Art::has_db($this->id, 'podcast_episode')) {
            $episode_id = $this->id;
            $type       = 'podcast_episode';
        } elseif (Art::has_db($this->podcast, 'podcast') || $force) {
            $episode_id = $this->podcast;
            $type       = 'podcast';
        }

        if ($episode_id !== null && $type !== null) {
            Art::display($type, $episode_id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * update
     * This takes a key'd array of data and updates the current podcast episode
     * @param array $data
     */
    public function update(array $data): int
    {
        $title    = $data['title'] ?? $this->title;
        $website  = $data['website'] ?? null;
        $category = $data['category'] ?? null;
        /** @var string $description */
        $description = (isset($data['description'])) ? scrub_in(Dba::check_length((string)$data['description'], 4096)) : null;
        /** @var string $author */
        $author   = (isset($data['author'])) ? scrub_in(Dba::check_length((string)$data['author'], 64)) : null;

        $sql = 'UPDATE `podcast_episode` SET `title` = ?, `website` = ?, `description` = ?, `author` = ?, `category` = ? WHERE `id` = ?';
        Dba::write($sql, array($title, $website, $description, $author, $category, $this->id));

        $this->title       = $title;
        $this->website     = $website;
        $this->description = $description;
        $this->author      = $author;
        $this->category    = $category;

        return $this->id;
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
    public function set_played($user_id, $agent, $location, $date = null): bool
    {
        // ignore duplicates or skip the last track
        if (!$this->check_play_history($user_id, $agent, $date)) {
            return false;
        }
        if (Stats::insert('podcast_episode', $this->id, $user_id, $agent, $location, 'stream', $date)) {
            Stats::insert('podcast', $this->podcast, $user_id, $agent, $location, 'stream', $date);
        }

        if (!$this->played) {
            self::_update_item('played', 1, $this->id);
        }

        return true;
    }

    /**
     * @param int $user
     * @param string $agent
     * @param int $date
     */
    public function check_play_history($user, $agent, $date): bool
    {
        return Stats::has_played_history('podcast_episode', $this, $user, $agent, $date);
    }

    /**
     * update_file
     * sets the file path
     */
    public static function update_file(string $path, int $id): void
    {
        self::_update_item('file', $path, $id);
    }

    /**
     * _update_item
     * This is a private function that should only be called from within the podcast episode class.
     * It takes a field, value podcast_episode_id and level. first and foremost it checks the level
     * against Core::get_global('user') to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param string|int $value
     * @param int $episode_id
     */
    private static function _update_item(string $field, $value, int $episode_id): void
    {
        /* Check them Rights! */
        if (!Access::check('interface', 25)) {
            return;
        }

        /* Can't update to blank */
        if (!strlen(trim((string)$value))) {
            return;
        }

        $sql = "UPDATE `podcast_episode` SET `$field` = ? WHERE `id` = ?";
        Dba::write($sql, array($value, $episode_id));
    }

    /**
     * Get stream name.
     */
    public function get_stream_name(): string
    {
        return (string)($this->get_f_podcast() . " - " . $this->get_fullname());
    }

    /**
     * Get transcode settings.
     * @param string $target
     * @param string $player
     * @param array $options
     * @return array
     */
    public function get_transcode_settings($target = null, $player = null, $options = array())
    {
        return Stream::get_transcode_settings_for_media($this->type, $target, $player, 'song', $options);
    }

    /**
     * getYear
     */
    public function getYear(): string
    {
        return date("Y", (int)$this->pubdate) ?: '';
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * stream URL taking into account the downsmapling mojo and everything
     * else, this is the true function
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
        $media_name = preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
        $media_name = (AmpConfig::get('stream_beautiful_url'))
            ? urlencode($media_name)
            : rawurlencode($media_name);

        $url = Stream::get_base_url($local, $streamToken) . "type=podcast_episode&oid=" . $this->id . "&uid=" . (string) $uid . '&format=raw' . $additional_params;
        if ($player !== '') {
            $url .= "&player=" . $player;
        }
        $url .= "&name=" . $media_name;

        return Stream_Url::format($url);
    }

    /**
     * Get stream types.
     * @param null|string $player
     * @return list<string>
     */
    public function get_stream_types($player = null): array
    {
        return Stream::get_stream_types_for_type($this->type, $player);
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        $this->getPodcastDeleter()->deleteEpisode([$this]);

        return true;
    }

    /**
     * change_state
     */
    public function change_state(string $state): void
    {
        $sql = "UPDATE `podcast_episode` SET `state` = ? WHERE `id` = ?";

        Dba::write($sql, array($state, $this->id));
    }

    /**
     * get_deleted
     * get items from the deleted_podcast_episodes table
     * @return list<array{
     *  id: int,
     *  addition_time: int,
     *  delete_time: int,
     *  title: string,
     *  file: string,
     *  catalog: int,
     *  total_count: int,
     *  total_skip: int,
     *  podcast: int
     * }>
     */
    public static function get_deleted(): array
    {
        $deleted    = array();
        $sql        = "SELECT * FROM `deleted_podcast_episode`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            /**
             * @var array{
             *  id: int,
             *  addition_time: int,
             *  delete_time: int,
             *  title: string,
             *  file: string,
             *  catalog: int,
             *  total_count: int,
             *  total_skip: int,
             *  podcast: int
             * } $row
             */
            $deleted[] = $row;
        }

        return $deleted;
    }

    /**
     * @deprecated Inject dependency
     */
    private function getPodcastDeleter(): PodcastDeleterInterface
    {
        global $dic;

        return $dic->get(PodcastDeleterInterface::class);
    }
}
