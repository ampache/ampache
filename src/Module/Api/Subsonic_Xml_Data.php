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
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\User_Playlist;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Preference;
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
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Repository\SongRepositoryInterface;
use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;

/**
 * Subsonic_Xml_Data Class
 *
 * This class takes care of all of the xml document stuff for SubSonic Responses
 * https://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd
 */
class Subsonic_Xml_Data
{
    private AlbumRepositoryInterface $albumRepository;
    private SongRepositoryInterface $songRepository;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository,
    ) {
        $this->albumRepository = $albumRepository;
        $this->songRepository  = $songRepository;
    }

    /**
     * addAlbum
     *
     * An album in the browsing hierarchy, serialized as the `Child` type.
     * `name` and `songCount` belong to `AlbumID3` only, so they are not emitted here.
     */
    public function addAlbum(SimpleXMLElement $xml, Album $album, bool $songs = false, string $elementName = 'album'): void
    {
        if ($album->isNew()) {
            return;
        }

        $sub_id = Subsonic_Api::getAlbumSubId($album->id);
        $xalbum = $this->_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xalbum->addAttribute('id', $sub_id);
        $album_artist = $album->findAlbumArtist();
        if ($album_artist) {
            $xalbum->addAttribute('parent', Subsonic_Api::getArtistSubId($album_artist));
        }
        $f_name = $album->get_fullname();
        $xalbum->addAttribute('album', $f_name);
        $xalbum->addAttribute('title', $f_name);
        $xalbum->addAttribute('isDir', 'true');
        //$xalbum->addAttribute('discNumber', (string)$album->disk);
        if ($album->has_art()) {
            $xalbum->addAttribute('coverArt', $sub_id);
        }
        $xalbum->addAttribute('created', date('c', (int) $album->addition_time));
        $xalbum->addAttribute('duration', (string) $album->time);
        $xalbum->addAttribute('playCount', (string) $album->total_count);
        if ($album_artist) {
            $xalbum->addAttribute('artistId', Subsonic_Api::getArtistSubId($album_artist));
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
        $this->_setIfStarred($xalbum, 'album', $album->id);

        if ($songs) {
            $media_ids = $this->albumRepository->getSongs($album->id);
            foreach ($media_ids as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xalbum, $song);
            }
        }
    }

    /**
     * addAlbumID3
     *
     * An album from ID3 tags, serialized as the `AlbumID3` type.
     * `parent`, `album`, `title` and `isDir` belong to `Child` only, so they are not emitted here.
     */
    public function addAlbumID3(SimpleXMLElement $xml, Album $album, bool $songs = false, string $elementName = 'album'): SimpleXMLElement
    {
        if ($album->isNew()) {
            return $xml;
        }

        $sub_id = Subsonic_Api::getAlbumSubId($album->id);
        $xalbum = $this->_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xalbum->addAttribute('id', $sub_id);
        $album_artist = $album->findAlbumArtist();
        $f_name       = $album->get_fullname();
        $xalbum->addAttribute('name', $f_name);
        if ($album->has_art()) {
            $xalbum->addAttribute('coverArt', $sub_id);
        }
        $xalbum->addAttribute('songCount', (string) $album->song_count);
        $xalbum->addAttribute('created', date('c', (int) $album->addition_time));
        $xalbum->addAttribute('duration', (string) $album->time);
        $xalbum->addAttribute('playCount', (string) $album->total_count);
        if ($album_artist) {
            $xalbum->addAttribute('artistId', Subsonic_Api::getArtistSubId($album_artist));
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
        }

        // `userRating` and `averageRating` are not part of the 1.16.1 `AlbumID3` type.
        $this->_setIfStarred($xalbum, 'album', $album->id);

        if ($songs) {
            $media_ids = $this->albumRepository->getSongs($album->id);
            foreach ($media_ids as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xalbum, $song);
            }
        }

        return $xml;
    }

    /**
     * addAlbumInfo
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
    public function addAlbumInfo(SimpleXMLElement $xml, array $info, Album $album): SimpleXMLElement
    {
        $xartist = $this->_addChildToResultXml($xml, htmlspecialchars('albumInfo'));
        $xartist->addChild('notes', htmlspecialchars(trim((string) $info['summary'])));
        $xartist->addChild('musicBrainzId', $album->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities((string) $info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities((string) $info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities((string) $info['largephoto']));

        return $xml;
    }

    /**
     * addAlbumListSubsoni
     * @param int[] $albums
     */
    public function addAlbumList(SimpleXMLElement $xml, array $albums): SimpleXMLElement
    {
        $xlist = $this->_addChildToResultXml($xml, htmlspecialchars('albumList'));
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            // `AlbumList` holds `Child` albums; `AlbumList2` is the ID3 variant.
            $this->addAlbum($xlist, $album);
        }

        return $xml;
    }

    /**
     * addAlbumList2
     * @param int[] $albums
     */
    public function addAlbumList2(SimpleXMLElement $xml, array $albums): SimpleXMLElement
    {
        $xlist = $this->_addChildToResultXml($xml, htmlspecialchars('albumList2'));
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $this->addAlbumID3($xlist, $album);
        }

        return $xml;
    }

    /**
     * addArtist
     */
    public function addArtist(SimpleXMLElement $xml, Artist $artist): SimpleXMLElement
    {
        if ($artist->isNew()) {
            return $xml;
        }

        $sub_id  = Subsonic_Api::getArtistSubId($artist->id);
        $xartist = $this->_addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string) $artist->get_fullname());
        // `coverArt` and `albumCount` belong to `ArtistID3` only, see $this->addArtistID3()
        $this->_setIfStarred($xartist, 'artist', $artist->id);

        return $xml;
    }

    /**
     * addArtistID3
     *
     * An artist from ID3 tags, serialized as the `ArtistID3` type.
     */
    public function addArtistID3(SimpleXMLElement $xml, Artist $artist, bool $albums = false): SimpleXMLElement
    {
        if ($artist->isNew()) {
            return $xml;
        }

        $sub_id  = Subsonic_Api::getArtistSubId($artist->id);
        $xartist = $this->_addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', (string) $artist->get_fullname());

        if ($artist->has_art()) {
            $xartist->addAttribute('coverArt', $sub_id);
        }

        $xartist->addAttribute('albumCount', (string) $artist->album_count);

        $this->_setIfStarred($xartist, 'artist', $artist->id);
        if ($albums) {
            $allalbums = $this->albumRepository->getAlbumByArtist($artist->id);
            foreach ($allalbums as $album_id) {
                $album = new Album($album_id);
                $this->addAlbumID3($xartist, $album);
            }
        }

        return $xml;
    }

    /**
     * addArtistInfo
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
    public function addArtistInfo(SimpleXMLElement $xml, array $info, Artist $artist, array $similars, string $elementName = 'artistInfo'): SimpleXMLElement
    {
        $xartist   = $this->_addChildToResultXml($xml, htmlspecialchars($elementName));
        $biography = trim((string) $info['summary']);
        if (!empty($biography)) {
            $xartist->addChild('biography', htmlspecialchars($biography));
        }
        $xartist->addChild('musicBrainzId', (string) $artist->mbid);
        //$xartist->addChild('lastFmUrl', "");
        $xartist->addChild('smallImageUrl', htmlentities((string) $info['smallphoto']));
        $xartist->addChild('mediumImageUrl', htmlentities((string) $info['mediumphoto']));
        $xartist->addChild('largeImageUrl', htmlentities((string) $info['largephoto']));

        foreach ($similars as $similar) {
            $xsimilar = $this->_addChildToResultXml($xartist, 'similarArtist');
            $xsimilar->addAttribute('id', (($similar['id'] !== null) ? Subsonic_Api::getArtistSubId($similar['id']) : "-1"));
            $xsimilar->addAttribute('name', (string) $similar['name']);
        }

        return $xml;
    }

    /**
     * addArtistInfo2
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
    public function addArtistInfo2(SimpleXMLElement $xml, array $info, Artist $artist, array $similars): SimpleXMLElement
    {
        return $this->addArtistInfo($xml, $info, $artist, $similars, 'artistInfo2');
    }

    /**
     * addArtists
     * @param array<int, array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    public function addArtists(SimpleXMLElement $xml, array $artists): SimpleXMLElement
    {
        $xartists = $this->_addChildToResultXml($xml, 'artists');
        $this->_addIgnoredArticles($xartists);
        $this->_addIndex($xartists, $artists);

        return $xml;
    }

    /**
     * addBookmarks
     * @param list<Bookmark> $bookmarks
     */
    public function addBookmarks(SimpleXMLElement $xml, array $bookmarks): SimpleXMLElement
    {
        $xbookmarks = $this->_addChildToResultXml($xml, 'bookmarks');
        foreach ($bookmarks as $bookmark) {
            $this->_addBookmark($xbookmarks, $bookmark);
        }

        return $xml;
    }

    /**
     * addChatMessages
     * @param int[] $messages
     */
    public function addChatMessages(SimpleXMLElement $xml, array $messages): SimpleXMLElement
    {
        $xmessages = $this->_addChildToResultXml($xml, 'chatMessages');
        if (empty($messages)) {
            return $xml;
        }

        foreach ($messages as $message) {
            $chat = new PrivateMsg($message);
            $this->_addMessage($xmessages, $chat);
        }

        return $xml;
    }

    /**
     * addDirectory will create the directory element based on the type
     */
    public function addDirectory(SimpleXMLElement $xml, Artist|Album|Catalog $object): SimpleXMLElement
    {
        if ($object instanceof Artist) {
            $this->_addDirectory_Artist($xml, $object);
        } elseif ($object instanceof Album) {
            $this->_addDirectory_Album($xml, $object);
        } elseif ($object instanceof Catalog) {
            $this->_addDirectory_Catalog($xml, $object);
        }

        return $xml;
    }

    /**
     * addError
     * Add a failed subsonic-response with error information.
     */
    public function addError(int $code, string $function): SimpleXMLElement
    {
        $xml  = $this->_createFailedResponse($function);
        $xerr = $this->_addChildToResultXml($xml, 'error');
        $xerr->addAttribute('code', (string) $code);

        $message = "Error creating response.";
        switch ($code) {
            case Subsonic_Api::SSERROR_MISSINGPARAM:
                $message = "Required parameter is missing.";
                break;
            case Subsonic_Api::SSERROR_APIVERSION_CLIENT:
                $message = "Incompatible Subsonic REST protocol version. Client must upgrade.";
                break;
            case Subsonic_Api::SSERROR_APIVERSION_SERVER:
                $message = "Incompatible Subsonic REST protocol version. Server must upgrade.";
                break;
            case Subsonic_Api::SSERROR_BADAUTH:
                $message = "Wrong username or password.";
                break;
            case Subsonic_Api::SSERROR_TOKENAUTHNOTSUPPORTED:
                $message = "Token authentication not supported.";
                break;
            case Subsonic_Api::SSERROR_UNAUTHORIZED:
                $message = "User is not authorized for the given operation.";
                break;
            case Subsonic_Api::SSERROR_TRIAL:
                $message = "The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.";
                break;
            case Subsonic_Api::SSERROR_DATA_NOTFOUND:
                $message = "The requested data was not found.";
                break;
        }
        $xerr->addAttribute('message', $message);

        return $xml;
    }

    /**
     * addGenres
     * @param array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags
     */
    public function addGenres(SimpleXMLElement $xml, array $tags): SimpleXMLElement
    {
        $xgenres = $this->_addChildToResultXml($xml, 'genres');

        foreach ($tags as $tag) {
            $otag   = new Tag($tag['id']);
            $xgenre = $this->_addChildToResultXml($xgenres, 'genre', htmlspecialchars((string) $otag->name));
            $xgenre->addAttribute('songCount', (string) ($otag->song));
            $xgenre->addAttribute('albumCount', (string) ($otag->album));
        }

        return $xml;
    }

    /**
     * addIndexes
     * @param array<int, array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    public function addIndexes(SimpleXMLElement $xml, array $artists, ?int $lastModified = 0): SimpleXMLElement
    {
        $xindexes = $this->_addChildToResultXml($xml, 'indexes');
        $xindexes->addAttribute('lastModified', number_format($lastModified * 1000, 0, '.', ''));
        $this->_addIgnoredArticles($xindexes);
        // `Indexes` holds the plain `Artist` type; `ArtistsID3` (addArtists) is the ID3 variant.
        $this->_addIndex($xindexes, $artists, false);

        return $xml;
    }

    /**
     * addInternetRadioStations
     * @param int[] $radios
     */
    public function addInternetRadioStations(SimpleXMLElement $xml, array $radios): SimpleXMLElement
    {
        $xradios = $this->_addChildToResultXml($xml, 'internetRadioStations');
        foreach ($radios as $radio_id) {
            $radio = new Live_Stream($radio_id);
            $this->_addInternetRadioStation($xradios, $radio);
        }

        return $xml;
    }

    /**
     * addJukeboxPlaylist
     */
    public function addJukeboxPlaylist(SimpleXMLElement $xml, LocalPlay $localplay): SimpleXMLElement
    {
        $xjbox  = $this->addJukeboxStatus($xml, $localplay, 'jukeboxPlaylist');
        $tracks = $localplay->get();
        foreach ($tracks as $track) {
            if (array_key_exists('oid', $track)) {
                $song = new Song((int) $track['oid']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xjbox, $song, 'entry');
            }
            // TODO This can be random play, democratic, podcasts, etc. not just songs
        }

        return $xml;
    }

    /**
     * addJukeboxStatus
     */
    public function addJukeboxStatus(SimpleXMLElement $xml, LocalPlay $localplay, string $elementName = 'jukeboxStatus'): SimpleXMLElement
    {
        $xjbox  = $this->_addChildToResultXml($xml, htmlspecialchars($elementName));
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
     */
    public function addLicense(SimpleXMLElement $xml): SimpleXMLElement
    {
        $xlic = $this->_addChildToResultXml($xml, 'license');
        $xlic->addAttribute('valid', 'true');
        $xlic->addAttribute('email', 'webmaster@ampache.org');

        return $xml;
    }

    /**
     * addLyrics
     */
    public function addLyrics(SimpleXMLElement $xml, string $artist, string $title, Song $song): SimpleXMLElement
    {
        if ($song->isNew() || !$song->enabled) {
            return $xml;
        }

        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text']) {
            $text    = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text    = preg_replace('/\\n\\n/i', "\n", (string) $text);
            $text    = str_replace("\r", '', (string) $text);
            $xlyrics = $this->_addChildToResultXml($xml, 'lyrics', html_entity_decode($text));
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
     * addLyricsListSubsoni
     */
    public function addLyricsList(SimpleXMLElement $xml, Song $song): SimpleXMLElement
    {
        if ($song->isNew() || !$song->enabled) {
            return $xml;
        }

        $xlist  = $this->_addChildToResultXml($xml, 'lyricsList');
        $lyrics = $song->get_lyrics();

        if (!empty($lyrics) && $lyrics['text']) {
            $xlyrics = $this->_addChildToResultXml($xlist, 'structuredLyrics');
            $xlyrics->addAttribute('displayArtist', $song->get_parent_fullname());
            $xlyrics->addAttribute('displayTitle', (string) $song->title);
            $xlyrics->addAttribute('lang', 'xxx');

            $text = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $lyrics['text']);
            $text = preg_replace('/\\n\\n/i', "\n", (string) $text);
            $text = str_replace("\r", '', (string) $text);

            $synced = [];
            $lines  = [];
            foreach (explode("\n", html_entity_decode($text)) as $line) {
                if (!empty($line)) {
                    if (preg_match('/^\[(\d{2}):(\d{2})\.(\d{2})\]\s*(.*)$/', $line, $matches)) {
                        $minutes      = (int) $matches[1];
                        $seconds      = (int) $matches[2];
                        $centiseconds = (int) $matches[3];
                        $milliseconds = ($minutes * 60 * 1000) + ($seconds * 1000) + ($centiseconds * 10);

                        // Lyrics text
                        $lyricLine = trim($matches[4]);
                        $synced[]  = [
                            'start' => (string) $milliseconds,
                            'value' => $lyricLine,
                        ];
                    } else {
                        $lines[] = ['value' => $line];
                    }
                }
            }

            if ($synced !== []) {
                $xlyrics->addAttribute('synced', 'true');
                foreach ($synced as $line) {
                    $xline = $this->_addChildToResultXml($xlyrics, 'line');
                    $xline->addAttribute('start', $line['start']);
                    $xline->addAttribute('value', $line['value']);
                }
            } elseif ($lines !== []) {
                $xlyrics->addAttribute('synced', 'false');
                foreach ($lines as $line) {
                    $xline = $this->_addChildToResultXml($xlyrics, 'line');
                    $xline->addAttribute('value', $line['value']);
                }
            }
        }

        return $xml;
    }

    /**
     * addMusicFolders
     * @param int[] $catalogs
     */
    public function addMusicFolders(SimpleXMLElement $xml, array $catalogs): SimpleXMLElement
    {
        $xfolders = $this->_addChildToResultXml($xml, 'musicFolders');
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }
            $xfolder = $this->_addChildToResultXml($xfolders, 'musicFolder');
            $xfolder->addAttribute('id', Subsonic_Api::getCatalogSubId($catalog_id));
            $xfolder->addAttribute('name', (string) $catalog->name);
        }

        return $xml;
    }

    /**
     * addNewestPodcasts
     * @param Podcast_Episode[] $episodes
     */
    public function addNewestPodcasts(SimpleXMLElement $xml, array $episodes): SimpleXMLElement
    {
        $xpodcasts = $this->_addChildToResultXml($xml, 'newestPodcasts');
        foreach ($episodes as $episode) {
            $this->_addPodcastEpisode($xpodcasts, $episode);
        }

        return $xml;
    }

    /**
     * addNowPlaying
     * @param array<int, array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }> $data
     */
    public function addNowPlaying(SimpleXMLElement $xml, array $data): SimpleXMLElement
    {
        $xplaynow = $this->_addChildToResultXml($xml, 'nowPlaying');
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

                $this->addSong($xplaynow, $row['media'], 'entry', $attributes);
            }
        }

        return $xml;
    }

    /**
     * addPlaylistSubsoniSubsoni
     */
    public function addPlaylist(SimpleXMLElement $xml, Playlist|Search $playlist, bool $songs = false): SimpleXMLElement
    {
        if ($playlist instanceof Playlist && $playlist->isNew() === false) {
            $xml = $this->_addPlaylist_Playlist($xml, $playlist, $songs);
        }
        if ($playlist instanceof Search && $playlist->isNew() === false) {
            $xml = $this->_addPlaylist_Search($xml, $playlist, $songs);
        }

        return $xml;
    }

    /**
     * addPlaylists
     * return playlists object with nested playlist itemsSubsoniSubsoni
     * @param int[]|string[] $playlists
     */
    public function addPlaylists(SimpleXMLElement $xml, User $user, array $playlists): SimpleXMLElement
    {
        $xplaylists = $this->_addChildToResultXml($xml, 'playlists');
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

            $this->addPlaylist($xplaylists, $playlist);
        }

        return $xml;
    }

    /**
     * addPlayQueue
     * current="133" position="45000" username="admin" changed="2015-02-18T15:22:22.825Z" changedBy="android"
     */
    public function addPlayQueue(SimpleXMLElement $xml, User_Playlist $playQueue, string $username): SimpleXMLElement
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
            $xplayqueue = $this->_addChildToResultXml($xml, 'playQueue');
            if (!empty($current)) {
                $xplayqueue->addAttribute('current', Subsonic_Api::getSongSubId($current['object_id']));
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
                $this->addSong($xplayqueue, $song, 'entry');
            }
        }

        return $xml;
    }

    /**
     * addPlayQueueByIndex
     * currentIndex="133" position="45000" username="admin" changed="2015-02-18T15:22:22.825Z" changedBy="android"
     */
    public function addPlayQueueByIndex(SimpleXMLElement $xml, User_Playlist $playQueue, string $username): SimpleXMLElement
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
            $xplayqueue = $this->_addChildToResultXml($xml, 'playQueueByIndex');
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
                $this->addSong($xplayqueue, $song, 'entry');
            }
        }

        return $xml;
    }

    /**
     * addPodcastEpisode
     */
    public function addPodcastEpisode(SimpleXMLElement $xml, Podcast_Episode $episode): SimpleXMLElement
    {
        $xepisode = $this->_addChildToResultXml($xml, 'podcastEpisode');
        $this->_addPodcastEpisode($xepisode, $episode);

        return $xml;
    }

    /**
     * addPodcasts
     * @param Podcast[] $podcasts
     */
    public function addPodcasts(SimpleXMLElement $xml, array $podcasts, bool $includeEpisodes = true, ?string $sub_id = null): SimpleXMLElement
    {
        $xpodcasts = $this->_addChildToResultXml($xml, 'podcasts');
        foreach ($podcasts as $podcast) {
            $sub_id = (!empty($sub_id))
                ? $sub_id
                : Subsonic_Api::getPodcastSubId($podcast->getId());
            $xchannel = $this->_addChildToResultXml($xpodcasts, 'channel');
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
                    $this->_addPodcastEpisode($xchannel, $episode);
                }
            }
        }

        return $xml;
    }

    /**
     * addRandomSongs
     * @param int[] $songs
     */
    public function addRandomSongs(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = $this->_addChildToResultXml($xml, 'randomSongs');
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xsongs, $song);
        }

        return $xml;
    }

    /**
     * addResponse
     *
     * Generate a subsonic-response
     */
    public function addResponse(string $function): SimpleXMLElement
    {
        return $this->_createSuccessResponse($function);
    }

    /**
     * addScanStatus
     */
    public function addScanStatus(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $counts = Catalog::get_server_counts($user->id);
        $count  = $counts['artist'] + $counts['album'] + $counts['song'] + $counts['podcast_episode'];
        $xscan  = $this->_addChildToResultXml($xml, htmlspecialchars('scanStatus'));
        $xscan->addAttribute('scanning', "false");
        $xscan->addAttribute('count', (string) $count);

        return $xml;
    }

    /**
     * addSearchResult
     * @param int[] $songs
     */
    public function addSearchResult(SimpleXMLElement $xml, array $songs, int $offset, int $total): SimpleXMLElement
    {
        $xresult = $this->_addChildToResultXml($xml, htmlspecialchars('searchResult'));
        $xresult->addAttribute('offset', (string) $offset);
        $xresult->addAttribute('totalHits', (string) $total);
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xresult, $song, 'match');
        }

        return $xml;
    }

    /**
     * addSearchResult2
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public function addSearchResult2(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xresult = $this->_addChildToResultXml($xml, htmlspecialchars('searchResult2'));
        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $this->addArtist($xresult, $artist);
        }
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $this->addAlbum($xresult, $album);
        }
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xresult, $song);
        }

        return $xml;
    }

    /**
     * addSearchResult3
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public function addSearchResult3(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xresult = $this->_addChildToResultXml($xml, htmlspecialchars('searchResult3'));
        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $this->addArtistID3($xresult, $artist);
        }
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $this->addAlbumID3($xresult, $album);
        }
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xresult, $song);
        }

        return $xml;
    }

    /**
     * addShares
     * @param int[] $shares
     */
    public function addShares(SimpleXMLElement $xml, array $shares): SimpleXMLElement
    {
        $xshares = $this->_addChildToResultXml($xml, 'shares');
        foreach ($shares as $share_id) {
            $share = new Share($share_id);
            // Don't add share with max counter already reached
            if ($share->max_counter === 0 || $share->counter < $share->max_counter) {
                $this->_addShare($xshares, $share);
            }
        }

        return $xml;
    }

    /**
     * addSimilarSongs
     * @param array<int, array{
     *     id: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string,
     * }> $similar_songs
     */
    public function addSimilarSongs(SimpleXMLElement $xml, array $similar_songs): SimpleXMLElement
    {
        $xsimilar = $this->_addChildToResultXml($xml, 'similarSongs');
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                $song = new Song($similar_song['id']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xsimilar, $song);
            }
        }

        return $xml;
    }

    /**
     * addSimilarSongs2
     * @param array<int, array{
     *     id: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string,
     * }> $similar_songs
     */
    public function addSimilarSongs2(SimpleXMLElement $xml, array $similar_songs): SimpleXMLElement
    {
        $xsimilar = $this->_addChildToResultXml($xml, 'similarSongs2');
        foreach ($similar_songs as $similar_song) {
            if ($similar_song['id'] !== null) {
                $song = new Song($similar_song['id']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xsimilar, $song);
            }
        }

        return $xml;
    }

    /**
     * addSong
     * @param array<string, string> $attributes
     */
    public function addSong(SimpleXMLElement $xml, Song $song, string $elementName = 'song', array $attributes = []): SimpleXMLElement
    {
        $sub_id    = Subsonic_Api::getSongSubId($song->id);
        $subParent = Subsonic_Api::getAlbumSubId($song->album);
        $xsong     = $this->_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xsong->addAttribute('id', $sub_id);
        $xsong->addAttribute('parent', $subParent);
        //$xsong->addAttribute('created', );
        $xsong->addAttribute('title', (string) $song->title);
        $xsong->addAttribute('isDir', 'false');
        $xsong->addAttribute('isVideo', 'false');
        $xsong->addAttribute('type', 'music');
        $xsong->addAttribute('albumId', $subParent);
        $xsong->addAttribute('album', $song->get_album_fullname());
        $xsong->addAttribute('artistId', ($song->artist) ? Subsonic_Api::getArtistSubId($song->artist) : '');
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
        $this->_setIfStarred($xsong, 'song', $song->id);
        if ($song->track > 0) {
            $xsong->addAttribute('track', (string) $song->track);
        }
        if ($song->year > 0) {
            $xsong->addAttribute('year', (string) $song->year);
        }
        $tags = Tag::get_object_tags('song', $song->id);
        if (!empty($tags)) {
            $xsong->addAttribute('genre', implode(',', array_column($tags, 'name')));
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
     * addSongsByGenre
     * @param int[] $songs
     */
    public function addSongsByGenre(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = $this->_addChildToResultXml($xml, 'songsByGenre');
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xsongs, $song);
        }

        return $xml;
    }

    /**
     * addStarred
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public function addStarred(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xstarred = $this->_addChildToResultXml($xml, htmlspecialchars('starred'));

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $this->addArtist($xstarred, $artist);
        }

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            // `Starred` holds `Child` albums; `Starred2` is the ID3 variant.
            $this->addAlbum($xstarred, $album);
        }

        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xstarred, $song);
        }

        return $xml;
    }

    /**
     * addStarred2
     * @param int[] $artists
     * @param int[] $albums
     * @param int[] $songs
     */
    public function addStarred2(SimpleXMLElement $xml, array $artists, array $albums, array $songs): SimpleXMLElement
    {
        $xstarred = $this->_addChildToResultXml($xml, htmlspecialchars('starred2'));

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $this->addArtistID3($xstarred, $artist);
        }

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $this->addAlbumID3($xstarred, $album);
        }

        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xstarred, $song);
        }

        return $xml;
    }

    /**
     * addTokenInfo
     */
    public function addTokenInfo(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $xscan = $this->_addChildToResultXml($xml, htmlspecialchars('tokenInfo'));
        $xscan->addAttribute('username', (string) $user->username);

        return $xml;
    }

    /**
     * addTopSongs
     * @param int[] $songs
     */
    public function addTopSongs(SimpleXMLElement $xml, array $songs): SimpleXMLElement
    {
        $xsongs = $this->_addChildToResultXml($xml, 'topSongs');
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xsongs, $song);
        }

        return $xml;
    }

    /**
     * addUser
     */
    public function addUser(SimpleXMLElement $xml, User $user): SimpleXMLElement
    {
        $xuser = $this->_addChildToResultXml($xml, 'user');
        $xuser->addAttribute('username', (string) $user->username);
        $xuser->addAttribute('email', (string) $user->email);
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
     * @param int[] $users
     */
    public function addUsers(SimpleXMLElement $xml, array $users): SimpleXMLElement
    {
        $xusers = $this->_addChildToResultXml($xml, 'users');
        foreach ($users as $user_id) {
            $user = new User($user_id);
            if ($user->isNew() === false) {
                $this->addUser($xusers, $user);
            }
        }

        return $xml;
    }

    /**
     * addVideoInfo
     */
    public function addVideoInfo(SimpleXMLElement $xml, int $video_id): SimpleXMLElement
    {
        $xvideoinfo = $this->_addChildToResultXml($xml, 'videoInfo');
        $xvideoinfo->addAttribute('id', Subsonic_Api::getVideoSubId($video_id));

        return $xml;
    }

    /**
     * addVideos
     * @param Video[] $videos
     */
    public function addVideos(SimpleXMLElement $xml, array $videos): SimpleXMLElement
    {
        $xvideos = $this->_addChildToResultXml($xml, 'videos');
        foreach ($videos as $video) {
            $this->_addVideo($xvideos, $video);
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
     *     catalog_id: int,
     *     has_art: int
     * } $artist
     */
    private function _addArtistArray(SimpleXMLElement $xml, array $artist, bool $id3 = true): void
    {
        $sub_id  = Subsonic_Api::getArtistSubId($artist['id']);
        $xartist = $this->_addChildToResultXml($xml, 'artist');
        $xartist->addAttribute('id', $sub_id);
        $xartist->addAttribute('name', $artist['f_name']);
        // `coverArt` and `albumCount` are `ArtistID3` only; a plain `Index` holds the `Artist` type.
        if ($id3) {
            if ($artist['has_art']) {
                $xartist->addAttribute('coverArt', $sub_id);
            }
            $xartist->addAttribute('albumCount', (string) $artist['album_count']);
        }
        $this->_setIfStarred($xartist, 'artist', $artist['id']);
    }

    /**
     * addBookmark
     */
    private function _addBookmark(SimpleXMLElement $xml, Bookmark $bookmark): void
    {
        $xbookmark = $this->_addChildToResultXml($xml, 'bookmark');
        $xbookmark->addAttribute('position', (string) $bookmark->position);
        $xbookmark->addAttribute('username', $bookmark->getUserName());
        $xbookmark->addAttribute('comment', (string) $bookmark->comment);
        $xbookmark->addAttribute('created', date("c", $bookmark->creation_date));
        $xbookmark->addAttribute('changed', date("c", $bookmark->update_date));
        if ($bookmark->object_type == "song") {
            $song = new Song($bookmark->object_id);
            if ($song->isNew() === false && $song->enabled) {
                $this->addSong($xbookmark, $song, 'entry');
            }
        } elseif ($bookmark->object_type == "video") {
            $this->_addVideo($xbookmark, new Video($bookmark->object_id), 'entry');
        } elseif ($bookmark->object_type == "podcast_episode") {
            $this->_addPodcastEpisode($xbookmark, new Podcast_Episode($bookmark->object_id), 'entry');
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
    private function _addChildArray(SimpleXMLElement $xml, array $child): void
    {
        $sub_id = Subsonic_Api::getArtistSubId($child['id']);
        $xchild = $this->_addChildToResultXml($xml, 'child');
        $xchild->addAttribute('id', $sub_id);
        $xchild->addAttribute('parent', Subsonic_Api::getCatalogSubId($child['catalog_id']));
        $xchild->addAttribute('isDir', 'true');
        $xchild->addAttribute('title', $child['f_name']);
        $xchild->addAttribute('artist', $child['f_name']);
        if ($child['has_art']) {
            $xchild->addAttribute('coverArt', $sub_id);
        }
    }

    /**
     * Adds a child to an existing result xml structure
     */
    private function _addChildToResultXml(SimpleXMLElement $xml, string $qualifiedName, ?string $value = null): SimpleXMLElement
    {
        /** @var SimpleXMLElement $child */
        $child = $xml->addChild($qualifiedName, $value);

        return $child;
    }

    /**
     * addDirectory_Album for subsonic album id
     */
    private function _addDirectory_Album(SimpleXMLElement $xml, Album $album): void
    {
        $album_id = $album->id;
        $xdir     = $this->_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', Subsonic_Api::getAlbumSubId($album_id));
        $album_artist = $album->findAlbumArtist();
        if ($album_artist) {
            $xdir->addAttribute('parent', Subsonic_Api::getArtistSubId($album_artist));
        } else {
            $xdir->addAttribute('parent', Subsonic_Api::getCatalogSubId($album->catalog));
        }
        $xdir->addAttribute('name', $album->get_fullname());
        $this->_setIfStarred($xdir, 'album', $album->id);

        $media_ids = $this->albumRepository->getSongs($album->id);
        foreach ($media_ids as $song_id) {
            // TODO addChild || use addChildArray
            $song = new Song($song_id);
            if ($song->isNew() || !$song->enabled) {
                continue;
            }
            $this->addSong($xdir, $song, 'child');
        }
    }

    /**
     * addDirectory_Artist for subsonic artist id
     */
    private function _addDirectory_Artist(SimpleXMLElement $xml, Artist $artist): void
    {
        $artist_id = $artist->id;
        $data      = Artist::get_id_array($artist_id);
        $xdir      = $this->_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', Subsonic_Api::getArtistSubId($artist_id));
        if ($data['catalog_id']) {
            $xdir->addAttribute('parent', Subsonic_Api::getCatalogSubId($data['catalog_id']));
        }
        $xdir->addAttribute('name', (string) $data['f_name']);
        $this->_setIfStarred($xdir, 'artist', $artist_id);
        $allalbums = $this->albumRepository->getAlbumByArtist($artist_id);
        foreach ($allalbums as $album_id) {
            $album = new Album($album_id);
            // TODO addChild || use addChildArray
            $this->addAlbum($xdir, $album, false, 'child');
        }
    }

    /**
     * addDirectory_Catalog for subsonic artist id
     */
    private function _addDirectory_Catalog(SimpleXMLElement $xml, Catalog $catalog): void
    {
        $catalog_id = $catalog->id;
        $xdir       = $this->_addChildToResultXml($xml, 'directory');
        $xdir->addAttribute('id', Subsonic_Api::getCatalogSubId($catalog_id));
        $xdir->addAttribute('name', (string) $catalog->name);
        $allartists = Catalog::get_artist_arrays([$catalog_id]);
        foreach ($allartists as $artist) {
            $this->_addChildArray($xdir, $artist);
        }
    }

    /**
     * addIgnoredArticles
     */
    private function _addIgnoredArticles(SimpleXMLElement $xml): void
    {
        $ignoredArticles = AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La');
        if (!empty($ignoredArticles)) {
            $ignoredArticles = str_replace('|', ' ', $ignoredArticles);
            $xml->addAttribute('ignoredArticles', (string) $ignoredArticles);
        }
    }

    /**
     * addIndex
     * @param array<int, array{
     *     id: int,
     *     f_name: string,
     *     name: string,
     *     album_count: int,
     *     catalog_id: int,
     *     has_art: int
     * }> $artists
     */
    private function _addIndex(SimpleXMLElement $xml, array $artists, bool $id3 = true): void
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
                    $xlastcat    = $this->_addChildToResultXml($xml, 'index');
                    $xlastcat->addAttribute('name', $xlastletter);
                }
            }

            if ($xlastcat != null) {
                $this->_addArtistArray($xlastcat, $artist, $id3);
            }
        }

        // Always add # index at the end
        if (count($sharpartists) > 0) {
            $xsharpcat = $this->_addChildToResultXml($xml, 'index');
            $xsharpcat->addAttribute('name', '#');

            foreach ($sharpartists as $artist) {
                $this->_addArtistArray($xsharpcat, $artist, $id3);
            }
        }
    }

    /**
     * addInternetRadioStation
     */
    private function _addInternetRadioStation(SimpleXMLElement $xml, Live_Stream $radio): void
    {
        $xradio = $this->_addChildToResultXml($xml, 'internetRadioStation');
        $xradio->addAttribute('id', Subsonic_Api::getLiveStreamSubId($radio->id));
        $xradio->addAttribute('name', (string) $radio->name);
        $xradio->addAttribute('streamUrl', (string) $radio->url);
        $xradio->addAttribute('homePageUrl', (string) $radio->site_url);
    }

    /**
     * addMessage
     */
    private function _addMessage(SimpleXMLElement $xml, PrivateMsg $message): void
    {
        $user      = new User($message->getSenderUserId());
        $xbookmark = $this->_addChildToResultXml($xml, 'chatMessage');
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
     */
    private function _addPlaylist_Playlist(SimpleXMLElement $xml, Playlist $playlist, bool $songs = false): SimpleXMLElement
    {
        $sub_id    = Subsonic_Api::getPlaylistSubId($playlist->id);
        $songcount = $playlist->get_media_count('song');
        $duration  = ($songcount > 0) ? $playlist->get_total_duration() : 0;
        $xplaylist = $this->_addChildToResultXml($xml, 'playlist');
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

        if ($songs) {
            $allsongs = $playlist->get_songs();
            foreach ($allsongs as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xplaylist, $song, 'entry');
            }
        }

        return $xml;
    }

    /**
     * addPlaylist_Search
     */
    private function _addPlaylist_Search(SimpleXMLElement $xml, Search $search, bool $songs = false): SimpleXMLElement
    {
        $sub_id    = Subsonic_Api::getSmartPlaylistSubId($search->id);
        $xplaylist = $this->_addChildToResultXml($xml, 'playlist');
        $xplaylist->addAttribute('id', $sub_id);
        $xplaylist->addAttribute('name', (string) $search->get_fullname());
        $xplaylist->addAttribute('owner', (string) $search->username);
        $xplaylist->addAttribute('public', ($search->type != 'private') ? 'true' : 'false');
        $xplaylist->addAttribute('created', date('c', $search->date));
        $xplaylist->addAttribute('changed', date('c', time()));

        if ($songs) {
            $allitems = $search->get_items();
            $xplaylist->addAttribute('songCount', (string) count($allitems));
            $duration = (count($allitems) > 0) ? Search::get_total_duration($allitems) : 0;
            $xplaylist->addAttribute('duration', (string) $duration);
            $xplaylist->addAttribute('coverArt', $sub_id);
            foreach ($allitems as $item) {
                $song = new Song((int) $item['object_id']);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xplaylist, $song, 'entry');
            }
        } else {
            // both are xs:int in the schema, so an unset counter must serialize as 0 rather than ''
            $xplaylist->addAttribute('songCount', (string) ((int) $search->last_count));
            $xplaylist->addAttribute('duration', (string) ((int) $search->last_duration));
            $xplaylist->addAttribute('coverArt', $sub_id);
        }

        return $xml;
    }

    /**
     * addPodcastEpisode
     */
    private function _addPodcastEpisode(SimpleXMLElement $xml, Podcast_Episode $episode, string $elementName = 'episode'): void
    {
        if ($episode->isNew()) {
            return;
        }

        $sub_id    = Subsonic_Api::getPodcastEpisodeSubId($episode->id);
        $subParent = Subsonic_Api::getPodcastSubId($episode->podcast);
        $xepisode  = $this->_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xepisode->addAttribute('id', $sub_id);
        $xepisode->addAttribute('channelId', $subParent);
        $xepisode->addAttribute('title', (string) $episode->get_fullname());
        $xepisode->addAttribute('album', $episode->getPodcastName());
        $xepisode->addAttribute('description', $episode->get_description());
        $xepisode->addAttribute('duration', (string) $episode->time);
        $xepisode->addAttribute('genre', "Podcast");
        $xepisode->addAttribute('isDir', "false");
        $xepisode->addAttribute('publishDate', $episode->getPubDate()->format(DATE_ATOM));
        $xepisode->addAttribute('status', (string) $episode->state);
        $xepisode->addAttribute('parent', $subParent);
        if ($episode->has_art()) {
            $xepisode->addAttribute('coverArt', $subParent);
        }

        $this->_setIfStarred($xepisode, 'podcast_episode', $episode->id);

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
     */
    private function _addShare(SimpleXMLElement $xml, Share $share): void
    {
        $xshare = $this->_addChildToResultXml($xml, 'share');
        $xshare->addAttribute('id', Subsonic_Api::getShareSubId($share->id));
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
                $this->addSong($xshare, $song, 'entry');
            }
        } elseif ($share->object_type == 'playlist') {
            $playlist = new Playlist($share->object_id);
            $songs    = $playlist->get_songs();
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xshare, $song, 'entry');
            }
        } elseif ($share->object_type == 'album') {
            $songs = $this->songRepository->getByAlbum($share->object_id);
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                if ($song->isNew() || !$song->enabled) {
                    continue;
                }
                $this->addSong($xshare, $song, 'entry');
            }
        }
    }

    /**
     * addVideo
     */
    private function _addVideo(SimpleXMLElement $xml, Video $video, string $elementName = 'video'): void
    {
        if ($video->isNew()) {
            return;
        }

        $sub_id = Subsonic_Api::getVideoSubId($video->id);
        $xvideo = $this->_addChildToResultXml($xml, htmlspecialchars($elementName));
        $xvideo->addAttribute('id', $sub_id);
        $xvideo->addAttribute('title', $video->getFileName());
        $xvideo->addAttribute('isDir', 'false');
        if ($video->has_art()) {
            $xvideo->addAttribute('coverArt', $sub_id);
        }
        $xvideo->addAttribute('isVideo', 'true');
        $xvideo->addAttribute('type', 'video');
        $xvideo->addAttribute('duration', (string) $video->time);
        if (isset($video->year) && $video->year > 0) {
            $xvideo->addAttribute('year', (string) $video->year);
        }
        $tags = Tag::get_object_tags('video', $video->id);
        if (!empty($tags)) {
            $xvideo->addAttribute('genre', implode(',', array_column($tags, 'name')));
        }
        $xvideo->addAttribute('size', (string) $video->size);
        $xvideo->addAttribute('suffix', $video->type);
        $xvideo->addAttribute('contentType', (string) $video->mime);
        // Create a clean fake path instead of song real file path to have better offline mode storage on Subsonic clients
        $path = basename($video->file ?? '');
        $xvideo->addAttribute('path', $path);

        $this->_setIfStarred($xvideo, 'video', $video->id);
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
     */
    private function _createFailedResponse(string $function = ''): SimpleXMLElement
    {
        $response = $this->_createResponse('failed');
        debug_event(self::class, 'API fail in function ' . $function . '-' . Subsonic_Api::API_VERSION, 3);

        return $response;
    }

    /**
     * _createResponse
     */
    private function _createResponse(string $status = 'ok'): SimpleXMLElement
    {
        $response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        $response->addAttribute('xmlns', 'http://subsonic.org/restapi');
        $response->addAttribute('status', $status);
        $response->addAttribute('version', Subsonic_Api::API_VERSION);

        return $response;
    }

    /**
     * _createSuccessResponse
     */
    private function _createSuccessResponse(string $function = ''): SimpleXMLElement
    {
        $response = $this->_createResponse();
        debug_event(self::class, 'API success in function ' . $function . '-' . Subsonic_Api::API_VERSION, 5);

        return $response;
    }

    /**
     * _setIfStarred
     */
    private function _setIfStarred(SimpleXMLElement $xml, string $objectType, int $object_id): void
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
}
