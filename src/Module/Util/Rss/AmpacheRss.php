<?php

declare(strict_types=0);

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

namespace Ampache\Module\Util\Rss;

use Ampache\Config\AmpConfig;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\User\Authorization\UserKeyGeneratorInterface;
use Ampache\Module\Util\Rss\Surrogate\PlayableItemRssItemAdapter;
use Ampache\Module\Util\Rss\Type\LatestAlbumFeed;
use Ampache\Module\Util\Rss\Type\LatestArtistFeed;
use Ampache\Module\Util\Rss\Type\LatestShoutFeed;
use Ampache\Module\Util\Rss\Type\NowPlayingFeed;
use Ampache\Module\Util\Rss\Type\RecentlyPlayedFeed;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;

final class AmpacheRss implements AmpacheRssInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ShoutRepositoryInterface $shoutRepository,
        private readonly ShoutObjectLoaderInterface $shoutObjectLoader,
        private readonly RssPodcastBuilderInterface $rssPodcastBuilder,
        private readonly ModelFactoryInterface $modelFactory,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
    ) {
    }

    /** @var list<string> */
    private const RSS_TYPES = [
        'now_playing',
        'recently_played',
        'latest_album',
        'latest_artist',
        'latest_shout',
        'podcast'
    ];

    /**
     * get_xml
     * This returns the xmldocument for the current rss type, it calls a sub function that gathers the data
     * and then uses the xmlDATA class to build the document
     *
     * @param null|array{object_type: string, object_id: int} $params
     */
    public function get_xml(
        string $rssToken,
        string $type,
        ?array $params = null
    ): string {
        $type = self::validate_type($type);

        $user = $this->userRepository->getByRssToken($rssToken);
        if ($user === null) {
            $user = new User(User::INTERNAL_SYSTEM_USER_ID);
        }

        if ($type === "podcast") {
            if ($params !== null) {
                $item = $this->libraryItemLoader->load(
                    LibraryItemEnum::from((string) $params['object_type']),
                    (int) $params['object_id'],
                    [Album::class, Artist::class, Podcast::class]
                );

                if ($item !== null) {
                    return $this->rssPodcastBuilder->build(
                        new PlayableItemRssItemAdapter(
                            $this->libraryItemLoader,
                            $this->modelFactory,
                            $item,
                            $user
                        ),
                        $user
                    );
                }
            }

            return '';
        } else {
            $functions = [
                'now_playing' => function (): NowPlayingFeed {
                    return new NowPlayingFeed();
                },
                'recently_played' => function () use ($user): RecentlyPlayedFeed {
                    return new RecentlyPlayedFeed($user->getId());
                },
                'latest_album' => function () use ($rssToken): LatestAlbumFeed {
                    return new LatestAlbumFeed(
                        $this->userRepository,
                        $rssToken
                    );
                },
                'latest_artist' => function () use ($rssToken): LatestArtistFeed {
                    return new LatestArtistFeed(
                        $this->userRepository,
                        $rssToken
                    );
                },
                'latest_shout' => function (): LatestShoutFeed {
                    return new LatestShoutFeed(
                        $this->shoutRepository,
                        $this->shoutObjectLoader
                    );
                },
            ];

            return $functions[$type]()->handle();
        }
    }

    /**
     * validate_type
     * this returns a valid type for an rss feed, if the specified type is invalid it returns a default value
     */
    private static function validate_type(string $rsstype): string
    {
        if (!in_array($rsstype, self::RSS_TYPES)) {
            return 'now_playing';
        }

        return $rsstype;
    }

    /**
     * get_display
     * This dumps out some html and an icon for the type of rss that we specify
     * @param string $type
     * @param int $user_id
     * @param string $title
     * @param array<string, string>|null $params
     */
    public static function get_display($type = 'now_playing', $user_id = -1, $title = '', $params = null): string
    {
        // Default to Now Playing
        $type = self::validate_type($type);

        $strparams = "";
        if ($params != null && is_array($params)) {
            foreach ($params as $key => $value) {
                $strparams .= "&" . scrub_out($key) . "=" . scrub_out($value);
            }
        }

        $rsstoken = "";
        $user     = new User($user_id);
        if ($user->id > 0) {
            $rsstoken = "&rsstoken=" . $user->getRssToken();
        }

        $string = '<a class="nohtml" href="' . AmpConfig::get('web_path') . '/rss.php?type=' . $type . $rsstoken . $strparams . '" target="_blank">' . Ui::get_icon('feed', T_('RSS Feed'));
        if (!empty($title)) {
            $string .= ' &nbsp;' . $title;
        }
        $string .= '</a>';

        return $string;
    }

    // type specific functions below, these are called semi-dynamically based on the current type //

    /**
     * load_now_playing
     * This loads in the Now Playing information. This is just the raw data with key=>value pairs that could be turned
     * into an xml document if we so wished
     * @return list<array{
     *  title: string,
     *  link: null|string,
     *  description: string,
     *  comments: string,
     *  pubDate: non-empty-string
     * }>
     */
    private function load_now_playing(): array
    {
        $data = Stream::get_now_playing();

        $results    = array();
        $format     = (string) (AmpConfig::get('rss_format') ?? '%t - %a - %A');
        $string_map = array(
            '%t' => 'title',
            '%a' => 'artist',
            '%A' => 'album'
        );
        foreach ($data as $element) {
            /** @var Song|Video $media */
            $media = $element['media'];
            /** @var User $client */
            $client      = $element['client'];
            $title       = $format;
            $description = $format;
            foreach ($string_map as $search => $replace) {
                switch ($replace) {
                    case 'title':
                        $text = (string)$media->get_fullname();
                        break;
                    case 'artist':
                        $text = ($media instanceof Song)
                            ? (string)$media->get_artist_fullname()
                            : '';
                        break;
                    case 'album':
                        $text = ($media instanceof Song)
                            ? (string)$media->get_album_fullname($media->album, true)
                            : '';
                        break;
                    default:
                        $text = '';
                }
                $title       = str_replace($search, $text, $title);
                $description = str_replace($search, $text, $description);
            }
            $xml_array = array(
                'title' => str_replace(' - - ', ' - ', $title),
                'link' => $media->get_link(),
                'description' => str_replace('<p>Artist: </p><p>Album: </p>', '', $description),
                'comments' => $client->get_fullname() . ' - ' . $element['agent'],
                'pubDate' => date("r", (int)$element['expire'])
            );
            $results[] = $xml_array;
        } // end foreach

        return $results;
    }

    /**
     * pubdate_now_playing
     * this is the pub date we should use for the Now Playing information,
     * this is a little specific as it uses the 'newest' expire we can find
     */
    private function pubdate_now_playing(): ?int
    {
        // Little redundent, should be fixed by an improvement in the get_now_playing stuff
        $data    = Stream::get_now_playing();
        $element = array_shift($data);

        return $element['expire'] ?? null;
    }

    /**
     * load_recently_played
     * This loads in the Recently Played information and formats it up real nice like
     * @return list<array{
     *  title: string,
     *  link: string,
     *  description: string,
     *  comments: string,
     *  pubDate: non-empty-string
     * }>
     */
    private function load_recently_played(int $user_id, int &$pub_date): array
    {
        $results = array();
        $data    = Stats::get_recently_played($user_id, 'stream', 'song');

        foreach ($data as $item) {
            $client = new User($item['user']);
            $song   = new Song($item['object_id']);
            $row_id = ($item['user'] > 0) ? (int) $item['user'] : -1;

            $has_allowed_recent = (bool) $item['user_recent'];
            $is_allowed_recent  = ($user_id > 0) ? $user_id == $row_id : $has_allowed_recent;
            if ($song->enabled && $is_allowed_recent) {
                $description = '<p>' . T_('User') . ': ' . $client->username . '</p><p>' . T_('Title') . ': ' . $song->get_fullname() . '</p><p>' . T_('Artist') . ': ' . $song->get_artist_fullname() . '</p><p>' . T_('Album') . ': ' . $song->get_album_fullname() . '</p><p>' . T_('Play date') . ': ' . get_datetime($item['date']) . '</p>';

                $xml_array = array(
                    'title' => $song->get_fullname() . ' - ' . $song->get_artist_fullname() . ' - ' . $song->get_album_fullname(),
                    'link' => str_replace('&amp;', '&', (string)$song->get_link()),
                    'description' => $description,
                    'comments' => (string)$client->username,
                    'pubDate' => date("r", (int)$item['date'])
                );
                $results[] = $xml_array;
                if ($pub_date == 0) {
                    $pub_date = (int)$item['date'];
                }
            }
        } // end foreach

        return $results;
    }

    /**
     * load_latest_album
     * This loads in the latest added albums
     * @return list<array{
     *  title: string,
     *  link: string,
     *  description: string,
     *  image: string,
     *  comments: string,
     *  pubDate: non-empty-string
     * }>
     */
    private function load_latest_album(string $rsstoken): array
    {
        $user    = $rsstoken !== '' ? $this->userRepository->getByRssToken($rsstoken) : null;
        $user_id = $user->id ?? 0;
        $ids     = Stats::get_newest('album', 10, 0, 0, $user_id);

        $results = array();

        foreach ($ids as $albumid) {
            $album = new Album($albumid);

            $xml_array = array(
                'title' => $album->get_fullname(),
                'link' => $album->get_link(),
                'description' => $album->get_artist_fullname() . ' - ' . $album->get_fullname(true),
                'image' => (string)Art::url($album->id, 'album', null, 2),
                'comments' => '',
                'pubDate' => date("c", $album->addition_time)
            );
            $results[] = $xml_array;
        } // end foreach

        return $results;
    }

    /**
     * load_latest_artist
     * This loads in the latest added artists
     * @return list<array{
     *  title: null|string,
     *  link: string,
     *  description: null|string,
     *  image: string,
     *  comments: string,
     *  pubDate: string
     * }>
     */
    private function load_latest_artist(string $rsstoken): array
    {
        $user    = $rsstoken !== '' ? $this->userRepository->getByRssToken($rsstoken) : null;
        $user_id = $user->id ?? 0;
        $ids     = Stats::get_newest('artist', 10, 0, 0, $user_id);

        $results = array();

        foreach ($ids as $artistid) {
            $artist = new Artist($artistid);
            $artist->format();

            $xml_array = array(
                'title' => $artist->get_fullname(),
                'link' => $artist->get_link(),
                'description' => $artist->summary,
                'image' => (string)Art::url($artist->id, 'artist', null, 2),
                'comments' => '',
                'pubDate' => ''
            );
            $results[] = $xml_array;
        } // end foreach

        return $results;
    }

    /**
     * load_latest_shout
     * This loads in the latest added shouts
     * @return list<array{
     *  title: string,
     *  link: string,
     *  description: string,
     *  image: string,
     *  comments: string,
     *  pubDate: non-empty-string
     * }>
     */
    private function load_latest_shout(): array
    {
        $shouts = $this->shoutRepository->getTop(10);

        $results = array();

        /** @var Shoutbox $shout */
        foreach ($shouts as $shout) {
            $object = $this->shoutObjectLoader->loadByShout($shout);

            if ($object !== null) {
                $object->format();
                $user = new User($shout->getUserId());
                if ($user->isNew()) {
                    continue;
                }
                $user->format();

                $xml_array = array(
                    'title' => $user->getUsername() . ' ' . T_('on') . ' ' . $object->get_fullname(),
                    'link' => $object->get_link(),
                    'description' => $shout->getText(),
                    'image' => (string)Art::url($shout->getObjectId(), $shout->getObjectType()->value, null, 2),
                    'comments' => '',
                    'pubDate' => $shout->getDate()->format(DATE_ATOM)
                );
                $results[] = $xml_array;
            }
        } // end foreach

        return $results;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getUserKeyGenerator(): UserKeyGeneratorInterface
    {
        global $dic;

        return $dic->get(UserKeyGeneratorInterface::class);
    }
}
