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
 *
 */

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\AmpError;
use Ampache\Repository\LiveStreamRepositoryInterface;

/**
 * This handles the internet radio stuff, that is inserted into live_stream
 * this can include podcasts or what-have-you
 */
final class Live_Stream extends database_object implements LiveStreamInterface
{
    private LiveStreamRepositoryInterface $liveStreamRepository;

    public int $id;

    public function __construct(
        LiveStreamRepositoryInterface $liveStreamRepository,
        int $id
    ) {
        $this->liveStreamRepository = $liveStreamRepository;
        $this->id                   = $id;
    }

    /**
     * @var null|array{
     *  id: int,
     *  name: string,
     *  site_url: string,
     *  url: string,
     *  genre: int,
     *  catalog: int,
     *  codec: string
     * }
     */
    private ?array $dbData = null;

    /**
     * @return array{
     *  id: int,
     *  name: string,
     *  site_url: string,
     *  url: string,
     *  genre: int,
     *  catalog: int,
     *  codec: string
     * }
     */
    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->liveStreamRepository->getDataById($this->getId());
        }

        return $this->dbData;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getDbData() === [];
    }

    /**
     * Not in use
     */
    public function format($details = true)
    {
    }

    public function getLink(): string
    {
        return sprintf(
            '%s/radio.php?action=show&radio=%d',
            AmpConfig::get('web_path'),
            $this->getId()
        );
    }

    public function getName(): string
    {
        return $this->getDbData()['name'] ?? '';
    }

    public function getUrl(): string
    {
        return $this->getDbData()['url'] ?? '';
    }

    public function getSiteUrl(): string
    {
        return $this->getDbData()['site_url'] ?? '';
    }

    public function getCodec(): string
    {
        return $this->getDbData()['codec'] ?? '';
    }

    /**
     * @return array
     */
    public function get_keywords()
    {
        return [];
    }

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->getName();
    }

    /**
     * @return array{object_type: string, object_id: int}|null
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
        return [];
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return [];
    }

    /**
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = [];
        if ($filter_type === null || $filter_type == 'live_stream') {
            $medias[] = [
                'object_type' => 'live_stream',
                'object_id' => $this->getId()
            ];
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return [];
    }

    /**
     * @return null
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
     * @return null
     */
    public function get_description()
    {
        return null;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->getId(), 'live_stream') || $force) {
            echo Art::display('live_stream', $this->getId(), $this->get_fullname(), $thumb, $this->getLink());
        }
    }

    /**
     * update
     * This is a static function that takes a key'd array for input
     * it depends on a ID element to determine which radio element it
     * should be updating
     * @param array $data
     * @return boolean|integer
     */
    public function update(array $data)
    {
        if (!$data['name']) {
            AmpError::add('general', T_('Name is required'));
        }

        $allowed_array = array('https', 'http', 'mms', 'mmsh', 'mmsu', 'mmst', 'rtsp', 'rtmp');

        $elements = explode(":", (string)$data['url']);

        if (!in_array($elements['0'], $allowed_array)) {
            AmpError::add('general', T_('URL is invalid, must be mms:// , https:// or http://'));
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

        $this->liveStreamRepository->update(
            $data['name'],
            $data['site_url'],
            $data['url'],
            strtolower((string) ($data['codec'] ?? '')),
            $this->getId()
        );

        return $this->getId();
    } // update

    /**
     * create
     * This is a static function that takes a key'd array for input
     * and if everything is good creates the object.
     * @param array $data
     */
    public static function create(array $data): bool
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
        if (!$catalog->name) {
            AmpError::add('catalog', T_('Catalog is invalid'));
        }

        if (AmpError::occurred()) {
            return false;
        }

        return static::getLiveStreamRepository()->create(
            $data['name'],
            $data['site_url'],
            $data['url'],
            $catalog->getId(),
            strtolower((string)$data['codec'])
        );
    } // create

    /**
     * get_stream_types
     * This is needed by the media interface
     * @param string $player
     * @return array
     */
    public function get_stream_types($player = null)
    {
        return array('foreign');
    } // native_stream

    /**
     * play_url
     * This is needed by the media interface
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @param string $sid
     * @param string $force_http
     * @return string
     */
    public function play_url($additional_params = '', $player = null, $local = false, $sid = '', $force_http = '')
    {
        return $this->getUrl() . $additional_params;
    } // play_url

    /**
     * @return string
     */
    public function get_stream_name()
    {
        return $this->get_fullname();
    }

    /**
     * get_transcode_settings
     *
     * This will probably never be implemented
     * @param string $target
     * @param string $player
     * @param array $options
     * @return false
     */
    public function get_transcode_settings($target = null, $player = null, $options = array())
    {
        return false;
    }

    /**
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param integer $date
     * @return boolean
     */
    public function set_played($user, $agent, $location, $date = null)
    {
        // Do nothing
        unset($user, $agent, $location, $date);

        return false;
    }

    /**
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public function check_play_history($user, $agent, $date)
    {
        // Do nothing
        unset($user, $agent, $date);

        return false;
    }

    public function remove()
    {
    }

    public function isEnabled(): bool
    {
        return true;
    }

    private static function getLiveStreamRepository(): LiveStreamRepositoryInterface
    {
        global $dic;

        return $dic->get(LiveStreamRepositoryInterface::class);
    }
}
