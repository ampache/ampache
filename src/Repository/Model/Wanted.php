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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\Wanted\MissingArtistRetrieverInterface;
use Ampache\Module\Wanted\WantedManagerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use Exception;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;
use MusicBrainz\MusicBrainz;

class Wanted extends database_object
{
    protected const DB_TABLENAME = 'wanted';

    public int $id = 0;

    public ?int $user = null;

    public ?int $artist = null;

    public ?string $artist_mbid = null;

    public ?string $mbid = null;

    public ?string $name = null;

    public ?int $year = null;

    public int $date;

    public int $accepted;

    /** @var null|string $link */
    public $link;

    /** @var null|string $f_link */
    public $f_link;

    /** @var null|string $f_artist_link */
    public $f_artist_link;

    /** @var null|string $f_user */
    public $f_user;

    /** @var array $songs */
    public $songs;

    /**
     * Constructor
     * @param int|null $wanted_id
     */
    public function __construct($wanted_id = 0)
    {
        if (!$wanted_id) {
            return;
        }

        $info = self::getWantedRepository()->getById((int) $wanted_id);
        if ($info === null) {
            return;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * get_missing_albums
     * Get list of library's missing albums from MusicBrainz
     * @param Artist|null $artist
     * @param string $mbid
     * @return list<Wanted>
     */
    public static function get_missing_albums($artist, $mbid = ''): array
    {
        $lookupId = $artist->mbid ?? $mbid;
        $mbrainz  = new MusicBrainz(new RequestsHttpAdapter());
        $includes = ['release-groups'];
        $types    = AmpConfig::get('wanted_types', []);
        if (is_string($types)) {
            $types = explode(',', $types);
        }

        if (!$types) {
            $types = [];
        }

        try {
            /**
             * https://musicbrainz.org/ws/2/artist/859a5c63-08df-42da-905c-7307f56db95d?inc=release-groups&fmt=json
             * @var object{
             *     sort-name: string,
             *     id: string,
             *     area: object,
             *     disambiguation: string,
             *     isnis: array,
             *     begin-area: ?string,
             *     name: string,
             *     ipis: array,
             *     release-groups: array,
             *     end-area: ?string,
             *     type: string,
             *     end_area: ?string,
             *     gender: ?string,
             *     life-span: object,
             *     gender-id: ?string,
             *     type-id: string,
             *     begin_area: ?string,
             *     country: string
             * } $martist
             */
            $martist = $mbrainz->lookup('artist', $lookupId, $includes);
            debug_event(self::class, 'get_missing_albums lookup: ' . $lookupId, 3);
        } catch (Exception $exception) {
            debug_event(self::class, 'get_missing_albums ERROR: ' . $exception, 3);

            return [];
        }

        $wartist = [];
        if ($artist === null) {
            $wartist['mbid'] = $lookupId;
            $wartist['name'] = $martist->{'name'};
            parent::add_to_cache('missing_artist', $lookupId, $wartist);
            $wartist = self::getMissingArtistRetriever()->retrieve($lookupId);
        }

        $wantedRepository = self::getWantedRepository();

        $results = [];
        if (!empty($martist)) {
            $user = Core::get_global('user');
            foreach ($martist->{'release-groups'} as $group) {
                if (is_array($types) && in_array(strtolower((string)$group->{'primary-type'}), $types)) {
                    $add     = true;
                    $g_count = count($group->{'secondary-types'});

                    for ($i = 0; $i < $g_count && $add; ++$i) {
                        $add = in_array(strtolower((string)$group->{'secondary-types'}[$i]), $types);
                    }

                    if (
                        $add &&
                        (static::getAlbumRepository()->getByMbidGroup(($group->id)) === [] ||
                            ($artist !== null && $artist->id && static::getAlbumRepository()->getByName($group->title, $artist->id) === []))
                    ) {
                        $wanted = $wantedRepository->findByMusicBrainzId($group->id);
                        if ($wanted !== null) {
                            $wanted->format();
                        } else {
                            $wanted       = $wantedRepository->prototype();
                            $wanted->mbid = $group->id;
                            if ($artist !== null) {
                                $wanted->artist = $artist->id;
                            } else {
                                $wanted->artist_mbid = $lookupId;
                            }

                            $wanted->user = $user?->getId();
                            $wanted->name = $group->title;
                            if (!empty($group->{'first-release-date'})) {
                                if (strlen((string)$group->{'first-release-date'}) == 4) {
                                    $wanted->year = $group->{'first-release-date'};
                                } elseif (strtotime((string) $group->{'first-release-date'})) {
                                    $wanted->year = (int)date("Y", strtotime((string) $group->{'first-release-date'}));
                                } else {
                                    $wanted->year = null;
                                }
                            }

                            $wanted->accepted = 0;
                            $wanted->link     = AmpConfig::get('web_path') . "/albums.php?action=show_missing&mbid=" . $group->id;
                            if ($artist !== null) {
                                $wanted->link .= "&artist=" . $wanted->artist;
                            } else {
                                $wanted->link .= "&artist_mbid=" . $lookupId;
                            }

                            $wanted->f_user        = Core::get_global('user')?->get_fullname() ?? '';
                            $wanted->f_link        = "<a href=\"" . $wanted->link . "\" title=\"" . $wanted->name . "\">" . $wanted->name . "</a>";
                            $wanted->f_artist_link = ($artist !== null)
                                ? $artist->get_f_link()
                                : $wartist['link'] ?? '';
                        }

                        if (
                            $user instanceof User &&
                            $wanted->mbid &&
                            $wanted->artist_mbid &&
                            $wanted->name &&
                            $wanted->year
                        ) {
                            static::getWantedManager()->add(
                                $user,
                                $wanted->mbid,
                                $wanted->artist,
                                $wanted->artist_mbid,
                                $wanted->name,
                                $wanted->year
                            );
                        }

                        $results[] = $wanted;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Accept a wanted request.
     */
    public function accept(): void
    {
        if (Core::get_global('user') instanceof User && Core::get_global('user')->has_access(AccessLevelEnum::MANAGER)) {
            $sql = "UPDATE `wanted` SET `accepted` = '1' WHERE `mbid` = ?";
            Dba::write($sql, [$this->mbid]);
            $this->accepted = 1;

            /** @var User $user */
            $user = Core::get_global('user');
            foreach (Plugin::get_plugins(PluginTypeEnum::WANTED_LOOKUP) as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin !== null && $plugin->load($user)) {
                    debug_event(self::class, 'Using Wanted Process plugin: ' . $plugin_name, 5);
                    $plugin->_plugin->process_wanted($this);
                }
            }
        }
    }

    /**
     * Show action buttons.
     */
    public function show_action_buttons(): string
    {
        if ($this->isNew() === false) {
            $result = '';
            if (
                $this->accepted === 0 &&
                (Core::get_global('user') instanceof User && Core::get_global('user')->has_access(AccessLevelEnum::MANAGER))
            ) {
                $result .= Ajax::button(
                    '?page=index&action=accept_wanted&mbid=' . $this->mbid,
                    'enable',
                    T_('Accept'),
                    'wanted_accept_' . $this->mbid
                );
            }

            /** @var User|null $user */
            $user = (Core::get_global('user') instanceof User) ? Core::get_global('user') : null;
            if (
                $user instanceof User &&
                (
                    $user->has_access(AccessLevelEnum::MANAGER) ||
                    (
                        $this->mbid !== null &&
                        self::getWantedRepository()->find($this->mbid, $user) &&
                        $this->accepted !== 1
                    )
                )
            ) {
                $result .= " " . Ajax::button('?page=index&action=remove_wanted&mbid=' . $this->mbid, 'hide_source', T_('Remove'), 'wanted_remove_' . $this->mbid);
            }

            return $result;
        } else {
            return Ajax::button('?page=index&action=add_wanted&mbid=' . $this->mbid . ($this->artist ? '&artist=' . $this->artist : '&artist_mbid=' . $this->artist_mbid) . '&name=' . urlencode((string)$this->name) . '&year=' . (int) $this->year, 'saved_search', T_('Add to wanted list'), 'wanted_add_' . $this->mbid);
        }
    }

    /**
     * Load wanted release data.
     */
    public function load_all(): void
    {
        $mbrainz     = new MusicBrainz(new RequestsHttpAdapter());
        $this->songs = [];

        try {
            $user            = Core::get_global('user');
            $preview_plugins = Plugin::get_plugins(PluginTypeEnum::SONG_PREVIEW_PROVIDER);
            if (
                $preview_plugins !== [] &&
                $user instanceof User &&
                $this->mbid !== null
            ) {
                /**
                 * https://musicbrainz.org/ws/2/release-group/3bd76d40-7f0e-36b7-9348-91a33afee20e?inc=releases&fmt=json
                 * @var object{
                 *     primary-type-id: string,
                 *     releases: array,
                 *     first-release-date: string,
                 *     secondary-type-ids: array,
                 *     id: string,
                 *     secondary-types: array,
                 *     title: string,
                 *     disambiguation: string,
                 *     primary-type: string
                 * } $group
                 */
                $group = $mbrainz->lookup('release-group', $this->mbid, ['releases']);
                // Set fresh data
                $this->name = $group->title;
                $this->year = (strtotime((string) $group->{'first-release-date'}))
                    ? (int)date("Y", strtotime((string) $group->{'first-release-date'}))
                    : null;

                // Load from database if already cached
                $this->songs = Song_Preview::get_song_previews($this->mbid);
                if (count($group->releases) > 0) {
                    // Use the first release as reference for track content
                    $release_mbid = $group->releases[0]->id;
                    if (count($this->songs) == 0) {
                        /**
                         * https://musicbrainz.org/ws/2/release/d8de198d-2162-4264-9cfe-926d92c4c7ad?inc=recordings&fmt=json
                         * @var object{
                         *     id: string,
                         *     media: array,
                         *     barcode: string,
                         *     date: string,
                         *     status-id: string,
                         *     asin: ?string,
                         *     quality: string,
                         *     title: string,
                         *     cover-art-archive: object,
                         *     release-events: array,
                         *     packaging: string,
                         *     disambiguation: string,
                         *     text-representation: object,
                         *     country: ?string,
                         *     status: string,
                         *     packaging-id: string,
                         * } $release
                         */
                        $release = $mbrainz->lookup('release', $release_mbid, ['recordings']);

                        foreach ($release->media as $media) {
                            foreach ($media->tracks as $track) {
                                $song                = [];
                                $song['disk']        = Album::sanitize_disk($media->position);
                                $song['track']       = $track->number;
                                $song['title']       = $track->title;
                                $song['mbid']        = $track->id;
                                $song['artist']      = $this->artist;
                                $song['artist_mbid'] = $this->artist_mbid;
                                $song['session']     = session_id();
                                $song['album_mbid']  = $this->mbid;

                                if ($this->artist) {
                                    $artist      = new Artist($this->artist);
                                    $artist_name = $artist->name;
                                } elseif ($this->artist_mbid !== null) {
                                    $wartist     = self::getMissingArtistRetriever()->retrieve($this->artist_mbid);
                                    $artist_name = $wartist['name'] ?? '';
                                } else {
                                    $artist_name = '';
                                }

                                $song['file'] = null;
                                foreach ($preview_plugins as $plugin_name) {
                                    $plugin = new Plugin($plugin_name);
                                    if ($plugin->_plugin !== null && $plugin->load($user)) {
                                        $song['file'] = $plugin->_plugin->get_song_preview($track->id, $artist_name, $track->title);
                                        if ($song['file'] !== null) {
                                            break;
                                        }
                                    }
                                }

                                if ($song['file'] !== null) {
                                    $this->songs[] = new Song_Preview(Song_Preview::insert($song));
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception) {
            $this->songs = [];
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
            $wartist             = self::getMissingArtistRetriever()->retrieve($this->artist_mbid);
            $this->f_artist_link = $wartist['link'] ?? '';
        } else {
            $this->f_artist_link = '';
        }


        $this->f_link = sprintf(
            '<a href="/albums.php?action=show_missing&mbid=%s&artist=%s&artist_mbid=%s" title="%s">%s</a>',
            $this->mbid,
            $this->artist,
            $this->artist_mbid,
            $this->name,
            scrub_out($this->name)
        );

        if ($this->user !== null) {
            $user         = new User($this->user);
            $this->f_user = $user->get_fullname();
        }
    }

    public function getMusicBrainzId(): ?string
    {
        return $this->mbid;
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getWantedManager(): WantedManagerInterface
    {
        global $dic;

        return $dic->get(WantedManagerInterface::class);
    }
    /**
     * @deprecated Inject dependency
     */
    private static function getWantedRepository(): WantedRepositoryInterface
    {
        global $dic;

        return $dic->get(WantedRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getMissingArtistRetriever(): MissingArtistRetrieverInterface
    {
        global $dic;

        return $dic->get(MissingArtistRetrieverInterface::class);
    }
}
