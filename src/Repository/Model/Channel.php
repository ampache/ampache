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
use Ampache\Module\System\Dba;
use Ampache\Module\Tag\TagListUpdaterInterface;
use Ampache\Repository\ChannelRepositoryInterface;

class Channel extends database_object implements Media, library_item
{
    protected const DB_TABLENAME = 'channel';

    public $id;
    private $interface;
    private $port;
    private $start_date;
    private $pid;
    private $listeners;
    private $peak_listeners;


    /** @var array<string, mixed>|null */
    private ?array $dbData = null;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFixedEndpoint(): int
    {
        return (int) ($this->getDbData()['fixed_endpoint'] ?? 0);
    }

    public function getUrl(): string
    {
        return $this->getDbData()['url'] ?? '';
    }

    public function getDescription(): string
    {
        return $this->getDbData()['description'] ?? '';
    }

    public function getName(): string
    {
        return $this->getDbData()['name'] ?? '';
    }

    public function getBitrate(): int
    {
        return (int) ($this->getDbData()['bitrate'] ?? 0);
    }

    public function getLoop(): int
    {
        return (int) ($this->getDbData()['loop'] ?? 0);
    }

    public function getRandom(): int
    {
        return (int) ($this->getDbData()['random'] ?? 0);
    }

    public function getStreamType(): string
    {
        return $this->getDbData()['stream_type'] ?? '';
    }

    public function getObjectId(): int
    {
        return (int) ($this->getDbData()['object_id'] ?? 0);
    }

    public function getObjectType(): string
    {
        return $this->getDbData()['object_type'] ?? '';
    }

    public function getPeakListeners(): int
    {
        return (int) ($this->getDbData()['peak_listeners'] ?? 0);
    }

    public function getMaxListeners(): int
    {
        return (int) ($this->getDbData()['max_listeners'] ?? 0);
    }

    public function getListeners(): int
    {
        return (int) ($this->getDbData()['listeners'] ?? 0);
    }

    public function getPid(): int
    {
        return (int) ($this->getDbData()['pid'] ?? 0);
    }

    public function getStartDate(): int
    {
        return (int) ($this->getDbData()['start_date'] ?? 0);
    }

    public function getPort(): int
    {
        return (int) ($this->getDbData()['port'] ?? 0);
    }

    public function getInterface(): string
    {
        return $this->getDbData()['interface'] ?? '';
    }

    public function getIsPrivate(): int
    {
        return (int) ($this->getDbData()['is_private'] ?? 0);
    }

