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

namespace Ampache\Module\Api;

use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Traversable;

/**
 * JSON_Data Class
 *
 * This class takes care of all of the JSON document stuff in Ampache these
 * are all static calls
 *
 */
class Json4_Data
{
    // This is added so that we don't pop any webservers
    private static ?int $limit = 5000;
    private static int $offset = 0;

    /**
     * constructor
     *
     * We don't use this, as its really a static class
     */
    private function __construct()
    {
        // Rien a faire
    }

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param int|string $offset Change the starting position of your results. (e.g 5001 when selecting in groups of 5000)
     */
    public static function set_offset(int|string $offset): void
    {
        self::$offset = (int)$offset;
    }

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param int|string $limit Set a limit on your results
     */
    public static function set_limit(int|string $limit): bool
    {
        if (!$limit) {
            return false;
        }

        self::$limit = (strtolower((string) $limit) == "none") ? null : (int)$limit;

        return true;
    }

    /**
     * error
     *
     * This generates a JSON Error message
     * nothing fancy here...
     *
     * @param string $code    Error code
     * @param string $string    Error message
     */
    public static function error(string $code, string $string): string
    {
        return json_encode([
            "error" => [
                "code" => $code,
                "message" => $string
            ]
        ], JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * success
     *
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param string $string    success message
     */
    public static function success(string $string): string
    {
        return json_encode(["success" => $string], JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * tags_array
     *
     * This returns the formatted 'tags' array for a JSON document
     * @param array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags
     * @param bool $simple
     * @return array<array{id?: string, name?: string}>
     */
    private static function tags_array(array $tags, bool $simple = false): array
    {
        $JSON = [];

        $atags = [];
        foreach ($tags as $tag) {
            if (array_key_exists($tag['id'], $atags)) {
                $atags[$tag['id']]['count']++;
            } else {
                $atags[$tag['id']] = [
                    'name' => $tag['name'],
                    'count' => 1
                ];
            }
        }

        foreach ($atags as $tag_id => $data) {
            if ($simple) {
                $JSON[] = ["name" => $data['name']];
            } else {
                $JSON[] = [
                    "id" => (string)$tag_id,
                    "name" => $data['name']
                ];
            }
        }

        return $JSON;
    }

    /**
     * indexes
     *
     * This takes an array of object_ids and return JSON based on the type of object
     *
     * @param list<int|string> $objects Array of object_ids (Mixed string|int)
     * @param string $object_type 'artist'|'album'|'song'|'playlist'|'share'|'podcast'|'podcast_episode'|'video'
     * @param User $user
     * @param string $auth
     * @param bool $include (add the extra songs details if a playlist or podcast_episodes if a podcast)
     * @return string JSON Object "artist"|"album"|"song"|"playlist"|"share"|"podcast"|"podcast_episode"|"video"
     */
    public static function indexes(array $objects, string $object_type, User $user, string $auth, bool $include = false): string
    {
        // here is where we call the object type
        switch ($object_type) {
            case 'song':
                /** @var string $results */
                $results = self::songs($objects, $user, $auth);
                break;
            case 'album':
                $include_array = ($include) ? ['songs'] : [];

                /** @var string $results */
                $results = self::albums($objects, $include_array, $user, $auth);
                break;
            case 'artist':
                $include_array = ($include) ? ['songs', 'albums'] : [];

                /** @var string $results */
                $results = self::artists($objects, $include_array, $user, $auth);
                break;
            case 'playlist':
                /** @var string $results */
                $results = self::playlists($objects, $user, $auth, $include);
                break;
            case 'share':
                /** @var string $results */
                $results = self::shares($objects);
                break;
            case 'podcast':
                /** @var string $results */
                $results = self::podcasts($objects, $user, $auth, $include);
                break;
            case 'podcast_episode':
                /** @var string $results */
                $results = self::podcast_episodes($objects, $user, $auth, true, false);
                break;
            case 'video':
                /** @var string $results */
                $results = self::videos($objects, $user, $auth);
                break;
            default:
                return self::error('401', T_('Wrong object type ' . $object_type));
        }

        return $results;
    }

    /**
     * licenses
     *
     * This returns licenses to the user, in a pretty JSON document with the information
     *
     * @param list<int|string> $licenses
     */
    public static function licenses(array $licenses): string
    {
        if ((count($licenses) > self::$limit || self::$offset > 0) && self::$limit) {
            $licenses = array_splice($licenses, self::$offset, self::$limit);
        }

        $JSON = [];
        foreach ($licenses as $license_id) {
            $license = self::getLicenseRepository()->findById((int)$license_id);

            if ($license !== null) {
                $JSON[] = [
                    'id' => (string)$license_id,
                    'name' => $license->getName(),
                    'description' => $license->getDescription(),
                    'external_link' => $license->getExternalLink()
                ];
            }
        }

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * tags
     *
     * This returns tags to the user, in a pretty JSON document with the information
     *
     * @param list<int|string> $tags
     */
    public static function tags(array $tags): string
    {
        if ((count($tags) > self::$limit || self::$offset > 0) && self::$limit) {
            $tags = array_splice($tags, self::$offset, self::$limit);
        }

        $JSON = [];
        $TAGS = [];

        foreach ($tags as $tag_id) {
            $tag    = new Tag((int)$tag_id);
            $TAGS[] = [
                "id" => (string)$tag_id,
                "name" => $tag->name,
                "albums" => $tag->album,
                "artists" => $tag->artist,
                "songs" => $tag->song,
                "videos" => $tag->video,
                "playlists" => 0,
                "stream" => 0,
            ];
        } // end foreach

        // return a tag object
        $JSON[] = ["tag" => $TAGS];

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty JSON document with the information
     * we want
     *
     * @param list<int|string> $artists
     * @param string[] $include
     * @param User $user
     * @param string $auth
     * @param bool $encode
     * @return array|string return JSON
     */
    public static function artists(array $artists, array $include, User $user, string $auth, bool $encode = true): array|string
    {
        if ((count($artists) > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $artists = array_splice($artists, self::$offset, self::$limit);
        }

        $JSON = [];

        Rating::build_cache('artist', $artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist((int)$artist_id);
            if ($artist->isNew()) {
                continue;
            }

            $rating      = new Rating($artist->id, 'artist');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($artist->id, 'artist');

            // Build the Art URL, include session
            $art_url = Art::url($artist->id, 'artist', $auth);

            // Handle includes
            if (in_array("albums", $include)) {
                $albums = self::albums(self::getAlbumRepository()->getAlbumByArtist($artist->id), [], $user, $auth, false);
            } else {
                $albums = $artist->album_count;
            }
            if (in_array("songs", $include)) {
                $songs = self::songs(self::getSongRepository()->getByArtist($artist->id), $user, $auth, false);
            } else {
                $songs = $artist->song_count;
            }

            $JSON[] = [
                "id" => (string)$artist->id,
                "name" => $artist->get_fullname(),
                "albums" => $albums,
                "albumcount" => $artist->album_count,
                "songs" => $songs,
                "songcount" => $artist->song_count,
                "tag" => self::tags_array($artist->get_tags()),
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => ($rating->get_average_rating() ?? null),
                "mbid" => $artist->mbid,
                "summary" => $artist->summary,
                "time" => (int)$artist->time,
                "yearformed" => (int)$artist->yearformed,
                "placeformed" => $artist->placeformed
            ];
        } // end foreach artists

        if ($encode) {
            return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * albums
     *
     * This echos out a standard albums JSON document, it pays attention to the limit
     *
     * @param list<int|string> $albums
     * @param string[] $include
     * @param User $user
     * @param string $auth
     * @param bool $encode
     * @return array|string
     */
    public static function albums(array $albums, array $include, User $user, string $auth, bool $encode = true): array|string
    {
        if ((count($albums) > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $albums = array_splice($albums, self::$offset, self::$limit);
        }

        Rating::build_cache('album', $albums);

        $JSON = [];
        foreach ($albums as $album_id) {
            $album = new Album((int)$album_id);
            if ($album->isNew()) {
                continue;
            }

            $rating      = new Rating($album->id, 'album');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($album->id, 'album');

            // Build the Art URL, include session
            $art_url = Art::url($album->id, 'album', $auth);

            $objArray = [];

            $objArray["id"]   = (string) $album->id;
            $objArray["name"] = $album->get_fullname();

            if ($album->get_artist_fullname() != "") {
                $objArray['artist'] = [
                    "id" => (string)$album->findAlbumArtist(),
                    "name" => $album->get_artist_fullname()
                ];
            }

            // Handle includes
            if (in_array("songs", $include) && isset($album->id)) {
                $songs = self::songs(self::getAlbumRepository()->getSongs($album->id), $user, $auth, false);
            } else {
                $songs = $album->song_count;
            }

            $objArray['time']          = (int)$album->time;
            $objArray['year']          = (int) $album->year;
            $objArray['tracks']        = $songs;
            $objArray['songcount']     = (int) $album->song_count;
            $objArray['type']          = $album->release_type;
            $objArray['disk']          = (int) $album->disk_count;
            $objArray['tag']           = self::tags_array($album->get_tags());
            $objArray['art']           = $art_url;
            $objArray['flag']          = (!$flag->get_flag($user->getId()) ? 0 : 1);
            $objArray['preciserating'] = $user_rating;
            $objArray['rating']        = $user_rating;
            $objArray['averagerating'] = ($rating->get_average_rating() ?? null);
            $objArray['mbid']          = $album->mbid;

            $JSON[] = $objArray;
        } // end foreach

        if ($encode) {
            return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty JSON document
     *
     * @param list<int|string> $playlists Playlist id's to include
     * @param User $user
     * @param string $auth
     * @param bool $songs
     * @return string
     */
    public static function playlists(array $playlists, User $user, string $auth, bool $songs = false): string
    {
        if ((count($playlists) > self::$limit || self::$offset > 0) && self::$limit) {
            $playlists = array_slice($playlists, self::$offset, self::$limit);
        }

        $JSON = [];

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if ((int)$playlist_id === 0) {
                $playlist = new Search((int) str_replace('smart_', '', (string) $playlist_id), 'song', $user);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'search';
                $playitem_total = $playlist->last_count;
            } else {
                $playlist = new Playlist((int)$playlist_id);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'playlist';
                $playitem_total = $playlist->get_media_count('song');
            }
            $art_url       = Art::url($playlist->id, $object_type, $auth);
            $playlist_name = $playlist->get_fullname();
            $playlist_user = $playlist->username;
            $playlist_type = $playlist->type;

            if ($songs) {
                $items          = [];
                $trackcount     = 1;
                $playlisttracks = $playlist->get_items();
                foreach ($playlisttracks as $objects) {
                    $items[] = [
                        "id" => (string)$objects['object_id'],
                        "playlisttrack" => $trackcount
                    ];
                    $trackcount++;
                }
            } else {
                $items = ($playitem_total ?? 0);
            }
            $rating      = new Rating($playlist->id, $object_type);
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($playlist->id, $object_type);

            // Build this element
            $JSON[] = [
                "id" => (string)$playlist_id,
                "name" => $playlist_name,
                "owner" => $playlist_user,
                "items" => $items,
                "type" => $playlist_type,
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => (string)($rating->get_average_rating() ?? null)];
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * shares
     *
     * This returns shares to the user, in a pretty json document with the information
     *
     * @param list<int|string> $shares
     */
    public static function shares(array $shares): string
    {
        if ((count($shares) > self::$limit || self::$offset > 0) && self::$limit) {
            $shares = array_splice($shares, self::$offset, self::$limit);
        }

        $allShares = [];
        foreach ($shares as $share_id) {
            $share                = new Share((int)$share_id);
            $share_name           = $share->getObjectName();
            $share_user           = $share->getUserName();
            $share_allow_stream   = (int) $share->allow_stream;
            $share_allow_download = (int) $share->allow_download;
            $share_creation_date  = $share->creation_date;
            $share_lastvisit_date = $share->lastvisit_date;
            $share_object_type    = $share->object_type;
            $share_object_id      = (string)$share->object_id;
            $share_expire_days    = $share->expire_days;
            $share_max_counter    = $share->max_counter;
            $share_counter        = $share->counter;
            $share_secret         = $share->secret;
            $share_public_url     = $share->public_url;
            $share_description    = $share->description;
            // Build this element
            $allShares[] = [
                "id" => (string)$share_id,
                "name" => $share_name,
                "owner" => $share_user,
                "allow_stream" => $share_allow_stream,
                "allow_download" => $share_allow_download,
                "creation_date" => $share_creation_date,
                "lastvisit_date" => $share_lastvisit_date,
                "object_type" => $share_object_type,
                "object_id" => $share_object_id,
                "expire_days" => $share_expire_days,
                "max_counter" => $share_max_counter,
                "counter" => $share_counter,
                "secret" => $share_secret,
                "public_url" => $share_public_url,
                "description" => $share_description
            ];
        } // end foreach

        return json_encode($allShares, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty json document with the information
     *
     * @param int[] $catalogs group of catalog id's
     */
    public static function catalogs(array $catalogs): string
    {
        if ((count($catalogs) > self::$limit || self::$offset > 0) && self::$limit) {
            $catalogs = array_splice($catalogs, self::$offset, self::$limit);
        }

        $allCatalogs = [];
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }
            $catalog_name           = $catalog->name;
            $catalog_type           = $catalog->catalog_type;
            $catalog_gather_types   = $catalog->gather_types;
            $catalog_enabled        = $catalog->enabled;
            $catalog_last_add       = $catalog->get_f_add();
            $catalog_last_clean     = $catalog->get_f_clean();
            $catalog_last_update    = $catalog->get_f_update();
            $catalog_path           = $catalog->get_f_info();
            $catalog_rename_pattern = $catalog->rename_pattern;
            $catalog_sort_pattern   = $catalog->sort_pattern;
            // Build this element
            $allCatalogs[] = [
                "id" => (string)$catalog_id,
                "name" => $catalog_name,
                "type" => $catalog_type,
                "gather_types" => $catalog_gather_types,
                "enabled" => $catalog_enabled,
                "last_add" => $catalog_last_add,
                "last_clean" => $catalog_last_clean,
                "last_update" => $catalog_last_update,
                "path" => $catalog_path,
                "rename_pattern" => $catalog_rename_pattern,
                "sort_pattern" => $catalog_sort_pattern
            ];
        } // end foreach

        return json_encode($allCatalogs, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * podcasts
     *
     * This returns podcasts to the user, in a pretty json document with the information
     *
     * @param list<int|string> $podcasts Podcast id's to include
     * @param User $user
     * @param string $auth
     * @param bool $episodes include the episodes of the podcast
     * @return string
     */
    public static function podcasts(array $podcasts, User $user, string $auth, bool $episodes = false): string
    {
        if ((count($podcasts) > self::$limit || self::$offset > 0) && self::$limit) {
            $podcasts = array_splice($podcasts, self::$offset, self::$limit);
        }

        $podcastRepository = self::getPodcastRepository();

        $allPodcasts = [];
        foreach ($podcasts as $podcast_id) {
            $podcast = $podcastRepository->findById((int)$podcast_id);

            if ($podcast === null) {
                continue;
            }

            $rating              = new Rating((int)$podcast_id, 'podcast');
            $user_rating         = $rating->get_user_rating($user->getId());
            $flag                = new Userflag((int)$podcast_id, 'podcast');
            $art_url             = Art::url((int)$podcast_id, 'podcast', $auth);
            $podcast_name        = $podcast->get_fullname();
            $podcast_description = $podcast->get_description();
            $podcast_language    = scrub_out($podcast->getLanguage());
            $podcast_copyright   = scrub_out($podcast->getCopyright());
            $podcast_feed_url    = $podcast->getFeedUrl();
            $podcast_generator   = scrub_out($podcast->getGenerator());
            $podcast_website     = scrub_out($podcast->getWebsite());
            $podcast_build_date  = $podcast->getLastBuildDate()->format(DATE_ATOM);
            $podcast_sync_date   = $podcast->getLastSyncDate()->format(DATE_ATOM);
            $podcast_public_url  = $podcast->get_link();
            $podcast_episodes    = [];
            if ($episodes) {
                $results          = $podcast->getEpisodeIds();
                $podcast_episodes = self::podcast_episodes($results, $user, $auth, false);
            }
            // Build this element
            $allPodcasts[] = [
                "id" => (string)$podcast_id,
                "name" => $podcast_name,
                "description" => $podcast_description,
                "language" => $podcast_language,
                "copyright" => $podcast_copyright,
                "feed_url" => $podcast_feed_url,
                "generator" => $podcast_generator,
                "website" => $podcast_website,
                "build_date" => $podcast_build_date,
                "sync_date" => $podcast_sync_date,
                "public_url" => $podcast_public_url,
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => (string)($rating->get_average_rating() ?? null),
                "podcast_episode" => $podcast_episodes
            ];
        } // end foreach

        return json_encode($allPodcasts, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * podcast_episodes
     *
     * This returns podcasts to the user, in a pretty json document with the information
     *
     * @param list<int|string> $podcast_episodes Podcast_Episode id's to include
     * @param User $user
     * @param string $auth
     * @param bool $encode
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "podcast_episode"
     */
    public static function podcast_episodes(array $podcast_episodes, User $user, string $auth, bool $encode = true, bool $object = true): array|string
    {
        if ((count($podcast_episodes) > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $podcast_episodes = array_splice($podcast_episodes, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($podcast_episodes as $episode_id) {
            $episode = new Podcast_Episode((int)$episode_id);
            if ($episode->isNew()) {
                continue;
            }

            $rating      = new Rating($episode->id, 'podcast_episode');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($episode->id, 'podcast_episode');
            $art_url     = Art::url($episode->podcast, 'podcast', $auth);
            $JSON[]      = [
                "id" => (string)$episode_id,
                "name" => $episode->get_fullname(),
                "description" => $episode->get_description(),
                "category" => $episode->getCategory(),
                "author" => $episode->getAuthor(),
                "author_full" => $episode->getAuthor(),
                "website" => $episode->getWebsite(),
                "pubdate" => $episode->getPubDate()->format(DATE_ATOM),
                "state" => $episode->getState()->toDescription(),
                "filelength" => $episode->get_f_time(true),
                "filesize" => $episode->getSizeFormatted(),
                "mime" => $episode->mime,
                "filename" => $episode->getFileName(),
                "public_url" => $episode->get_link(),
                "url" => $episode->play_url('', 'api', false, $user->getId(), $user->streamtoken),
                "catalog" => (string)$episode->catalog,
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => (string)($rating->get_average_rating() ?? null),
                "played" => (string)$episode->played
            ];
        }
        if (!$encode) {
            return $JSON;
        }
        $output = ($object) ? ["podcast_episode" => $JSON] : $JSON;

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * songs
     *
     * This returns an array of songs populated from an array of song ids.
     * (Spiffy isn't it!)
     * @param list<int|string> $songs
     * @param User $user
     * @param string $auth
     * @param bool $encode return JSON encoded string
     * @return array|string
     */
    public static function songs(array $songs, User $user, string $auth, bool $encode = true): array|string
    {
        if ((count($songs) > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $songs = array_slice($songs, self::$offset, self::$limit);
        }

        Song::build_cache($songs);
        Stream::set_session($auth);

        $JSON           = [];
        $playlist_track = 0;

        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song((int)$song_id);
            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }

            $song->fill_ext_info();
            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($song->id, 'song');
            $art_url     = Art::url($song->album, 'album', $auth);
            $songMime    = $song->mime;
            $songBitrate = $song->bitrate;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);
            $license     = $song->getLicense();
            $licenseLink = $license?->getExternalLink() ?: null;

            $playlist_track++;

            $ourSong = [
                "id" => (string) $song->id,
                "title" => $song->title,
                "name" => $song->title,
                "artist" => [
                    "id" => (string) $song->artist,
                    "name" => $song->get_artist_fullname()],
                "album" => [
                    "id" => (string) $song->album,
                    "name" => $song->get_album_fullname()],
                'albumartist' => [
                    "id" => (string) $song->albumartist,
                    "name" => $song->get_album_artist_fullname()
                ]
            ];

            $ourSong['disk']                  = $song->disk;
            $ourSong['track']                 = $song->track;
            $ourSong['filename']              = $song->file;
            $ourSong['tag']                   = self::tags_array($song->get_tags());
            $ourSong['playlisttrack']         = $playlist_track;
            $ourSong['time']                  = (int) $song->time;
            $ourSong['year']                  = (int) $song->year;
            $ourSong['bitrate']               = (int) $songBitrate;
            $ourSong['rate']                  = (int) $song->rate;
            $ourSong['mode']                  = $song->mode;
            $ourSong['mime']                  = $songMime;
            $ourSong['url']                   = $play_url;
            $ourSong['size']                  = (int)$song->size;
            $ourSong['mbid']                  = $song->mbid;
            $ourSong['album_mbid']            = $song->get_album_mbid();
            $ourSong['artist_mbid']           = $song->get_artist_mbid();
            $ourSong['albumartist_mbid']      = $song->get_album_mbid();
            $ourSong['art']                   = $art_url;
            $ourSong['flag']                  = (!$flag->get_flag($user->getId()) ? 0 : 1);
            $ourSong['preciserating']         = $user_rating;
            $ourSong['rating']                = $user_rating;
            $ourSong['averagerating']         = ($rating->get_average_rating() ?? null);
            $ourSong['playcount']             = (int) $song->played;
            $ourSong['catalog']               = $song->getCatalogId();
            $ourSong['composer']              = $song->composer;
            $ourSong['channels']              = $song->channels;
            $ourSong['comment']               = $song->comment;
            $ourSong['license']               = $licenseLink;
            $ourSong['publisher']             = $song->label;
            $ourSong['language']              = $song->language;
            $ourSong['replaygain_album_gain'] = $song->replaygain_album_gain;
            $ourSong['replaygain_album_peak'] = $song->replaygain_album_peak;
            $ourSong['replaygain_track_gain'] = $song->replaygain_track_gain;
            $ourSong['replaygain_track_peak'] = $song->replaygain_track_peak;
            $ourSong['genre']                 = self::tags_array($song->get_tags(), true);

            /** @var Metadata $metadata */
            foreach ($song->getMetadata() as $metadata) {
                $field = $metadata->getField();

                if ($field !== null) {
                    $meta_name = str_replace([' ', '(', ')', '/', '\\', '#'], '_', $field->getName());

                    $ourSong[$meta_name] = $metadata->getData();
                }
            }

            $JSON[] = $ourSong;
        } // end foreach

        if ($encode) {
            return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * videos
     *
     * This builds the JSON document for displaying video objects
     *
     * @param int[]|string[] $videos
     * @param User $user
     * @param string $auth
     * @return string
     */
    public static function videos(array $videos, User $user, string $auth): string
    {
        if ((count($videos) > self::$limit || self::$offset > 0) && self::$limit) {
            $videos = array_slice($videos, self::$offset, self::$limit);
        }

        $JSON = [];
        foreach ($videos as $video_id) {
            $video = new Video((int)$video_id);
            if ($video->isNew()) {
                continue;
            }
            $rating      = new Rating($video->id, 'video');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($video->id, 'video');
            $art_url     = Art::url($video->id, 'video', $auth);
            $JSON[]      = [
                "id" => (string)$video->id,
                "title" => $video->title,
                "mime" => $video->mime,
                "resolution" => $video->get_f_resolution(),
                "size" => (int)$video->size,
                "tag" => self::tags_array($video->get_tags()),
                "time" => (int)$video->time,
                "url" => $video->play_url('', 'api', false, $user->getId(), $user->streamtoken),
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => (string)($rating->get_average_rating() ?? null)
            ];
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * democratic
     *
     * This handles creating an JSON document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param list<array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int}> $object_ids Object IDs
     * @param User $user
     * @param string $auth
     * @return string
     */
    public static function democratic(array $object_ids, User $user, string $auth): string
    {
        $democratic = Democratic::get_current_playlist($user);

        $JSON = [];
        foreach ($object_ids as $row_id => $data) {
            $className = ObjectTypeToClassNameMapper::map($data['object_type']->value);
            /** @var Song $song */
            $song = new $className($data['object_id']);
            if ($song->isNew()) {
                continue;
            }
            $song->fill_ext_info();

            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $art_url     = Art::url($song->album, 'album', $auth);
            $songMime    = $song->mime;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);

            $JSON[] = [
                "id" => (string)$song->id,
                "title" => $song->title,
                "artist" => [
                    "id" => (string)$song->artist,
                    "name" => $song->get_artist_fullname()
                ],
                "album" => [
                    "id" => (string)$song->album,
                    "name" => $song->get_album_fullname()
                ],
                "tag" => self::tags_array($song->get_tags()),
                "track" => (int)$song->track,
                "time" => (int)$song->time,
                "mime" => $songMime,
                "url" => $play_url,
                "size" => (int)$song->size,
                "art" => $art_url,
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => ($rating->get_average_rating() ?? null),
                "vote" => $democratic->get_vote($row_id),
                "genre" => self::tags_array($song->get_tags(), true)
            ];
        } // end foreach

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * user
     *
     * This handles creating an JSON document for a user
     */
    public static function user(User $user, bool $fullinfo): string
    {
        $JSON = [];
        if ($fullinfo) {
            $JSON['user'] = [
                "id" => (string) $user->getId(),
                "username" => $user->username,
                "auth" => $user->apikey,
                "email" => $user->email,
                "access" => (string) $user->access,
                "fullname_public" => (string) $user->fullname_public,
                "validation" => $user->validation,
                "disabled" => (string) $user->disabled,
                "create_date" => $user->create_date,
                "last_seen" => $user->last_seen,
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city
            ];
        } else {
            $JSON['user'] = [
                "id" => (string) $user->getId(),
                "username" => $user->username,
                "create_date" => $user->create_date,
                "last_seen" => $user->last_seen,
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city
            ];
        }

        if ($user->fullname_public) {
            $JSON['user']['fullname'] = $user->fullname;
        }

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * users
     *
     * This handles creating an JSON document for a user list
     *
     * @param list<int|string> $users    User identifier list
     */
    public static function users(array $users): string
    {
        $JSON       = [];
        $user_array = [];
        foreach ($users as $user_id) {
            $user         = new User((int)$user_id);
            $user_array[] = [
                "id" => (string)$user_id,
                "username" => $user->username
            ];
        } // end foreach

        // return a user object
        $JSON[] = ["user" => $user_array];

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * shouts
     *
     * This handles creating an JSON document for a shout list
     *
     * @param Traversable<Shoutbox> $shouts Shout identifier list
     */
    public static function shouts(Traversable $shouts): string
    {
        $JSON = [];
        /** @var Shoutbox $shout */
        foreach ($shouts as $shout) {
            $user = $shout->getUser();

            $JSON[] = [
                'id' => (string) $shout->getId(),
                'date' => $shout->getDate()->getTimestamp(),
                'text' => $shout->getText(),
                'user' => [
                    'id' => (string) ($user?->getId() ?? 0),
                    'username' => $user?->getUsername() ?? ''
                ]
            ];
        }

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * timeline
     *
     * This handles creating an JSON document for an activity list
     *
     * @param int[] $activities    Activity identifier list
     */
    public static function timeline(array $activities): string
    {
        $JSON             = [];
        $JSON['timeline'] = []; // To match the XML style, IMO kinda uselesss
        foreach ($activities as $activity_id) {
            $activity     = new Useractivity($activity_id);
            $user         = new User($activity->user);
            $user_array   = [];
            $user_array[] = [
                "id" => (string)$user->getId(),
                "username" => $user->username
            ];
            $objArray = [
                "id" => (string) $activity_id,
                "date" => $activity->activity_date,
                "object_type" => $activity->object_type,
                "object_id" => (string)$activity->object_id,
                "action" => $activity->action,
                "user" => $user_array
            ];

            $JSON['timeline'][] = $objArray;
        }

        return json_encode($JSON, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
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
     * @deprecated Inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
    }
}
