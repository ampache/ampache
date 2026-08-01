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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\User_Playlist;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\SongRepositoryInterface;
use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;

/**
 * OpenSubsonic_Xml_Data Class
 *
 * This class takes care of all of the xml document stuff for SubSonic Responses
 * https://opensubsonic.netlify.app/docs/responses/
 */
class OpenSubsonic_Xml_Data
{
    /**
     * addAlbum
     *
     * https://opensubsonic.netlify.app/docs/responses/child/
     */
    public static function addAlbum(SimpleXMLElement $xml, Album $album, bool $songs = false, string $elementName = 'album'): void
    {
        if ($album->isNew()) {
            return;
        }

        $sub_id = OpenSubsonic_Api::getAlbumSubId($album->id);
        $xalbum = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xalbum->addAttribute('id', $sub_id);
        $album_artist = $album->findAlbumArtist();
        if ($album_artist) {
            $xalbum->addAttribute('parent', OpenSubsonic_Api::getArtistSubId($album_artist));
        }
        $f_name = $album->get_fullname();
        $xalbum->addAttribute('album', $f_name);
        $xalbum->addAttribute('title', $f_name);
        $xalbum->addAttribute('isDir', 'true');
        $xalbum->addAttribute('isVideo', 'false');
        $xalbum->addAttribute('type', 'music');
        if ($album->has_art()) {
            $xalbum->addAttribute('coverArt', $sub_id);
        }
        $xalbum->addAttribute('created', date('c', (int) $album->addition_time));
        $xalbum->addAttribute('duration', (string) $album->time);
        if ($album_artist) {
            $xalbum->addAttribute('artistId', OpenSubsonic_Api::getArtistSubId($album_artist));
        }
        $xalbum->addAttribute('artist', (string) $album->get_parent_fullname());
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');
        $year          = ($original_year && $album->original_year)
            ? $album->original_year
            : $album->year;
        if ($year > 0) {
            $xalbum->addAttribute('year', (string) $year);
        }
        $tags = Tag::get_object_tags('album', $album->id);
        if (!empty($tags)) {
            $xalbum->addAttribute('genre', implode(',', array_column($tags, 'name')));
            foreach ($tags as $tag) {
                $xlastcat = self::_addChildToResultXml($xalbum, 'genres');
                $xlastcat->addAttribute('name', (string) $tag['name']);
            }
        }

        $rating      = new Rating($album->id, 'album');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $xalbum->addAttribute('userRating', (string) ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xalbum->addAttribute('averageRating', (string) $avg_rating);
        }

        $xalbum->addAttribute('playCount', (string) $album->total_count);

        $played = OpenSubsonic_Fields::lastPlayed($album->last_played);
        if ($played !== null) {
            $xalbum->addAttribute('played', $played);
        }

        self::_setIfStarred($xalbum, 'album', $album->id);

        if ($songs) {
            $media_ids = self::getAlbumRepository()->getSongs($album->id);
            foreach ($media_ids as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xalbum, $song);
            }
        }
    }

    /**
     * addAlbumID3
     *
     * An album from ID3 tags.
     * `parent`, `album`, `title` and `isDir` belong to `Child` only, so they are not emitted here.
     * https://opensubsonic.netlify.app/docs/responses/albumid3/
     */
    public static function addAlbumID3(SimpleXMLElement $xml, Album $album, bool $songs = false, string $elementName = 'album'): SimpleXMLElement
    {
        if ($album->isNew()) {
            return $xml;
        }

        $sub_id = OpenSubsonic_Api::getAlbumSubId($album->id);
        $xalbum = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xalbum->addAttribute('id', $sub_id);
        $album_artist = $album->findAlbumArtist();
        $f_name       = $album->get_fullname();
        $xalbum->addAttribute('name', $f_name);
        $xalbum->addAttribute('version', (string) $album->version);
        if ($album->has_art()) {
            $xalbum->addAttribute('coverArt', $sub_id);
        }
        $xalbum->addAttribute('songCount', (string) $album->song_count);
        $xalbum->addAttribute('created', date('c', (int) $album->addition_time));
        $xalbum->addAttribute('duration', (string) $album->time);
        if ($album_artist) {
            $xalbum->addAttribute('artistId', OpenSubsonic_Api::getArtistSubId($album_artist));
        }
        $xalbum->addAttribute('artist', (string) $album->get_parent_fullname());
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');
        $year          = ($original_year && $album->original_year)
            ? $album->original_year
            : $album->year;
        if ($year > 0) {
            $xalbum->addAttribute('year', (string) $year);
        }
        $tags = Tag::get_object_tags('album', $album->id);
        if (!empty($tags)) {
            $xalbum->addAttribute('genre', implode(',', array_column($tags, 'name')));
            foreach ($tags as $tag) {
                $xlastcat = self::_addChildToResultXml($xalbum, 'genres');
                $xlastcat->addAttribute('name', (string) $tag['name']);
            }
        }

        $rating      = new Rating($album->id, 'album');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $xalbum->addAttribute('userRating', (string) ceil($user_rating));
        }
        // `averageRating` is not part of the `AlbumID3` response, unlike `Child`.

        $xalbum->addAttribute('playCount', (string) $album->total_count);

        $played = OpenSubsonic_Fields::lastPlayed($album->last_played);
        if ($played !== null) {
            $xalbum->addAttribute('played', $played);
        }

        self::_setIfStarred($xalbum, 'album', $album->id);

        // These mirror OpenSubsonic_Json_Data::_getAlbumID3() through OpenSubsonic_Fields, so the formats cannot drift.
        $artist_names = [];
        foreach ($album->get_artists() as $artist_id) {
            $array          = Artist::get_name_array_by_id($artist_id);
            $artist_names[] = (string) $array['name'];
            $xartist        = self::_addChildToResultXml($xalbum, 'artists');
            $xartist->addAttribute('id', OpenSubsonic_Api::getArtistSubId($artist_id));
            $xartist->addAttribute('name', (string) $array['name']);
        }

        if ($artist_names !== []) {
            $xalbum->addAttribute('displayArtist', implode(', ', $artist_names));
        }

        $xalbum->addAttribute('musicBrainzId', (string) $album->mbid);

        $sort_name = OpenSubsonic_Fields::sortName($album->name, $f_name);
        if ($sort_name !== null) {
            $xalbum->addAttribute('sortName', $sort_name);
        }

        foreach (['releaseDate' => $album->year, 'originalReleaseDate' => $album->original_year] as $key => $value) {
            $date = OpenSubsonic_Fields::itemDate($value);
            if ($date !== []) {
                $xdate = self::_addChildToResultXml($xalbum, $key);
                $xdate->addAttribute('year', (string) $date['year']);
            }
        }

        foreach (OpenSubsonic_Fields::albumReleaseTypes($album) as $release_type) {
            self::_addChildToResultXml($xalbum, 'releaseTypes', $release_type);
        }

        foreach (OpenSubsonic_Fields::albumDiscTitles($album) as $disc_title) {
            $xdisc = self::_addChildToResultXml($xalbum, 'discTitles');
            $xdisc->addAttribute('disc', (string) $disc_title['disc']);
            $xdisc->addAttribute('title', $disc_title['title']);
        }

        foreach (OpenSubsonic_Fields::albumRecordLabels($album) as $record_label) {
            $xlabel = self::_addChildToResultXml($xalbum, 'recordLabels');
            $xlabel->addAttribute('name', $record_label['name']);
        }

        if ($songs) {
            $media_ids = self::getAlbumRepository()->getSongs($album->id);
            foreach ($media_ids as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xalbum, $song);
            }
        }

        return $xml;
    }

    /**
     * addAlbumInfo
     *
     * https://opensubsonic.netlify.app/docs/responses/albuminfo/
     * @param array{
     *     id: int,
     *     summary: ?string,
     *     lastfm_url: ?string,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * } $info
     */
    public static function addAlbumInfo(SimpleXMLElement $xml, array $info, Album $album): SimpleXMLElement
    {
        $xartist = self::_addChildToResultXml($xml, htmlspecialchars('albumInfo'));
        $xartist->addChild('notes', htmlspecialchars(trim((string) $info['summary'])));
        $xartist->addChild('musicBrainzId', $album->mbid);
        // Only present once last.fm has answered for this album; the field is optional so an unknown one is omitted
        if (!empty($info['lastfm_url'])) {
            $xartist->addChild('lastFmUrl', htmlspecialchars((string) $info['lastfm_url']));
        }

        $xartist->addChild('smallImageUrl', html_entity_decode((string) $info['smallphoto']));
        $xartist->addChild('mediumImageUrl', html_entity_decode((string) $info['mediumphoto']));
        $xartist->addChild('largeImageUrl', html_entity_decode((string) $info['largephoto']));

        return $xml;
    }

    /**
     * addAlbumList
     *
     * https://opensubsonic.netlify.app/docs/responses/albumList/
     * @param int[] $albums
     */
    public static function addAlbumList(SimpleXMLElement $xml, array $albums): SimpleXMLElement
    {
        $xlist = self::_addChildToResultXml($xml, htmlspecialchars('albumList'));
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            // `AlbumList` holds `Child` albums; `AlbumList2` is the ID3 variant.
            self::addAlbum($xlist, $album);
        }

        return $xml;
    }

    /**
     * addAlbumList2
     *
     * https://opensubsonic.netlify.app/docs/responses/albumList2/
     * @param int[] $albums
     */
    public static function addAlbumList2(SimpleXMLElement $xml, array $albums): SimpleXMLElement
    {
        $xlist = self::_addChildToResultXml($xml, htmlspecialchars('albumList2'));
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbumID3($xlist, $album);
        }

        return $xml;
    }

    /**
     * addArtist
     *
     * https://opensubsonic.netlify.app/docs/responses/artist/
     */
    public static function addArtist(SimpleXMLElement $xml, Artist $artist): SimpleXMLElement
    {
        if ($artist->isNew()) {
            return $xml;
        }

        $sub_id  = OpenSubsonic_Api::getArtistSubId($artist->id);
        $xartist = self::_addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string) $artist->get_fullname());
        // `coverArt` and `albumCount` belong to `ArtistID3` only, see self::addArtistID3()
        self::_setIfStarred($xartist, 'artist', $artist->id);

        return $xml;
    }

    /**
     * addArtistID3
     *
     * An artist from ID3 tags.
     * https://opensubsonic.netlify.app/docs/responses/artistid3/
     */
    public static function addArtistID3(SimpleXMLElement $xml, Artist $artist, bool $albums = false): SimpleXMLElement
    {
        if ($artist->isNew()) {
            return $xml;
        }

        $sub_id  = OpenSubsonic_Api::getArtistSubId($artist->id);
        $xartist = self::_addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string) $artist->get_fullname());

        if ($artist->has_art()) {
            $xartist->addAttribute('coverArt', $sub_id);
        }

        $xartist->addAttribute('albumCount', (string) $artist->album_count);

        self::_setIfStarred($xartist, 'artist', $artist->id);

        // [OPENSUBSONIC] roles: repeated <role> children (see _addArtistRoles).
        self::_addArtistRoles($xartist, $artist->album_count, $artist->song_count);

        $xartist->addAttribute('musicBrainzId', (string) $artist->mbid);

        $sort_name = OpenSubsonic_Fields::sortName($artist->name, $artist->get_fullname());
        if ($sort_name !== null) {
            $xartist->addAttribute('sortName', $sort_name);
        }

        $image_url = OpenSubsonic_Fields::artistImageUrl($artist);
        if ($image_url !== null) {
            $xartist->addAttribute('artistImageUrl', $image_url);
        }

        if ($albums) {
            $allalbums = self::getAlbumRepository()->getAlbumByArtist($artist->id);
            foreach ($allalbums as $album_id) {
                $album = new Album($album_id);
                self::addAlbumID3($xartist, $album);
            }
        }

        return $xml;
    }

    /**
     * addArtistInfo
     *
     * https://opensubsonic.netlify.app/docs/responses/artistinfo/
     * @param array{
     *     id: ?int,
     *     summary: ?string,
     *     placeformed: ?string,
     *     yearformed: ?int,
     *     lastfm_url: ?string,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * } $info
     * @param array<int, array{
     *     id: ?int,
     *     name: string,
     *     rel?: ?string,
     *     mbid?: ?string
     * }> $similars
     */
    public static function addArtistInfo(SimpleXMLElement $xml, array $info, Artist $artist, array $similars, string $elementName = 'artistInfo'): SimpleXMLElement
    {
        $xartist   = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $biography = trim((string) $info['summary']);
        if (!empty($biography)) {
            $xartist->addChild('biography', htmlspecialchars($biography));
        }
        $xartist->addChild('musicBrainzId', (string) $artist->mbid);
        // Only present once last.fm has answered for this artist; the field is optional so an unknown one is omitted
        if (!empty($info['lastfm_url'])) {
            $xartist->addChild('lastFmUrl', htmlspecialchars((string) $info['lastfm_url']));
        }

        $xartist->addChild('smallImageUrl', html_entity_decode((string) $info['smallphoto']));
        $xartist->addChild('mediumImageUrl', html_entity_decode((string) $info['mediumphoto']));
        $xartist->addChild('largeImageUrl', html_entity_decode((string) $info['largephoto']));

        $unknownCount = 0;
        foreach ($similars as $similar) {
            $xsimilar = self::_addChildToResultXml($xartist, 'similarArtist');
            $xsimilar->addAttribute('id', (($similar['id'] !== null) ? OpenSubsonic_Api::getArtistSubId($similar['id']) : '-' . $unknownCount++));
            $xsimilar->addAttribute('name', (string) $similar['name']);
        }

        return $xml;
    }

    /**
     * addArtistInfo2
     *
     * https://opensubsonic.netlify.app/docs/responses/artistinfo2/
     * @param array{
          *     id: ?int,
          *     summary: ?string,
          *     placeformed: ?string,
          *     yearformed: ?int,
          *     lastfm_url: ?string,
          *     largephoto: ?string,
          *     smallphoto: ?string,
          *     mediumphoto: ?string,
          *     megaphoto: ?string
          * } $info
     * @param array<int, array{
          *     id: ?int,
          *     name: string,
          *     rel?: ?string,
          *     mbid?: ?string
          * }> $similars
     */
    public static function addArtistInfo2(SimpleXMLElement $xml, array $info, Artist $artist, array $similars): SimpleXMLElement
    {
        return self::addArtistInfo($xml, $info, $artist, $similars, 'artistInfo2');
    }

    /**
     * addArtists
     *
     * https://opensubsonic.netlify.app/docs/responses/artistsid3/
     * @param array<int, array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     song_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    public static function addArtists(SimpleXMLElement $xml, array $artists): SimpleXMLElement
    {
        $xartists = self::_addChildToResultXml($xml, 'artists');
        self::_addIgnoredArticles($xartists);
        self::_addIndex($xartists, $artists);

        return $xml;
    }

    /**
     * addBookmarks
     *
     * https://opensubsonic.netlify.app/docs/responses/bookmarks/
     * @param list<Bookmark> $bookmarks
     */
    public static function addBookmarks(SimpleXMLElement $xml, array $bookmarks): SimpleXMLElement
    {
        $xbookmarks = self::_addChildToResultXml($xml, 'bookmarks');
        foreach ($bookmarks as $bookmark) {
            self::_addBookmark($xbookmarks, $bookmark);
        }

        return $xml;
    }

    /**
     * addChatMessages
     *
     * https://opensubsonic.netlify.app/docs/responses/chatmessages/
     * @param int[] $messages
     */
    public static function addChatMessages(SimpleXMLElement $xml, array $messages): SimpleXMLElement
    {
        $xmessages = self::_addChildToResultXml($xml, 'chatMessages');
        if (empty($messages)) {
            return $xml;
        }

        foreach ($messages as $message) {
            $chat = new PrivateMsg($message);
            self::_addMessage($xmessages, $chat);
        }

        return $xml;
    }

    /**
     * addDirectory
     *
     * Create the directory element based on the type
     * https://opensubsonic.netlify.app/docs/responses/directory/
     */
    public static function addDirectory(SimpleXMLElement $xml, Artist|Album|Catalog $object): SimpleXMLElement
    {
        if ($object instanceof Artist) {
            self::_addDirectory_Artist($xml, $object);
        } elseif ($object instanceof Album) {
            self::_addDirectory_Album($xml, $object);
        } elseif ($object instanceof Catalog) {
            self::_addDirectory_Catalog($xml, $object);
        }

        return $xml;
    }

    /**
     * addError
     * Add a failed subsonic-response with error information.
     * https://opensubsonic.netlify.app/docs/responses/error/
     */
    public static function addError(int $code, string $function): SimpleXMLElement
    {
        $xml  = self::_createFailedResponse($function);
        $xerr = self::_addChildToResultXml($xml, 'error');
        $xerr->addAttribute('code', (string) $code);

        $message = "Error creating response.";
        switch ($code) {
            case OpenSubsonic_Api::SSERROR_MISSINGPARAM:
                $message = "Required parameter is missing.";
                break;
            case OpenSubsonic_Api::SSERROR_APIVERSION_CLIENT:
                $message = "Incompatible Subsonic REST protocol version. Client must upgrade.";
                break;
            case OpenSubsonic_Api::SSERROR_APIVERSION_SERVER:
                $message = "Incompatible Subsonic REST protocol version. Server must upgrade.";
                break;
            case OpenSubsonic_Api::SSERROR_BADAUTH:
                $message = "Wrong username or password.";
                break;
            case OpenSubsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED:
                $message = "Token authentication not supported.";
                break;
            case OpenSubsonic_Api::SSERROR_UNAUTHORIZED:
                $message = "User is not authorized for the given operation.";
                break;
            case OpenSubsonic_Api::SSERROR_TRIAL:
                $message = "The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.";
                break;
            case OpenSubsonic_Api::SSERROR_DATA_NOTFOUND:
                $message = "The requested data was not found.";
                break;
            case OpenSubsonic_Api::SSERROR_AUTHMETHODNOTSUPPORTED:
                $message = "Provided authentication mechanism not supported.";
                break;
            case OpenSubsonic_Api::SSERROR_AUTHMETHODCONFLICT:
                $message = "Multiple conflicting authentication mechanisms provided.";
                break;
            case OpenSubsonic_Api::SSERROR_BADAPIKEY:
                $message = "Invalid API key.";
                break;
        }
        $xerr->addAttribute('message', $message);
        $xerr->addAttribute('helpUrl', 'https://ampache.org/api/subsonic');

        return $xml;
    }

    /**
     * addGenres
     *
     * https://opensubsonic.netlify.app/docs/responses/genres/
     * @param array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags
     */
    public static function addGenres(SimpleXMLElement $xml, array $tags): SimpleXMLElement
    {
        $xgenres = self::_addChildToResultXml($xml, 'genres');

        foreach ($tags as $tag) {
            $otag   = new Tag($tag['id']);
            $xgenre = self::_addChildToResultXml($xgenres, 'genre', htmlspecialchars((string) $otag->name));
            $xgenre->addAttribute('songCount', (string) ($otag->song));
            $xgenre->addAttribute('albumCount', (string) ($otag->album));
        }

        return $xml;
    }

    /**
     * addIndexes
     *
     * https://opensubsonic.netlify.app/docs/responses/indexes/
     * @param array<int, array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     song_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    public static function addIndexes(SimpleXMLElement $xml, array $artists, ?int $lastModified = 0): SimpleXMLElement
    {
        $xindexes = self::_addChildToResultXml($xml, 'indexes');
        $xindexes->addAttribute('lastModified', number_format($lastModified * 1000, 0, '.', ''));
        self::_addIgnoredArticles($xindexes);
        // `Indexes` holds the plain `Artist` type; `ArtistsID3` (addArtists) is the ID3 variant.
        self::_addIndex($xindexes, $artists, false);

        return $xml;
    }

    /**
     * addInternetRadioStations
     *
     * https://opensubsonic.netlify.app/docs/responses/internetradiostations/
     * @param int[] $radios
     */
    public static function addInternetRadioStations(SimpleXMLElement $xml, array $radios): SimpleXMLElement
    {
        $xradios = self::_addChildToResultXml($xml, 'internetRadioStations');
        foreach ($radios as $radio_id) {
            $radio = new Live_Stream($radio_id);
            self::_addInternetRadioStation($xradios, $radio);
        }

        return $xml;
    }

    /**
     * addJukeboxPlaylist
     *
     * https://opensubsonic.netlify.app/docs/responses/jukeboxplaylist/
     */
    public static function addJukeboxPlaylist(SimpleXMLElement $xml, LocalPlay $localplay): SimpleXMLElement
    {
        $xjbox  = self::addJukeboxStatus($xml, $localplay, 'jukeboxPlaylist');
        $tracks = $localplay->get();
        foreach ($tracks as $track) {
            if (array_key_exists('oid', $track)) {
                $song = new Song((int) $track['oid']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xjbox, $song, 'entry');
            }
            // TODO This can be random play, democratic, podcasts, etc. not just songs
        }

        return $xml;
    }

    /**
     * addJukeboxStatus
     *
     * https://opensubsonic.netlify.app/docs/responses/jukeboxstatus/
     */
    public static function addJukeboxStatus(SimpleXMLElement $xml, LocalPlay $localplay, string $elementName = 'jukeboxStatus'): SimpleXMLElement
    {
        $xjbox  = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $status = $localplay->status();
        if (empty($status)) {
            $xjbox->addAttribute('currentIndex', '0');
            $xjbox->addAttribute('playing', 'false');
            $xjbox->addAttribute('gain', '0');

            return $xml;
        }
        $index = (((int) $status['track']) === 0)
            ? 0
            : $status['track'] - 1;
        $xjbox->addAttribute('currentIndex', (string) $index);
        $xjbox->addAttribute('playing', ($status['state'] == 'play') ? 'true' : 'false');
        $xjbox->addAttribute('gain', (string) $status['volume']);
        $xjbox->addAttribute('position', '0'); // TODO Not supported

        return $xml;
    }

    /**
     * addLicense
     *
     * getLicense result.
     * https://opensubsonic.netlify.app/docs/responses/license/
     */
    public static function addLicense(SimpleXMLElement $xml): SimpleXMLElement
    {
        $xlic = self::_addChildToResultXml($xml, 'license');
        $xlic->addAttribute('valid', 'true');
        $xlic->addAttribute('email', 'webmaster@ampache.org');

        return $xml;
    }

    /**
     * addLyrics
     *
     * https://opensubsonic.netlify.app/docs/responses/lyrics/
     */
    public static function addLyrics(SimpleXMLElement $xml, string $artist, string $title, Song $song): SimpleXMLElement
    {
        if ($song->isNew() || !$song->enabled) {
            return $xml;
        }

        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text']) {
            $text    = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text    = preg_replace('/\\n\\n/i', "\n", (string) $text);
            $text    = str_replace("\r", '', (string) $text);
            $xlyrics = self::_addChildToResultXml($xml, 'lyrics', html_entity_decode($text));
            if ($artist) {
                $xlyrics->addAttribute('artist', $artist);
            }
            if ($title) {
                $xlyrics->addAttribute('title', $title);
            }
        }

        return $xml;
    }

    /**
     * addLyricsList
     *
     * https://opensubsonic.netlify.app/docs/responses/lyricslist/
     */
    public static function addLyricsList(SimpleXMLElement $xml, Song $song, bool $enhanced = false): SimpleXMLElement
    {
        if ($song->isNew() || !$song->enabled) {
            return $xml;
        }

        $xlist  = self::_addChildToResultXml($xml, 'lyricsList');
        $lyrics = OpenSubsonic_Fields::structuredLyrics($song, $enhanced);
        if ($lyrics === []) {
            return $xml;
        }

        $xlyrics = self::_addChildToResultXml($xlist, 'structuredLyrics');
        $xlyrics->addAttribute('displayArtist', $lyrics['displayArtist']);
        $xlyrics->addAttribute('displayTitle', $lyrics['displayTitle']);
        $xlyrics->addAttribute('lang', $lyrics['lang']);
        $xlyrics->addAttribute('synced', ($lyrics['synced']) ? 'true' : 'false');

        foreach ($lyrics['line'] as $line) {
            $xline = self::_addChildToResultXml($xlyrics, 'line');
            if (isset($line['start'])) {
                $xline->addAttribute('start', (string) $line['start']);
            }
            $xline->addAttribute('value', $line['value']);
        }

        foreach ($lyrics['cueLine'] ?? [] as $cueLine) {
            $xcueline = self::_addChildToResultXml($xlyrics, 'cueLine');
            $xcueline->addAttribute('index', (string) $cueLine['index']);
            $xcueline->addAttribute('start', (string) $cueLine['start']);
            $xcueline->addAttribute('value', $cueLine['value']);
            foreach ($cueLine['cue'] as $cue) {
                $xcue = self::_addChildToResultXml($xcueline, 'cue');
                $xcue->addAttribute('start', (string) $cue['start']);
                $xcue->addAttribute('byteStart', (string) $cue['byteStart']);
                $xcue->addAttribute('byteEnd', (string) $cue['byteEnd']);
            }
        }

        return $xml;
    }

    /**
     * addMusicFolders
     *
     * https://opensubsonic.netlify.app/docs/responses/musicfolders/
     * @param int[] $catalogs
     */
    public static function addMusicFolders(SimpleXMLElement $xml, array $catalogs): SimpleXMLElement
    {
        $xfolders = self::_addChildToResultXml($xml, 'musicFolders');
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }
            $xfolder = self::_addChildToResultXml($xfolders, 'musicFolder');
            $xfolder->addAttribute('id', OpenSubsonic_Api::getCatalogSubId($catalog_id));
            $xfolder->addAttribute('name', (string) $catalog->name);
        }

        return $xml;
    }

    /**
     * addNewestPodcasts
     *
     * https://opensubsonic.netlify.app/docs/responses/newestpodcasts/
     * @param Podcast_Episode[] $episodes
     */
    public static function addNewestPodcasts(SimpleXMLElement $xml, array $episodes): SimpleXMLElement
    {
        $xpodcasts = self::_addChildToResultXml($xml, 'newestPodcasts');
        foreach ($episodes as $episode) {
            self::_addPodcastEpisode($xpodcasts, $episode);
        }

        return $xml;
    }

    /**
     * addNowPlaying
     *
     * https://opensubsonic.netlify.app/docs/responses/nowplaying/
     * @param array<int, array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int,
     *     position_ms?: ?int,
     *     playback_rate?: ?float,
     *     state?: ?string
     * }> $data
     */
    public static function addNowPlaying(SimpleXMLElement $xml, array $data): SimpleXMLElement
    {
        $xplaynow = self::_addChildToResultXml($xml, 'nowPlaying');
        foreach ($data as $row) {
            if (
                $row['media'] instanceof Song
                && $row['media']->isNew() === false
                && $row['media']->enabled
            ) {
                $attributes = [
                    'username' => (string) $row['client']->username,
                    'minutesAgo' => (string) ((int) abs((time() - ($row['expire'] - $row['media']->time)) / 60)),
                    'playerId' => '0',
                    'playerName' => (string) $row['agent'],
                ];

                // Only a client that called `reportPlayback` has these; an unreported one is omitted, not guessed
                if (isset($row['position_ms'])) {
                    $attributes['positionMs'] = (string) $row['position_ms'];
                }

                if (isset($row['playback_rate'])) {
                    $attributes['playbackRate'] = (string) $row['playback_rate'];
                }

                if (isset($row['state'])) {
                    $attributes['state'] = $row['state'];
                }

                self::addSong($xplaynow, $row['media'], 'entry', $attributes);
            }
        }

        return $xml;
    }

    /**
     * addOpenSubsonicExtension
     *
     * https://opensubsonic.netlify.app/docs/responses/opensubsonicextensions/
     * @param array<string, int[]> $extensions
     */
    public static function addOpenSubsonicExtensions(SimpleXMLElement $xml, array $extensions): SimpleXMLElement
    {
        foreach ($extensions as $name => $versions) {
            $xextension = self::_addChildToResultXml($xml, 'openSubsonicExtensions');
            $xextension->addAttribute('name', $name);
            foreach ($versions as $version) {
                $xextension->addChild('versions', (string) $version);
            }
        }

        return $xml;
    }

    /**
     * addPlaylist
     * https://opensubsonic.netlify.app/docs/responses/playlist/
     * https://opensubsonic.netlify.app/docs/responses/playlistwithsongs/
     */
    public static function addPlaylist(SimpleXMLElement $xml, Playlist|Search $playlist, User $user, bool $songs = false): SimpleXMLElement
    {
        if ($playlist instanceof Playlist && $playlist->isNew() === false) {
            $xml = self::_addPlaylist_Playlist($xml, $playlist, $user, $songs);
        }
        if ($playlist instanceof Search && $playlist->isNew() === false) {
            $xml = self::_addPlaylist_Search($xml, $playlist, $songs);
        }

        return $xml;
    }

    /**
     * addPlaylists
     *
     * return playlists object with nested playlist items
     * https://opensubsonic.netlify.app/docs/responses/playlists/
     * https://opensubsonic.netlify.app/docs/responses/playlist/
     * @param int[]|string[] $playlists
     */
    public static function addPlaylists(SimpleXMLElement $xml, User $user, array $playlists): SimpleXMLElement
    {
        $xplaylists = self::_addChildToResultXml($xml, 'playlists');
        foreach ($playlists as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist = 1000000
             */
            $playlist = ((int) $playlist_id === 0)
                ? new Search((int) str_replace('smart_', '', (string) $playlist_id), 'song', $user)
                : new Playlist((int) $playlist_id);

            if ($playlist->isNew()) {
                continue;
            }

            self::addPlaylist($xplaylists, $playlist, $user);
        }

        return $xml;
    }

    /**
     * addPlayQueue
     *
     * https://opensubsonic.netlify.app/docs/responses/playqueue/
     */
    public static function addPlayQueue(SimpleXMLElement $xml, User_Playlist $playQueue, string $username): SimpleXMLElement
    {
        $items = $playQueue->get_items();
        if (!empty($items)) {
            $current   = $playQueue->get_current_object();
            $play_time = date("Y-m-d H:i:s", $playQueue->get_time());
            $date      = new DateTime($play_time);
            $date->setTimezone(new DateTimeZone('UTC'));
            $changedBy  = $playQueue->client ?? '';
            $xplayqueue = self::_addChildToResultXml($xml, 'playQueue');
            if (!empty($current)) {
                $xplayqueue->addAttribute('current', OpenSubsonic_Api::getSongSubId($current['object_id']));
                $xplayqueue->addAttribute('position', (string) ($current['current_time'] * 1000));
                $xplayqueue->addAttribute('username', $username);
                $xplayqueue->addAttribute('changed', $date->format('c'));
                $xplayqueue->addAttribute('changedBy', $changedBy);
            }

            foreach ($items as $row) {
                $song = new Song((int) $row['object_id']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xplayqueue, $song, 'entry');
            }
        }

        return $xml;
    }

    /**
     * addPlayQueueByIndex
     *
     * https://opensubsonic.netlify.app/docs/responses/playqueue/
     */
    public static function addPlayQueueByIndex(SimpleXMLElement $xml, User_Playlist $playQueue, string $username): SimpleXMLElement
    {
        $items = $playQueue->get_items();
        if (!empty($items)) {
            $current   = $playQueue->get_current_object();
            $play_time = date("Y-m-d H:i:s", $playQueue->get_time());
            try {
                $date = new DateTime($play_time);
            } catch (Exception $error) {
                debug_event(self::class, 'DateTime error: ' . $error->getMessage(), 5);

                return $xml;
            }
            $date->setTimezone(new DateTimeZone('UTC'));
            $changedBy  = $playQueue->client ?? '';
            $xplayqueue = self::_addChildToResultXml($xml, 'playQueueByIndex');
            if (!empty($current)) {
                $xplayqueue->addAttribute('currentIndex', (string) $current['current_track']);
                $xplayqueue->addAttribute('position', (string) ($current['current_time'] * 1000));
                $xplayqueue->addAttribute('username', $username);
                $xplayqueue->addAttribute('changed', $date->format('c'));
                $xplayqueue->addAttribute('changedBy', $changedBy);
            }

            foreach ($items as $row) {
                $song = new Song((int) $row['object_id']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xplayqueue, $song, 'entry');
            }
        }

        return $xml;
    }

    /**
     * addPodcastEpisode
     *
     * https://opensubsonic.netlify.app/docs/responses/podcastepisode/
     */
    public static function addPodcastEpisode(SimpleXMLElement $xml, Podcast_Episode $episode): SimpleXMLElement
    {
        $xepisode = self::_addChildToResultXml($xml, 'podcastEpisode');
        self::_addPodcastEpisode($xepisode, $episode);

        return $xml;
    }

    /**
     * addPodcasts
     *
     * https://opensubsonic.netlify.app/docs/responses/podcasts/
     * @param Podcast[] $podcasts
     */
    public static function addPodcasts(SimpleXMLElement $xml, array $podcasts, bool $includeEpisodes = true, ?string $sub_id = null): SimpleXMLElement
    {
        $xpodcasts = self::_addChildToResultXml($xml, 'podcasts');
        foreach ($podcasts as $podcast) {
            $sub_id = (!empty($sub_id))
                ? $sub_id
                : Subsonic_Api::getPodcastSubId($podcast->getId());
            $xchannel = self::_addChildToResultXml($xpodcasts, 'channel');
            $xchannel->addAttribute('id', $sub_id);
            $xchannel->addAttribute('url', $podcast->getFeedUrl());
            $xchannel->addAttribute('title', (string) $podcast->get_fullname());
            $xchannel->addAttribute('description', $podcast->get_description());
            if ($podcast->has_art()) {
                $xchannel->addAttribute('coverArt', $sub_id);
            }
            $xchannel->addAttribute('status', 'completed');
            if ($includeEpisodes) {
                $episodes = $podcast->getEpisodeIds();

                foreach ($episodes as $episode_id) {
                    $episode = new Podcast_Episode($episode_id);
                    self::_addPodcastEpisode($xchannel, $episode);
                }
            }
        }

        return $xml;
    }

    /**
     * addRandomSongs
     *
     * https://opensubsonic.netlify.app/docs/responses/randomsongs/
     * @param int[] $songs
     */
    public static function addRandomSongs(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = self::_addChildToResultXml($xml, 'randomSongs');
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xsongs, $song);
        }

        return $xml;
    }

    /**
     * addResponse
     *
     * Generate a subsonic-response
     * https://opensubsonic.netlify.app/docs/responses/subsonic-response/
     */
    public static function addResponse(string $function): SimpleXMLElement
    {
        return self::_createSuccessResponse($function);
    }

    /**
     * addScanStatus
     *
     * https://opensubsonic.netlify.app/docs/responses/scanstatus/
     */
    public static function addScanStatus(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $counts = Catalog::get_server_counts($user->id);
        $count  = $counts['artist'] + $counts['album'] + $counts['song'] + $counts['podcast_episode'];
        $xscan  = self::_addChildToResultXml($xml, htmlspecialchars('scanStatus'));
        $xscan->addAttribute('scanning', "false");
        $xscan->addAttribute('count', (string) $count);

        return $xml;
    }

    /**
     * addSearchResult
     *
     * https://opensubsonic.netlify.app/docs/responses/searchresult/
     * @param int[] $songs
     */
    public static function addSearchResult(SimpleXMLElement $xml, array $songs, int $offset, int $total): SimpleXMLElement
    {
        $xresult = self::_addChildToResultXml($xml, htmlspecialchars('searchResult'));
        $xresult->addAttribute('offset', (string) $offset);
        $xresult->addAttribute('totalHits', (string) $total);
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xresult, $song, 'match');
        }

        return $xml;
    }

    /**
     * addSearchResult2
     *
     * https://opensubsonic.netlify.app/docs/responses/searchresult2/
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public static function addSearchResult2(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xresult = self::_addChildToResultXml($xml, htmlspecialchars('searchResult2'));
        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            self::addArtist($xresult, $artist);
        }
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbum($xresult, $album);
        }
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xresult, $song);
        }

        return $xml;
    }

    /**
     * addSearchResult3
     *
     * https://opensubsonic.netlify.app/docs/responses/searchresult3/
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public static function addSearchResult3(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xresult = self::_addChildToResultXml($xml, htmlspecialchars('searchResult3'));
        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            self::addArtistID3($xresult, $artist);
        }
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbumID3($xresult, $album);
        }
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xresult, $song);
        }

        return $xml;
    }

    /**
     * addShares
     *
     * https://opensubsonic.netlify.app/docs/responses/shares/
     * @param int[] $shares
     */
    public static function addShares(SimpleXMLElement $xml, array $shares): SimpleXMLElement
    {
        $xshares = self::_addChildToResultXml($xml, 'shares');
        foreach ($shares as $share_id) {
            $share = new Share($share_id);
            // Don't add share with max counter already reached
            if ($share->max_counter === 0 || $share->counter < $share->max_counter) {
                self::_addShare($xshares, $share);
            }
        }

        return $xml;
    }

    /**
     * addSimilarSongs
     *
     * https://opensubsonic.netlify.app/docs/responses/similarsongs/
     * @param array<int, array{
     *     id: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string,
     * }> $similar_songs
     */
    public static function addSimilarSongs(SimpleXMLElement $xml, array $similar_songs): SimpleXMLElement
    {
        $xsimilar = self::_addChildToResultXml($xml, 'similarSongs');
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                $song = new Song($similar_song['id']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xsimilar, $song);
            }
        }

        return $xml;
    }

    /**
     * addSimilarSongs2
     *
     * https://opensubsonic.netlify.app/docs/responses/similarsongs2/
     * @param array<int, array{
     *     id: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string,
     * }> $similar_songs
     */
    public static function addSimilarSongs2(SimpleXMLElement $xml, array $similar_songs): SimpleXMLElement
    {
        $xsimilar = self::_addChildToResultXml($xml, 'similarSongs2');
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                $song = new Song($similar_song['id']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xsimilar, $song);
            }
        }

        return $xml;
    }

    /**
     * addSong
     *
     * https://opensubsonic.netlify.app/docs/responses/song/
     * @param array<string, string> $attributes
     */
    public static function addSong(SimpleXMLElement $xml, Song $song, string $elementName = 'song', array $attributes = []): SimpleXMLElement
    {
        return self::_addChildSong($xml, $song, $elementName, $attributes);
    }

    /**
     * addSongsByGenre
     *
     * https://opensubsonic.netlify.app/docs/responses/songsbygenre/
     * @param int[] $songs
     */
    public static function addSongsByGenre(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = self::_addChildToResultXml($xml, 'songsByGenre');
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xsongs, $song);
        }

        return $xml;
    }

    /**
     * addSonicMatches
     *
     * The ordered sonicMatch list shared by getSonicSimilarTracks and findSonicPath. It sits directly on the
     * response rather than under a wrapper element, and a song that has since gone is dropped from the list.
     *
     * https://opensubsonic.netlify.app/docs/responses/sonicmatch/
     * @param list<array{'id': int, 'similarity': float}> $matches
     */
    public static function addSonicMatches(SimpleXMLElement $xml, array $matches): SimpleXMLElement
    {
        foreach ($matches as $match) {
            $song = new Song($match['id']);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }

            $xmatch = self::_addChildToResultXml($xml, 'sonicMatch');
            $xmatch->addAttribute('similarity', (string) $match['similarity']);
            self::addSong($xmatch, $song, 'entry');
        }

        return $xml;
    }

    /**
     * addStarred
     *
     * https://opensubsonic.netlify.app/docs/responses/starred/
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public static function addStarred(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xstarred = self::_addChildToResultXml($xml, htmlspecialchars('starred'));

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            self::addArtist($xstarred, $artist);
        }

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            // `Starred` holds `Child` albums; `Starred2` is the ID3 variant.
            self::addAlbum($xstarred, $album);
        }

        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xstarred, $song);
        }

        return $xml;
    }

    /**
     * addStarred2
     *
     * https://opensubsonic.netlify.app/docs/responses/starred2/
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public static function addStarred2(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xstarred = self::_addChildToResultXml($xml, htmlspecialchars('starred2'));

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            self::addArtistID3($xstarred, $artist);
        }

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            self::addAlbumID3($xstarred, $album);
        }

        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xstarred, $song);
        }

        return $xml;
    }

    /**
     * addTokenInfo
     *
     * Information about an API key
     * https://opensubsonic.netlify.app/docs/responses/tokeninfo/
     */
    public static function addTokenInfo(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $xscan = self::_addChildToResultXml($xml, htmlspecialchars('tokenInfo'));
        $xscan->addAttribute('username', (string) $user->username);

        return $xml;
    }

    /**
     * addTopSongs
     *
     * https://opensubsonic.netlify.app/docs/responses/topsongs/
     * @param int[] $songs
     */
    public static function addTopSongs(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = self::_addChildToResultXml($xml, 'topSongs');
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xsongs, $song);
        }

        return $xml;
    }

    /**
     * addTranscodeDecision
     *
     * https://opensubsonic.netlify.app/docs/responses/transcodedecision/
     * @param array<string, mixed> $decision
     */
    public static function addTranscodeDecision(SimpleXMLElement $xml, array $decision): SimpleXMLElement
    {
        $xdecision = self::_addChildToResultXml($xml, 'transcodeDecision');
        foreach (['canDirectPlay', 'canTranscode'] as $key) {
            $xdecision->addAttribute($key, ($decision[$key] ?? false) ? 'true' : 'false');
        }

        foreach (['errorReason', 'transcodeParams'] as $key) {
            if (isset($decision[$key])) {
                $xdecision->addAttribute($key, (string) $decision[$key]);
            }
        }

        foreach ((array) ($decision['transcodeReason'] ?? []) as $reason) {
            self::_addChildToResultXml($xdecision, 'transcodeReason', (string) $reason);
        }

        // Both stream blocks are flat scalar maps, so each becomes one element carrying the values as attributes.
        foreach (['sourceStream', 'transcodeStream'] as $key) {
            if (!is_array($decision[$key] ?? null)) {
                continue;
            }

            $xstream = self::_addChildToResultXml($xdecision, $key);
            foreach ($decision[$key] as $name => $value) {
                $xstream->addAttribute((string) $name, (string) $value);
            }
        }

        return $xml;
    }

    /**
     * addUser
     *
     * https://opensubsonic.netlify.app/docs/responses/user/
     */
    public static function addUser(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $xuser = self::_addChildToResultXml($xml, 'user');
        $xuser->addAttribute('username', (string) $user->username);
        $xuser->addAttribute('email', (string) $user->email);
        $xuser->addAttribute('maxBitRate', (string) OpenSubsonic_Fields::userMaxBitRate($user));
        $xuser->addAttribute('scrobblingEnabled', 'true');
        $isManager = ($user->access >= 75);
        $isAdmin   = ($user->access === 100);
        $xuser->addAttribute('adminRole', ($isAdmin) ? 'true' : 'false');
        $xuser->addAttribute('settingsRole', 'true');
        $xuser->addAttribute('downloadRole', (Preference::get_by_user($user->id, 'download')) ? 'true' : 'false');
        $xuser->addAttribute('uploadRole', (Preference::get_by_user($user->id, 'allow_upload')) ? 'true' : 'false');
        $xuser->addAttribute('playlistRole', 'true');
        $xuser->addAttribute('coverArtRole', ($isManager) ? 'true' : 'false');
        $xuser->addAttribute('commentRole', (AmpConfig::get('social')) ? 'true' : 'false');
        $xuser->addAttribute('podcastRole', (AmpConfig::get('podcast')) ? 'true' : 'false');
        $xuser->addAttribute('streamRole', 'true');
        $xuser->addAttribute('jukeboxRole', (AmpConfig::get('allow_localplay_playback') && AmpConfig::get('localplay_controller') && Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::GUEST, $user->getId())) ? 'true' : 'false');
        $xuser->addAttribute('shareRole', (Preference::get_by_user($user->id, 'share')) ? 'true' : 'false');
        $xuser->addAttribute('videoConversionRole', 'false');

        return $xml;
    }

    /**
     * addUsers
     *
     * https://opensubsonic.netlify.app/docs/responses/users/
     * @param int[] $users
     */
    public static function addUsers(SimpleXMLElement $xml, array $users): SimpleXMLElement
    {
        $xusers = self::_addChildToResultXml($xml, 'users');
        foreach ($users as $user_id) {
            $user = new User($user_id);
            if ($user->isNew() === false) {
                self::addUser($xusers, $user);
            }
        }

        return $xml;
    }

    /**
     * addVideoInfo
     *
     * https://opensubsonic.netlify.app/docs/responses/videoinfo/
     */
    public static function addVideoInfo(SimpleXMLElement $xml, int $video_id): SimpleXMLElement
    {
        $xvideoinfo = self::_addChildToResultXml($xml, 'videoInfo');
        $xvideoinfo->addAttribute('id', OpenSubsonic_Api::getVideoSubId($video_id));

        return $xml;
    }

    /**
     * addVideos
     *
     * https://opensubsonic.netlify.app/docs/responses/videos/
     * @param Video[] $videos
     */
    public static function addVideos(SimpleXMLElement $xml, array $videos): SimpleXMLElement
    {
        $xvideos = self::_addChildToResultXml($xml, 'videos');
        foreach ($videos as $video) {
            self::_addVideo($xvideos, $video);
        }

        return $xml;
    }

    /**
     * addArtistArray
     * @param array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     song_count: int,
     *     catalog_id: int,
     *     has_art: int
     * } $artist
     */
    private static function _addArtistArray(SimpleXMLElement $xml, array $artist, bool $id3 = true): void
    {
        $sub_id  = OpenSubsonic_Api::getArtistSubId($artist['id']);
        $xartist = self::_addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', $artist['f_name']);
        // `coverArt` and `albumCount` are `ArtistID3` only; a plain `Index` holds the `Artist` type.
        if ($id3) {
            if ($artist['has_art']) {
                $xartist->addAttribute('coverArt', $sub_id);
            }
            $xartist->addAttribute('albumCount', (string) $artist['album_count']);
        }
        self::_setIfStarred($xartist, 'artist', $artist['id']);

        // [OPENSUBSONIC] roles: tell an album artist apart from a song-only artist regardless of the
        // `subsonic_force_album_artist` server preference (see _addArtistRoles).
        self::_addArtistRoles($xartist, $artist['album_count'], $artist['song_count']);
    }

    /**
     * _addArtistRoles
     *
     * Add the OpenSubsonic `roles` list as repeated <role> children: `albumartist` when the artist is
     * credited on at least one album, `artist` when credited on at least one song.
     */
    private static function _addArtistRoles(SimpleXMLElement $xartist, int $album_count, int $song_count): void
    {
        if ($album_count > 0) {
            $xartist->addChild('role', 'albumartist');
        }
        if ($song_count > 0) {
            $xartist->addChild('role', 'artist');
        }
    }

    /**
     * addBookmark
     *
     * https://opensubsonic.netlify.app/docs/responses/bookmark/
     */
    private static function _addBookmark(SimpleXMLElement $xml, Bookmark $bookmark): void
    {
        $xbookmark = self::_addChildToResultXml($xml, 'bookmark');
        $xbookmark->addAttribute('position', (string) $bookmark->position);
        $xbookmark->addAttribute('username', $bookmark->getUserName());
        $xbookmark->addAttribute('comment', (string) $bookmark->comment);
        $xbookmark->addAttribute('created', date("c", $bookmark->creation_date));
        $xbookmark->addAttribute('changed', date("c", $bookmark->update_date));
        if ($bookmark->object_type == "song") {
            $song = new Song($bookmark->object_id);
            if ($song->isNew() === false && $song->enabled) {
                self::addSong($xbookmark, $song, 'entry');
            }
        } elseif ($bookmark->object_type == "video") {
            self::_addVideo($xbookmark, new Video($bookmark->object_id), 'entry');
        } elseif ($bookmark->object_type == "podcast_episode") {
            self::_addPodcastEpisode($xbookmark, new Podcast_Episode($bookmark->object_id), 'entry');
        }
    }

    /**
     * addChildArray
     * @param array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * } $child
     */
    private static function _addChildArray(SimpleXMLElement $xml, array $child): void
    {
        $sub_id = OpenSubsonic_Api::getArtistSubId($child['id']);
        $xchild = self::_addChildToResultXml($xml, 'child');
        $xchild->addAttribute('id', $sub_id);
        $xchild->addAttribute('parent', OpenSubsonic_Api::getCatalogSubId($child['catalog_id']));
        $xchild->addAttribute('isDir', 'true');
        $xchild->addAttribute('title', $child['f_name']);
        $xchild->addAttribute('artist', $child['f_name']);
        if ($child['has_art']) {
            $xchild->addAttribute('coverArt', $sub_id);
        }
    }

    /**
     * addChildSong
     *
     * https://opensubsonic.netlify.app/docs/responses/child/
     * @param array<string, string> $attributes
     */
    private static function _addChildSong(SimpleXMLElement $xml, Song $song, string $elementName, array $attributes = []): SimpleXMLElement
    {
        $sub_id    = OpenSubsonic_Api::getSongSubId($song->id);
        $subParent = OpenSubsonic_Api::getAlbumSubId($song->album);
        $xsong     = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xsong->addAttribute('id', $sub_id);
        $xsong->addAttribute('parent', $subParent);
        //$xsong->addAttribute('created', );
        $xsong->addAttribute('title', (string) $song->title);
        $xsong->addAttribute('isDir', 'false');
        $xsong->addAttribute('isVideo', 'false');
        $xsong->addAttribute('type', 'music');
        $xsong->addAttribute('albumId', $subParent);
        $xsong->addAttribute('album', $song->get_album_fullname());
        $xsong->addAttribute('artistId', ($song->artist) ? OpenSubsonic_Api::getArtistSubId($song->artist) : '');
        $xsong->addAttribute('artist', $song->get_parent_fullname());
        if ($song->has_art()) {
            $art_id = (AmpConfig::get('show_song_art', false)) ? $sub_id : $subParent;
            $xsong->addAttribute('coverArt', $art_id);
        }
        $xsong->addAttribute('duration', (string) $song->time);
        $xsong->addAttribute('bitRate', (string) ((int) ($song->bitrate / 1024)));
        $rating      = new Rating($song->id, 'song');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $xsong->addAttribute('userRating', (string) ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xsong->addAttribute('averageRating', (string) $avg_rating);
        }

        $xsong->addAttribute('playCount', (string) $song->total_count);

        $played = OpenSubsonic_Fields::lastPlayed($song->last_played);
        if ($played !== null) {
            $xsong->addAttribute('played', $played);
        }

        self::_setIfStarred($xsong, 'song', $song->id);
        if ($song->track > 0) {
            $xsong->addAttribute('track', (string) $song->track);
        }
        if ($song->year > 0) {
            $xsong->addAttribute('year', (string) $song->year);
        }
        $tags = Tag::get_object_tags('song', $song->id);
        if (!empty($tags)) {
            $xsong->addAttribute('genre', implode(',', array_column($tags, 'name')));
            foreach ($tags as $tag) {
                $xlastcat = self::_addChildToResultXml($xsong, 'genres');
                $xlastcat->addAttribute('name', (string) $tag['name']);
            }
        }
        $xsong->addAttribute('size', (string) $song->size);
        $disk = $song->disk;
        if ($disk > 0) {
            $xsong->addAttribute('discNumber', (string) $disk);
        }
        $xsong->addAttribute('suffix', $song->type);
        $xsong->addAttribute('contentType', (string) $song->mime);
        // Always return the original filename, not the transcoded one
        $xsong->addAttribute('path', (string) $song->file);

        // These mirror OpenSubsonic_Json_Data::_getChildSong() through OpenSubsonic_Fields, so the formats cannot drift.
        $artists = [];
        foreach ($song->get_artists() as $artist_id) {
            $array     = Artist::get_name_array_by_id($artist_id);
            $artists[] = (string) $array['name'];
            $xartist   = self::_addChildToResultXml($xsong, 'artists');
            $xartist->addAttribute('id', OpenSubsonic_Api::getArtistSubId($artist_id));
            $xartist->addAttribute('name', (string) $array['name']);
        }

        $album_artists = [];
        foreach ($song->get_album_artists() as $artist_id) {
            $array           = Artist::get_name_array_by_id($artist_id);
            $album_artists[] = (string) $array['name'];
            $xalbumartist    = self::_addChildToResultXml($xsong, 'albumArtists');
            $xalbumartist->addAttribute('id', OpenSubsonic_Api::getArtistSubId($artist_id));
            $xalbumartist->addAttribute('name', (string) $array['name']);
        }

        $xsong->addAttribute('displayArtist', $song->get_parent_fullname());
        if ($album_artists !== []) {
            $xsong->addAttribute('displayAlbumArtist', implode(', ', $album_artists));
        }

        $composer = trim((string) $song->composer);
        if ($composer !== '') {
            $xsong->addAttribute('displayComposer', $composer);
        }

        foreach (OpenSubsonic_Fields::songContributors($song) as $contributor) {
            $xcontributor = self::_addChildToResultXml($xsong, 'contributors');
            $xcontributor->addAttribute('role', $contributor['role']);
            $xartist = self::_addChildToResultXml($xcontributor, 'artist');
            $xartist->addAttribute('id', $contributor['artist']['id']);
            $xartist->addAttribute('name', $contributor['artist']['name']);
        }

        $xsong->addAttribute('musicBrainzId', (string) $song->mbid);
        $xsong->addAttribute('mediaType', 'song');

        if ($song->rate > 0) {
            $xsong->addAttribute('samplingRate', (string) $song->rate);
        }

        if ($song->channels !== null && $song->channels > 0) {
            $xsong->addAttribute('channelCount', (string) $song->channels);
        }

        foreach (OpenSubsonic_Fields::songIsrc($song) as $isrc) {
            self::_addChildToResultXml($xsong, 'isrc', $isrc);
        }

        $bookmark_position = OpenSubsonic_Fields::songBookmarkPosition($song);
        if ($bookmark_position !== null) {
            $xsong->addAttribute('bookmarkPosition', (string) $bookmark_position);
        }

        // replayGain is required on a Child even when Ampache holds no gain tags, so the element is always added.
        $xreplaygain = self::_addChildToResultXml($xsong, 'replayGain');
        foreach (OpenSubsonic_Fields::songReplayGain($song) as $key => $value) {
            $xreplaygain->addAttribute($key, (string) $value);
        }

        if (AmpConfig::get('transcode', 'default') != 'never') {
            $cache_path     = (string) AmpConfig::get('cache_path', '');
            $cache_target   = (string) AmpConfig::get('cache_target', '');
            $file_target    = Catalog::get_cache_path($song->getId(), $song->getCatalogId(), $cache_path, $cache_target);
            $transcode_type = ($file_target !== null && is_file($file_target))
                ? $cache_target
                : Stream::get_transcode_format($song->type, null, 'api');

            if (!empty($transcode_type) && $song->type !== $transcode_type) {
                // Set transcoding information
                $xsong->addAttribute('transcodedSuffix', $transcode_type);
                $xsong->addAttribute('transcodedContentType', Song::type_to_mime($transcode_type));
            }
        }
        foreach ($attributes as $key => $value) {
            $xsong->addAttribute($key, $value);
        }

        return $xml;
    }

    /**
     * Adds a child to an existing result xml structure
     */
    private static function _addChildToResultXml(SimpleXMLElement $xml, string $qualifiedName, ?string $value = null): SimpleXMLElement
    {
        /** @var SimpleXMLElement $child */
        $child = $xml->addChild($qualifiedName, $value);

        return $child;
    }

    /**
     * addDirectory_Album for subsonic album id
     */
    private static function _addDirectory_Album(SimpleXMLElement $xml, Album $album): void
    {
        $album_id = $album->id;
        $xdir     = self::_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', OpenSubsonic_Api::getAlbumSubId($album_id));
        $album_artist = $album->findAlbumArtist();
        if ($album_artist) {
            $xdir->addAttribute('parent', OpenSubsonic_Api::getArtistSubId($album_artist));
        } else {
            $xdir->addAttribute('parent', OpenSubsonic_Api::getCatalogSubId($album->catalog));
        }
        $xdir->addAttribute('name', $album->get_fullname());
        self::_setIfStarred($xdir, 'album', $album->id);

        $media_ids = self::getAlbumRepository()->getSongs($album->id);
        foreach ($media_ids as $song_id) {
            // TODO addChild || use addChildArray
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xdir, $song, 'child');
        }
    }

    /**
     * addDirectory_Artist for subsonic artist id
     */
    private static function _addDirectory_Artist(SimpleXMLElement $xml, Artist $artist): void
    {
        $artist_id = $artist->id;
        $data      = Artist::get_id_array($artist_id);
        $xdir      = self::_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', OpenSubsonic_Api::getArtistSubId($artist_id));
        if ($data['catalog_id']) {
            $xdir->addAttribute('parent', OpenSubsonic_Api::getCatalogSubId($data['catalog_id']));
        }
        $xdir->addAttribute('name', (string) $data['f_name']);
        self::_setIfStarred($xdir, 'artist', $artist_id);
        $allalbums = self::getAlbumRepository()->getAlbumByArtist($artist_id);
        foreach ($allalbums as $album_id) {
            $album = new Album($album_id);
            // TODO addChild || use addChildArray
            self::addAlbum($xdir, $album, false, 'child');
        }
    }

    /**
     * addDirectory_Catalog for subsonic artist id
     */
    private static function _addDirectory_Catalog(SimpleXMLElement $xml, Catalog $catalog): void
    {
        $catalog_id = $catalog->id;
        $xdir       = self::_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', OpenSubsonic_Api::getCatalogSubId($catalog_id));
        $xdir->addAttribute('name', (string) $catalog->name);
        $allartists = Catalog::get_artist_arrays([$catalog_id]);
        foreach ($allartists as $artist) {
            self::_addChildArray($xdir, $artist);
        }
    }

    /**
     * addIgnoredArticles
     */
    private static function _addIgnoredArticles(SimpleXMLElement $xml): void
    {
        $ignoredArticles = AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La');
        if (!empty($ignoredArticles)) {
            $ignoredArticles = str_replace('|', ' ', $ignoredArticles);
            $xml->addAttribute('ignoredArticles', (string) $ignoredArticles);
        }
    }

    /**
     * addIndex
     *
     * https://opensubsonic.netlify.app/docs/responses/index_/
     * @param array<int, array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     song_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    private static function _addIndex(SimpleXMLElement $xml, array $artists, bool $id3 = true): void
    {
        $xlastcat     = null;
        $sharpartists = [];
        $xlastletter  = '';
        foreach ($artists as $artist) {
            if (strlen((string) $artist['name']) > 0) {
                $letter = strtoupper((string) $artist['name'][0]);
                if ($letter == 'X' || $letter == 'Y' || $letter == 'Z') {
                    $letter = 'X-Z';
                } elseif (!preg_match("/^[A-W]$/", $letter)) {
                    $sharpartists[] = $artist;
                    continue;
                }

                if ($letter != $xlastletter) {
                    $xlastletter = $letter;
                    $xlastcat    = self::_addChildToResultXml($xml, 'index');
                    $xlastcat->addAttribute('name', $xlastletter);
                }
            }

            if ($xlastcat != null) {
                self::_addArtistArray($xlastcat, $artist, $id3);
            }
        }

        // Always add # index at the end
        if (count($sharpartists) > 0) {
            $xsharpcat = self::_addChildToResultXml($xml, 'index');
            $xsharpcat->addAttribute('name', '#');

            foreach ($sharpartists as $artist) {
                self::_addArtistArray($xsharpcat, $artist, $id3);
            }
        }
    }

    /**
     * addInternetRadioStation
     *
     * https://opensubsonic.netlify.app/docs/responses/internetradiostation/
     */
    private static function _addInternetRadioStation(SimpleXMLElement $xml, Live_Stream $radio): void
    {
        $sub_id = OpenSubsonic_Api::getLiveStreamSubId($radio->id);
        $xradio = self::_addChildToResultXml($xml, 'internetRadioStation');
        $xradio->addAttribute('id', $sub_id);
        $xradio->addAttribute('name', (string) $radio->name);
        $xradio->addAttribute('streamUrl', (string) $radio->url);
        $xradio->addAttribute('homePageUrl', (string) $radio->site_url);
        if ($radio->has_art()) {
            $xradio->addAttribute('coverArt', $sub_id);
        }
    }

    /**
     * addMessage
     *
     * A chatMessage.
     * https://opensubsonic.netlify.app/docs/responses/chatmessage/
     */
    private static function _addMessage(SimpleXMLElement $xml, PrivateMsg $message): void
    {
        $user      = new User($message->getSenderUserId());
        $xbookmark = self::_addChildToResultXml($xml, 'chatMessage');
        if ($user->fullname_public) {
            $xbookmark->addAttribute('username', (string) $user->fullname);
        } else {
            $xbookmark->addAttribute('username', (string) $user->username);
        }
        $xbookmark->addAttribute('time', (string) ($message->getCreationDate() * 1000));
        $xbookmark->addAttribute('message', $message->getMessage());
    }

    /**
     * addPlaylist_Playlist
     *
     * https://opensubsonic.netlify.app/docs/responses/playlist/
     * https://opensubsonic.netlify.app/docs/responses/playlistwithsongs/
     */
    private static function _addPlaylist_Playlist(SimpleXMLElement $xml, Playlist $playlist, User $user, bool $songs = false): SimpleXMLElement
    {
        $sub_id    = OpenSubsonic_Api::getPlaylistSubId($playlist->id);
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;
        $xplaylist = self::_addChildToResultXml($xml, 'playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string) $playlist->get_fullname());
        $xplaylist->addAttribute('owner', (string) $playlist->username);
        $xplaylist->addAttribute('public', ($playlist->type != 'private') ? 'true' : 'false');
        $xplaylist->addAttribute('songCount', (string) $songcount);
        $xplaylist->addAttribute('duration', (string) $duration);
        $xplaylist->addAttribute('created', date('c', $playlist->date));
        $xplaylist->addAttribute('changed', date('c', (int) $playlist->last_update));
        if ($playlist->has_art()) {
            $xplaylist->addAttribute('coverArt', $sub_id);
        }

        $xplaylist->addAttribute('readonly', (string) $playlist->has_access($user));

        foreach (OpenSubsonic_Fields::allowedUsers($playlist) as $allowed_user) {
            self::_addChildToResultXml($xplaylist, 'allowedUser', $allowed_user);
        }

        try {
            $date = new DateTime(date("Y-m-d H:i:s", time() + 300));
            $date->setTimezone(new DateTimeZone('UTC'));
            $xplaylist->addAttribute('validUntil', $date->format('c'));
        } catch (Exception $error) {
            debug_event(self::class, 'DateTime error: ' . $error->getMessage(), 5);
        }
        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xplaylist, $song, 'entry');
            }
        }

        return $xml;
    }

    /**
     * addPlaylist_Search
     *
     * https://opensubsonic.netlify.app/docs/responses/playlist/
     * https://opensubsonic.netlify.app/docs/responses/playlistwithsongs/
     */
    private static function _addPlaylist_Search(SimpleXMLElement $xml, Search $search, bool $songs = false): SimpleXMLElement
    {
        $sub_id    = OpenSubsonic_Api::getSmartPlaylistSubId($search->id);
        $xplaylist = self::_addChildToResultXml($xml, 'playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string) $search->get_fullname());
        $xplaylist->addAttribute('owner', (string) $search->username);
        $xplaylist->addAttribute('public', ($search->type != 'private') ? 'true' : 'false');
        if ($songs) {
            $allitems  = $search->get_items();
            $songcount = count($allitems);
            $duration  = ($songcount > 0) ? Search::get_total_duration($allitems) : 0;
        } else {
            $allitems = [];
            // both are integers in the schema, so an unset counter must serialize as 0 rather than ''
            $songcount = (int) $search->last_count;
            $duration  = (int) $search->last_duration;
        }
        $xplaylist->addAttribute('songCount', (string) $songcount);
        $xplaylist->addAttribute('duration', (string) $duration);
        $xplaylist->addAttribute('created', date('c', $search->date));
        $xplaylist->addAttribute('changed', date('c', time()));
        $xplaylist->addAttribute('coverArt', $sub_id);
        $xplaylist->addAttribute('readonly', '0');

        try {
            $date = new DateTime(date("Y-m-d H:i:s", time() + 300));
            $date->setTimezone(new DateTimeZone('UTC'));
            $xplaylist->addAttribute('validUntil', $date->format('c'));
        } catch (Exception $error) {
            debug_event(self::class, 'DateTime error: ' . $error->getMessage(), 5);
        }

        foreach ($allitems as $item) {
            $song = new Song((int) $item['object_id']);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            self::addSong($xplaylist, $song, 'entry');
        }

        return $xml;
    }

    /**
     * addPodcastEpisode
     *
     * A Child plus `channelId`, `description`, `publishDate`, `status` and `streamId`.
     *
     * https://opensubsonic.netlify.app/docs/responses/podcastepisode/
     */
    private static function _addPodcastEpisode(SimpleXMLElement $xml, Podcast_Episode $episode, string $elementName = 'episode'): void
    {
        if ($episode->isNew()) {
            return;
        }

        $sub_id    = OpenSubsonic_Api::getPodcastEpisodeSubId($episode->id);
        $subParent = OpenSubsonic_Api::getPodcastSubId($episode->podcast);
        $xepisode  = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xepisode->addAttribute('id', $sub_id);
        $xepisode->addAttribute('channelId', $subParent);
        $xepisode->addAttribute('title', (string) $episode->get_fullname());
        $xepisode->addAttribute('album', $episode->getPodcastName());
        $xepisode->addAttribute('description', $episode->get_description());
        $xepisode->addAttribute('duration', (string) $episode->time);
        $xepisode->addAttribute('isDir', "false");
        $xepisode->addAttribute('isVideo', "false");
        $xepisode->addAttribute('type', 'podcast');
        $xepisode->addAttribute('publishDate', $episode->getPubDate()->format(DATE_ATOM));
        $xepisode->addAttribute('status', (string) $episode->state);
        $xepisode->addAttribute('parent', $subParent);
        if ($episode->has_art()) {
            $xepisode->addAttribute('coverArt', $subParent);
        }
        $xepisode->addAttribute('bitRate', (string) ((int) ($episode->bitrate / 1024)));

        // Episodes are rarely tagged, so the long standing "Podcast" genre remains the fallback when none are set.
        $tags = Tag::get_object_tags('podcast_episode', $episode->id);
        if (!empty($tags)) {
            $xepisode->addAttribute('genre', implode(',', array_column($tags, 'name')));
            foreach ($tags as $tag) {
                $xlastcat = self::_addChildToResultXml($xepisode, 'genres');
                $xlastcat->addAttribute('name', (string) $tag['name']);
            }
        } else {
            $xepisode->addAttribute('genre', "Podcast");
        }

        $rating      = new Rating($episode->id, 'podcast_episode');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $xepisode->addAttribute('userRating', (string) ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xepisode->addAttribute('averageRating', (string) $avg_rating);
        }

        $xepisode->addAttribute('playCount', (string) $episode->total_count);

        $played = OpenSubsonic_Fields::lastPlayed($episode->last_played);
        if ($played !== null) {
            $xepisode->addAttribute('played', $played);
        }

        $xepisode->addAttribute('created', date("Y-m-d\TH:i:s\Z", $episode->addition_time));

        self::_setIfStarred($xepisode, 'podcast_episode', $episode->id);

        if ($episode->file) {
            $xepisode->addAttribute('streamId', $sub_id);
            $xepisode->addAttribute('size', (string) $episode->size);
            $xepisode->addAttribute('suffix', $episode->type);
            $xepisode->addAttribute('contentType', (string) $episode->mime);
            // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
            $path = basename($episode->file);
            $xepisode->addAttribute('path', $path);
        }
    }

    /**
     * addShare
     *
     * https://opensubsonic.netlify.app/docs/responses/share/
     */
    private static function _addShare(SimpleXMLElement $xml, Share $share): void
    {
        $xshare = self::_addChildToResultXml($xml, 'share');
        $xshare->addAttribute('id', OpenSubsonic_Api::getShareSubId($share->id));
        $xshare->addAttribute('url', (string) $share->public_url);
        $xshare->addAttribute('description', (string) $share->description);
        $user = new User($share->user);
        $xshare->addAttribute('username', (string) $user->username);
        $xshare->addAttribute('created', date('c', $share->creation_date));
        if ($share->lastvisit_date > 0) {
            $xshare->addAttribute('lastVisited', date('c', $share->lastvisit_date));
        }
        if ($share->expire_days > 0) {
            $xshare->addAttribute('expires', date('c', $share->creation_date + ($share->expire_days * 86400)));
        }
        $xshare->addAttribute('visitCount', (string) $share->counter);

        if ($share->object_type == 'song') {
            $song = new Song($share->object_id);
            if ($song->isNew() === false && $song->enabled) {
                self::addSong($xshare, $song, 'entry');
            }
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs    = $playlist->get_songs();
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xshare, $song, 'entry');
            }
        } elseif ($share->object_type == 'album') {
            $songs = self::getSongRepository()->getByAlbum($share->object_id);
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                self::addSong($xshare, $song, 'entry');
            }
        }
    }

    /**
     * addVideo
     *
     * https://opensubsonic.netlify.app/docs/responses/child/
     */
    private static function _addVideo(SimpleXMLElement $xml, Video $video, string $elementName = 'video'): void
    {
        if ($video->isNew()) {
            return;
        }

        $sub_id    = OpenSubsonic_Api::getVideoSubId($video->id);
        $subParent = OpenSubsonic_Api::getCatalogSubId($video->catalog);
        $xvideo    = self::_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xvideo->addAttribute('id', $sub_id);
        $xvideo->addAttribute('parent', $subParent);
        $xvideo->addAttribute('title', $video->getFileName());
        $xvideo->addAttribute('isDir', 'false');
        if ($video->has_art()) {
            $xvideo->addAttribute('coverArt', $sub_id);
        }
        $xvideo->addAttribute('isVideo', 'true');
        $xvideo->addAttribute('type', 'video');
        $xvideo->addAttribute('duration', (string) $video->time);
        $xvideo->addAttribute('bitRate', (string) ((int) ($video->bitrate / 1024)));
        if (isset($video->year) && $video->year > 0) {
            $xvideo->addAttribute('year', (string) $video->year);
        }
        $tags = Tag::get_object_tags('video', $video->id);
        if (!empty($tags)) {
            $xvideo->addAttribute('genre', implode(',', array_column($tags, 'name')));
            foreach ($tags as $tag) {
                $xlastcat = self::_addChildToResultXml($xvideo, 'genres');
                $xlastcat->addAttribute('name', (string) $tag['name']);
            }
        }
        $xvideo->addAttribute('size', (string) $video->size);
        $xvideo->addAttribute('suffix', $video->type);
        $xvideo->addAttribute('contentType', (string) $video->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = basename($video->file ?? '');
        $xvideo->addAttribute('path', $path);

        // The source dimensions, so a client can decide for itself whether a transcode would downscale the video.
        if ($video->resolution_x > 0 && $video->resolution_y > 0) {
            $xvideo->addAttribute('originalWidth', (string) $video->resolution_x);
            $xvideo->addAttribute('originalHeight', (string) $video->resolution_y);
        }

        $rating      = new Rating($video->id, 'video');
        $user_rating = ($rating->get_user_rating() ?? 0);
        if ($user_rating > 0) {
            $xvideo->addAttribute('userRating', (string) ceil($user_rating));
        }
        $avg_rating = $rating->get_average_rating();
        if ($avg_rating > 0) {
            $xvideo->addAttribute('averageRating', (string) $avg_rating);
        }

        $xvideo->addAttribute('playCount', (string) $video->total_count);

        $played = OpenSubsonic_Fields::lastPlayed($video->last_played);
        if ($played !== null) {
            $xvideo->addAttribute('played', $played);
        }

        $xvideo->addAttribute('created', date("Y-m-d\TH:i:s\Z", (int) $video->addition_time));

        self::_setIfStarred($xvideo, 'video', $video->id);
        // Set transcoding information if required
        $transcode_cfg = AmpConfig::get('transcode', 'default');
        $valid_types   = Stream::get_stream_types_for_type($video->type, 'api');
        if ($transcode_cfg == 'always' || ($transcode_cfg != 'never' && !in_array('native', $valid_types))) {
            $transcode_settings = $video->get_transcode_settings(null, 'api');
            if (!empty($transcode_settings['format'])) {
                $transcode_type = $transcode_settings['format'];
                $xvideo->addAttribute('transcodedSuffix', (string) $transcode_type);
                $xvideo->addAttribute('transcodedContentType', Video::type_to_mime($transcode_type));
            }
        }
    }

    /**
     * _createFailedResponse
     *
     * https://opensubsonic.netlify.app/docs/responses/subsonic-response/
     */
    private static function _createFailedResponse(string $function = ''): SimpleXMLElement
    {
        $response = self::_createResponse('failed');
        debug_event(self::class, 'API fail in function ' . $function . '-' . OpenSubsonic_Api::API_VERSION, 3);

        return $response;
    }

    /**
     * _createResponse
     *
     * Common answer wrapper.
     * https://opensubsonic.netlify.app/docs/responses/subsonicresponse/
     */
    private static function _createResponse(string $status = 'ok'): SimpleXMLElement
    {
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        $response->addAttribute('xmlns', 'http://subsonic.org/restapi');
        $response->addAttribute('status', $status);
        $response->addAttribute('version', OpenSubsonic_Api::API_VERSION);
        $response->addAttribute('type', 'ampache');
        $response->addAttribute('serverVersion', AmpConfig::get('version'));
        $response->addAttribute('openSubsonic', "1");

        return $response;
    }

    /**
     * _createSuccessResponse
     *
     * https://opensubsonic.netlify.app/docs/responses/subsonicresponse/
     */
    private static function _createSuccessResponse(string $function = ''): SimpleXMLElement
    {
        $response = self::_createResponse();
        debug_event(self::class, 'API success in function ' . $function . '-' . OpenSubsonic_Api::API_VERSION, 5);

        return $response;
    }

    /**
     * _setIfStarred
     */
    private static function _setIfStarred(SimpleXMLElement $xml, string $objectType, int $object_id): void
    {
        if (InterfaceImplementationChecker::is_library_item($objectType)) {
            if (AmpConfig::get('ratings')) {
                $starred = new Userflag($object_id, $objectType);
                $result  = $starred->get_flag(null, true);
                if (is_array($result)) {
                    $xml->addAttribute('starred', date("Y-m-d\TH:i:s\Z", $result[1]));
                }
            }
        }
    }

    /**
     * @deprecated
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
