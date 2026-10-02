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

namespace Ampache\Module\Api\Subsonic\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Subsonic\MusicFolderResolverInterface;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic_Api;
use Ampache\Module\Api\Subsonic_Json_Data;
use Ampache\Module\Api\Subsonic_Xml_Data;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;

final class BrowsingHandler implements BrowsingHandlerInterface
{
    public function __construct(
        private readonly AlbumRepositoryInterface $albumRepository,
        private readonly ArtistRepositoryInterface $artistRepository,
        private readonly FolderRepositoryInterface $folderRepository,
        private readonly MusicFolderResolverInterface $musicFolderResolver,
        private readonly Random $random,
        private readonly SubsonicResponseHandlerInterface $responseHandler,
        private readonly SongRepositoryInterface $songRepository,
        private readonly Subsonic_Json_Data $subsonicJsonData,
        private readonly Subsonic_Xml_Data $subsonicXmlData,
    ) {}

    /**
     * getAlbum
     *
     * Returns details for an album.
     * https://www.subsonic.org/pages/api.jsp#getalbum
     * @param array<string, mixed> $input
     */
    public function getalbum(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $album = Subsonic_Api::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumID3($response, $album, true);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumID3($response, $album, true);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumInfo
     *
     * Returns album info.
     * https://www.subsonic.org/pages/api.jsp#getalbuminfo
     * @param array<string, mixed> $input
     */
    public function getalbuminfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $album = Subsonic_Api::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $info   = Recommendation::get_album_info($album->getId());
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumInfo($response, $info, $album);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumInfo($response, $info, $album);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumInfo2
     *
     * Returns album info.
     * https://www.subsonic.org/pages/api.jsp#getalbuminfo2
     * @param array<string, mixed> $input
     */
    public function getalbuminfo2(array $input, User $user): void
    {
        $this->getalbuminfo($input, $user);
    }

    /**
     * getAlbumList
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://www.subsonic.org/pages/api.jsp#getalbumlist
     * @param array<string, mixed> $input
     */
    public function getalbumlist(array $input, User $user): void
    {
        $type = $this->responseHandler->checkParameter($input, 'type', __FUNCTION__);
        if ($type === false) {
            return;
        }

        if ($type === 'byGenre' && !$this->responseHandler->checkParameter($input, 'genre', __FUNCTION__)) {
            return;
        }

        $albums = $this->albumList($input, $user, (string) $type);
        if ($albums === null) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumList($response, $albums);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumList($response, $albums);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumList2
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://www.subsonic.org/pages/api.jsp#getalbumlist2
     * @param array<string, mixed> $input
     */
    public function getalbumlist2(array $input, User $user): void
    {
        $type = $this->responseHandler->checkParameter($input, 'type', __FUNCTION__);
        if ($type === false) {
            return;
        }

        if ($type === 'byGenre' && !$this->responseHandler->checkParameter($input, 'genre', __FUNCTION__)) {
            return;
        }

        $albums = $this->albumList($input, $user, (string) $type);
        if ($albums === null) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumList2($response, $albums);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumList2($response, $albums);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtist
     *
     * Returns details for an artist.
     * https://www.subsonic.org/pages/api.jsp#getartist
     * @param array<string, mixed> $input
     */
    public function getartist(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = new Artist(Subsonic_Api::getAmpacheId($sub_id));
        if ($artist->isNew()) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistID3($response, $artist, true);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistWithAlbumsID3($response, $artist);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtistInfo
     *
     * Returns artist info.
     * https://www.subsonic.org/pages/api.jsp#getartistinfo
     * @param array<string, mixed> $input
     */
    public function getartistinfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = Subsonic_Api::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = (int) ($input['count'] ?? 20);
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistInfo($response, $info, $artist, $similars);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistInfo($response, $info, $artist, $similars);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtistInfo2
     *
     * Returns artist info.
     * https://www.subsonic.org/pages/api.jsp#getartistinfo2
     * @param array<string, mixed> $input
     */
    public function getartistinfo2(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = Subsonic_Api::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = (int) ($input['count'] ?? 20);
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistInfo2($response, $info, $artist, $similars);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistInfo2($response, $info, $artist, $similars);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtists
     *
     * Returns all artists.
     * https://www.subsonic.org/pages/api.jsp#getartists
     * @param array<string, mixed> $input
     */
    public function getartists(array $input, User $user): void
    {
        $catalogs = $this->musicFolderResolver->musicFolders($input, $user);

        $user_id = $user->id;
        // an empty catalog list makes get_id_arrays return everything, so only ask when there is something to ask for
        $artists = ($catalogs === [])
            ? []
            : Artist::get_id_arrays($catalogs, ((bool) Preference::get_by_user($user_id, 'subsonic_force_album_artist') === true));

        $format  = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtists($response, $artists);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtists($response, $artists);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getGenres
     *
     * Returns all genres.
     * https://www.subsonic.org/pages/api.jsp#getgenres
     * @param array<string, mixed> $input
     */
    public function getgenres(array $input, User $user): void
    {
        unset($user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addGenres($response, Tag::get_tags('song'));
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addGenres($response, Tag::get_tags('song'));
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getIndexes
     *
     * Returns an indexed structure of all artists.
     * https://www.subsonic.org/pages/api.jsp#getindexes
     * @param array<string, mixed> $input
     */
    public function getindexes(array $input, User $user): void
    {
        set_time_limit(300);

        $ifModifiedSince = $input['ifModifiedSince'] ?? '';
        $catalogs        = $this->musicFolderResolver->musicFolders($input, $user);

        $lastmodified = 0;
        $fcatalogs    = [];

        foreach ($catalogs as $catalogid) {
            $clastmodified = 0;
            $catalog       = Catalog::create_from_id($catalogid);
            if ($catalog === null) {
                // `get_catalogs()` appends the pseudo-catalog 0, and a type whose module is gone resolves
                // to null too: skipping one must not drop every catalog listed after it
                continue;
            }
            if ($catalog->last_update > $clastmodified) {
                $clastmodified = $catalog->last_update;
            }
            if ($catalog->last_add > $clastmodified) {
                $clastmodified = $catalog->last_add;
            }
            if ($catalog->last_clean > $clastmodified) {
                $clastmodified = $catalog->last_clean;
            }

            if ($clastmodified > $lastmodified) {
                $lastmodified = $clastmodified;
            }
            if (!empty($ifModifiedSince) && $clastmodified > (((int) $ifModifiedSince) / 1000)) {
                $fcatalogs[] = $catalogid;
            }
        }
        if (empty($ifModifiedSince)) {
            $fcatalogs = $catalogs;
        }

        // the folder tree only exists once a catalog scan has built `folder_map`; before that the index
        // would come back empty, so fall back to the artists the specification actually asks for
        $children = ($fcatalogs === [])
            ? []
            : $this->folderRepository->getCatalogRootChildren($fcatalogs, $user->getId());
        $artists  = ($fcatalogs !== [] && $children === [])
            ? Catalog::get_artist_arrays($fcatalogs)
            : [];

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = ($children === [])
                ? $this->subsonicXmlData->addIndexes($response, $artists, $lastmodified)
                : $this->subsonicXmlData->addFolderIndexes($response, $children, $lastmodified);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = ($children === [])
                ? $this->subsonicJsonData->addIndexes($response, $artists, $lastmodified)
                : $this->subsonicJsonData->addFolderIndexes($response, $children, $lastmodified);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getMusicDirectory
     *
     * Returns a listing of all files in a music directory.
     * https://www.subsonic.org/pages/api.jsp#getmusicdirectory
     * @param array<string, mixed> $input
     */
    public function getmusicdirectory(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = Subsonic_Api::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $object = Subsonic_Api::getAmpacheObject($sub_id);
        if (!$object) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Album || $object instanceof Artist || $object instanceof Catalog || $object instanceof Folder) {
            $format = (string) ($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                $response = $this->subsonicXmlData->addDirectory($response, $object, $user->getId());
            } else {
                $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                $response = $this->subsonicJsonData->addDirectory($response, $object, $user->getId());
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * getMusicFolders
     *
     * Returns all configured top-level music folders.
     * https://www.subsonic.org/pages/api.jsp#getmusicfolders
     * @param array<string, mixed> $input
     */
    public function getmusicfolders(array $input, User $user): void
    {
        $catalogs = $user->get_catalogs('music');
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addMusicFolders($response, $catalogs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addMusicFolders($response, $catalogs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getRandomSongs
     *
     * Returns random songs matching the given criteria.
     * https://www.subsonic.org/pages/api.jsp#getrandomsongs
     * @param array<string, mixed> $input
     */
    public function getrandomsongs(array $input, User $user): void
    {
        $size = (int) ($input['size'] ?? 10);

        $genre         = $input['genre'] ?? '';
        $fromYear      = $input['fromYear'] ?? null;
        $toYear        = $input['toYear'] ?? null;
        $sub_id        = $input['musicFolderId'] ?? null;
        $musicFolderId = ($sub_id) ? (int) Subsonic_Api::getAmpacheId($sub_id) : 0;

        $data           = [];
        $data['limit']  = $size;
        $data['random'] = 1;
        $data['type']   = "song";
        $count          = 0;
        if ($genre) {
            $data['rule_' . $count . '_input']    = $genre;
            $data['rule_' . $count . '_operator'] = 0;
            $data['rule_' . $count]               = "tag";
            ++$count;
        }
        if ($fromYear) {
            $data['rule_' . $count . '_input']    = $fromYear;
            $data['rule_' . $count . '_operator'] = 0;
            $data['rule_' . $count]               = "year";
            ++$count;
        }
        if ($toYear) {
            $data['rule_' . $count . '_input']    = $toYear;
            $data['rule_' . $count . '_operator'] = 1;
            $data['rule_' . $count]               = "year";
            ++$count;
        }
        if ($musicFolderId > 0) {
            $type = Subsonic_Api::getAmpacheType($sub_id);
            if ($type === 'artist') {
                $artist   = new Artist($musicFolderId);
                $finput   = $artist->get_fullname();
                $operator = 4;
                $ftype    = "artist";
            } elseif ($type === 'album') {
                $album    = new Album($musicFolderId);
                $finput   = $album->get_fullname(true);
                $operator = 4;
                $ftype    = "artist";
            } else {
                // a real music folder must be one the user can browse
                $finput   = $this->musicFolderResolver->musicFolderId($input, $user);
                $operator = 0;
                $ftype    = "catalog";
            }

            $data['rule_' . $count . '_input']    = $finput;
            $data['rule_' . $count . '_operator'] = $operator;
            $data['rule_' . $count]               = $ftype;
            ++$count;
        }
        if ($count > 0) {
            $songs = $this->random->advanced('song', $data);
        } else {
            $songs = Random::get_default($size, $user);
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addRandomSongs($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addRandomSongs($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSimilarSongs
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://www.subsonic.org/pages/api.jsp#getsimilarsongs
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs(array $input, User $user, string $elementName = 'similarSongs'): void
    {
        unset($user);
        if (!AmpConfig::get('show_similar')) {
            debug_event(self::class, $elementName . ': Enable: show_similar', 4);
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_GENERIC, __FUNCTION__);

            return;
        }

        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }
        $object_id = Subsonic_Api::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count = (int) ($input['count'] ?? 50);
        $songs = [];
        $type  = Subsonic_Api::getAmpacheType($sub_id);
        if ($type === 'artist') {
            $similars = Recommendation::get_artists_like($object_id);
            if (!empty($similars)) {
                debug_event(self::class, 'Found: ' . count($similars) . ' similar artists', 4);
                foreach ($similars as $similar) {
                    debug_event(self::class, $similar['name'] . ' (id=' . $similar['id'] . ')', 5);
                    if ($similar['id']) {
                        $artist = new Artist($similar['id']);
                        if ($artist->isNew()) {
                            continue;
                        }
                        // get the songs in a random order for even more chaos
                        $artist_songs = $this->songRepository->getRandomByArtist($artist);
                        foreach ($artist_songs as $song) {
                            $songs[] = ['id' => $song];
                        }
                    }
                }
            }
            // randomize and slice
            shuffle($songs);
            $songs = array_slice($songs, 0, $count);
        } elseif ($type === 'album') {
            // TODO: support similar songs for albums
            debug_event(self::class, $elementName . ': album is unsupported', 4);
        } elseif ($type === 'song') {
            $songs = Recommendation::get_songs_like($object_id, $count);
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = $this->subsonicXmlData->addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = $this->subsonicXmlData->addSimilarSongs2($response, $songs);
                    break;
            }
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = $this->subsonicJsonData->addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = $this->subsonicJsonData->addSimilarSongs2($response, $songs);
                    break;
            }
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSimilarSongs2
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://www.subsonic.org/pages/api.jsp#getsimilarsongs2
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs2(array $input, User $user): void
    {
        $this->getsimilarsongs($input, $user, "similarSongs2");
    }

    /**
     * getSong
     *
     * Returns details for a song.
     * https://www.subsonic.org/pages/api.jsp#getsong
     * @param array<string, mixed> $input
     */
    public function getsong(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $song_id = Subsonic_Api::getAmpacheId($sub_id);
        if (!$song_id) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $song = new Song($song_id);
        if ($song->isNew() || !$song->enabled) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSong($response, $song);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSong($response, $song_id);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSongsByGenre
     *
     * Returns songs in a given genre.
     * https://www.subsonic.org/pages/api.jsp#getsongsbygenre
     * @param array<string, mixed> $input
     */
    public function getsongsbygenre(array $input, User $user): void
    {
        $genre = $this->responseHandler->checkParameter($input, 'genre', __FUNCTION__);
        if ($genre === false) {
            return;
        }

        $count         = (int) ($input['count'] ?? 0);
        $offset        = (int) ($input['offset'] ?? 0);
        $musicFolderId = $this->musicFolderResolver->musicFolderId($input, $user);

        $tag = Tag::construct_from_name($genre);
        if ($tag->isNew()) {
            $songs = [];
        } else {
            $songs = Tag::get_tag_objects("song", $tag->id, $count, $offset, $musicFolderId);
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSongsByGenre($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSongsByGenre($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getTopSongs
     *
     * Returns top songs for the given artist.
     * https://www.subsonic.org/pages/api.jsp#gettopsongs
     * @param array<string, mixed> $input
     */
    public function gettopsongs(array $input, User $user): void
    {
        unset($user);
        $name = $this->responseHandler->checkParameter($input, 'artist', __FUNCTION__);
        if ($name === false) {
            return;
        }

        $artist = $this->artistRepository->findByName(urldecode((string) $name));
        $count  = (int) ($input['count'] ?? 50);
        $songs  = [];
        if ($count < 1) {
            $count = 50;
        }
        if ($artist) {
            $songs = $this->songRepository->getTopSongsByArtist(
                $artist,
                $count
            );
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addTopSongs($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addTopSongs($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getVideoInfo
     *
     * Returns details for a video.
     * https://www.subsonic.org/pages/api.jsp#getvideoinfo
     * @param array<string, mixed> $input
     */
    public function getvideoinfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $video_id = Subsonic_Api::getAmpacheId($sub_id);
        if (!$video_id) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addVideoInfo($response, $video_id);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addVideoInfo($response, $video_id);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getVideos
     *
     * Returns all video files.
     * https://www.subsonic.org/pages/api.jsp#getvideos
     * @param array<string, mixed> $input
     */
    public function getvideos(array $input, User $user): void
    {
        unset($user);

        $videos = Catalog::get_videos();
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addVideos($response, $videos);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addVideos($response, $videos);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * albumList
     * @param array<string, mixed> $input
     * @return int[]|null
     */
    private function albumList(array $input, User $user, string $type): ?array
    {
        $size          = (int) ($input['size'] ?? 10);
        $offset        = (int) ($input['offset'] ?? 0);
        $musicFolderId = $this->musicFolderResolver->musicFolderId($input, $user);
        $catalogFilter = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'));

        // hide ratings and flags for other users if single user data is enabled
        $by_user     = (bool) Preference::get_by_user($user->id, 'subsonic_single_user_data') === true;
        $output_user = ($by_user)
            ? $user
            : null;

        // Get albums from all catalogs by default
        $catalogs = ($catalogFilter)
            ? $user->get_catalogs('music')
            : null;
        if ($musicFolderId !== 0) {
            $catalogs = $this->musicFolderResolver->musicFolders($input, $user);
        }
        $albums = null;
        switch ($type) {
            case 'random':
                $albums = $this->albumRepository->getRandom(
                    $user->id,
                    $size,
                    $musicFolderId
                );
                break;
            case 'newest':
                $albums = Stats::get_newest('album', $size, $offset, $musicFolderId, $user);
                break;
            case 'highest':
                $albums = Rating::get_highest('album', $size, $offset, $output_user?->id, $by_user, $musicFolderId);
                break;
            case 'frequent':
                $albums = Stats::get_top('album', $size, 0, $offset, $output_user, false, 0, 0, $by_user, $musicFolderId);
                break;
            case 'recent':
                $albums = Stats::get_recent('album', $size, $offset, $output_user, true, $musicFolderId);
                break;
            case 'starred':
                $albums = Userflag::get_latest('album', $output_user, $size, $offset, 0, 0, $by_user, $musicFolderId);
                break;
            case 'alphabeticalByName':
                // an empty catalog list means everything to these calls, so a filtered request must bail out first
                $albums = (empty($catalogs) && ($catalogFilter || $musicFolderId !== 0))
                    ? []
                    : Catalog::get_albums($size, $offset, $catalogs);
                break;
            case 'alphabeticalByArtist':
                $albums = (empty($catalogs) && ($catalogFilter || $musicFolderId !== 0))
                    ? []
                    : Catalog::get_albums_by_artist($size, $offset, $catalogs);
                break;
            case 'byYear':
                $fromYear = (int) min(($input['fromYear'] ?? 0), ($input['toYear'] ?? 0));
                $toYear   = (int) max(($input['fromYear'] ?? 0), ($input['toYear'] ?? 0));

                if ($fromYear || $toYear) {
                    $data = Search::year_search($fromYear, $toYear, $size, $offset);
                    if ($musicFolderId !== 0) {
                        $data['catalog_id'] = $musicFolderId;
                    }

                    $albums = Search::run($data, $user);
                }
                break;
            case 'byGenre':
                $genre  = $input['genre'];
                $tag_id = Tag::tag_exists($genre);
                if ($tag_id > 0) {
                    $albums = Tag::get_tag_objects('album', $tag_id, $size, $offset, $musicFolderId);
                }
                break;
        }

        return $albums;
    }
}
