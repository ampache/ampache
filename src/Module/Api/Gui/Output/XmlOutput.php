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

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Output;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\Util\XmlWriterInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PlayableMediaInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\UseractivityInterface;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final class XmlOutput implements ApiOutputInterface
{
    // This is added so that we don't pop any webservers
    private const DEFAULT_LIMIT = 5000;

    private ModelFactoryInterface $modelFactory;

    private XmlWriterInterface $xmlWriter;

    private AlbumRepositoryInterface $albumRepository;

    private SongRepositoryInterface $songRepository;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        XmlWriterInterface $xmlWriter,
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->modelFactory             = $modelFactory;
        $this->xmlWriter                = $xmlWriter;
        $this->albumRepository          = $albumRepository;
        $this->songRepository           = $songRepository;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->podcastRepository        = $podcastRepository;
    }

    /**
     * This generates a standard XML Error message
     */
    public function error(int $code, string $message, string $action, string $type): string
    {
        $xml_string = "\t<error errorCode=\"$code\">" .
            "\n\t\t<errorAction><![CDATA[$action]]></errorAction>" .
            "\n\t\t<errorType><![CDATA[$type]]></errorType>" .
            "\n\t\t<errorMessage><![CDATA[$message]]></errorMessage>" .
            "\n\t</error>";

        return $this->xmlWriter->writeXml($xml_string);
    }

    /**
     * This echos out a standard albums XML document, it pays attention to the limit
     *
     * @param int[] $albumIds Album id's to include
     * @param array $include Array of other items to include.
     * @param int|null $userId
     * @param bool $encode whether to return a full XML document or just the node.
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function albums(
        array $albumIds,
        array $include = [],
        ?int $userId = null,
        bool $encode = true,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ) {
        if ($include == null || $include == '') {
            $include = array();
        }

        if ((count($albumIds) > $limit || $offset > 0) && ($limit && $encode)) {
            $albumIds = array_splice($albumIds, $offset, $limit);
        }
        $string = ($encode) ? "<total_count>" . Catalog::get_count('album') . "</total_count>\n" : '';

        foreach ($albumIds as $albumId) {
            $album = new Album($albumId);
            $album->format();

            $disk   = $album->disk;
            $rating = new Rating($albumId, 'album');
            $flag   = new Userflag($albumId, 'album');

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $album->id . '&object_type=album&auth=' . scrub_out(Core::get_request('auth'));

            $string .= "<album id=\"" . $album->id . "\">\n" . "\t<name><![CDATA[" . $album->full_name . "]]></name>\n";

            // Do a little check for artist stuff
            if ($album->album_artist_name != "") {
                $string .= "\t<artist id=\"$album->artist_id\"><![CDATA[$album->album_artist_name]]></artist>\n";
            } elseif ($album->artist_count != 1) {
                $string .= "\t<artist id=\"0\"><![CDATA[Various]]></artist>\n";
            } else {
                $string .= "\t<artist id=\"$album->artist_id\"><![CDATA[$album->artist_name]]></artist>\n";
            }

            // Handle includes
            $songs = (in_array("songs", $include))
                ? $this->songs($this->songRepository->getByAlbum($album->id), $userId, false)
                : '';

            // count multiple disks
            if ($album->allow_group_disks) {
                $disk = (count($album->album_suite) <= 1) ? $album->disk : count($album->album_suite);
            }

            $string .= "\t<time>" . $album->total_duration . "</time>\n" .
                "\t<year>" . $album->year . "</year>\n" .
                "\t<tracks>" . $songs . "</tracks>\n" .
                "\t<songcount>" . $album->song_count . "</songcount>\n" .
                "\t<diskcount>" . $disk . "</diskcount>\n" .
                "\t<type>" . $album->release_type . "</type>\n" .
                $this->genre_string($album->tags) .
                "\t<art><![CDATA[$art_url]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($userId, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($userId) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($userId) ?: null) . "</rating>\n" .
                "\t<averagerating>" . ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "\t<mbid><![CDATA[" . $album->mbid . "]]></mbid>\n" .
                "</album>\n";
        }

        return $this->xmlWriter->writeXml($string, '', null, $encode);
    }

    public function emptyResult(string $type): string
    {
        return "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n<root>\n</root>\n";
    }

    /**
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param int[] $artistIds
     * @param array $include
     * @param null|int $userId
     * @param boolean $encode
     * @param boolean $asObject (whether to return as a named object array or regular array)
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function artists(
        array $artistIds,
        array $include = [],
        ?int $userId = null,
        bool $encode = true,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ) {
        $artistIds = $this->applyLimit($artistIds, $limit, $offset);

        $string = '';

        foreach ($artistIds as $artistId) {
            $artist = $this->modelFactory->createArtist($artist_id);
            $artist->format();

            $rating     = new Rating($artistId, 'artist');
            $flag       = new Userflag($artistId, 'artist');
            $tag_string = $this->genre_string($artist->tags);

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $artistId . '&object_type=artist&auth=' . scrub_out(Core::get_request('auth'));

            // Handle includes
            $albums = (in_array("albums", $include))
                ? $this->albums($this->albumRepository->getByArtist((int) $artistId), array(), $userId, false)
                : '';
            $songs = (in_array("songs", $include))
                ? $this->songs($this->songRepository->getByArtist((int) $artistId), $userId, false)
                : '';

            $string .= "<artist id=\"" . $artist->id . "\">\n" .
                "\t<name><![CDATA[" . $artist->f_full_name . "]]></name>\n" .
                $tag_string .
                "\t<albums>" . $albums . "</albums>\n" .
                "\t<albumcount>" . ($artist->albums ?: 0) . "</albumcount>\n" .
                "\t<songs>" . $songs . "</songs>\n" .
                "\t<songcount>" . ($artist->songs ?: 0) . "</songcount>\n" .
                "\t<art><![CDATA[$art_url]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($userId, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($userId) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($userId) ?: null) . "</rating>\n" .
                "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "\t<mbid><![CDATA[" . $artist->mbid . "]]></mbid>\n" .
                "\t<summary><![CDATA[" . $artist->summary . "]]></summary>\n" .
                "\t<time><![CDATA[" . $artist->time . "]]></time>\n" .
                "\t<yearformed>" . (int) $artist->yearformed . "</yearformed>\n" .
                "\t<placeformed><![CDATA[" . $artist->placeformed . "]]></placeformed>\n" .
                "</artist>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This returns an xml document from an array of song ids.
     *
     * @param int[] $songIds
     * @param int|null $userId
     * @param boolean $encode
     * @param boolean $asObject (whether to return as a named object array or regular array)
     * @param boolean $fullXml
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function songs(
        array $songIds,
        ?int $userId = null,
        bool $encode = true,
        bool $asObject = true,
        bool $fullXml = true,
        int $limit = 0,
        int $offset = 0
    ) {
        if ((count($songIds) > $limit || $offset > 0) && ($limit && $fullXml)) {
            $songIds = array_slice($songIds, $offset, $limit);
        }
        $string = ($fullXml) ? "<total_count>" . Catalog::get_count('song') . "</total_count>\n" : '';

        Stream::set_session(Core::get_request('auth'));

        $playlist_track = 0;

        // Foreach the ids!
        foreach ($songIds as $songId) {
            $song = new Song($songId);

            // If the song id is invalid/null
            if (!$song->id) {
                continue;
            }

            $song->format();
            $tag_string    = $this->genre_string(Tag::get_top_tags('song', $songId));
            $rating        = new Rating($songId, 'song');
            $flag          = new Userflag($songId, 'song');
            $show_song_art = AmpConfig::get('show_song_art', false);
            $art_object    = ($show_song_art) ? $song->id : $song->album;
            $art_type      = ($show_song_art) ? 'song' : 'album';
            $art_url       = Art::url($art_object, $art_type, Core::get_request('auth'));
            $playlist_track++;

            $string .= "<song id=\"" . $song->id . "\">\n" .
                // Title is an alias for name
                "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                "\t<name><![CDATA[" . $song->title . "]]></name>\n" .
                "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_name() . "]]></artist>\n" .
                "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_name() . "]]></album>\n" .
                "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_name() . "]]></albumartist>\n" .
                "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n" .
                "\t<track>" . $song->track . "</track>\n";
            $string .= $tag_string .
                "\t<filename><![CDATA[" . $song->file . "]]></filename>\n" .
                "\t<playlisttrack>" . $playlist_track . "</playlisttrack>\n" .
                "\t<time>" . $song->time . "</time>\n" .
                "\t<year>" . $song->year . "</year>\n" .
                "\t<bitrate>" . $song->bitrate . "</bitrate>\n" .
                "\t<rate>" . $song->rate . "</rate>\n" .
                "\t<mode><![CDATA[" . $song->mode . "]]></mode>\n" .
                "\t<mime><![CDATA[" . $song->mime . "]]></mime>\n" .
                "\t<url><![CDATA[" . $song->play_url('', 'api', false, $userId) . "]]></url>\n" .
                "\t<size>" . $song->size . "</size>\n" .
                "\t<mbid><![CDATA[" . $song->mbid . "]]></mbid>\n" .
                "\t<album_mbid><![CDATA[" . $song->album_mbid . "]]></album_mbid>\n" .
                "\t<artist_mbid><![CDATA[" . $song->artist_mbid . "]]></artist_mbid>\n" .
                "\t<albumartist_mbid><![CDATA[" . $song->albumartist_mbid . "]]></albumartist_mbid>\n" .
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($userId, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($userId) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($userId) ?: null) . "</rating>\n" .
                "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "\t<playcount>" . $song->played . "</playcount>\n" .
                "\t<catalog>" . $song->getCatalogId() . "</catalog>\n" .
                "\t<composer><![CDATA[" . $song->composer . "]]></composer>\n" .
                "\t<channels>" . $song->channels . "</channels>\n" .
                "\t<comment><![CDATA[" . $song->comment . "]]></comment>\n" .
                "\t<license><![CDATA[" . $song->f_license . "]]></license>\n" .
                "\t<publisher><![CDATA[" . $song->label . "]]></publisher>\n" .
                "\t<language>" . $song->language . "</language>\n" .
                "\t<replaygain_album_gain>" . $song->replaygain_album_gain . "</replaygain_album_gain>\n" .
                "\t<replaygain_album_peak>" . $song->replaygain_album_peak . "</replaygain_album_peak>\n" .
                "\t<replaygain_track_gain>" . $song->replaygain_track_gain . "</replaygain_track_gain>\n" .
                "\t<replaygain_track_peak>" . $song->replaygain_track_peak . "</replaygain_track_peak>\n" .
                "\t<r128_album_gain>" . $song->r128_album_gain . "</r128_album_gain>\n" .
                "\t<r128_track_gain>" . $song->r128_track_gain . "</r128_track_gain>\n";
            if (Song::isCustomMetadataEnabled()) {
                foreach ($song->getMetadata() as $metadata) {
                    $meta_name = str_replace(array(' ', '(', ')', '/', '\\', '#'), '_',
                        $metadata->getField()->getName());
                    $string .= "\t<" . $meta_name . "><![CDATA[" . $metadata->getData() . "]]></" . $meta_name . ">\n";
                }
            }

            $string .= "</song>\n";
        }

        return $this->xmlWriter->writeXml($string, '', null, $fullXml);
    }

    /**
     * This handles creating an xml document for an user list
     *
     * @param int[] $users User identifier list
     */
    public function users(array $users): string
    {
        $string = "";
        foreach ($users as $userId) {
            $user = $this->modelFactory->createUser($userId);
            $string .= "<user id=\"" . (string)$user->id . "\">\n" . "\t<username><![CDATA[" . $user->username . "]]></username>\n" . "</user>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This handles creating an xml document for a shout list
     *
     * @param int[] $shoutIds Shout identifier list
     */
    public function shouts(array $shoutIds): string
    {
        $result = '';
        foreach ($shoutIds as $shoutId) {
            $shout = $this->modelFactory->createShoutbox($shoutId);
            $user  = $this->modelFactory->createUser($shout->getUserId());

            $result .= "\t<shout id=\"" . $shoutId . "\">\n" . "\t\t<date>" . $shout->getDate() . "</date>\n" . "\t\t<text><![CDATA[" . $shout->getText() . "]]></text>\n";
            if ($user->id) {
                $result .= "\t\t<user id=\"" . (string)$user->id . "\">\n" . "\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n" . "\t\t</user>\n";
            }
            $result .= "\t</shout>\n";
        }

        return $this->xmlWriter->writeXml($result);
    }

    /**
     * This handles creating an xml document for a user
     */
    public function user(User $user, bool $fullinfo): string
    {
        $user->format();
        $string = "<user id=\"" . (string)$user->id . "\">\n" . "\t<username><![CDATA[" . $user->username . "]]></username>\n";
        if ($fullinfo) {
            $string .= "\t<auth><![CDATA[" . $user->apikey . "]]></auth>\n" .
                "\t<email><![CDATA[" . $user->email . "]]></email>\n" .
                "\t<access>" . (int) $user->access . "</access>\n" .
                "\t<fullname_public>" . (int) $user->fullname_public . "</fullname_public>\n" .
                "\t<validation><![CDATA[" . $user->validation . "]]></validation>\n" .
                "\t<disabled>" . (int) $user->disabled . "</disabled>\n";
        }
        $string .= "\t<create_date>" . (int) $user->create_date . "</create_date>\n" .
            "\t<last_seen>" . (int) $user->last_seen . "</last_seen>\n" .
            "\t<link><![CDATA[" . $user->link . "]]></link>\n" .
            "\t<website><![CDATA[" . $user->website . "]]></website>\n" .
            "\t<state><![CDATA[" . $user->state . "]]></state>\n" .
            "\t<city><![CDATA[" . $user->city . "]]></city>\n";
        if ($user->fullname_public || $fullinfo) {
            $string .= "\t<fullname><![CDATA[" . $user->fullname . "]]></fullname>\n";
        }
        $string .= "</user>\n";

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This returns genres to the user
     *
     * @param int[] $tagIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function genres(
        array $tagIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $tagIds = $this->applyLimit($tagIds, $limit, $offset);

        $string = "<total_count>" . Catalog::get_count('tag') . "</total_count>\n";

        foreach ($tagIds as $tag_id) {
            $tag    = $this->modelFactory->createTag($tag_id);
            $counts = $tag->count();

            $string .= "<genre id=\"$tag_id\">\n" .
                "\t<name><![CDATA[$tag->name]]></name>\n" .
                "\t<albums>" . (int) ($counts['album']) . "</albums>\n" .
                "\t<artists>" . (int) ($counts['artist']) . "</artists>\n" .
                "\t<songs>" . (int) ($counts['song']) . "</songs>\n" .
                "\t<videos>" . (int) ($counts['video']) . "</videos>\n" .
                "\t<playlists>" . (int) ($counts['playlist']) . "</playlists>\n" .
                "\t<live_streams>" . (int) ($counts['live_stream']) . "</live_streams>\n" .
                "</genre>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This builds the xml document for displaying video objects
     *
     * @param int[] $videoIds
     * @param int|null $userId
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function videos(
        array $videoIds,
        ?int $userId = null,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $videoIds = $this->applyLimit($videoIds, $limit, $offset);

        $string = "<total_count>" . Catalog::get_count('video') . "</total_count>\n";

        foreach ($videoIds as $videoId) {
            $video = new Video($videoId);
            $video->format();
            $rating  = new Rating($videoId, 'video');
            $flag    = new Userflag($videoId, 'video');
            $art_url = Art::url($videoId, 'video', Core::get_request('auth'));

            $string .= "<video id=\"" . $video->id . "\">\n" .
                "\t<title><![CDATA[" . $video->title . "]]></title>\n" .
                "\t<name><![CDATA[" . $video->title . "]]></name>\n" .
                "\t<mime><![CDATA[" . $video->mime . "]]></mime>\n" .
                "\t<resolution><![CDATA[" . $video->f_resolution . "]]></resolution>\n" .
                "\t<size>" . $video->size . "</size>\n" .
                $this->genre_string($video->tags) .
                "\t<time><![CDATA[" . $video->time . "]]></time>\n" .
                "\t<url><![CDATA[" . $video->play_url('', 'api', false, $userId) . "]]></url>\n" .
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($userId, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($userId) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($userId) ?: null) . "</rating>\n" .
                "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "</video>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    public function success(
        string $string,
        array $return_data = []
    ): string {
        $xml_string = "\t<success code=\"1\"><![CDATA[$string]]></success>";
        foreach ($return_data as $title => $data) {
            $xml_string .= "\n\t<$title><![CDATA[$data]]></$title>";
        }

        return $this->xmlWriter->writeXml($xml_string);
    }

    /**
     * This returns licenses to the user
     *
     * @param int[] $licenseIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function licenses(
        array $licenseIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $licenseIds = $this->applyLimit($licenseIds, $limit, $offset);

        $string = "<total_count>" . Catalog::get_count('license') . "</total_count>\n";

        foreach ($licenseIds as $licenseId) {
            $license = $this->modelFactory->createLicense($licenseId);
            $string .= "<license id=\"$licenseId\">\n" .
                "\t<name><![CDATA[" . $license->getName() . "]]></name>\n" .
                "\t<description><![CDATA[" . $license->getDescription() . "]]></description>\n" .
                "\t<external_link><![CDATA[" . $license->getLink() . "]]></external_link>\n" .
                "</license>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This returns labels to the user, in a pretty xml document with the information
     *
     * @param int[] $labelIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function labels(
        array $labelIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $labelIds = $this->applyLimit($labelIds, $limit, $offset);

        $string = "<total_count>" . Catalog::get_count('license') . "</total_count>\n";

        foreach ($labelIds as $labelId) {
            $label = $this->modelFactory->createLabel($labelId);
            $label->format();

            $string .= "<license id=\"$labelId\">\n" .
                "\t<name><![CDATA[" . scrub_out($label->getNameFormatted()) . "]]></name>\n" .
                "\t<artists><![CDATA[" . $label->getArtistCount() . "]]></artists>\n" .
                "\t<summary><![CDATA[$label->summary]]></summary>\n" .
                "\t<external_link><![CDATA[" . $label->getLink() . "]]></external_link>\n" .
                "\t<address><![CDATA[$label->address]]></address>\n" .
                "\t<category><![CDATA[$label->category]]></category>\n" .
                "\t<email><![CDATA[$label->email]]></email>\n" .
                "\t<website><![CDATA[$label->website]]></website>\n" .
                "\t<user><![CDATA[$label->user]]></user>\n" .
                "</license>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param int[] $podcastIds
     * @param int $userId
     * @param bool $episodes include the episodes of the podcast
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function podcasts(
        array $podcastIds,
        int $userId,
        bool $episodes = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $podcastIds = $this->applyLimit($podcastIds, $limit, $offset);

        $string = "<total_count>" . Catalog::get_count('podcast') . "</total_count>\n";

        foreach ($podcastIds as $podcastId) {
            $podcast = $this->podcastRepository->findById($podcastId);
            $rating  = new Rating($podcastId, 'podcast');
            $flag    = new Userflag($podcastId, 'podcast');
            $art_url = Art::url($podcastId, 'podcast', Core::get_request('auth'));
            $string .= "<podcast id=\"$podcastId\">\n" .
                "\t<name><![CDATA[" . $podcast->getTitleFormatted() . "]]></name>\n" .
                "\t<description><![CDATA[" . $podcast->getDescription() . "]]></description>\n" .
                "\t<language><![CDATA[" . $podcast->getLanguageFormatted() . "]]></language>\n" .
                "\t<copyright><![CDATA[" . $podcast->getCopyrightFormatted() . "]]></copyright>\n" .
                "\t<feed_url><![CDATA[" . $podcast->getFeed() . "]]></feed_url>\n" .
                "\t<generator><![CDATA[" . $podcast->getGeneratorFormatted() . "]]></generator>\n" .
                "\t<website><![CDATA[" . $podcast->getWebsiteFormatted() . "]]></website>\n" .
                "\t<build_date><![CDATA[" . $podcast->getLastBuildDateFormatted() . "]]></build_date>\n" .
                "\t<sync_date><![CDATA[" . $podcast->getLastSyncFormatted() . "]]></sync_date>\n" .
                "\t<public_url><![CDATA[" . $podcast->getLink() . "]]></public_url>\n" .
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($userId, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($userId) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($userId) ?: null) . "</rating>\n" .
                "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n";
            if ($episodes) {
                $items = $this->podcastEpisodeRepository->getEpisodeIds($podcast);
                if (count($items) > 0) {
                    $string .= $this->podcast_episodes($items, $userId);
                }
            }
            $string .= "\t</podcast>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param int[] $podcastEpisodeIds
     * @param int $userId
     * @param bool $simple just return the data as an array for pretty somewhere else
     * @param bool $asObject
     * @param bool $encode
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function podcast_episodes(
        array $podcastEpisodeIds,
        int $userId,
        bool $simple = false,
        bool $asObject = true,
        bool $encode = true,
        int $limit = 0,
        int $offset = 0
    ) {
        if ((count($podcastEpisodeIds) > $limit || $offset > 0) && ($limit && $simple === false)) {
            $podcastEpisodeIds = array_splice($podcastEpisodeIds, $offset, $limit);
        }
        $string = ($simple === false) ? "<total_count>" . Catalog::get_count('podcast_episode') . "</total_count>\n" : '';

        foreach ($podcastEpisodeIds as $episodeId) {
            $episode = $this->podcastEpisodeRepository->findById($episodeId);
            $rating  = new Rating($episodeId, 'podcast_episode');
            $flag    = new Userflag($episodeId, 'podcast_episode');
            $art_url = Art::url($episode->getPodcast()->getId(), 'podcast', Core::get_request('auth'));
            $string .= "\t<podcast_episode id=\"$episodeId\">\n" .
                "\t\t<title><![CDATA[" . $episode->getTitleFormatted() . "]]></title>\n" .
                "\t\t<name><![CDATA[" . $episode->getTitleFormatted() . "]]></name>\n" .
                "\t\t<description><![CDATA[" . $episode->getDescriptionFormatted() . "]]></description>\n" .
                "\t\t<category><![CDATA[" . $episode->getCategoryFormatted() . "]]></category>\n" .
                "\t\t<author><![CDATA[" . $episode->getAuthorFormatted() . "]]></author>\n" .
                "\t\t<author_full><![CDATA[" . $episode->getAuthorFormatted() . "]]></author_full>\n" .
                "\t\t<website><![CDATA[" . $episode->getWebsiteFormatted() . "]]></website>\n" .
                "\t\t<pubdate><![CDATA[" . $episode->getPublicationDateFormatted() . "]]></pubdate>\n" .
                "\t\t<state><![CDATA[" . $episode->getStateFormatted() . "]]></state>\n" .
                "\t\t<filelength><![CDATA[" . $episode->getFullDurationFormatted() . "]]></filelength>\n" .
                "\t\t<filesize><![CDATA[" . $episode->getSizeFormatted() . "]]></filesize>\n" .
                "\t\t<filename><![CDATA[" . $episode->getFilename() . "]]></filename>\n" .
                "\t\t<mime><![CDATA[" . $episode->mime . "]]></mime>\n" .
                "\t\t<public_url><![CDATA[" . $episode->getLink() . "]]></public_url>\n" .
                "\t\t<url><![CDATA[" . $episode->play_url('', 'api', false, $userId) . "]]></url>\n" .
                "\t\t<catalog><![CDATA[" . $episode->getPodcast()->getCatalog() . "]]></catalog>\n" .
                "\t\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t\t<flag>" . (!$flag->get_flag($userId, false) ? 0 : 1) . "</flag>\n" .
                "\t\t<preciserating>" . ($rating->get_user_rating($userId) ?: null) . "</preciserating>\n" .
                "\t\t<rating>" . ($rating->get_user_rating($userId) ?: null) . "</rating>\n" .
                "\t\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "\t\t<played>" . $episode->getPlayed() . "</played>\n";
            $string .= "\t</podcast_episode>\n";
        }

        return $this->xmlWriter->writeXml($string, '', null, $simple === false);
    }

    /**
     * This takes an array of playlist ids and then returns a nice pretty XML document
     *
     * @param int[] $playlistIds
     * @param int $userId
     * @param bool $songs
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function playlists(
        array $playlistIds,
        int $userId,
        bool $songs = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $playlistIds = $this->applyLimit($playlistIds, $limit, $offset);

        $string = "<total_count>" . Catalog::get_count('playlist') . "</total_count>\n";

        // Foreach the playlist ids
        foreach ($playlistIds as $playlistId) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if ((int) $playlistId === 0) {
                $playlist = new Search((int) str_replace('smart_', '', (string) $playlistId));
                $playlist->format();

                $playlist_name  = Search::get_name_byid(str_replace('smart_', '', (string) $playlistId));
                $playlist_user  = ($playlist->type !== 'public') ? $playlist->f_user : $playlist->type;
                $last_count     = ((int) $playlist->last_count > 0) ? $playlist->last_count : 5000;
                $playitem_total = ($playlist->limit == 0) ? $last_count : $playlist->limit;
                $playlist_type  = $playlist->type;
                $object_type    = 'search';
            } else {
                $playlist   = new Playlist($playlistId);
                $playlistId = $playlist->id;
                $playlist->format();

                $playlist_name  = $playlist->name;
                $playlist_user  = $playlist->f_user;
                $playitem_total = $playlist->get_media_count('song');
                $playlist_type  = $playlist->type;
                $object_type    = 'playlist';
            }
            $rating  = new Rating($playlistId, $object_type);
            $flag    = new Userflag($playlistId, $object_type);
            $art_url = Art::url($playlistId, $object_type, Core::get_request('auth'));

            // Build this element
            $string .= "<playlist id=\"$playlistId\">\n" .
                "\t<name><![CDATA[$playlist_name]]></name>\n" .
                "\t<owner><![CDATA[$playlist_user]]></owner>\n" .
                "\t<items>$playitem_total</items>\n" .
                "\t<type>$playlist_type</type>\n" .
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($userId, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($userId) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($userId) ?: null) . "</rating>\n" .
                "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "</playlist>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    public function dict(
        array $data,
        bool $xmlOutput = true,
        ?string $tagName = null
    ): string {
        $string = '';
        // Foreach it
        foreach ($data as $key => $value) {
            $attribute = '';
            // See if the key has attributes
            if (is_array($value) && isset($value['attributes'])) {
                $attribute = ' ' . $value['attributes'];
                $key       = $value['value'];
            }

            // If it's an array, run again
            if (is_array($value)) {
                $value = $this->dict($value, true);
                $string .= ($tagName) ? "<$tagName>\n$value\n</$tagName>\n" : "<$key$attribute>\n$value\n</$key>\n";
            } else {
                $string .= ($tagName) ? "\t<$tagName index=\"" . $key . "\"><![CDATA[$value]]></$tagName>\n" : "\t<$key$attribute><![CDATA[$value]]></$key>\n";
            }
        } // end foreach

        if ($xmlOutput) {
            $string = $this->xmlWriter->writeXml($string);
        }

        return $string;
    }

    /**
     * This returns catalogs to the user, in a pretty xml document with the information
     *
     * @param int[] $catalogIds group of catalog id's
     * @param bool $asObject (whether to return as a named object array or regular array)
     * @param int $limit
     * @param int $offset
     */
    public function catalogs(
        array $catalogIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $catalogIds = $this->applyLimit($catalogIds, $limit, $offset);

        $string = "<total_count>" . Catalog::get_count('catalog') . "</total_count>\n";

        foreach ($catalogIds as $catalogId) {
            $catalog = Catalog::create_from_id($catalogId);
            $catalog->format();
            $string .= "<catalog id=\"$catalogId\">\n" . "\t<name><![CDATA[" . $catalog->name . "]]></name>\n" . "\t<type><![CDATA[" . $catalog->catalog_type . "]]></type>\n" . "\t<gather_types><![CDATA[" . $catalog->gather_types . "]]></gather_types>\n" . "\t<enabled>" . $catalog->enabled . "</enabled>\n" . "\t<last_add><![CDATA[" . $catalog->f_add . "]]></last_add>\n" . "\t<last_clean><![CDATA[" . $catalog->f_clean . "]]></last_clean>\n" . "\t<last_update><![CDATA[" . $catalog->f_update . "]]></last_update>\n" . "\t<path><![CDATA[" . $catalog->f_info . "]]></path>\n" . "\t<rename_pattern><![CDATA[" . $catalog->rename_pattern . "]]></rename_pattern>\n" . "\t<sort_pattern><![CDATA[" . $catalog->sort_pattern . "]]></sort_pattern>\n" . "</catalog>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This handles creating an xml document for an activity list
     *
     * @param UseractivityInterface[] $activities Activity identifier list
     */
    public function timeline(array $activities): string
    {
        $string = '';
        foreach ($activities as $activity) {
            $user = $this->modelFactory->createUser($activity->getUser());

            $string .= "\t<activity id=\"" . $activity->getId() . "\">\n" . "\t\t<date>" . $activity->getActivityDate() . "</date>\n" . "\t\t<object_type><![CDATA[" . $activity->getObjectType() . "]]></object_type>\n" . "\t\t<object_id>" . $activity->getObjectId() . "</object_id>\n" . "\t\t<action><![CDATA[" . $activity->getAction() . "]]></action>\n";
            if ($user->id) {
                $string .= "\t\t<user id=\"" . (string)$user->id . "\">\n" . "\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n" . "\t\t</user>\n";
            }
            $string .= "\t</activity>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This returns bookmarks to the user
     *
     * @param int[] $bookmarkIds
     * @param int $limit
     * @param int $offset
     */
    public function bookmarks(
        array $bookmarkIds,
        int $limit = 0,
        int $offset = 0
    ): string {
        $string = "";
        foreach ($bookmarkIds as $bookmark_id) {
            $bookmark = $this->modelFactory->createBookmark($bookmark_id);

            $string .= "<bookmark id=\"$bookmark_id\">\n" .
                "\t<user><![CDATA[" . $bookmark->getUserName() . "]]></user>\n" .
                "\t<object_type><![CDATA[" . $bookmark->object_type . "]]></object_type>\n" .
                "\t<object_id>" . $bookmark->object_id . "</object_id>\n" .
                "\t<position>" . $bookmark->position . "</position>\n" .
                "\t<client><![CDATA[" . $bookmark->comment . "]]></client>\n" .
                "\t<creation_date>" . $bookmark->creation_date . "</creation_date>\n" .
                "\t<update_date><![CDATA[" . $bookmark->update_date . "]]></update_date>\n" .
                "</bookmark>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This returns shares to the user, in a pretty xml document with the information
     *
     * @param int[] $shareIds Share id's to include
     * @param bool  $asObject
     * @param int   $limit
     * @param int   $offset
     */
    public function shares(
        array $shareIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $shareIds = $this->applyLimit($shareIds, $limit, $offset);

        $string = "<total_count>" . Catalog::get_count('share') . "</total_count>\n";

        foreach ($shareIds as $share_id) {
            $share = $this->modelFactory->createShare((int) $share_id);
            $string .= "<share id=\"$share_id\">\n" . "\t<name><![CDATA[" . $share->getObjectName() . "]]></name>\n" . "\t<user><![CDATA[" . $share->getUserName() . "]]></user>\n" . "\t<allow_stream>" . $share->getAllowStream() . "</allow_stream>\n" . "\t<allow_download>" . $share->getAllowDownload() . "</allow_download>\n" . "\t<creation_date><![CDATA[" . $share->getCreationDateFormatted() . "]]></creation_date>\n" . "\t<lastvisit_date><![CDATA[" . $share->getLastVisitDateFormatted() . "]]></lastvisit_date>\n" . "\t<object_type><![CDATA[" . $share->getObjectType() . "]]></object_type>\n" . "\t<object_id>" . $share->getObjectId() . "</object_id>\n" . "\t<expire_days>" . $share->getExpireDays() . "</expire_days>\n" . "\t<max_counter>" . $share->getMaxCounter() . "</max_counter>\n" . "\t<counter>" . $share->getCounter() . "</counter>\n" . "\t<secret><![CDATA[" . $share->getSecret() . "]]></secret>\n" . "\t<public_url><![CDATA[" . $share->getPublicUrl() . "]]></public_url>\n" . "\t<description><![CDATA[" . $share->getDescription() . "]]></description>\n" . "</share>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This will build an xml document from an array of arrays, an id is required for the array data
     * <root>
     *   <$object_type> //optional
     *     <$item id="123">
     *       <data></data>
     *
     * @param array  $array
     * @param string $item
     */
    public function object_array(
        array $array,
        string $item
    ): string {
        $string = '';
        // Foreach it
        foreach ($array as $object) {
            $string .= "\t<$item id=\"" . $object['id'] . "\">\n";
            foreach ($object as $name => $value) {
                $filter = (is_numeric($value)) ? $value : "<![CDATA[$value]]>";
                $string .= ($name !== 'id') ? "\t\t<$name>$filter</$name>\n" : '';
            }
            $string .= "\t</$item>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This takes an array of object_ids and return XML based on the type of object
     * we want
     *
     * @param int[]    $objectIds Array of object_ids
     * @param string   $type
     * @param null|int $userId
     * @param bool     $include (add the extra songs details if a playlist or podcast_episodes if a podcast)
     * @param bool     $fullXml
     * @param int      $limit
     * @param int      $offset
     */
    public function indexes(
        array $objectIds,
        string $type,
        ?int $userId = null,
        bool $include = false,
        bool $fullXml = false,
        int $limit = 0,
        int $offset = 0
    ): string {
        if ((count($objectIds) > $limit || $offset > 0) && ($limit && $fullXml)) {
            $objectIds = array_splice($objectIds, $offset, $limit);
        }
        $string = ($fullXml) ? "<total_count>" . Catalog::get_count($type) . "</total_count>\n" : '';

        // here is where we call the object type
        foreach ($objectIds as $objectId) {
            switch ($type) {
                case 'artist':
                    if ($include) {
                        $string .= $this->artists(array($objectId), array('songs', 'albums'), $userId, false);
                    } else {
                        $artist = $this->modelFactory->createArtist($objectId);
                        $artist->format();
                        $albums = $this->albumRepository->getByArtist((int) $objectId, null, true);
                        $string .= "<$type id=\"" . $objectId . "\">\n" .
                            "\t<name><![CDATA[" . $artist->f_full_name . "]]></name>\n";
                        foreach ($albums as $album_id) {
                            if ($album_id > 0) {
                                $album = new Album($album_id);
                                $string .= "\t<album id=\"" . $album_id .
                                    '"><![CDATA[' . $album->full_name .
                                    "]]></album>\n";
                            }
                        }
                        $string .= "</$type>\n";
                    }
                    break;
                case 'album':
                    if ($include) {
                        $string .= $this->albums(array($objectId), array('songs'), $userId, false);
                    } else {
                        $album = new Album($objectId);
                        $album->format();
                        $string .= "<$type id=\"" . $objectId . "\">\n" .
                            "\t<name><![CDATA[" . $album->full_name . "]]></name>\n" .
                            "\t\t<artist id=\"" . $album->album_artist . "\"><![CDATA[" . $album->album_artist_name . "]]></artist>\n" .
                            "</$type>\n";
                    }
                    break;
                case 'song':
                    $song = new Song($objectId);
                    $song->format();
                    $string .= "<$type id=\"" . $objectId . "\">\n" .
                        "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                        "\t<name><![CDATA[" . $song->f_title . "]]></name>\n" .
                        "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_name() . "]]></artist>\n" .
                        "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_name() . "]]></album>\n" .
                        "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_name() . "]]></albumartist>\n" .
                        "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n" .
                        "\t<track>" . $song->track . "</track>\n" .
                        "</$type>\n";
                    break;
                case 'playlist':
                    if ((int) $objectId === 0) {
                        $playlist = new Search((int) str_replace('smart_', '', (string) $objectId));
                        $playlist->format();

                        $playlist_name  = Search::get_name_byid(str_replace('smart_', '', (string) $objectId));
                        $playlist_user  = ($playlist->type !== 'public')
                            ? $playlist->f_user
                            : $playlist->type;
                        $last_count     = ((int) $playlist->last_count > 0) ? $playlist->last_count : 5000;
                        $playitem_total = ($playlist->limit == 0) ? $last_count : $playlist->limit;
                    } else {
                        $playlist = new Playlist($objectId);
                        $playlist->format();

                        $playlist_name  = $playlist->name;
                        $playlist_user  = $playlist->f_user;
                        $playitem_total = $playlist->get_media_count('song');
                    }
                    $songs = ($include) ? $playlist->get_items() : array();
                    $string .= "<$type id=\"" . $objectId . "\">\n" .
                        "\t<name><![CDATA[" . $playlist_name . "]]></name>\n" .
                        "\t<items><![CDATA[" . $playitem_total . "]]></items>\n" .
                        "\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n" .
                        "\t<type><![CDATA[" . $playlist->type . "]]></type>\n";
                    $playlist_track = 0;
                    foreach ($songs as $song_id) {
                        if ($song_id['object_type'] == 'song') {
                            $playlist_track++;
                            $string .= "\t\t<playlisttrack id=\"" . $song_id['object_id'] . "\">" . $playlist_track . "</playlisttrack>\n";
                        }
                    }
                    $string .= "</$type>\n";
                    break;
                case 'share':
                    $string .= $this->shares($objectIds);
                    break;
                case 'podcast':
                    $podcast = $this->podcastRepository->findById($objectId);
                    $string .= "<podcast id=\"$objectId\">\n" .
                        "\t<name><![CDATA[" . $podcast->getTitleFormatted() . "]]></name>\n" .
                        "\t<description><![CDATA[" . $podcast->getDescription() . "]]></description>\n" .
                        "\t<language><![CDATA[" . $podcast->getLanguageFormatted() . "]]></language>\n" .
                        "\t<copyright><![CDATA[" . $podcast->getCopyrightFormatted() . "]]></copyright>\n" .
                        "\t<feed_url><![CDATA[" . $podcast->getFeed() . "]]></feed_url>\n" .
                        "\t<generator><![CDATA[" . $podcast->getGeneratorFormatted() . "]]></generator>\n" .
                        "\t<website><![CDATA[" . $podcast->getWebsiteFormatted() . "]]></website>\n" .
                        "\t<build_date><![CDATA[" . $podcast->getLastBuildDateFormatted() . "]]></build_date>\n" .
                        "\t<sync_date><![CDATA[" . $podcast->getLastSyncFormatted() . "]]></sync_date>\n" .
                        "\t<public_url><![CDATA[" . $podcast->getLink() . "]]></public_url>\n";
                    if ($include) {
                        $episodeIds = $this->podcastEpisodeRepository->getEpisodeIds($podcast);
                        foreach ($episodeIds as $episodeId) {
                            $string .= $this->podcast_episodes(array($episodeId), $userId);
                        }
                    }
                    $string .= "\t</podcast>\n";
                    break;
                case 'podcast_episode':
                    $string .= self::podcast_episodes($objectIds, $userId);
                    break;
                case 'video':
                    $string .= self::videos($objectIds, $userId);
                    break;
                case 'live_stream':
                    $live_stream = $this->modelFactory->createLiveStream($objectId);
                    $string .= "<$type id=\"" . $objectId . "\">\n" .
                        "\t<name><![CDATA[" . scrub_out($live_stream->getName()) . "]]></name>\n" .
                        "\t<url><![CDATA[" . $live_stream->getUrl() . "]]></url>\n" .
                        "\t<codec><![CDATA[" . $live_stream->getCodec() . "]]></codec>\n" .
                        "</$type>\n";
            }
        }

        return $this->xmlWriter->writeXml($string, '', null, $fullXml);
    }

    /**
     * This handles creating an result for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param int[] $objectIds Object IDs
     * @param int   $userId
     */
    public function democratic(
        array $objectIds,
        int $userId
    ): string {
        $democratic = Democratic::get_current_playlist();
        $string     = '';

        foreach ($objectIds as $row_id => $data) {
            /** @var Media&PlayableMediaInterface $song */
            $song = $this->modelFactory->mapObjectType($data['object_type'], (int) $data['object_id']);
            $song->format();

            // FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag           = new Tag($song->tags['0']);
            $song->genre   = $tag->id;
            $song->f_genre = $tag->name;

            $tag_string = $this->genre_string($song->tags);

            $rating = new Rating($song->id, 'song');

            $art_url = Art::url($song->album, 'album', Core::get_request('auth'));

            $string .= "<song id=\"" . $song->id . "\">\n" .
                // Title is an alias for name
                "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                "\t<name><![CDATA[" . $song->title . "]]></name>\n" .
                "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->getFullArtistNameFormatted() . "]]></artist>\n" .
                "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->f_album_full . "]]></album>\n" .
                "\t<genre id=\"" . $song->genre . "\"><![CDATA[" . $song->f_genre . "]]></genre>\n" .
                $tag_string .
                "\t<track>" . $song->track . "</track>\n" .
                "\t<time><![CDATA[" . $song->time . "]]></time>\n" .
                "\t<mime><![CDATA[" . $song->mime . "]]></mime>\n" .
                "\t<url><![CDATA[" . $song->play_url('', 'api', false, $userId) . "]]></url>\n" .
                "\t<size>" . $song->size . "</size>\n" .
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($userId) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($userId) ?: null) . "</rating>\n" .
                "\t<averagerating>" . ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "\t<vote>" . $democratic->get_vote($row_id) . "</vote>\n" .
                "</song>\n";
        }

        return $this->xmlWriter->writeXml($string);
    }

    /**
     * This returns the formatted 'genre' string for an xml document
     * @param  array  $tags
     * @return string
     */
    private function genre_string($tags)
    {
        $string = '';

        if (!empty($tags)) {
            $atags = array();
            foreach ($tags as $tag_id => $data) {
                if (array_key_exists($data['id'], $atags)) {
                    $atags[$data['id']]['count']++;
                } else {
                    $atags[$data['id']] = array(
                        'name' => $data['name'],
                        'count' => 1
                    );
                }
            }

            foreach ($atags as $tag => $data) {
                $string .= "\t<genre id=\"" . $tag . "\"><![CDATA[" . $data['name'] . "]]></genre>\n";
            }
        }

        return $string;
    }

    private function applyLimit(array $itemList, int $limit, int $offset): array
    {
        if ($limit === 0) {
            $limit = static::DEFAULT_LIMIT;
        }
        if ((count($itemList) > $limit || $offset > 0) && $limit) {
            return array_slice($itemList, $offset, $limit);
        }

        return $itemList;
    }
}
