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
 *
 */

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\System\Dba;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;

/**
 * Radio Class
 *
 * This handles the internet radio stuff, that is inserted into live_stream
 * this can include podcasts or what-have-you
 *
 */
class Live_Stream extends database_object implements Media, library_item
{
    protected const DB_TABLENAME = 'live_stream';

    /* DB based variables */
    public int $id = 0;
    public ?string $name;
    public ?string $site_url;
    public ?string $url;
    public int $genre;
    public int $catalog;
    public ?string $codec;

    public ?string $link = null;
    /**
     * @var null|string $f_name
     */
    public $f_name;

    /**
     * @var null|string $f_link
     */
    public $f_link;
    /**
     * @var null|string $f_name_link
     */
    public $f_name_link;
    /**
     * @var null|string $f_url_link
     */
    public $f_url_link;
    /**
     * @var null|string $f_site_url_link
     */
    public $f_site_url_link;

    /**
     * Constructor
     * This takes a flagged. id and then pulls in the information for said flag entry
     * @param int|null $stream_id
     */
    public function __construct($stream_id = null)
    {
        if (!$stream_id) {
            return;
        }
        $info = $this->get_info($stream_id, static::DB_TABLENAME);
        if (empty($info)) {
            return;
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * format
     * This takes the normal data from the database and makes it pretty
     * for the users, the new variables are put in f_??? and f_???_link
     *
     * @param bool $details
     */
    public function format($details = true): void
    {
        unset($details);
        $this->get_f_link();
        $this->f_name_link     = "<a target=\"_blank\" href=\"" . $this->site_url . "\">" . $this->get_fullname() . "</a>";
        $this->f_url_link      = "<a target=\"_blank\" href=\"" . $this->url . "\">" . $this->url . "</a>";
        $this->f_site_url_link = "<a target=\"_blank\" href=\"" . $this->site_url . "\">" . $this->site_url . "</a>";
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        return array();
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        if (!isset($this->f_name)) {
            $this->f_name = $this->name;
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
            $this->link = $web_path . '/radio.php?action=show&radio=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): ?string
    {
        // don't do anything if it's formatted
        if (!isset($this->f_link)) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\">" . scrub_out($this->get_fullname()) . "</a>";
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
        if ($filter_type === null || $filter_type == 'live_stream') {
            $medias[] = array(
                'object_type' => 'live_stream',
                'object_id' => $this->id
            );
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return list<int>
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }

    /**
     * @return int|null
     */
    public function get_user_owner(): ?int
    {
        return null;
    }

    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
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
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'live_stream') || $force) {
            Art::display('live_stream', $this->id, (string)$this->get_fullname(), $thumb, $this->get_link());
        }
    }

    /**
     * update
     * This is a static function that takes a key'd array for input
     * it depends on a ID element to determine which radio element it
     * should be updating
     * @param array $data
     * @return int|false
     */
    public function update(array $data)
    {
        if (!$data['name']) {
            AmpError::add('general', T_('Name is required'));
        }

        $allowed_array = array('https', 'http', 'mms', 'mmsh', 'mmsu', 'mmst', 'rtsp', 'rtmp');

        $elements = explode(":", (string)$data['url']);

        if (!in_array($elements['0'], $allowed_array)) {
            AmpError::add('general', T_('URL is invalid, must be mms://, https:// or http://'));
        }

        if (!empty($data['site_url'])) {
            $elements = explode(":", (string)$data['site_url']);
            if (!in_array($elements['0'], $allowed_array)) {
                AmpError::add('site_url', T_('URL is invalid, must be http:// or https://'));
            }
        }

        if (AmpError::occurred()) {
            return false;
        }

        $sql = "UPDATE `live_stream` SET `name` = ?,`site_url` = ?,`url` = ?, codec = ? WHERE `id` = ?";
        Dba::write($sql,
            array(
                $data['name'] ?? $this->name,
                $data['site_url'] ?? null,
                $data['url'] ?? $this->url,
                strtolower((string)$data['codec']),
                $this->id
            )
        );

        return $this->id;
    } // update

    /**
     * create
     * This is a static function that takes a key'd array for input
     * and if everything is good creates the object.
     * @param array $data
     */
    public static function create(array $data): ?string
    {
        // Make sure we've got a name and codec
        if (!strlen((string)$data['name'])) {
            AmpError::add('name', T_('Name is required'));
        }
        if (!strlen((string)$data['codec'])) {
            AmpError::add('codec', T_('Codec is required (e.g. MP3, OGG...)'));
        }

        $allowed_array = array('https', 'http', 'mms', 'mmsh', 'mmsu', 'mmst', 'rtsp', 'rtmp');

        $elements = explode(":", (string)$data['url']);

        if (!in_array($elements['0'], $allowed_array)) {
            AmpError::add('url', T_('URL is invalid, must be http:// or https://'));
        }

        if (!empty($data['site_url'])) {
            $elements = explode(":", (string)$data['site_url']);
            if (!in_array($elements['0'], $allowed_array)) {
                AmpError::add('site_url', T_('URL is invalid, must be http:// or https://'));
            }
        }

        // Make sure it's a real catalog
        $catalog = Catalog::create_from_id($data['catalog']);
        if (!$catalog instanceof Catalog) {
            AmpError::add('catalog', T_('Catalog is invalid'));
        }

        if (AmpError::occurred()) {
            return null;
        }

        // If we've made it this far everything must be ok... I hope
        $sql = "INSERT INTO `live_stream` (`name`, `site_url`, `url`, `catalog`, `codec`) VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, array($data['name'], $data['site_url'], $data['url'], $catalog->id, strtolower((string)$data['codec'])));
        $insert_id = Dba::insert_id();
        if (!$insert_id) {
            return null;
        }
        Catalog::count_table('live_stream');

        return $insert_id;
    } // create

    /**
     * get_stream_types
     * This is needed by the media interface
     * @param string $player
     * @return array
     */
    public function get_stream_types($player = null)
    {
        return array('native');
    } // native_stream

    /**
     * play_url
     * This is needed by the media interface
     * @param string $additional_params
     * @param string $player
     * @param bool $local
     * @param string $sid
     * @param string $force_http
     */
    public function play_url($additional_params = '', $player = '', $local = false, $sid = '', $force_http = ''): string
    {
        return $this->url . $additional_params;
    } // play_url

    /**
     * get_stream_name
     */
    public function get_stream_name(): string
    {
        return (string)$this->get_fullname();
    }

    /**
     * get_transcode_settings
     *
     * This will probably never be implemented
     * @param string $target
     * @param string $player
     * @param array $options
     * @return array
     */
    public function get_transcode_settings($target = null, $player = null, $options = array())
    {
        return array();
    }

    /**
     * @param int $user_id
     * @param string $agent
     * @param array $location
     * @param int $date
     */
    public function set_played($user_id, $agent, $location, $date = null): bool
    {
        // Do nothing
        unset($user_id, $agent, $location, $date);

        return false;
    }

    /**
     * @param int $user
     * @param string $agent
     * @param int $date
     */
    public function check_play_history($user, $agent, $date): bool
    {
        // Do nothing
        unset($user, $agent, $date);

        return false;
    }

    public function remove(): bool
    {
        return true;
    }
}
