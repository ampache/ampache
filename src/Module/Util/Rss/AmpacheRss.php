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
use Ampache\Module\Playback\Stream;
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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
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
}
