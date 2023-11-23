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

use Ampache\Module\Api\Ajax;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use Exception;
use MusicBrainz\MusicBrainz;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;
use PDOStatement;

class Wanted extends database_object
{
    protected const DB_TABLENAME = 'wanted';

    /* Variables from DB */
    public int $id = 0;
    public ?int $user;
    public ?int $artist;
    public ?string $artist_mbid;
    public ?string $mbid;
    public ?string $name;
    public ?int $year;
    public int $date;
    public bool $accepted;

    /**
     * @var null|string $release_mbid
     */
    public $release_mbid;

    /**
     * @var null|string $link
     */
    public $link;

    /**
     * @var null|string $f_link
     */
    public $f_link;
    /**
     * @var null|string $f_artist_link
     */
    public $f_artist_link;
    /**
     * @var null|string $f_user
     */
    public $f_user;
    /**
     * @var array $songs
     */
    public $songs;

    /**
     * Constructor
     * @param int $wanted_id
     */
    public function __construct($wanted_id)
    {
        $info = static::getWantedRepository()->getById((int) $wanted_id);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * get_missing_albums
     * Get list of library's missing albums from MusicBrainz
     * @param Artist|null $artist
     * @param string $mbid
     * @return array
     */
    public static function get_missing_albums($artist, $mbid = '')
    {
        $lookupId = $artist->mbid ?? $mbid;
        $mbrainz  = new MusicBrainz(new RequestsHttpAdapter());
        $includes = array('release-groups');
        $types    = AmpConfig::get('wanted_types', array());
        if (is_string($types)) {
            $types = explode(',', $types);
        }
        if (!$types) {
            $types = array();
        }
        try {
            $martist = $mbrainz->lookup('artist', $lookupId, $includes);
            debug_event(self::class, 'get_missing_albums lookup: ' . $lookupId, 3);
        } catch (Exception $error) {
            debug_event(self::class, 'get_missing_albums ERROR: ' . $error, 3);

            return array();
        }

        $wartist = array();
        if (!$artist) {
            $wartist['mbid'] = $lookupId;
            $wartist['name'] = $martist->{'name'};
            parent::add_to_cache('missing_artist', $lookupId, $wartist);
            $wartist = self::get_missing_artist($lookupId);
        }

        $results = array();
        if (!empty($martist)) {
            foreach ($martist->{'release-groups'} as $group) {
                if (is_array($types) && in_array(strtolower((string)$group->{'primary-type'}), $types)) {
                    $add     = true;
                    $g_count = count($group->{'secondary-types'});

                    for ($i = 0; $i < $g_count && $add; ++$i) {
                        $add = in_array(strtolower((string)$group->{'secondary-types'}[$i]), $types);
                    }

                    if ($add) {
                        if (empty(static::getAlbumRepository()->getByMbidGroup(($group->id))) && (!empty($artist) && $artist->id && empty(static::getAlbumRepository()->getByName($group->title, $artist->id)))) {
                            $wantedid = self::get_wanted($group->id);
                            $wanted   = new Wanted($wantedid);
                            if ($wanted->id) {
                                $wanted->format();
                            } else {
                                $wanted->mbid = $group->id;
                                if ($artist) {
                                    $wanted->artist = $artist->id;
                                } else {
                                    $wanted->artist_mbid = $lookupId;
                                }
                                $wanted->name = $group->title;
                                if (!empty($group->{'first-release-date'})) {
                                    if (strlen((string)$group->{'first-release-date'}) == 4) {
                                        $wanted->year = $group->{'first-release-date'};
                                    } else {
                                        $wanted->year = (int)date("Y", strtotime($group->{'first-release-date'}));
                                    }
                                }
                                $wanted->accepted = false;
                                $wanted->link     = AmpConfig::get('web_path') . "/albums.php?action=show_missing&mbid=" . $group->id;
                                if ($artist) {
                                    $wanted->link .= "&artist=" . $wanted->artist;
                                } else {
                                    $wanted->link .= "&artist_mbid=" . $lookupId;
                                }
                                $wanted->f_link        = "<a href=\"" . $wanted->link . "\" title=\"" . $wanted->name . "\">" . $wanted->name . "</a>";
                                $wanted->f_artist_link = $artist->get_f_link() ?? $wartist['link'];
                                $wanted->f_user        = Core::get_global('user')->f_name;
                            }
                            $results[] = $wanted;
                        }
                    }
                }
            }
        }

        return $results;
    } // get_missing_albums

    /**
     * Get missing artist data.
     * @param string $mbid
     * @return array
     */
    public static function get_missing_artist($mbid)
    {
        $wartist = array();
        if (empty($mbid)) {
            return $wartist;
        }

        if (parent::is_cached('missing_artist', $mbid)) {
            $wartist = parent::get_from_cache('missing_artist', $mbid);
        } else {
            $mbrainz         = new MusicBrainz(new RequestsHttpAdapter());
            $wartist['mbid'] = $mbid;
            $wartist['name'] = T_('Unknown Artist');

            try {
                $martist = $mbrainz->lookup('artist', $mbid);
            } catch (Exception $error) {
                return $wartist;
            }
            if (!isset($martist->{'name'})) {
                return $wartist;
            }

            $wartist['name'] = $martist->{'name'};
            parent::add_to_cache('missing_artist', $mbid, $wartist);
        }

        $wartist['link'] = "<a href=\"" . AmpConfig::get('web_path') . "/artists.php?action=show_missing&mbid=" . $wartist['mbid'] . "\" title=\"" . $wartist['name'] . "\">" . $wartist['name'] . "</a>";

        return $wartist;
    }

    /**
     * Get wanted release by mbid.
     * @param string $mbid
     */
    public static function get_wanted($mbid): int
    {
        $sql        = "SELECT `id` FROM `wanted` WHERE `mbid` = ?";
        $db_results = Dba::read($sql, array($mbid));
        if ($row = Dba::fetch_assoc($db_results)) {
            return (int)$row['id'];
        }

        return 0;
    }

    /**
     * Get wanted release by name.
     * @param string $name
     */
    public static function get_wanted_by_name($name): int
    {
        $sql        = "SELECT `id` FROM `wanted` WHERE `name` = ? LIMIT 1";
        $db_results = Dba::read($sql, array($name));
        if ($row = Dba::fetch_assoc($db_results)) {
            return (int)$row['id'];
        }

        return 0;
    }

    /**
     * Delete a wanted release by mbid.
     * @param string $mbid
     * @throws \MusicBrainz\Exception
     */
    public static function delete_wanted_release($mbid): void
    {
        if (static::getWantedRepository()->getAcceptedCount() > 0) {
            $mbrainz = new MusicBrainz(new RequestsHttpAdapter());
            $malbum  = $mbrainz->lookup('release', $mbid, array('release-groups'));
            if ($malbum->{'release-group'}) {
                $userId = (empty(Core::get_global('user')) || Core::get_global('user')->has_access(75))
                    ? null
                    : Core::get_global('user')->id;
                static::getWantedRepository()->deleteByMusicbrainzId(
                    print_r($malbum->{'release-group'}, true),
                    $userId
                );
            }
        }
    }

    /**
     * Accept a wanted request.
     */
    public function accept()
    {
        if (!empty(Core::get_global('user')) && Core::get_global('user')->has_access(75)) {
            $sql = "UPDATE `wanted` SET `accepted` = '1' WHERE `mbid` = ?";
            Dba::write($sql, array($this->mbid));
            $this->accepted = true;

            foreach (Plugin::get_plugins('process_wanted') as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load(Core::get_global('user'))) {
                    debug_event(self::class, 'Using Wanted Process plugin: ' . $plugin_name, 5);
                    $plugin->_plugin->process_wanted($this);
                }
            }
        }
    }

