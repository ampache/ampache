<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Podcast\PodcastEpisodeDeleterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\Util\ExtensionToMimeTypeMapperInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;

class Podcast_Episode extends database_object implements PodcastEpisodeInterface
{
    protected const DB_TABLENAME = 'podcast_episode';

    public $id;
    public $title;
    private string $guid;
    private int $podcast;
    private string $state;
    private $file;
    private string $source;
    public $size;
    public $time;
    private int $played;
    public $type;
    public $mime;
    private string $website;
    private string $description;
    private string $author;
    private string $category;
    private string $pubdate;
    private bool $enabled;
    private ?int $bitrate;
    private ?int $rate;
    private ?string $mode;

    /** @var int */
    private $total_count;

    private ?PodcastInterface $podcastObj = null;

    private ?string $filename = null;

    public function __construct($id = null)
    {
        if ($id === null) {
            return false;
        }

        $this->id = (int)$id;

        if ($info = $this->get_info($this->id)) {
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
            if (!empty($this->file)) {
                $data          = pathinfo($this->file);
                $this->type    = strtolower((string)$data['extension']);
                $this->mime    = $this->getExtentionToMimeTypeMapper()->mapAudio($this->type);
                $this->enabled = true;
            }
        } else {
            $this->id = null;

            return false;
        }

        return true;
    } // constructor

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array($this->getPodcast()->getCatalog());
    }

    /**
     * @todo remove
     *
     * this function takes the object and formats some values
     * @param boolean $details
     * @return boolean
     */
    public function format($details = true)
    {
        return true;
    }

    public function getPodcast(): PodcastInterface
    {
        if ($this->podcastObj === null) {
            $this->podcastObj = $this->getModelFactory()->createPodcast(
                (int) $this->podcast
            );
        }

        return $this->podcastObj;
    }

    public function getLink(): string
    {
        return sprintf(
            '%s/podcast_episode.php?action=show&podcast_episode=%d',
            AmpConfig::get('web_path'),
            $this->getId()
        );
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getLinkFormatted(): string
    {
        return sprintf(
            "<a href=\"%s\" title=\"%s\">%s</a>",
            $this->getLink(),
            $this->getTitleFormatted(),
            $this->getTitleFormatted()
        );
    }

    public function getStateFormatted(): string
    {
        return ucfirst($this->getState());
    }

    public function getPublicationDate(): int
    {
        return (int) $this->pubdate;
    }

    public function getPublicationDateFormatted(): string
    {
        return get_datetime($this->getPublicationDate());
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getAuthorFormatted(): string
    {
        return scrub_out($this->author);
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function getWebsiteFormatted(): string
    {
        return scrub_out($this->website);
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getSizeFormatted(): string
    {
        return Ui::format_bytes($this->size);
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getCategoryFormatted(): string
    {
        return scrub_out($this->category);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDescriptionFormatted(): string
    {
        return scrub_out($this->description);
    }

    public function getTitleFormatted(): string
    {
        return scrub_out($this->title);
    }

    public function getPlayed(): int
    {
        return $this->played;
    }

    /**
     * @return array|mixed
     */
    public function get_keywords()
    {
        $keywords            = array();
        $keywords['podcast'] = array(
            'important' => true,
            'label' => T_('Podcast'),
            'value' => $this->getPodcast()->getTitleFormatted()
        );
        $keywords['title'] = array(
            'important' => true,
            'label' => T_('Title'),
            'value' => $this->getTitleFormatted()
        );

        return $keywords;
    }

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->getTitleFormatted();
    }

    /**
     * @return array{object_type: string, object_id: int}|null
     */
    public function get_parent(): ?array
    {
        return ['object_type' => 'podcast', 'object_id' => $this->podcast];
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array|mixed
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
     * @return mixed|null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->getDescriptionFormatted();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $episode_id = null;
        $type       = null;

        if (Art::has_db($this->id, 'podcast_episode')) {
            $episode_id = $this->id;
            $type       = 'podcast_episode';
        } else {
            if (Art::has_db($this->podcast, 'podcast') || $force) {
                $episode_id = $this->podcast;
                $type       = 'podcast';
            }
        }

        if ($episode_id !== null && $type !== null) {
            echo Art::display($type, $episode_id, $this->get_fullname(), $thumb, $this->getLink());
        }
    }

    /**
     * This takes a key'd array of data and updates the current podcast episode
     *
     * @param array{title?: string, website?: string, description?: string, author?: string, category?: string} $data
     */
    public function update(array $data): int
    {
        $this->getPodcastEpisodeRepository()->update(
            $this,
            $data['title'] ?? $this->title,
            $data['website'] ?? $this->website,
            $data['description'] ?? $this->description,
            $data['author'] ?? $this->author,
            $data['category'] ?? $this->category
        );

        return (int) $this->id;
    }

    /**
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param integer $date
     * @return boolean
     */
    public function set_played($user, $agent, $location, $date = null)
    {
        // ignore duplicates or skip the last track
        if ($this->check_play_history($user, $agent, $date)) {
            Stats::insert('podcast_episode', $this->id, $user, $agent, $location, 'stream', $date);
        }

        if (!$this->played) {
            if (!Access::check('interface', 25)) {
                return false;
            }

            $this->getPodcastEpisodeRepository()->setPlayed($this);
        }

        return true;
    } // set_played

    /**
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public function check_play_history($user, $agent, $date)
    {
        return Stats::has_played_history($this, $user, $agent, $date);
    }

    /**
     * Get stream name.
     * @return string
     */
    public function get_stream_name()
    {
        return sprintf(
            '%s - %s',
            $this->getPodcast()->getTitleFormatted(),
            $this->getTitleFormatted()
        );
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
        return Song::get_transcode_settings_for_media($this->type, $target, $player, 'song', $options);
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * a stream URL taking into account the downsmapling mojo and everything
     * else, this is the true function
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @param string $uid
     * @return string
     */
    public function play_url($additional_params = '', $player = '', $local = false, $uid = false)
    {
        if (!$this->id) {
            return '';
        }
        if (!$uid) {
            // No user in the case of upnp. Set to 0 instead. required to fix database insertion errors
            $uid = Core::get_global('user')->id ?: 0;
        }
        // set no use when using auth
        if (!AmpConfig::get('use_auth') && !AmpConfig::get('require_session')) {
            $uid = -1;
        }

        $type = $this->type;

        $media_name = $this->get_stream_name() . "." . $type;
        $media_name = preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
        $media_name = rawurlencode($media_name);

        $url = Stream::get_base_url($local) . "type=podcast_episode&oid=" . $this->id . "&uid=" . (string) $uid . '&format=raw' . $additional_params;
        if ($player !== '') {
            $url .= "&player=" . $player;
        }
        $url .= "&name=" . $media_name;

        return Stream_Url::format($url);
    }

    /**
     * Get stream types.
     * @param string $player
     * @return array
     */
    public function get_stream_types($player = null)
    {
        return Song::get_stream_types_for_type($this->type, $player);
    }

    public function getFilename(): string
    {
        if ($this->filename === null) {
            $this->filename = sprintf(
            '%s - %s.%s',
            $this->getPodcast()->getTitleFormatted(),
            scrub_out($this->title),
            $this->type
            );
        }

        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * remove
     * @return boolean
     */
    public function remove()
    {
        return $this->getPodcastEpisodeDeleter()->delete($this);
    }

    public function getFullArtistNameFormatted(): string
    {
        return scrub_out($this->author);
    }

    public function getFullDurationFormatted(): string
    {
        $min   = floor($this->time / 60);
        $sec   = sprintf("%02d", ($this->time % 60));
        $hour  = sprintf("%02d", floor($min / 60));
        $min_h = sprintf("%02d", ($min % 60));

        return sprintf('%s:%s:%s', $hour, $min_h, $sec);
    }

    public function getDurationFormatted(): string
    {
        $min = floor($this->time / 60);
        $sec = sprintf("%02d", ($this->time % 60));

        return sprintf('%s:%s', $min, $sec);
    }

    public function getObjectCount(): ?int
    {
        if (AmpConfig::get('show_played_times')) {
            return (int) $this->total_count;
        }

        return null;
    }

    public function getTime(): int
    {
        return (int) $this->time;
    }

    public function hasFile(): bool
    {
        return !empty($this->file);
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    public function getCatalogId(): int
    {
        return $this->getPodcast()->getCatalog();
    }

    public function getBitrate(): ?int
    {
        return $this->bitrate;
    }

    public function getRate(): ?int
    {
        return $this->rate;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastEpisodeDeleter(): PodcastEpisodeDeleterInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeDeleterInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastEpisodeRepository(): PodcastEpisodeRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getExtentionToMimeTypeMapper(): ExtensionToMimeTypeMapperInterface
    {
        global $dic;

        return $dic->get(ExtensionToMimeTypeMapperInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getModelFactory(): ModelFactoryInterface
    {
        global $dic;

        return $dic->get(ModelFactoryInterface::class);
    }
}
