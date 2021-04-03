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
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\SongRepositoryInterface;

final class JsonOutput implements ApiOutputInterface
{
    // This is added so that we don't pop any webservers
    private const DEFAULT_LIMIT = 5000;

    private ModelFactoryInterface $modelFactory;

    private AlbumRepositoryInterface $albumRepository;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository
    ) {
        $this->modelFactory     = $modelFactory;
        $this->albumRepository  = $albumRepository;
        $this->songRepository   = $songRepository;
    }

    /**
     * This generates an error message
     */
    public function error(int $code, string $message, string $action, string $type): string
    {
        $message = [
            'error' => [
                'errorCode' => (string) $code,
                'errorAction' => $action,
                'errorType' => $type,
                'errorMessage' => $message
            ]
        ];

        return json_encode($message, JSON_PRETTY_PRINT);
    }

    /**
     * This echos out a standard albums JSON document, it pays attention to the limit
     *
     * @param int[] $albumIds
     * @param array $include
     * @param int|null $userId
     * @param bool $encode
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
        $albumIds = $this->applyLimit($albumIds, $limit, $offset);

        Rating::build_cache('album', $albumIds);

        $result = [];
        foreach ($albumIds as $album_id) {
            $album = new Album($album_id);
            $album->format();

            $disk   = $album->disk;
            $rating = new Rating($album_id, 'album');
            $flag   = new Userflag($album_id, 'album');

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $album->id . '&object_type=album&auth=' . scrub_out($_REQUEST['auth']);

            $theArray = [];

            $theArray['id']   = (string)$album->id;
            $theArray['name'] = $album->name;

            // Do a little check for artist stuff
            if ($album->album_artist_name != '') {
                $theArray['artist'] = [
                    'id' => (string)$album->artist_id,
                    'name' => $album->album_artist_name
                ];
            } elseif ($album->artist_count != 1) {
                $theArray['artist'] = [
                    'id' => '0',
                    'name' => 'Various'
                ];
            } else {
                $theArray['artist'] = [
                    'id' => (string)$album->artist_id,
                    'name' => $album->artist_name
                ];
            }

            // Handle includes
            $songs = (in_array("songs", $include))
                ? $this->songs($this->songRepository->getByAlbum($album->id), $userId, false)
                : [];

            // count multiple disks
            if ($album->allow_group_disks) {
                $disk = (count($album->album_suite) <= 1) ? $album->disk : count($album->album_suite);
            }

            $theArray['time']          = (int) $album->total_duration;
            $theArray['year']          = (int) $album->year;
            $theArray['tracks']        = $songs;
            $theArray['songcount']     = (int) $album->song_count;
            $theArray['diskcount']     = (int) $disk;
            $theArray['type']          = $album->release_type;
            $theArray['genre']         = $this->genre_array($album->tags);
            $theArray['art']           = $art_url;
            $theArray['flag']          = (!$flag->get_flag($userId, false) ? 0 : 1);
            $theArray['preciserating'] = ($rating->get_user_rating() ?: null);
            $theArray['rating']        = ($rating->get_user_rating() ?: null);
            $theArray['averagerating'] = ($rating->get_average_rating() ?: null);
            $theArray['mbid']          = $album->mbid;

            array_push($result, $theArray);
        } // end foreach

        if ($encode) {
            $output = ($asObject) ? array("album" => $result) : $result[0];

            return json_encode($output, JSON_PRETTY_PRINT);
        }

        return $result;
    }

    /**
     * This generates a JSON empty object
     *
     * @param string $type object type
     *
     * @return string return empty JSON message
     */
    public function emptyResult(string $type): string
    {
        return json_encode([$type => []], JSON_PRETTY_PRINT);
    }

    /**
     * This takes an array of artists and then returns a pretty JSON document with the information
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
     * @return array|string JSON Object "artist"
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

        $result = [];

        Rating::build_cache('artist', $artistIds);

        foreach ($artistIds as $artist_id) {
            $artist = new Artist($artist_id);
            $artist->format();

            $rating = new Rating($artist_id, 'artist');
            $flag   = new Userflag($artist_id, 'artist');

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $artist_id . '&object_type=artist&auth=' . scrub_out(Core::get_request('auth'));

            // Handle includes
            $albums = (in_array('albums', $include))
                ? $this->albums($this->albumRepository->getByArtist($artist), array(), $userId, false)
                : array();
            $songs = (in_array('songs', $include))
                ? $this->songs($this->songRepository->getByArtist($artist), $userId, false)
                : array();

            $result[] = [
                'id' => (string)$artist->id,
                'name' => $artist->f_full_name,
                'albums' => $albums,
                'albumcount' => (int) $artist->albums,
                'songs' => $songs,
                'songcount' => (int) $artist->songs,
                'genre' => $this->genre_array($artist->tags),
                'art' => $art_url,
                'flag' => (!$flag->get_flag($userId, false) ? 0 : 1),
                'preciserating' => ($rating->get_user_rating() ?: null),
                'rating' => ($rating->get_user_rating() ?: null),
                'averagerating' => ($rating->get_average_rating() ?: null),
                'mbid' => $artist->mbid,
                'summary' => $artist->summary,
                'time' => (int) $artist->time,
                'yearformed' => (int) $artist->yearformed,
                'placeformed' => $artist->placeformed
            ];
        }

        if ($encode) {
            $output = ($asObject) ? ['artist' => $result] : $result[0];

            return json_encode($output, JSON_PRETTY_PRINT);
        }

        return $result;
    }

    /**
     * This returns an array of songs populated from an array of song ids.
     * (Spiffy isn't it!)
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
        $songIds = $this->applyLimit($songIds, $limit, $offset);

        Song::build_cache($songIds);
        Stream::set_session($_REQUEST['auth']);

        $result         = [];
        $playlist_track = 0;

        // Foreach the ids!
        foreach ($songIds as $song_id) {
            $song = new Song($song_id);

            // If the song id is invalid/null
            if (!$song->id) {
                continue;
            }

            $song->format();
            $rating  = new Rating($song_id, 'song');
            $flag    = new Userflag($song_id, 'song');
            $art_url = Art::url($song->album, 'album', $_REQUEST['auth']);
            $playlist_track++;

            $ourSong = [
                'id' => (string)$song->id,
                'title' => $song->title,
                'name' => $song->title,
                'artist' => [
                    'id' => (string) $song->artist,
                    'name' => $song->get_artist_name()],
                'album' => [
                    'id' => (string) $song->album,
                    'name' => $song->get_album_name()],
                'albumartist' => [
                    'id' => (string) $song->albumartist,
                    'name' => $song->get_album_artist_name()
                ]
            ];

            $ourSong['disk']                  = (int) $song->disk;
            $ourSong['track']                 = (int) $song->track;
            $ourSong['filename']              = $song->file;
            $ourSong['genre']                 = $this->genre_array($song->tags);
            $ourSong['playlisttrack']         = $playlist_track;
            $ourSong['time']                  = (int)$song->time;
            $ourSong['year']                  = (int)$song->year;
            $ourSong['bitrate']               = (int)$song->bitrate;
            $ourSong['rate']                  = (int)$song->rate;
            $ourSong['mode']                  = $song->mode;
            $ourSong['mime']                  = $song->mime;
            $ourSong['url']                   = $song->play_url('', 'api', false, $userId);
            $ourSong['size']                  = (int) $song->size;
            $ourSong['mbid']                  = $song->mbid;
            $ourSong['album_mbid']            = $song->album_mbid;
            $ourSong['artist_mbid']           = $song->artist_mbid;
            $ourSong['albumartist_mbid']      = $song->albumartist_mbid;
            $ourSong['art']                   = $art_url;
            $ourSong['flag']                  = (!$flag->get_flag($userId, false) ? 0 : 1);
            $ourSong['preciserating']         = ($rating->get_user_rating() ?: null);
            $ourSong['rating']                = ($rating->get_user_rating() ?: null);
            $ourSong['averagerating']         = ($rating->get_average_rating() ?: null);
            $ourSong['playcount']             = (int)$song->played;
            $ourSong['catalog']               = (int)$song->catalog;
            $ourSong['composer']              = $song->composer;
            $ourSong['channels']              = $song->channels;
            $ourSong['comment']               = $song->comment;
            $ourSong['license']               = $song->f_license;
            $ourSong['publisher']             = $song->label;
            $ourSong['language']              = $song->language;
            $ourSong['replaygain_album_gain'] = $song->replaygain_album_gain;
            $ourSong['replaygain_album_peak'] = $song->replaygain_album_peak;
            $ourSong['replaygain_track_gain'] = $song->replaygain_track_gain;
            $ourSong['replaygain_track_peak'] = $song->replaygain_track_peak;
            $ourSong['r128_album_gain']       = $song->r128_album_gain;
            $ourSong['r128_track_gain']       = $song->r128_track_gain;

            if (Song::isCustomMetadataEnabled()) {
                foreach ($song->getMetadata() as $metadata) {
                    $meta_name = str_replace(
                        [' ', '(', ')', '/', '\\', '#'],
                        '_',
                        $metadata->getField()->getName()
                    );
                    $ourSong[$meta_name] = $metadata->getData();
                }
            }

            $result[] = $ourSong;
        } // end foreach

        if ($encode) {
            $output = ($asObject) ? ['song' => $result] : $result[0];

            return json_encode($output, JSON_PRETTY_PRINT);
        }

        return $result;
    }

    /**
     * This handles creating a user result
     *
     * @param int[] $users User identifier list
     *
     * @return string
     */
    public function users(array $users): string
    {
        return json_encode(
            [
                'user' => array_map(
                    function (int $userId): array {
                        $user = $this->modelFactory->createUser($userId);

                        return [
                            'id' => (string) $user->getId(),
                            'username' => $user->username
                        ];
                    },
                    $users
                )
            ],
            JSON_PRETTY_PRINT
        );
    }

    /**
     * This handles creating a result for a shout list
     *
     * @param int[] $shoutIds Shout identifier list
     */
    public function shouts(array $shoutIds): string
    {
        $result = [];
        foreach ($shoutIds as $shoutId) {
            $shout = $this->modelFactory->createShoutbox($shoutId);
            $user  = $this->modelFactory->createUser((int) $shout->user);

            $result[] = [
                'id' => (string) $shoutId,
                'date' => $shout->date,
                'text' => $shout->text,
                'user' => [
                    'id' => (string) $shout->user,
                    'username' => $user->username
                ]
            ];
        }

        return json_encode(['shout' => $result], JSON_PRETTY_PRINT);
    }

    /**
     * This handles creating an JSON document for a user
     */
    public function user(User $user, bool $fullinfo): string
    {
        $user->format();
        if ($fullinfo) {
            $result = [
                'id' => (string) $user->id,
                'username' => $user->username,
                'auth' => $user->apikey,
                'email' => $user->email,
                'access' => (int) $user->access,
                'fullname_public' => (int) $user->fullname_public,
                'validation' => $user->validation,
                'disabled' => (int) $user->disabled,
                'create_date' => (int) $user->create_date,
                'last_seen' => (int) $user->last_seen,
                'website' => $user->website,
                'state' => $user->state,
                'city' => $user->city
            ];
        } else {
            $result = [
                'id' => (string) $user->id,
                'username' => $user->username,
                'create_date' => (int) $user->create_date,
                'last_seen' => (int) $user->last_seen,
                'website' => $user->website,
                'state' => $user->state,
                'city' => $user->city
            ];
        }

        if ($user->fullname_public) {
            $result['fullname'] = $user->fullname;
        }
        $output = ['user' => $result];

        return json_encode($output, JSON_PRETTY_PRINT);
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

        $result = [];
        foreach ($tagIds as $tagId) {
            $tag    = $this->modelFactory->createTag($tagId);
            $counts = $tag->count();

            $result[] = [
                'id' => (string) $tagId,
                'name' => $tag->name,
                'albums' => (int) $counts['album'],
                'artists' => (int) $counts['artist'],
                'songs' => (int) $counts['song'],
                'videos' => (int) $counts['video'],
                'playlists' => (int) $counts['playlist'],
                'live_streams' => (int) $counts['live_stream']
            ];
        }
        $output = ($asObject) ? ['genre' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * This builds the JSON document for displaying video objects
     *
     * @param int[] $videoIds
     * @param int|null $userId
     * @param bool $asObject (whether to return as a named object array or regular array)
     */
    public function videos(
        array $videoIds,
        ?int $userId = null,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $videoIds = $this->applyLimit($videoIds, $limit, $offset);

        $result = [];

        foreach ($videoIds as $video_id) {
            $video = new Video($video_id);
            $video->format();
            $rating  = new Rating($video_id, 'video');
            $flag    = new Userflag($video_id, 'video');
            $art_url = Art::url($video_id, 'video', Core::get_request('auth'));

            $result[] = [
                'id' => (string)$video->id,
                'title' => $video->title,
                'mime' => $video->mime,
                'resolution' => $video->f_resolution,
                'size' => (int) $video->size,
                'genre' => $this->genre_array($video->tags),
                'time' => (int) $video->time,
                'url' => $video->play_url('', 'api', false, $userId),
                'art' => $art_url,
                'flag' => (!$flag->get_flag($userId, false) ? 0 : 1),
                'preciserating' => ($rating->get_user_rating($userId) ?: null),
                'rating' => ($rating->get_user_rating($userId) ?: null),
                'averagerating' => (string) ($rating->get_average_rating() ?: null)
            ];
        }
        $output = ($asObject) ? ['video' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    public function success(string $string, array $return_data = []): string
    {
        $message = ['success' => $string];
        foreach ($return_data as $title => $data) {
            $message[$title] = $data;
        }

        return json_encode($message, JSON_PRETTY_PRINT);
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

        $result = [];

        foreach ($licenseIds as $licenseId) {
            $license = $this->modelFactory->createLicense($licenseId);

            $result[] = [
                'id' => (string) $licenseId,
                'name' => $license->getName(),
                'description' => $license->getDescription(),
                'external_link' => $license->getLink()
            ];
        }
        $output = ($asObject) ? ['license' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * This returns labels to the user, in a pretty JSON document with the information
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

        $result = array_map(
            function (int $labelId): array {
                $label = $this->modelFactory->createLabel($labelId);
                $label->format();

                return [
                    'id' => (string) $labelId,
                    'name' => $label->f_name,
                    'artists' => $label->artists,
                    'summary' => $label->summary,
                    'external_link' => $label->link,
                    'address' => $label->address,
                    'category' => $label->category,
                    'email' => $label->email,
                    'website' => $label->website,
                    'user' => $label->user,
                ];
            },
            $labelIds
        );

        $output = ($asObject) ? ['label' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * This returns podcasts to the user
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

        $result = [];

        foreach ($podcastIds as $podcast_id) {
            $podcast = new Podcast($podcast_id);
            $podcast->format();
            $rating              = new Rating($podcast_id, 'podcast');
            $flag                = new Userflag($podcast_id, 'podcast');
            $art_url             = Art::url($podcast_id, 'podcast', Core::get_request('auth'));
            $podcast_name        = $podcast->f_title;
            $podcast_description = $podcast->description;
            $podcast_language    = $podcast->f_language;
            $podcast_copyright   = $podcast->f_copyright;
            $podcast_feed_url    = $podcast->feed;
            $podcast_generator   = $podcast->f_generator;
            $podcast_website     = $podcast->f_website;
            $podcast_build_date  = $podcast->f_lastbuilddate;
            $podcast_sync_date   = $podcast->f_lastsync;
            $podcast_public_url  = $podcast->link;
            $podcast_episodes    = array();
            if ($episodes) {
                $items            = $podcast->get_episodes();
                $podcast_episodes = $this->podcast_episodes($items,
                    $userId,
                    false,
                    true,
                    true,
                    $limit,
                    $offset);
            }

            // Build this element
            $result[] = [
                'id' => (string) $podcast_id,
                'name' => $podcast_name,
                'description' => $podcast_description,
                'language' => $podcast_language,
                'copyright' => $podcast_copyright,
                'feed_url' => $podcast_feed_url,
                'generator' => $podcast_generator,
                'website' => $podcast_website,
                'build_date' => $podcast_build_date,
                'sync_date' => $podcast_sync_date,
                'public_url' => $podcast_public_url,
                'art' => $art_url,
                'flag' => (!$flag->get_flag($userId, false) ? 0 : 1),
                'preciserating' => ($rating->get_user_rating($userId) ?: null),
                'rating' => ($rating->get_user_rating($userId) ?: null),
                'averagerating' => (string) ($rating->get_average_rating() ?: null),
                'podcast_episode' => $podcast_episodes
            ];
        } // end foreach
        $output = ($asObject) ? ['podcast' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * This returns podcast episodes to the user
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
        $podcastEpisodeIds = $this->applyLimit($podcastEpisodeIds, $limit, $offset);

        $result = [];

        foreach ($podcastEpisodeIds as $episode_id) {
            $episode = new Podcast_Episode($episode_id);
            $episode->format();
            $rating  = new Rating($episode_id, 'podcast_episode');
            $flag    = new Userflag($episode_id, 'podcast_episode');
            $art_url = Art::url($episode->podcast, 'podcast', Core::get_request('auth'));

            $result[] = [
                'id' => (string) $episode_id,
                'title' => $episode->f_title,
                'name' => $episode->f_title,
                'description' => $episode->f_description,
                'category' => $episode->f_category,
                'author' => $episode->f_author,
                'author_full' => $episode->f_artist_full,
                'website' => $episode->f_website,
                'pubdate' => $episode->f_pubdate,
                'state' => $episode->f_state,
                'filelength' => $episode->f_time_h,
                'filesize' => $episode->f_size,
                'filename' => $episode->f_file,
                'mime' => $episode->mime,
                'public_url' => $episode->link,
                'url' => $episode->play_url('', 'api', false, $userId),
                'catalog' => $episode->catalog,
                'art' => $art_url,
                'flag' => (!$flag->get_flag($userId, false) ? 0 : 1),
                'preciserating' => ($rating->get_user_rating($userId) ?: null),
                'rating' => ($rating->get_user_rating($userId) ?: null),
                'averagerating' => (string) ($rating->get_average_rating() ?: null),
                'played' => $episode->played
            ];
        }
        if (!$encode) {
            return $result;
        }
        $output = ($asObject) ? ['podcast_episode' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * This returns the playlists to the user
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

        $result = [];

        // Foreach the playlist ids
        foreach ($playlistIds as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if ((int) $playlist_id === 0) {
                $playlist = new Search((int) str_replace('smart_', '', (string) $playlist_id));
                $playlist->format();

                $playlist_name = Search::get_name_byid(str_replace('smart_', '', (string) $playlist_id));
                $playlist_user = ($playlist->type !== 'public')
                    ? $playlist->f_user
                    : $playlist->type;

                $last_count     = ((int) $playlist->last_count > 0) ? $playlist->last_count : 5000;
                $playitem_total = ($playlist->limit == 0) ? $last_count : $playlist->limit;
                $playlist_type  = $playlist->type;
                $object_type    = 'search';
            } else {
                $playlist    = new Playlist($playlist_id);
                $playlist_id = $playlist->id;
                $playlist->format();

                $playlist_name  = $playlist->name;
                $playlist_user  = $playlist->f_user;
                $playitem_total = $playlist->get_media_count('song');
                $playlist_type  = $playlist->type;
                $object_type    = 'playlist';
            }

            if ($songs) {
                $items          = [];
                $trackcount     = 1;
                $playlisttracks = $playlist->get_items();
                foreach ($playlisttracks as $objects) {
                    $items[] = ['id' => (string) $objects['object_id'], 'playlisttrack' => $trackcount];
                    $trackcount++;
                }
            } else {
                $items = ($playitem_total ?: 0);
            }
            $rating  = new Rating($playlist_id, $object_type);
            $flag    = new Userflag($playlist_id, $object_type);
            $art_url = Art::url($playlist_id, $object_type, Core::get_request('auth'));

            // Build this element
            $result[] = [
                'id' => (string) $playlist_id,
                'name' => $playlist_name,
                'owner' => $playlist_user,
                'items' => $items,
                'type' => $playlist_type,
                'art' => $art_url,
                'flag' => (!$flag->get_flag($userId, false) ? 0 : 1),
                'preciserating' => ($rating->get_user_rating($userId) ?: null),
                'rating' => ($rating->get_user_rating($userId) ?: null),
                'averagerating' => (string) ($rating->get_average_rating() ?: null)
            ];
        }
        $output = ($asObject) ? ['playlist' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    public function dict(
        array $data,
        bool $xmlOutput = true,
        ?string $tagName = null
    ): string {
        return json_encode(
            $data,
            JSON_PRETTY_PRINT
        );
    }

    /**
     * This returns catalogs to the user
     *
     * @param int[] $catalogIds group of catalog id's
     * @param bool $asObject (whether to return as a named object array or regular array)
     */
    public function catalogs(
        array $catalogIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        $catalogIds = $this->applyLimit($catalogIds, $limit, $offset);

        $result = [];
        foreach ($catalogIds as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            $catalog->format();
            $catalog_name           = $catalog->name;
            $catalog_type           = $catalog->catalog_type;
            $catalog_gather_types   = $catalog->gather_types;
            $catalog_enabled        = (int) $catalog->enabled;
            $catalog_last_add       = $catalog->f_add;
            $catalog_last_clean     = $catalog->f_clean;
            $catalog_last_update    = $catalog->f_update;
            $catalog_path           = $catalog->f_info;
            $catalog_rename_pattern = $catalog->rename_pattern;
            $catalog_sort_pattern   = $catalog->sort_pattern;

            $result[] = [
                'id' => (string) $catalog_id,
                'name' => $catalog_name,
                'type' => $catalog_type,
                'gather_types' => $catalog_gather_types,
                'enabled' => $catalog_enabled,
                'last_add' => $catalog_last_add,
                'last_clean' => $catalog_last_clean,
                'last_update' => $catalog_last_update,
                'path' => $catalog_path,
                'rename_pattern' => $catalog_rename_pattern,
                'sort_pattern' => $catalog_sort_pattern
            ];
        }
        $output = ($asObject) ? ['catalog' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * This return user activity to the user
     *
     * @param int[] $activityIds Activity identifier list
     */
    public function timeline(array $activityIds): string
    {
        $result = [];
        foreach ($activityIds as $activityId) {
            $activity = $this->modelFactory->createUseractivity($activityId);
            $user     = $this->modelFactory->createUser((int) $activity->user);

            $result[] = [
                'id' => (string) $activityId,
                'date' => $activity->activity_date,
                'object_type' => $activity->object_type,
                'object_id' => $activity->object_id,
                'action' => $activity->action,
                'user' => [
                    'id' => (string) $activity->user,
                    'username' => $user->username
                ]
            ];
        }

        return json_encode(['activity' => $result], JSON_PRETTY_PRINT);
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
        $bookmarkIds = $this->applyLimit($bookmarkIds, $limit, $offset);

        $result = [];
        foreach ($bookmarkIds as $bookmarkId) {
            $bookmark               = $this->modelFactory->createBookmark($bookmarkId);
            $bookmark_user          = $bookmark->getUserName();
            $bookmark_object_type   = $bookmark->object_type;
            $bookmark_object_id     = $bookmark->object_id;
            $bookmark_position      = $bookmark->position;
            $bookmark_comment       = $bookmark->comment;
            $bookmark_creation_date = $bookmark->creation_date;
            $bookmark_update_date   = $bookmark->update_date;

            $result[] = [
                'id' => (string) $bookmarkId,
                'owner' => $bookmark_user,
                'object_type' => $bookmark_object_type,
                'object_id' => $bookmark_object_id,
                'position' => $bookmark_position,
                'client' => $bookmark_comment,
                'creation_date' => $bookmark_creation_date,
                'update_date' => $bookmark_update_date
            ];
        }

        return json_encode(['bookmark' => $result], JSON_PRETTY_PRINT);
    }

    /**
     * This returns shares to the user
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
        $result = [];

        foreach ($this->applyLimit($shareIds, $limit, $offset) as $share_id) {
            $share                = new Share($share_id);
            $share_name           = $share->getObjectName();
            $share_user           = $share->getUserName();
            $share_allow_stream   = (int) $share->allow_stream;
            $share_allow_download = (int) $share->allow_download;
            $share_creation_date  = $share->getCreationDateFormatted();
            $share_lastvisit_date = $share->getLastVisitDateFormatted();
            $share_object_type    = $share->object_type;
            $share_object_id      = $share->object_id;
            $share_expire_days    = (int) $share->expire_days;
            $share_max_counter    = (int) $share->max_counter;
            $share_counter        = (int) $share->counter;
            $share_secret         = $share->secret;
            $share_public_url     = $share->public_url;
            $share_description    = $share->description;

            // Build this element
            $result[] = [
                'id' => (string) $share_id,
                'name' => $share_name,
                'owner' => $share_user,
                'allow_stream' => $share_allow_stream,
                'allow_download' => $share_allow_download,
                'creation_date' => $share_creation_date,
                'lastvisit_date' => $share_lastvisit_date,
                'object_type' => $share_object_type,
                'object_id' => $share_object_id,
                'expire_days' => $share_expire_days,
                'max_counter' => $share_max_counter,
                'counter' => $share_counter,
                'secret' => $share_secret,
                'public_url' => $share_public_url,
                'description' => $share_description
            ];
        }
        $output = ($asObject) ? ['share' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * Formats a list of arrays
     *
     * @param array  $array
     * @param string $item
     */
    public function object_array(
        array $array,
        string $item
    ): string {
        return json_encode([$item => $array], JSON_PRETTY_PRINT);
    }

    /**
     * This takes an array of object_ids and returns a result based on type
     *
     * @param int[]    $objects Array of object_ids
     * @param string   $type
     * @param null|int $user_id
     * @param bool     $include (add the extra songs details if a playlist or podcast_episodes if a podcast)
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
        switch ($type) {
            case 'song':
                return $this->songs($objectIds, $userId, true, true, true, $limit, $offset);
            case 'album':
                $include_array = ($include) ? ['songs'] : [];

                return $this->albums($objectIds, $include_array, $userId, true, true, $limit, $offset);
            case 'artist':
                $include_array = ($include) ? ['songs', 'albums'] : [];

                return $this->artists($objectIds, $include_array, $userId, true, true, $limit, $offset);
            case 'playlist':
                return $this->playlists($objectIds, $userId, $include, true, $limit, $offset);
            case 'share':
                return $this->shares($objectIds, true, $limit, $offset);
            case 'podcast':
                return $this->podcasts($objectIds, $userId, $include, true, $limit, $offset);
            case 'podcast_episode':
                return $this->podcast_episodes($objectIds,
                    $userId,
                    true,
                    true,
                    true,
                    $limit,
                    $offset
                );
            case 'video':
                return $this->videos($objectIds, $userId, true, $limit, $offset);
            default:
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                return $this->error(4710, sprintf(T_('Bad Request: %s'), $type), 'indexes', 'type');
        }
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

        $result = [];

        foreach ($objectIds as $row_id => $data) {
            $song = $this->modelFactory->mapObjectType(
                $data['object_type'],
                (int) $data['object_id']
            );
            $song->format();

            $rating  = new Rating($song->id, 'song');
            $art_url = Art::url($song->album, 'album', $_REQUEST['auth']);

            $result[] = [
                'id' => (string) $song->id,
                'title' => $song->title,
                'artist' => ['id' => (string) $song->artist, 'name' => $song->f_artist_full],
                'album' => ['id' => (string) $song->album, 'name' => $song->f_album_full],
                'genre' => $this->genre_array($song->tags),
                'track' => (int) $song->track,
                'time' => (int) $song->time,
                'mime' => $song->mime,
                'url' => $song->play_url('', 'api', false, $userId),
                'size' => (int) $song->size,
                'art' => $art_url,
                'preciserating' => ($rating->get_user_rating() ?: null),
                'rating' => ($rating->get_user_rating() ?: null),
                'averagerating' => ($rating->get_average_rating() ?: null),
                'vote' => $democratic->get_vote($row_id)
            ];
        }

        return json_encode(['song' => $result], JSON_PRETTY_PRINT);
    }
    /**
     * This returns the formatted 'genre' array for a JSON document
     *
     * @param  array $tags
     * @return array
     */
    public function genre_array($tags)
    {
        $JSON = array();

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

            foreach ($atags as $id => $data) {
                array_push($JSON, array(
                    "id" => (string) $id,
                    "name" => $data['name']
                ));
            }
        }

        return $JSON;
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