    /**
     * Add a new wanted release.
     * @param string $mbid
     * @param int $artist
     * @param string $artist_mbid
     * @param string $name
     * @param int $year
     */
    public static function add_wanted($mbid, $artist, $artist_mbid, $name, $year): void
    {
        $sql    = "INSERT INTO `wanted` (`user`, `artist`, `artist_mbid`, `mbid`, `name`, `year`, `date`, `accepted`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $accept = Core::get_global('user')->has_access(75) ? true : AmpConfig::get('wanted_auto_accept', false);
        $params = array(Core::get_global('user')->id, $artist, $artist_mbid, $mbid, $name, (int) $year, time(), '0');
        Dba::write($sql, $params);

        if ($accept) {
            $wanted_id = (int)Dba::insert_id();
            $wanted    = new Wanted($wanted_id);
            $wanted->accept();

            database_object::remove_from_cache('wanted', $wanted_id);
        }
    }

    /**
     * Show action buttons.
     */
    public function show_action_buttons()
    {
        if ($this->id) {
            if (!$this->accepted) {
                if ((!empty(Core::get_global('user')) && Core::get_global('user')->has_access(75))) {
                    echo Ajax::button('?page=index&action=accept_wanted&mbid=' . $this->mbid, 'enable', T_('Accept'),
                        'wanted_accept_' . $this->mbid);
                }
            }
            if (
                Core::get_global('user')->has_access(75) ||
                (
                    $this->mbid !== null &&
                    static::getWantedRepository()->find($this->mbid, Core::get_global('user')->id) &&
                    $this->accepted != '1'
                )
            ) {
                echo " " . Ajax::button('?page=index&action=remove_wanted&mbid=' . $this->mbid, 'disable', T_('Remove'), 'wanted_remove_' . $this->mbid);
            }
        } else {
            echo Ajax::button('?page=index&action=add_wanted&mbid=' . $this->mbid . ($this->artist ? '&artist=' . $this->artist : '&artist_mbid=' . $this->artist_mbid) . '&client=' . urlencode((string)$this->name) . '&year=' . (int) $this->year, 'add_wanted', T_('Add to wanted list'), 'wanted_add_' . $this->mbid);
        }
    }

