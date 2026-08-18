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

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Wanted\MissingArtistFinderInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

final readonly class SearchAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private MissingArtistFinderInterface $missingArtistFinder,
        private LabelRepositoryInterface $labelRepository,
    ) {}

    private static function matchRank(?string $value, string $search): int
    {
        return ($value !== null && stripos($value, $search) === 0) ? 0 : 1;
    }

    /**
     * Rank already-loaded objects so search-term prefix matches sort first, then trims to $limit.
     * @template T of object
     * @param T[] $objects
     * @param callable(T): ?string $textExtractor
     * @return T[]
     */
    private static function rankBySearchMatch(array $objects, callable $textExtractor, string $search, int $limit): array
    {
        usort(
            $objects,
            static fn(object $a, object $b): int => self::matchRank($textExtractor($a), $search) <=> self::matchRank($textExtractor($b), $search)
        );

        return array_slice($objects, 0, $limit);
    }

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'search':
                $web_path = AmpConfig::get_web_path();

                $album_group = ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP));
                $search      = htmlspecialchars_decode(($_REQUEST['search'] ?? ''));
                $target      = $_REQUEST['target'] ?? '';
                $limit       = (int) ($_REQUEST['limit'] ?? 5);

                if ($target == 'anywhere' || $target == 'artist') {
                    $searchreq = [
                        'limit' => $limit * 4,
                        'type' => 'artist',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '0', // Contains (a superset of 'starts with')
                        'rule_1' => 'title',
                        'weight' => true,
                    ];
                    $artistIds = Search::run($searchreq);
                    Artist::build_cache($artistIds);
                    $artists = self::rankBySearchMatch(
                        array_map(static fn(int $artistid): Artist => new Artist($artistid), $artistIds),
                        static fn(Artist $artist): ?string => $artist->name,
                        $search,
                        $limit
                    );

                    foreach ($artists as $artist) {
                        $results[] = [
                            'type' => T_('Artists'),
                            'id' => $artist->id,
                            'link' => $web_path . '/artists.php?action=show&artist=' . $artist->id,
                            'label' => scrub_out($artist->get_fullname()),
                            'value' => scrub_out($artist->get_fullname()),
                            'rels' => '',
                            'image' => (string) Art::url($artist->id, 'artist', null, 10),
                        ];
                    }
                }

                if (($target == 'anywhere' && $album_group) || $target == 'album') {
                    $searchreq = [
                        'limit' => $limit * 4,
                        'type' => 'album',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '0', // Contains (a superset of 'starts with')
                        'rule_1' => 'title',
                        'weight' => true,
                    ];
                    $albumIds = Search::run($searchreq);
                    Album::build_cache($albumIds);
                    $albums = self::rankBySearchMatch(
                        array_map(static fn(int $albumid): Album => new Album($albumid), $albumIds),
                        static fn(Album $album): ?string => $album->name,
                        $search,
                        $limit
                    );

                    foreach ($albums as $album) {
                        $results[] = [
                            'type' => T_('Albums'),
                            'id' => $album->id,
                            'link' => $web_path . '/albums.php?action=show&album=' . $album->id,
                            'label' => scrub_out($album->get_fullname()),
                            'value' => scrub_out($album->get_fullname()),
                            'rels' => scrub_out($album->get_parent_fullname()),
                            'image' => (string) Art::url($album->id, 'album', null, 10),
                        ];
                    }
                }

                if (($target == 'anywhere' && !$album_group) || $target == 'album_disk') {
                    $searchreq = [
                        'limit' => $limit * 4,
                        'type' => 'album_disk',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '0', // Contains (a superset of 'starts with')
                        'rule_1' => 'title',
                        'weight' => true,
                    ];
                    $albumDiskIds = Search::run($searchreq);
                    AlbumDisk::build_cache($albumDiskIds);
                    $albumdisks = self::rankBySearchMatch(
                        array_map(static fn(int $albumdiskid): AlbumDisk => new AlbumDisk($albumdiskid), $albumDiskIds),
                        static fn(AlbumDisk $albumdisk): ?string => $albumdisk->name,
                        $search,
                        $limit
                    );

                    foreach ($albumdisks as $albumdisk) {
                        $results[] = [
                            'type' => T_('Albums'),
                            'id' => $albumdisk->id,
                            'link' => $web_path . '/albums.php?action=show_disk&album_disk=' . $albumdisk->id,
                            'label' => scrub_out($albumdisk->get_fullname()),
                            'value' => scrub_out($albumdisk->get_fullname()),
                            'rels' => scrub_out($albumdisk->get_parent_fullname()),
                            'image' => (string) Art::url($albumdisk->album_id, 'album', null, 10),
                        ];
                    }
                }

                if ($target == 'anywhere' || $target == 'title') {
                    $searchreq = [
                        'limit' => $limit * 4,
                        'type' => 'song',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '0', // Contains (a superset of 'starts with')
                        'rule_1' => 'title',
                        'weight' => true,
                    ];
                    $songIds = Search::run($searchreq);
                    Song::build_cache($songIds);
                    $songs = self::rankBySearchMatch(
                        array_map(static fn(int $songid): Song => new Song($songid), $songIds),
                        static fn(Song $song): ?string => $song->title,
                        $search,
                        $limit
                    );

                    $show_song_art = AmpConfig::get('show_song_art', false);
                    foreach ($songs as $song) {
                        $has_art    = Art::has_db($song->id, 'song');
                        $art_object = ($show_song_art && $has_art) ? $song->id : $song->album;
                        $art_type   = ($show_song_art && $has_art) ? 'song' : 'album';
                        $results[]  = [
                            'type' => T_('Songs'),
                            'id' => $song->id,
                            'link' => $web_path . "/song.php?action=show_song&song_id=" . $song->id,
                            'label' => scrub_out($song->title),
                            'value' => scrub_out($song->title),
                            'rels' => scrub_out($song->get_parent_fullname()),
                            'image' => (string) Art::url($art_object, $art_type, null, 10),
                            'album' => $song->get_album_fullname(),
                        ];
                    }
                }

                if ($target == 'anywhere' || $target == 'playlist') {
                    $searchreq = [
                        'limit' => $limit * 4,
                        'type' => 'playlist',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '0', // Contains (a superset of 'starts with')
                        'rule_1' => 'title',
                        'weight' => true,
                    ];
                    $playlistIds = Search::run($searchreq);
                    Playlist::build_cache($playlistIds);
                    $playlists = self::rankBySearchMatch(
                        array_map(static fn(int $playlistid): Playlist => new Playlist($playlistid), $playlistIds),
                        static fn(Playlist $playlist): ?string => $playlist->name,
                        $search,
                        $limit
                    );

                    foreach ($playlists as $playlist) {
                        $results[] = [
                            'type' => T_('Playlists'),
                            'id' => $playlist->id,
                            'link' => $web_path . '/playlist.php?action=show&playlist_id=' . $playlist->id,
                            'label' => $playlist->name,
                            'value' => $playlist->get_fullname(),
                            'rels' => '',
                            'image' => '',
                        ];
                    }
                }

                if (($target == 'anywhere' || $target == 'label') && AmpConfig::get('label')) {
                    $searchreq = [
                        'limit' => $limit * 4,
                        'type' => 'label',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '0', // Contains (a superset of 'starts with')
                        'rule_1' => 'title',
                        'weight' => true,
                    ];
                    $labelIds = Search::run($searchreq);
                    Label::build_cache($labelIds);
                    $labels = self::rankBySearchMatch(
                        array_filter(array_map(
                            fn(int $labelid): ?Label => $this->labelRepository->findById($labelid),
                            $labelIds
                        )),
                        static fn(Label $label): ?string => $label->name,
                        $search,
                        $limit
                    );

                    foreach ($labels as $label) {
                        $results[] = [
                            'type' => T_('Labels'),
                            'id' => $label->getId(),
                            'link' => $web_path . '/labels.php?action=show&label=' . $label->getId(),
                            'label' => $label->name,
                            'value' => $label->name,
                            'rels' => '',
                            'image' => (string) Art::url($label->getId(), 'label', null, 10),
                        ];
                    }
                }

                if ($target == 'missing_artist' && AmpConfig::get('wanted')) {
                    $sres  = $this->missingArtistFinder->find($search);
                    $count = 0;
                    foreach ($sres as $artist) {
                        $results[] = [
                            'type' => T_('Missing Artists'),
                            'link' => $web_path . '/artists.php?action=show_missing&mbid=' . $artist['mbid'],
                            'label' => scrub_out($artist['name']),
                            'value' => scrub_out($artist['name']),
                            'rels' => '',
                            'image' => '',
                        ];
                        $count++;

                        if ($count >= $limit) {
                            break;
                        }
                    }
                }

                if ($target == 'user' && AmpConfig::get('sociable')) {
                    $searchreq = [
                        'limit' => $limit * 4,
                        'type' => 'user',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '0', // Contains (a superset of 'starts with')
                        'rule_1' => 'username',
                        'weight' => true,
                    ];
                    $userIds = Search::run($searchreq);
                    User::build_cache($userIds);
                    $users = self::rankBySearchMatch(
                        array_map(static fn(int $user_id): User => new User($user_id), $userIds),
                        static fn(User $user): ?string => $user->username,
                        $search,
                        $limit
                    );

                    foreach ($users as $user) {
                        $avatar    = $user->get_avatar();
                        $results[] = [
                            'type' => T_('Users'),
                            'link' => '',
                            'label' => $user->username,
                            'value' => $user->username,
                            'rels' => '',
                            'image' => $avatar['url'] ?? '',
                        ];
                    }
                }

                break;
            case 'search_random':
                if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
                    return;
                }

                $_SESSION['iframe']['target'] = AmpConfig::get_web_path() . '/stream.php?action=search_random&search_id=' . scrub_out((string) ($_REQUEST['playlist_id'] ?? ''));
                $results['reloader']          = '<script>' . Core::get_reloadutil() . '("' . $_SESSION['iframe']['target'] . '")</script>';
        } // switch on action;

        // We always do this
        echo xoutput_from_array($results);
    }
}