    public function isNew(): bool
    {
        return $this->getDbData() === [];
    }

    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->getChannelRepository()->getDataById($this->id);
        }

        return $this->dbData;
    }

    /**
     * update_start
     * @param string $start_date
     * @param string $address
     * @param string $port
     * @param string $pid
     */
    public function update_start($start_date, $address, $port, $pid)
    {
        $sql = "UPDATE `channel` SET `start_date` = ?, `interface` = ?, `port` = ?, `pid` = ?, `listeners` = '0' WHERE `id` = ?";
        Dba::write($sql, array($start_date, $address, $port, $pid, $this->id));

        $this->start_date = $start_date;
        $this->interface  = $address;
        $this->port       = (int)$port;
        $this->pid        = $pid;
    }

    /**
     * update_listeners
     * @param integer $listeners
     * @param boolean $addition
     */
    public function update_listeners($listeners, $addition = false)
    {
        $sql             = "UPDATE `channel` SET `listeners` = ? ";
        $params          = array($listeners);
        $this->listeners = $listeners;
        if ($listeners > $this->getPeakListeners()) {
            $this->peak_listeners = $listeners;
            $sql .= ", `peak_listeners` = ? ";
            $params[] = $listeners;
        }
        if ($addition) {
            $sql .= ", `connections`=`connections`+1 ";
        }
        $sql .= "WHERE `id` = ?";
        $params[] = $this->id;
        Dba::write($sql, $params);
    }

    /**
     * get_genre
     * @return string
     */
    public function get_genre()
    {
        $tags  = Tag::get_object_tags('channel', $this->id);
        $genre = "";
        if ($tags) {
            foreach ($tags as $tag) {
                $genre .= $tag['name'] . ' ';
            }
            $genre = trim((string)$genre);
        }

        return $genre;
    }

    /**
     * delete
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM `channel` WHERE `id` = ?";

        return Dba::write($sql, array($this->id));
    }

    /**
     * get_next_port
     * @return integer
     */
    public static function get_next_port()
    {
        $port       = 8200;
        $sql        = "SELECT MAX(`port`) AS `max_port` FROM `channel`";
        $db_results = Dba::read($sql);

        if ($results = Dba::fetch_assoc($db_results)) {
            if ($results['max_port'] > 0) {
                $port = $results['max_port'] + 1;
            }
        }

        return $port;
    }

    /**
     * create
     * @param string $name
     * @param string $description
     * @param string $url
     * @param string $object_type
     * @param integer $object_id
     * @param array $interface
     * @param array $port
     * @param string $admin_password
     * @param string $private
     * @param string $max_listeners
     * @param string $random
     * @param string $loop
     * @param string $stream_type
     * @param string $bitrate
     */
    public static function create(
        $name,
        $description,
        $url,
        $object_type,
        $object_id,
        $interface,
        $port,
        $admin_password,
        $private,
        $max_listeners,
        $random,
        $loop,
        $stream_type,
        $bitrate
    ): bool {
        if (!empty($name)) {
            $sql    = "INSERT INTO `channel` (`name`, `description`, `url`, `object_type`, `object_id`, `interface`, `port`, `fixed_endpoint`, `admin_password`, `is_private`, `max_listeners`, `random`, `loop`, `stream_type`, `bitrate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = array(
                $name,
                $description,
                $url,
                $object_type,
                $object_id,
                $interface,
                $port,
                (!empty($interface) && !empty($port)),
                $admin_password,
                $private,
                $max_listeners,
                $random,
                $loop,
                $stream_type,
                $bitrate
            );

            return Dba::write($sql, $params);
        }

        return false;
    }

    /**
     * update
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        if (isset($data['edit_tags'])) {
            $this->getTagListUpdater()->update($data['edit_tags'], 'channel', $this->id, true);
        }

        $sql    = "UPDATE `channel` SET `name` = ?, `description` = ?, `url` = ?, `interface` = ?, `port` = ?, `fixed_endpoint` = ?, `admin_password` = ?, `is_private` = ?, `max_listeners` = ?, `random` = ?, `loop` = ?, `stream_type` = ?, `bitrate` = ?, `object_id` = ? " . "WHERE `id` = ?";
        $params = array(
            $data['name'],
            $data['description'],
            $data['url'],
            $data['interface'],
            $data['port'],
            (!empty($data['interface']) && !empty($data['port'])),
            $data['admin_password'],
            (int) $data['private'],
            $data['max_listeners'],
            (int) $data['random'],
            $data['loop'],
            $data['stream_type'],
            $data['bitrate'],
            $data['object_id'],
            $this->id
        );
        Dba::write($sql, $params);

        return $this->id;
    }

    /**
     * format_type
     * @param string $type
     * @return string
     */
    public static function format_type($type)
    {
        switch ($type) {
            case 'playlist':
                $ftype = $type;
                break;
            default:
                $ftype = '';
                break;
        }

        return $ftype;
    }

    /**
     * format
     * @param boolean $details
     */
    public function format($details = true)
    {
    }

    /**
     * @return array<int, array{
     *  user: int,
     *  id: int,
     *  name: string
     * }>
     */
    public function getTags(): array
    {
        return Tag::get_top_tags('channel', $this->id);
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        return array();
    }

    /**
     * get_fullname
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
     * get_childrens
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * search_childrens
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * get_medias
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'channel') {
            $medias[] = array(
                'object_type' => 'channel',
                'object_id' => $this->id
            );
        }

        return $medias;
    }

    /**
     * get_user_owner
     * @return boolean|null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * get_default_art_kind
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * get_description
     * @return string
     */
    public function get_description()
    {
        return $this->getDescription();
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'channel') || $force) {
            echo Art::display('channel', $this->id, $this->get_fullname(), $thumb);
        }
    }

    /**
     * get_target_object
     * @return ?Playlist
     */
    public function get_target_object()
    {
        $object = null;
        if ($this->getObjectType() == 'playlist') {
            $object = new Playlist($this->getObjectId());
            $object->format();
        }

        return $object;
    }

    /**
     * get_stream_url
     * show the internal interface used for the stream
     * e.g. http://0.0.0.0:8200/stream.mp3
     *
     * @return string
     */
    public function get_stream_url()
    {
        return "http://" . $this->getInterface() . ":" . $this->getPort() . "/stream." . $this->getStreamType();
    }

    /**
     * get_stream_proxy_url
     * show the external address used for the stream
     * e.g. https://music.com.au/channel/6/stream.mp3
     *
     * @return string
     */
    public function get_stream_proxy_url()
    {
        return AmpConfig::get('web_path') . '/channel/' . $this->getId() . '/stream.' . $this->getStreamType();
    }

    /**
     * get_stream_proxy_url_status
     * show the external address used for the stream
     * e.g. https://music.com.au/channel/6/status.xsl
     *
     * @return string
     */
    public function get_stream_proxy_url_status()
    {
        return AmpConfig::get('web_path') . '/channel/' . $this->id . '/status.xsl';
    }

    /**
     * start_channel
     */
    public function start_channel()
    {
        $path = __DIR__ . '/../../../bin/cli';
        $cmd  = sprintf(
            'env php %s run:channel %d > /dev/null &',
            $path,
            $this->id
        );
        exec($cmd);
    }

    /**
     * stop_channel
     */
    public function stop_channel()
    {
        if ($this->getPid()) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /PID " . $this->getPid());
            } else {
                exec("kill -9 " . $this->getPid());
            }

            $sql = "UPDATE `channel` SET `start_date` = '0', `listeners` = '0', `pid` = '0' WHERE `id` = ?";
            Dba::write($sql, array($this->id));

            $this->pid = 0;
        }
    }

    /**
     * check_channel
     * @return boolean
     */
    public function check_channel()
    {
        $check = false;
        if ($this->getInterface() && $this->getPort()) {
            $connection = @fsockopen($this->getInterface(), $this->getPort());
            if (is_resource($connection)) {
                $check = true;
                fclose($connection);
            }
        }

        return $check;
    }

    /**
     * get_channel_state
     * @return string
     */
    public function get_channel_state()
    {
        if ($this->check_channel()) {
            $state = T_("Running");
        } else {
            $state = T_("Stopped");
        }

        return $state;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array();
    }

    /**
     * play_url
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @return string
     */
    public function play_url($additional_params = '', $player = null, $local = false)
    {
        return $this->get_stream_proxy_url() . '?rt=' . time() . '&filename=' . urlencode($this->getName()) . '.' . $this->getStreamType() . $additional_params;
    }

    /**
     * get_stream_types
     * @param string $player
     * @return string[]
     */
    public function get_stream_types($player = null)
    {
        // Transcode is mandatory to keep a consistant stream
        return array('transcode');
    }

    /**
     * get_stream_name
     * @return string
     */
    public function get_stream_name()
    {
        return $this->get_fullname();
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

    /**
     * @param $target
     * @param $player
     * @param array $options
     * @return boolean
     */
    public function get_transcode_settings($target = null, $player = null, $options = array())
    {
        return false;
    }

    public function remove()
    {
    }

    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getTagListUpdater(): TagListUpdaterInterface
    {
        global $dic;

        return $dic->get(TagListUpdaterInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getChannelRepository(): ChannelRepositoryInterface
    {
        global $dic;

        return $dic->get(ChannelRepositoryInterface::class);
    }
}
