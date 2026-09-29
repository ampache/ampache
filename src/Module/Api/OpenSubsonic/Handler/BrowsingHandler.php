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

namespace Ampache\Module\Api\OpenSubsonic\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\OpenSubsonic\MusicFolderResolverInterface;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\SonicAnalysisPluginResolverInterface;
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
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
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
        private readonly Random $random,
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly SongRepositoryInterface $songRepository,
        private readonly SonicAnalysisPluginResolverInterface $sonicAnalysisPluginResolver,
    ) {}

    /**
     * findSonicPath [OS] //TODO
     * https://opensubsonic.netlify.app/docs/endpoints/findsonicpath/
     * @param array<string, mixed> $input
     */
    public function findSonicPath(array $input, User $user): void
    {
        $plugin = $this->sonicAnalysisPluginResolver->resolve($user);
        if ($plugin === null) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_APIVERSION_SERVER, __FUNCTION__);

            return;
        }

        $start_id = $this->responseHandler->checkParameter($input, 'startSongId', __FUNCTION__);
        if ($start_id === false) {
            return;
        }

        $end_id = $this->responseHandler->checkParameter($input, 'endSongId', __FUNCTION__);
        if ($end_id === false) {
            return;
        }

        $start = OpenSubsonic_Api::getAmpacheObject((string) $start_id);
        $end   = OpenSubsonic_Api::getAmpacheObject((string) $end_id);
        if (
            !$start instanceof Song
            || !$end instanceof Song
            || $start->isNew()
            || $end->isNew()
        ) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count   = $this->sonicCount($input);
        $matches = $plugin->get_sonic_path($start, $end, $count);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addSonicMatches($response, $matches);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addSonicMatches($response, $matches);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbum
     *
     * Returns details for an album.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbum/
     * @param array<string, mixed> $input
     */
    public function getalbum(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $album = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addAlbumID3($response, $album, true);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addAlbumID3($response, $album, true);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumInfo
     *
     * Returns album info.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbuminfo/
     * @param array<string, mixed> $input
     */
    public function getalbuminfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $album = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $info   = Recommendation::get_album_info($album->getId());
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addAlbumInfo($response, $info, $album);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addAlbumInfo($response, $info, $album);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumInfo2
     *
     * Returns album info.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbuminfo2/
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
     * https://opensubsonic.netlify.app/docs/endpoints/getalbumlist/
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
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addAlbumList($response, $albums);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addAlbumList($response, $albums);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumList2
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbumlist2/
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
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addAlbumList2($response, $albums);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addAlbumList2($response, $albums);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtist
     *
     * Returns details for an artist.
     * https://opensubsonic.netlify.app/docs/endpoints/getartist/
     * @param array<string, mixed> $input
     */
    public function getartist(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = new Artist(OpenSubsonic_Api::getAmpacheId($sub_id));
        if ($artist->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addArtistID3($response, $artist, true);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addArtistWithAlbumsID3($response, $artist);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtistInfo
     *
     * Returns artist info.
     * https://opensubsonic.netlify.app/docs/endpoints/getartistinfo/
     * @param array<string, mixed> $input
     */
    public function getartistinfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = (int) ($input['count'] ?? 20);
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addArtistInfo($response, $info, $artist, $similars);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addArtistInfo($response, $info, $artist, $similars);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtistInfo2
     *
     * Returns artist info.
     * https://opensubsonic.netlify.app/docs/endpoints/getartistinfo2/
     * @param array<string, mixed> $input
     */
    public function getartistinfo2(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = (int) ($input['count'] ?? 20);
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addArtistInfo2($response, $info, $artist, $similars);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addArtistInfo2($response, $info, $artist, $similars);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtists
     *
     * Returns all artists.
     * https://opensubsonic.netlify.app/docs/endpoints/getartists/
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
            $response = $this->openSubsonicXmlData->addArtists($response, $artists);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addArtists($response, $artists);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getGenres
     *
     * Returns all genres.
     * https://opensubsonic.netlify.app/docs/endpoints/getgenres/
     * @param array<string, mixed> $input
     */
    public function getgenres(array $input, User $user): void
    {
        unset($user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addGenres($response, Tag::get_tags('song'));
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addGenres($response, Tag::get_tags('song'));
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getIndexes
     *
     * Returns an indexed structure of all artists.
     * https://opensubsonic.netlify.app/docs/endpoints/getindexes/
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
                ? $this->openSubsonicXmlData->addIndexes($response, $artists, $lastmodified)
                : $this->openSubsonicXmlData->addFolderIndexes($response, $children, $lastmodified);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = ($children === [])
                ? $this->openSubsonicJsonData->addIndexes($response, $artists, $lastmodified)
                : $this->openSubsonicJsonData->addFolderIndexes($response, $children, $lastmodified);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getMusicDirectory
     *
     * Returns a listing of all files in a music directory.
     * https://opensubsonic.netlify.app/docs/endpoints/getmusicdirectory/
     * @param array<string, mixed> $input
     */
    public function getmusicdirectory(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = OpenSubsonic_Api::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $object = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (!$object) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Album || $object instanceof Artist || $object instanceof Catalog || $object instanceof Folder) {
            $format = (string) ($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                $response = $this->openSubsonicXmlData->addDirectory($response, $object, $user->getId());
            } else {
                $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                $response = $this->openSubsonicJsonData->addDirectory($response, $object, $user->getId());
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * getMusicFolders
     *
     * Returns all configured top-level music folders.
     * https://opensubsonic.netlify.app/docs/endpoints/getmusicfolders/
     * @param array<string, mixed> $input
     */
    public function getmusicfolders(array $input, User $user): void
    {
        $catalogs = $user->get_catalogs('music');
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addMusicFolders($response, $catalogs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addMusicFolders($response, $catalogs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getRandomSongs
     *
     * Returns random songs matching the given criteria.
     * https://opensubsonic.netlify.app/docs/endpoints/getrandomsongs/
     * @param array<string, mixed> $input
     */
    public function getrandomsongs(array $input, User $user): void
    {
        $size = (int) ($input['size'] ?? 10);

        $genre         = $input['genre'] ?? '';
        $fromYear      = $input['fromYear'] ?? null;
        $toYear        = $input['toYear'] ?? null;
        $sub_id        = $input['musicFolderId'] ?? null;
        $musicFolderId = ($sub_id) ? (int) OpenSubsonic_Api::getAmpacheId($sub_id) : 0;

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
            $type = OpenSubsonic_Api::getAmpacheType($sub_id);
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
            $response = $this->openSubsonicXmlData->addRandomSongs($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addRandomSongs($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSimilarSongs
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://opensubsonic.netlify.app/docs/endpoints/getsimilarsongs/
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs(array $input, User $user, string $elementName = 'similarSongs'): void
    {
        unset($user);
        if (!AmpConfig::get('show_similar')) {
            debug_event(self::class, $elementName . ': Enable: show_similar', 4);
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_GENERIC, __FUNCTION__);

            return;
        }

        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }
        $object_id = OpenSubsonic_Api::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count = (int) ($input['count'] ?? 50);
        $songs = [];
        $type  = OpenSubsonic_Api::getAmpacheType($sub_id);
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
                    $response = $this->openSubsonicXmlData->addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = $this->openSubsonicXmlData->addSimilarSongs2($response, $songs);
                    break;
            }
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = $this->openSubsonicJsonData->addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = $this->openSubsonicJsonData->addSimilarSongs2($response, $songs);
                    break;
            }
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSimilarSongs2
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://opensubsonic.netlify.app/docs/endpoints/getsimilarsongs2/
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
     * https://opensubsonic.netlify.app/docs/endpoints/getsong/
     * @param array<string, mixed> $input
     */
    public function getsong(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $song_id = OpenSubsonic_Api::getAmpacheId($sub_id);
        if (!$song_id) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $song = new Song($song_id);
        if ($song->isNew() || !$song->enabled) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addSong($response, $song);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addSong($response, $song_id);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSongsByGenre
     *
     * Returns songs in a given genre.
     * https://opensubsonic.netlify.app/docs/endpoints/getsongsbygenre/
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
            $response = $this->openSubsonicXmlData->addSongsByGenre($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addSongsByGenre($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSonicSimilarTracks [OS] //TODO
     * https://opensubsonic.netlify.app/docs/endpoints/getsonicsimilartracks/
     * @param array<string, mixed> $input
     */
    public function getSonicSimilarTracks(array $input, User $user): void
    {
        $plugin = $this->sonicAnalysisPluginResolver->resolve($user);
        if ($plugin === null) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_APIVERSION_SERVER, __FUNCTION__);

            return;
        }

        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $song = OpenSubsonic_Api::getAmpacheObject((string) $sub_id);
        if (!$song instanceof Song || $song->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count   = $this->sonicCount($input);
        $matches = $plugin->get_sonic_similar_songs($song, $count);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addSonicMatches($response, $matches);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addSonicMatches($response, $matches);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getTopSongs
     *
     * Returns top songs for the given artist.
     * https://opensubsonic.netlify.app/docs/endpoints/gettopsongs/
     * @param array<string, mixed> $input
     */
    public function gettopsongs(array $input, User $user): void
    {
        unset($user);
        // [OPENSUBSONIC] `topSongsByArtistId`: an id may stand in for the name and wins when both are given.
        $sub_id = $input['id'] ?? null;
        $name   = $input['artist'] ?? null;
        if ($sub_id === null && $name === null) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        if ($sub_id !== null) {
            $artist = OpenSubsonic_Api::getAmpacheObject((string) $sub_id);
            if (!$artist instanceof Artist || $artist->isNew()) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }
        } else {
            $artist = $this->artistRepository->findByName(urldecode((string) $name));
        }

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
            $response = $this->openSubsonicXmlData->addTopSongs($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addTopSongs($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getVideoInfo
     *
     * Returns details for a video.
     * https://opensubsonic.netlify.app/docs/endpoints/getvideoinfo/
     * @param array<string, mixed> $input
     */
    public function getvideoinfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $video_id = OpenSubsonic_Api::getAmpacheId($sub_id);
        if (!$video_id) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addVideoInfo($response, $video_id);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addVideoInfo($response, $video_id);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getVideos
     *
     * Returns all video files.
     * https://opensubsonic.netlify.app/docs/endpoints/getvideos/
     * @param array<string, mixed> $input
     */
    public function getvideos(array $input, User $user): void
    {
        unset($user);

        $videos = Catalog::get_videos();
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addVideos($response, $videos);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addVideos($response, $videos);
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

    /**
     * sonicCount
     *
     * The result ceiling shared by the two sonic endpoints. The spec allows 0, which asks for nothing at all, so
     * only a negative value falls back to the default.
     *
     * @param array<string, mixed> $input
     */
    private function sonicCount(array $input): int
    {
        $count = (int) ($input['count'] ?? 50);

        return ($count < 0) ? 50 : $count;
    }
}