    /**
     * Load wanted release data.
     * @param bool $track_details
     */
    public function load_all($track_details = true)
    {
        $mbrainz     = new MusicBrainz(new RequestsHttpAdapter());
        $this->songs = array();

        try {
            if ($this->mbid !== null) {
                $group = $mbrainz->lookup('release-group', $this->mbid, array('releases'));
                // Set fresh data
                $this->name = $group->title;
                $this->year = (int)date("Y", strtotime($group->{'first-release-date'}));

                // Load from database if already cached
                $this->songs = Song_Preview::get_song_previews($this->mbid);
                if (count($group->releases) > 0) {
                    $this->release_mbid = $group->releases[0]->id;
                    if ($track_details && count($this->songs) == 0) {
                        // Use the first release as reference for track content
                        $release = $mbrainz->lookup('release', $this->release_mbid, array('recordings'));
                        foreach ($release->media as $media) {
                            foreach ($media->tracks as $track) {
                                $song                = array();
                                $song['disk']        = Album::sanitize_disk($media->position);
                                $song['track']       = $track->number;
                                $song['title']       = $track->title;
                                $song['mbid']        = $track->id;
                                $song['artist']      = $group->artist ?? $this->artist;
                                $song['artist_mbid'] = $this->artist_mbid;
                                $song['session']     = session_id();
                                $song['album_mbid']  = $this->mbid;

                                if ($this->artist) {
                                    $artist      = new Artist($this->artist);
                                    $artist_name = $artist->name;
                                } elseif ($this->artist_mbid !== null) {
                                    $wartist     = Wanted::get_missing_artist($this->artist_mbid);
                                    $artist_name = $wartist['name'];
                                } else {
                                    $artist_name = '';
                                }

                                $song['file'] = null;
                                foreach (Plugin::get_plugins('get_song_preview') as $plugin_name) {
                                    $plugin = new Plugin($plugin_name);
                                    if ($plugin->load(Core::get_global('user'))) {
                                        $song['file'] = $plugin->_plugin->get_song_preview($track->id, $artist_name, $track->title);
                                        if ($song['file'] != null) {
                                            break;
                                        }
                                    }
                                }

                                if ($song != null) {
                                    $this->songs[] = new Song_Preview(Song_Preview::insert($song));
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $error) {
            $this->songs = array();
        }

        foreach ($this->songs as $song) {
            $song->f_album = $this->name;
            $song->format();
        }
    }

    /**
     * Format data.
     */
    public function format(): void
    {
        if ($this->artist) {
            $artist              = new Artist($this->artist);
            $this->f_artist_link = $artist->get_f_link();
        } elseif ($this->artist_mbid !== null) {
            $wartist             = Wanted::get_missing_artist($this->artist_mbid);
            $this->f_artist_link = $wartist['link'];
        } else {
            $this->f_artist_link = '';
        }
        $this->f_link = "<a href=\"" . $this->get_link() . "\">" . scrub_out($this->name) . "</a>";
        if (isset($this->user)) {
            $user         = new User($this->user);
            $this->f_user = $user->get_fullname();
        }
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->name;
    }

    /**
     * Get item link.
     */
    public function get_link(): ?string
    {
        // don't do anything if it's formatted
        if (!isset($this->link)) {
            $web_path   = AmpConfig::get('web_path');
            $this->link = $web_path . "/albums.php?action=show_missing&mbid=" . $this->mbid . "&artist=" . $this->artist . "&artist_mbid=" . $this->artist_mbid . "\" title=\"" . $this->name;
        }

        return $this->link;
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @return PDOStatement|bool
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        if ($object_type == 'artist') {
            $sql    = "UPDATE `wanted` SET `artist` = ? WHERE `artist` = ?";
            $params = array($new_object_id, $old_object_id);

            return Dba::write($sql, $params);
        }

        return false;
    }

    /**
     * garbage_collection
     *
     * This cleans out unused artists
     */
    public static function garbage_collection(): void
    {
        Dba::write("DELETE FROM `wanted` WHERE `wanted`.`artist` NOT IN (SELECT `artist`.`id` FROM `artist`);");
    }

    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    private static function getWantedRepository(): WantedRepositoryInterface
    {
        global $dic;

        return $dic->get(WantedRepositoryInterface::class);
    }
}
