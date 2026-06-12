<?php

declare(strict_types=0);

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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playlist\Search\AlbumDiskSearch;
use Ampache\Module\Playlist\Search\AlbumSearch;
use Ampache\Module\Playlist\Search\ArtistSearch;
use Ampache\Module\Playlist\Search\LabelSearch;
use Ampache\Module\Playlist\Search\PlaylistSearch;
use Ampache\Module\Playlist\Search\PodcastEpisodeSearch;
use Ampache\Module\Playlist\Search\PodcastSearch;
use Ampache\Module\Playlist\Search\SearchInterface;
use Ampache\Module\Playlist\Search\SongSearch;
use Ampache\Module\Playlist\Search\TagSearch;
use Ampache\Module\Playlist\Search\UserSearch;
use Ampache\Module\Playlist\Search\VideoSearch;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\MetadataFieldRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use JsonException;

/**
 * Search-related voodoo.  Beware tentacles.
 */
class Search extends playlist_object
{
    protected const string DB_TABLENAME = 'search';

    /** @var array<string, array<int, array{name: string, description: string, sql: string, preg_match?: string|array{string, string}, preg_replace?:string|array{string, string}}>> BASE_TYPES */
    private const array BASE_TYPES = [
        'numeric' => [
            ['name' => 'gte', 'description' => 'is greater than or equal to', 'sql' => '>='],
            ['name' => 'lte', 'description' => 'is less than or equal to', 'sql' => '<='],
            ['name' => 'equal', 'description' => 'equals', 'sql' => '<=>'],
            ['name' => 'ne', 'description' => 'does not equal', 'sql' => '<>'],
            ['name' => 'gt', 'description' => 'is greater than', 'sql' => '>'],
            ['name' => 'lt', 'description' => 'is less than', 'sql' => '<'],
        ],

        'is_true' => [
            ['name' => 'true', 'description' => 'is true', 'sql' => '1'],
        ],

        'boolean' => [
            ['name' => 'true', 'description' => 'is true', 'sql' => '1'],
            ['name' => 'false', 'description' => 'is false', 'sql' => '0'],
        ],

        'text' => [
            ['name' => 'contain', 'description' => 'contains', 'sql' => 'LIKE', 'preg_match' => ['/^/', '/$/'], 'preg_replace' => ['%', '%']],
            ['name' => 'notcontain', 'description' => 'does not contain', 'sql' => 'NOT LIKE', 'preg_match' => ['/^/', '/$/'], 'preg_replace' => ['%', '%']],
            ['name' => 'start', 'description' => 'starts with', 'sql' => 'LIKE', 'preg_match' => '/$/', 'preg_replace' => '%'],
            ['name' => 'end', 'description' => 'ends with', 'sql' => 'LIKE', 'preg_match' => '/^/', 'preg_replace' => '%'],
            ['name' => 'equal', 'description' => 'is', 'sql' => '='],
            ['name' => 'not equal', 'description' => 'is not', 'sql' => '!='],
            ['name' => 'sounds', 'description' => 'sounds like', 'sql' => 'SOUNDS LIKE'],
            ['name' => 'notsounds', 'description' => 'does not sound like', 'sql' => 'NOT SOUNDS LIKE'],
            ['name' => 'regexp', 'description' => 'matches regular expression', 'sql' => 'REGEXP'],
            ['name' => 'notregexp', 'description' => 'does not match regular expression', 'sql' => 'NOT REGEXP'],
        ],

        'tags' => [
            ['name' => 'contain', 'description' => 'contains', 'sql' => 'LIKE', 'preg_match' => ['/^/', '/$/'], 'preg_replace' => ['%', '%']],
            ['name' => 'notcontain', 'description' => 'does not contain', 'sql' => 'NOT LIKE', 'preg_match' => ['/^/', '/$/'], 'preg_replace' => ['%', '%']],
            ['name' => 'start', 'description' => 'starts with', 'sql' => 'LIKE', 'preg_match' => '/$/', 'preg_replace' => '%'],
            ['name' => 'end', 'description' => 'ends with', 'sql' => 'LIKE', 'preg_match' => '/^/', 'preg_replace' => '%'],
            ['name' => 'equal', 'description' => 'is', 'sql' => '>'],
            ['name' => 'not equal', 'description' => 'is not', 'sql' => '='],
        ],

        'boolean_numeric' => [
            ['name' => 'equal', 'description' => 'is', 'sql' => '<=>'],
            ['name' => 'ne', 'description' => 'is not', 'sql' => '<>'],
        ],

        'boolean_subsearch' => [
            ['name' => 'equal', 'description' => 'is', 'sql' => ''],
            ['name' => 'ne', 'description' => 'is not', 'sql' => 'NOT'],
        ],

        'date' => [
            ['name' => 'lt', 'description' => 'before', 'sql' => '<'],
            ['name' => 'gt', 'description' => 'after', 'sql' => '>'],
        ],

        'days' => [
            ['name' => 'lt', 'description' => 'before (x) days ago', 'sql' => '<'],
            ['name' => 'gt', 'description' => 'after (x) days ago', 'sql' => '>'],
        ],

        'recent_played' => [
            ['name' => 'ply', 'description' => 'Limit', 'sql' => '`date`'],
        ],

        'recent_added' => [
            ['name' => 'add', 'description' => 'Limit', 'sql' => '`addition_time`'],
        ],

        'recent_updated' => [
            ['name' => 'upd', 'description' => 'Limit', 'sql' => '`update_time`'],
        ],

        'user_numeric' => [
            ['name' => 'love', 'description' => 'has loved', 'sql' => 'userflag'],
            ['name' => '5star', 'description' => 'has rated 5 stars', 'sql' => '`rating` = 5'],
            ['name' => '4star', 'description' => 'has rated 4 stars', 'sql' => '`rating` = 4'],
            ['name' => '3star', 'description' => 'has rated 3 stars', 'sql' => '`rating` = 3'],
            ['name' => '2star', 'description' => 'has rated 2 stars', 'sql' => '`rating` = 2'],
            ['name' => '1star', 'description' => 'has rated 1 star', 'sql' => '`rating` = 1'],
            ['name' => 'unrated', 'description' => 'has not rated', 'sql' => 'unrated'],
        ],
    ];

    public const array VALID_TYPES = [
        'album_artist',
        'album_disk',
        'album',
        'artist',
        'genre',
        'label',
        'playlist',
        'podcast_episode',
        'podcast',
        'song_artist',
        'song',
        'tag',
        'user',
        'video',
    ];

    public ?string $type = 'public'; // override playlist_object

    /** @var array<int, array<int, mixed>> $rules */
    public array $rules = []; // rules used to run a search (User chooses rules from available types for that object). JSON string to decoded to array

    public ?string $logic_operator = 'and';

    public ?int $random = 0;

    public int $limit = 0;

    public ?int $catalog_id = null; // filter a search for a single catalog

    public string $objectType; // the type of object you want to return (self::VALID_TYPES)

    public User $search_user; // user running the search

    private SearchInterface $searchType;

    /** @var string[] $stars */
    private array $stars; // generate sql for the object type (Ampache\Module\Playlist\Search\*)

    private string $order_by;

    /**
     * constructor
     * @param int $search_id // saved searches have rules already
     * @param string $object_type // map to self::VALID_TYPES
     */
    public function __construct(
        int $search_id = 0,
        string $object_type = 'song',
        ?User $user = null,
    ) {
        $this->search_user = ($user instanceof User)
            ? $user
            : User::get_from_global() ?? new User(-1);

        $this->objectType = (in_array(strtolower($object_type), self::VALID_TYPES))
            ? strtolower($object_type)
            : 'song';
        $this->user = $this->search_user->id ?: -1; // define a user for live searches (overwriten if saved before)
        if ($search_id > 0) {
            $info = $this->get_info($search_id, static::DB_TABLENAME);
            foreach ($info as $key => $value) {
                if ($key == 'basetypes' || $key == 'types') {
                    continue;
                }
                if ($key == 'rules') {
                    try {
                        $this->rules = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException $error) {
                        debug_event(self::class, "Can't decode key 'rules'. Not a valid json. " . $error, 1);
                        $this->rules = [];
                    }
                } else {
                    $this->$key = $value;
                }
            }

            // make sure saved rules match the correct names
            $rule_count = 0;
            foreach ($this->rules as $rule) {
                $this->rules[$rule_count][0] = $this->_set_rule_name($rule[0]);
                ++$rule_count;
            }

            // When loading a search use the owner ID for the search
            if ($this->user > 0) {
                $this->search_user = new User($this->user);
            }
        }

        $this->stars = [
            T_('0 Stars'),
            T_('1 Star'),
            T_('2 Stars'),
            T_('3 Stars'),
            T_('4 Stars'),
            T_('5 Stars'),
        ];

        switch ($this->objectType) {
            case 'album':
                $this->searchType = new AlbumSearch();
                $this->order_by   = '`album`.`name`';
                break;
            case 'album_disk':
                $this->searchType = new AlbumDiskSearch();
                $this->order_by   = '`album`.`name`';
                break;
            case 'artist':
            case 'album_artist':
            case 'song_artist':
                $this->searchType = new ArtistSearch($this->objectType);
                $this->order_by   = '`artist`.`name`';
                $this->objectType = 'artist';
                break;
            case 'label':
                $this->searchType = new LabelSearch();
                $this->order_by   = '`label`.`name`';
                break;
            case 'playlist':
                $this->searchType = new PlaylistSearch();
                $this->order_by   = '`playlist`.`name`';
                break;
            case 'podcast':
                $this->searchType = new PodcastSearch();
                $this->order_by   = '`podcast`.`title`';
                break;
            case 'podcast_episode':
                $this->searchType = new PodcastEpisodeSearch();
                $this->order_by   = '`podcast_episode`.`pubdate` DESC';
                break;
            case 'song':
                $this->searchType = new SongSearch();
                $this->order_by   = '`song`.`file`';
                break;
            case 'tag':
            case 'genre':
                $this->searchType = new TagSearch();
                $this->order_by   = '`tag`.`name`';
                break;
            case 'user':
                $this->searchType = new UserSearch();
                $this->order_by   = '`user`.`username`';
                break;
            case 'video':
                $this->searchType = new VideoSearch();
                $this->order_by   = '`video`.`file`';
                break;
        }
    }

    /**
     * _set_basetypes
     *
     * Function called during construction to set the different types and rules for search
     * @return array<string, array<int, array{name: string, description: string, sql: string, preg_match?: string|array{string, string}, preg_replace?:string|array{string, string}}>>
     */
    public function get_basetypes(bool $translate = false): array
    {
        $basetypes = self::BASE_TYPES;
        if ($translate) {
            foreach ($basetypes as $key => $group) {
                foreach ($group as $typeKey => $typeValue) {
                    $basetypes[$key][$typeKey]['description'] =
                        T_($typeValue['description']);
                }
            }
        }

        $basetypes['multiple'] = array_merge($basetypes['text'], $basetypes['numeric']);

        return $basetypes;
    }

    /**
     * get_rule_types
     *
     * Return rule list for the current search type, This is used for display purposes only so it's still translated
     * @return array<int, array<string, mixed>>
     */
    public function get_rule_types(): array
    {
        $ruleTypes = [];
        switch ($this->objectType) {
            case 'album':
            case 'album_disk':
                $ruleTypes = $this->_get_types_album();
                break;
            case 'artist':
            case 'album_artist':
            case 'song_artist':
                $ruleTypes = $this->_get_types_artist();
                break;
            case 'label':
                $ruleTypes = $this->_get_types_label();
                break;
            case 'playlist':
                $ruleTypes = $this->_get_types_playlist();
                break;
            case 'podcast':
                $ruleTypes = $this->_get_types_podcast();
                break;
            case 'podcast_episode':
                $ruleTypes = $this->_get_types_podcast_episode();
                break;
            case 'song':
                $ruleTypes = $this->_get_types_song();
                break;
            case 'tag':
            case 'genre':
                $ruleTypes = $this->_get_types_tag();
                break;
            case 'user':
                $ruleTypes = $this->_get_types_user();
                break;
            case 'video':
                $ruleTypes = $this->_get_types_video();
                break;
        }

        return $ruleTypes;
    }

    /**
     * _get_rule_numeric
     *
     * Generic integer searches rules
     * @return array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     widget: string[],
     *     title:string
     * }
     */
    private function _get_rule_numeric(string $name, string $label, string $type = 'numeric', string $group = ''): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => [
                'input',
                'number',
            ],
            'title' => $group,
        ];
    }

    /**
     * _get_rule_date
     *
     * Generic date searches rules
     * @return array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     widget: string[],
     *     title:string
     * }
     */
    private function _get_rule_date(string $name, string $label, string $group = ''): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => 'date',
            'widget' => [
                'input',
                'datetime-local',
            ],
            'title' => $group,
        ];
    }

    /**
     * _get_rule_text
     *
     * Generic text rules
     * @return array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     widget: string[],
     *     title:string
     * }
     */
    private function _get_rule_text(string $name, string $label, string $group = ''): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => 'text',
            'widget' => [
                'input',
                'text',
            ],
            'title' => $group,
        ];
    }

    /**
     * _get_rule_select
     *
     * Generic rule to select from a list
     * @param string[] $array
     * @return array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     widget: array{
     *         string,
     *         string[]
     *     },
     *     title:string
     * }
     */
    private function _get_rule_select(string $name, string $label, string $type, array $array, string $group = ''): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => [
                'select',
                $array,
            ],
            'title' => $group,
        ];
    }

    /**
     * _get_rule_boolean
     *
     * True or false generic searches
     * @return array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     widget: string[],
     *     title:string
     * }
     */
    private function _get_rule_boolean(string $name, string $label, string $type = 'boolean', string $group = ''): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'widget' => [
                'input',
                'hidden',
            ],
            'title' => $group,
        ];
    }

    /**
     * _get_types_song
     *
     * this is where all the available rules for songs are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_song(): array
    {
        $rule_type = [];

        $rule_type[] = $this->_get_rule_text('anywhere', T_('Any searchable text'));

        $rule_type[] = $this->_get_rule_boolean('none', T_('None'), 'is_true');

        $t_song_data = T_('Song Data');
        $rule_type[] = $this->_get_rule_text('title', T_('Title'), $t_song_data);
        $rule_type[] = $this->_get_rule_text('album', T_('Album Title'), $t_song_data);
        $rule_type[] = $this->_get_rule_text('song_artist', T_('Song Artist'), $t_song_data);
        $rule_type[] = $this->_get_rule_text('album_artist', T_('Album Artist'), $t_song_data);
        $rule_type[] = $this->_get_rule_text('composer', T_('Composer'), $t_song_data);
        $rule_type[] = $this->_get_rule_numeric('track', T_('Track'), 'numeric', $t_song_data);
        $rule_type[] = $this->_get_rule_numeric('year', T_('Year'), 'numeric', $t_song_data);
        $rule_type[] = $this->_get_rule_numeric('time', T_('Length (in minutes)'), 'numeric', $t_song_data);
        $rule_type[] = $this->_get_rule_text('label', T_('Label'), $t_song_data);
        $rule_type[] = $this->_get_rule_text('comment', T_('Comment'), $t_song_data);
        $rule_type[] = $this->_get_rule_text('lyrics', T_('Lyrics'), $t_song_data);
        $rule_type[] = $this->_get_rule_numeric('id', T_('Database ID'), 'numeric', $t_song_data);

        if (AmpConfig::get('ratings')) {
            $t_ratings   = T_('Ratings');
            $rule_type[] = $this->_get_rule_select('myrating', T_('My Rating'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('rating', T_('Rating (Average)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('albumrating', T_('My Rating (Album)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('artistrating', T_('My Rating (Artist)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_song', T_('My Favorite Songs'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_album', T_('My Favorite Albums'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_artist', T_('My Favorite Artists'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_text('favorite', T_('Favorites'), $t_ratings);
            $rule_type[] = $this->_get_rule_text('favorite_album', T_('Favorites (Album)'), $t_ratings);
            $rule_type[] = $this->_get_rule_text('favorite_artist', T_('Favorites (Artist)'), $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_song', T_('Song popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_album', T_('Album popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_artist', T_('Artist popularity score'), 'numeric', $t_ratings);
            $users       = $this->getUserRepository()->getValidArray();
            $rule_type[] = $this->_get_rule_select('other_user', T_('Another User'), 'user_numeric', $users, $t_ratings);
            $rule_type[] = $this->_get_rule_select('other_user_album', T_('Another User (Album)'), 'user_numeric', $users, $t_ratings);
            $rule_type[] = $this->_get_rule_select('other_user_artist', T_('Another User (Artist)'), 'user_numeric', $users, $t_ratings);
        }

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('skipped_times', T_('# Skipped'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('played_or_skipped_times', T_('# Played or Skipped'), 'numeric', $t_play_data);
        /* HINT: Percentage of (Times Played / Times skipped) * 100 */
        $rule_type[] = $this->_get_rule_numeric('play_skip_ratio', T_('Played/Skipped ratio'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('myplayedalbum', T_('Played by Me (Album)'), 'boolean', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('myplayedartist', T_('Played by Me (Artist)'), 'boolean', $t_play_data);
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('myplayed_times', T_('# Played by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('myskipped_times', T_('# Skipped by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('myplayed_or_skipped_times', T_('# Played or Skipped by Me'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('recent_played', T_('Recently Played'), 'recent_played', $t_play_data);

        $t_genre     = T_('Genre');
        $rule_type[] = $this->_get_rule_text('genre', $t_genre, $t_genre);
        $rule_type[] = $this->_get_rule_text('album_genre', T_('Album Genre'), $t_genre);
        $rule_type[] = $this->_get_rule_text('artist_genre', T_('Artist Genre'), $t_genre);
        $rule_type[] = $this->_get_rule_boolean('no_genre', T_('No Genre'), 'is_true', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_song', T_('Song Count'), 'numeric', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_album', T_('Album Count'), 'numeric', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_artist', T_('Artist Count'), 'numeric', $t_genre);

        $t_playlists = T_('Playlists');
        $playlists   = Playlist::get_playlist_array($this->user);
        if ($playlists !== []) {
            $rule_type[] = $this->_get_rule_select('playlist', T_('Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }

        $playlists = self::get_search_array($this->user);
        if ($playlists !== []) {
            $rule_type[] = $this->_get_rule_select('smartplaylist', T_('Smart Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }

        $rule_type[] = $this->_get_rule_text('playlist_name', T_('Playlist Name'), $t_playlists);

        $t_file_data = T_('File Data');
        $rule_type[] = $this->_get_rule_text('file', T_('Filename'), $t_file_data);

        $bitrate_array = [
            '32' => '32',
            '40' => '40',
            '48' => '48',
            '56' => '56',
            '64' => '64',
            '80' => '80',
            '96' => '96',
            '112' => '112',
            '128' => '128',
            '160' => '160',
            '192' => '192',
            '224' => '224',
            '256' => '256',
            '320' => '320',
            '640' => '640',
            '1280' => '1280',
        ];
        $rule_type[] = $this->_get_rule_select('bitrate', T_('Bitrate'), 'numeric', $bitrate_array, $t_file_data);
        $rule_type[] = $this->_get_rule_date('added', T_('Date Added'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('updated', T_('Date Updated'), $t_file_data);
        if (AmpConfig::get('licensing')) {
            $licenses = iterator_to_array(
                $this->getLicenseRepository()->getList(false)
            );
            $rule_type[] = $this->_get_rule_select('license', T_('Music License'), 'boolean_numeric', $licenses, $t_file_data);
            $rule_type[] = $this->_get_rule_boolean('no_license', T_('No Music License'), 'is_true', $t_file_data);
        }

        $rule_type[] = $this->_get_rule_numeric('recent_added', T_('Recently Added'), 'recent_added', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('recent_updated', T_('Recently Updated'), 'recent_updated', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('days_added', T_('Added'), 'days', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('days_updated', T_('Updated'), 'days', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('duplicate_tracks', T_('Duplicate Album Tracks'), 'is_true', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('possible_duplicate_album', T_('Possible Duplicate Albums'), 'is_true', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('orphaned_album', T_('Orphaned Album'), 'is_true', $t_file_data);

        $catalogs = [];
        foreach (Catalog::get_catalogs('music', $this->user) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            if ($catalog === null || !$catalog->name) {
                break;
            }

            $catalogs[$catid] = $catalog->name;
        }

        if ($catalogs !== []) {
            $rule_type[] = $this->_get_rule_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs, $t_file_data);
        }

        // can't read the file from the db if it's stored on the disk
        if (!AmpConfig::get('album_art_store_disk')) {
            $rule_type[] = $this->_get_rule_boolean('waveform', T_('Waveform'), 'boolean', $t_file_data);
        }

        $t_musicbrainz = T_('Musicbrainz');
        $rule_type[]   = $this->_get_rule_text('mbid', T_('MusicBrainz ID'), $t_musicbrainz);
        $rule_type[]   = $this->_get_rule_text('mbid_album', T_('MusicBrainz ID (Album)'), $t_musicbrainz);
        $rule_type[]   = $this->_get_rule_text('mbid_artist', T_('MusicBrainz ID (Artist)'), $t_musicbrainz);

        if (AmpConfig::get('enable_custom_metadata')) {
            $rule_type[] = [
                'name' => 'metadata',
                'label' => 'Metadata',
                'type' => 'multiple',
                'subtypes' => iterator_to_array($this->getMetadataFieldRepository()->getPropertyList()),
                'widget' => [
                    'subtypes',
                    [
                        'input',
                        'text',
                    ],
                ],
                'title' => 'Metadata'];
        }

        return $rule_type;
    }

    /**
     * _get_types_artist
     *
     * this is where all the available rules for artists are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_artist(): array
    {
        $rule_type = [];

        $t_artist_data = T_('Artist Data');
        $rule_type[]   = $this->_get_rule_text('title', T_('Name'), $t_artist_data);
        $rule_type[]   = $this->_get_rule_text('album', T_('Album Title'), $t_artist_data);
        $rule_type[]   = $this->_get_rule_text('song', T_('Song Title'), $t_artist_data);
        $rule_type[]   = $this->_get_rule_text('summary', T_('Summary'), $t_artist_data);
        $rule_type[]   = $this->_get_rule_numeric('yearformed', T_('Year Formed'), 'numeric', $t_artist_data);
        $rule_type[]   = $this->_get_rule_text('placeformed', T_('Place Formed'), $t_artist_data);
        $rule_type[]   = $this->_get_rule_numeric('time', T_('Length (in minutes)'), 'numeric', $t_artist_data);
        $rule_type[]   = $this->_get_rule_numeric('album_count', T_('Album Count'), 'numeric', $t_artist_data);
        $rule_type[]   = $this->_get_rule_numeric('song_count', T_('Song Count'), 'numeric', $t_artist_data);
        $rule_type[]   = $this->_get_rule_numeric('id', T_('Database ID'), 'numeric', $t_artist_data);

        if (AmpConfig::get('ratings')) {
            $t_ratings   = T_('Ratings');
            $rule_type[] = $this->_get_rule_select('myrating', T_('My Rating'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('rating', T_('Rating (Average)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('songrating', T_('My Rating (Song)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('albumrating', T_('My Rating (Album)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_song', T_('My Favorite Songs'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_album', T_('My Favorite Albums'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_artist', T_('My Favorite Artists'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_song', T_('Song popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_album', T_('Album popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_artist', T_('Artist popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_text('favorite', T_('Favorites'), $t_ratings);
            $users       = $this->getUserRepository()->getValidArray();
            $rule_type[] = $this->_get_rule_select('other_user', T_('Another User'), 'user_numeric', $users, $t_ratings);
        }

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('skipped_times', T_('# Skipped'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('played_or_skipped_times', T_('# Played or Skipped'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('myplayed_times', T_('# Played by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('myskipped_times', T_('# Skipped by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('myplayed_or_skipped_times', T_('# Played or Skipped by Me'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('recent_played', T_('Recently Played'), 'recent_played', $t_play_data);

        $t_genre     = T_('Genre');
        $rule_type[] = $this->_get_rule_text('genre', $t_genre, $t_genre);
        $rule_type[] = $this->_get_rule_text('song_genre', T_('Song Genre'), $t_genre);
        $rule_type[] = $this->_get_rule_boolean('no_genre', T_('No Genre'), 'is_true', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_song', T_('Song Count'), 'numeric', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_album', T_('Album Count'), 'numeric', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_artist', T_('Artist Count'), 'numeric', $t_genre);

        $t_playlists = T_('Playlists');
        $playlists   = Playlist::get_playlist_array($this->user);
        if ($playlists !== []) {
            $rule_type[] = $this->_get_rule_select('playlist', T_('Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }

        $rule_type[] = $this->_get_rule_text('playlist_name', T_('Playlist Name'), $t_playlists);

        $t_file_data = T_('File Data');
        $rule_type[] = $this->_get_rule_text('file', T_('Filename'), $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('has_image', T_('Local Image'), 'boolean', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('image_width', T_('Image Width'), 'numeric', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('image_height', T_('Image Height'), 'numeric', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('days_added', T_('Added'), 'days', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('possible_duplicate_album', T_('Possible Duplicate Albums'), 'is_true', $t_file_data);

        $catalogs = [];
        foreach (Catalog::get_catalogs('music', $this->user) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            if ($catalog === null || !$catalog->name) {
                break;
            }

            $catalogs[$catid] = $catalog->name;
        }

        if ($catalogs !== []) {
            $rule_type[] = $this->_get_rule_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs, $t_file_data);
        }

        $t_musicbrainz = T_('Musicbrainz');
        $rule_type[]   = $this->_get_rule_text('mbid', T_('MusicBrainz ID'), $t_musicbrainz);
        $rule_type[]   = $this->_get_rule_text('mbid_album', T_('MusicBrainz ID (Album)'), $t_musicbrainz);
        $rule_type[]   = $this->_get_rule_text('mbid_song', T_('MusicBrainz ID (Song)'), $t_musicbrainz);

        return $rule_type;
    }

    /**
     * _get_types_album
     *
     * this is where all the available rules for albums are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_album(): array
    {
        $rule_type = [];

        $t_album_data = T_('Album Data');
        $rule_type[]  = $this->_get_rule_text('title', T_('Title'), $t_album_data);
        $rule_type[]  = $this->_get_rule_text('album_artist', T_('Album Artist'), $t_album_data);
        $rule_type[]  = $this->_get_rule_text('song_artist', T_('Song Artist'), $t_album_data);
        $rule_type[]  = $this->_get_rule_text('song', T_('Song Title'), $t_album_data);
        $rule_type[]  = $this->_get_rule_numeric('year', T_('Year'), 'numeric', $t_album_data);
        $rule_type[]  = $this->_get_rule_numeric('original_year', T_('Original Year'), 'numeric', $t_album_data);
        $rule_type[]  = $this->_get_rule_numeric('time', T_('Length (in minutes)'), 'numeric', $t_album_data);
        $rule_type[]  = $this->_get_rule_text('release_type', T_('Release Type'), $t_album_data);
        $rule_type[]  = $this->_get_rule_text('release_status', T_('Release Status'), $t_album_data);
        $rule_type[]  = $this->_get_rule_text('version', T_('Release Comment'), $t_album_data);
        $rule_type[]  = $this->_get_rule_text('barcode', T_('Barcode'), $t_album_data);
        $rule_type[]  = $this->_get_rule_text('catalog_number', T_('Catalog Number'), $t_album_data);
        $rule_type[]  = $this->_get_rule_numeric('disk_count', T_('Disk Count'), 'numeric', $t_album_data);
        $rule_type[]  = $this->_get_rule_numeric('song_count', T_('Song Count'), 'numeric', $t_album_data);
        $rule_type[]  = $this->_get_rule_numeric('id', T_('Database ID'), 'numeric', $t_album_data);

        if (AmpConfig::get('ratings')) {
            $t_ratings   = T_('Ratings');
            $rule_type[] = $this->_get_rule_select('myrating', T_('My Rating'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('rating', T_('Rating (Average)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('songrating', T_('My Rating (Song)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('artistrating', T_('My Rating (Artist)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_song', T_('My Favorite Songs'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_album', T_('My Favorite Albums'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_artist', T_('My Favorite Artists'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_song', T_('Song popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_album', T_('Album popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_artist', T_('Artist popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_text('favorite', T_('Favorites'), $t_ratings);
            $users       = $this->getUserRepository()->getValidArray();
            $rule_type[] = $this->_get_rule_select('other_user', T_('Another User'), 'user_numeric', $users, $t_ratings);
        }

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('skipped_times', T_('# Skipped'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('played_or_skipped_times', T_('# Played or Skipped'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('myplayedartist', T_('Played by Me (Artist)'), 'boolean', $t_play_data);
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('myplayed_times', T_('# Played by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('myskipped_times', T_('# Skipped by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('myplayed_or_skipped_times', T_('# Played or Skipped by Me'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('recent_played', T_('Recently Played'), 'recent_played', $t_play_data);

        $t_genre     = T_('Genre');
        $rule_type[] = $this->_get_rule_text('genre', $t_genre, $t_genre);
        $rule_type[] = $this->_get_rule_text('song_genre', T_('Song Genre'), $t_genre);
        $rule_type[] = $this->_get_rule_boolean('no_genre', T_('No Genre'), 'is_true', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_song', T_('Song Count'), 'numeric', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_album', T_('Album Count'), 'numeric', $t_genre);
        $rule_type[] = $this->_get_rule_numeric('genre_count_artist', T_('Artist Count'), 'numeric', $t_genre);

        $t_playlists = T_('Playlists');
        $playlists   = Playlist::get_playlist_array($this->user);
        if ($playlists !== []) {
            $rule_type[] = $this->_get_rule_select('playlist', T_('Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }

        $playlists = self::get_search_array($this->user);
        if ($playlists !== []) {
            $rule_type[] = $this->_get_rule_select('smartplaylist', T_('Smart Playlist'), 'boolean_subsearch', $playlists, $t_playlists);
        }

        $rule_type[] = $this->_get_rule_text('playlist_name', T_('Playlist Name'), $t_playlists);

        $t_file_data = T_('File Data');
        $rule_type[] = $this->_get_rule_text('file', T_('Filename'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('added', T_('Date Added'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('updated', T_('Date Updated'), $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('has_image', T_('Local Image'), 'boolean', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('image_width', T_('Image Width'), 'numeric', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('image_height', T_('Image Height'), 'numeric', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('recent_added', T_('Recently Added'), 'recent_added', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('days_added', T_('Added'), 'days', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('possible_duplicate', T_('Possible Duplicate'), 'is_true', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('duplicate_tracks', T_('Duplicate Album Tracks'), 'is_true', $t_file_data);
        $rule_type[] = $this->_get_rule_boolean('duplicate_mbid_group', T_('Duplicate MusicBrainz Release Group'), 'is_true', $t_file_data);

        $catalogs = [];
        foreach (Catalog::get_catalogs('music', $this->user) as $catid) {
            $catalog = Catalog::create_from_id($catid);
            if ($catalog === null || !$catalog->name) {
                break;
            }

            $catalogs[$catid] = $catalog->name;
        }

        if ($catalogs !== []) {
            $rule_type[] = $this->_get_rule_select('catalog', T_('Catalog'), 'boolean_numeric', $catalogs, $t_file_data);
        }

        $t_musicbrainz = T_('MusicBrainz');
        $rule_type[]   = $this->_get_rule_text('mbid', T_('MusicBrainz ID'), $t_musicbrainz);
        $rule_type[]   = $this->_get_rule_text('mbid_artist', T_('MusicBrainz ID (Artist)'), $t_musicbrainz);
        $rule_type[]   = $this->_get_rule_text('mbid_song', T_('MusicBrainz ID (Song)'), $t_musicbrainz);

        return $rule_type;
    }

    /**
     * _get_types_video
     *
     * this is where all the available rules for videos are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_video(): array
    {
        $rule_type = [];

        $rule_type[] = $this->_get_rule_text('file', T_('Filename'));

        return $rule_type;
    }

    /**
     * _get_types_playlist
     *
     * this is where all the available rules for playlists are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_playlist(): array
    {
        $rule_type = [];

        $t_playlist  = T_('Playlist');
        $rule_type[] = $this->_get_rule_text('title', T_('Name'), $t_playlist);

        $playlist_types = [
            0 => T_('public'),
            1 => T_('private'),
        ];
        $rule_type[] = $this->_get_rule_select('type', T_('Type'), 'boolean_numeric', $playlist_types, $t_playlist);
        $users       = $this->getUserRepository()->getValidArray();
        $rule_type[] = $this->_get_rule_select('owner', T_('Owner'), 'user_numeric', $users, $t_playlist);
        $rule_type[] = $this->_get_rule_numeric('id', T_('Database ID'), 'numeric', $t_playlist);

        return $rule_type;
    }

    /**
     * _get_types_podcast
     *
     * this is where all the available rules for podcasts are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_podcast(): array
    {
        $rule_type = [];

        $t_podcasts  = T_('Podcast');
        $rule_type[] = $this->_get_rule_text('title', T_('Name'), $t_podcasts);
        $rule_type[] = $this->_get_rule_numeric('episode_count', T_('Episode Count'), 'numeric', $t_podcasts);
        $rule_type[] = $this->_get_rule_numeric('id', T_('Database ID'), 'numeric', $t_podcasts);

        $t_podcast_episodes = T_('Podcast Episodes');
        $rule_type[]        = $this->_get_rule_text('podcast_episode', T_('Podcast Episode'), $t_podcast_episodes);
        $episode_states     = [
            0 => T_('skipped'),
            1 => T_('pending'),
            2 => T_('completed'),
        ];
        $rule_type[] = $this->_get_rule_select('state', T_('Status'), 'boolean_numeric', $episode_states, $t_podcast_episodes);
        $rule_type[] = $this->_get_rule_numeric('time', T_('Length (in minutes)'), 'numeric', $t_podcast_episodes);

        if (AmpConfig::get('ratings')) {
            $t_ratings   = T_('Ratings');
            $rule_type[] = $this->_get_rule_select('myrating', T_('My Rating'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('rating', T_('Rating (Average)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('podcastrating', T_('My Rating (Podcast)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('podcast_episoderating', T_('My Rating (Podcast Episode)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_podcast', T_('My Favorite Podcasts'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_podcast_episode', T_('My Favorite Podcast Episodes'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_podcast', T_('Podcast popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_podcast_episode', T_('Podcast Episode popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_text('favorite', T_('Favorites'), $t_ratings);
            $users       = $this->getUserRepository()->getValidArray();
            $rule_type[] = $this->_get_rule_select('other_user', T_('Another User'), 'user_numeric', $users, $t_ratings);
        }

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('skipped_times', T_('# Skipped'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('played_or_skipped_times', T_('# Played or Skipped'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('myplayed_times', T_('# Played by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('myskipped_times', T_('# Skipped by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('myplayed_or_skipped_times', T_('# Played or Skipped by Me'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('recent_played', T_('Recently Played'), 'recent_played', $t_play_data);

        $t_file_data = T_('File Data');
        $rule_type[] = $this->_get_rule_text('file', T_('Filename'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('pubdate', T_('Publication Date'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('added', T_('Date Added'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('updated', T_('Date Updated'), $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('days_added', T_('Added'), 'days', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('days_updated', T_('Updated'), 'days', $t_file_data);

        return $rule_type;
    }

    /**
     * _get_types_podcast_episode
     *
     * this is where all the available rules for podcast_episodes are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_podcast_episode(): array
    {
        $rule_type = [];

        $t_podcast_episodes = T_('Podcast Episode');
        $rule_type[]        = $this->_get_rule_text('title', T_('Name'), $t_podcast_episodes);
        $rule_type[]        = $this->_get_rule_text('podcast', T_('Podcast'), $t_podcast_episodes);
        $episode_states     = [
            0 => T_('skipped'),
            1 => T_('pending'),
            2 => T_('completed'),
        ];
        $rule_type[] = $this->_get_rule_select('state', T_('Status'), 'boolean_numeric', $episode_states, $t_podcast_episodes);
        $rule_type[] = $this->_get_rule_numeric('time', T_('Length (in minutes)'), 'numeric', $t_podcast_episodes);
        $rule_type[] = $this->_get_rule_numeric('id', T_('Database ID'), 'numeric', $t_podcast_episodes);

        if (AmpConfig::get('ratings')) {
            $t_ratings   = T_('Ratings');
            $rule_type[] = $this->_get_rule_select('myrating', T_('My Rating'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('rating', T_('Rating (Average)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('podcastrating', T_('My Rating (Podcast)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_select('podcast_episoderating', T_('My Rating (Podcast Episode)'), 'numeric', $this->stars, $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_podcast', T_('My Favorite Podcasts'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_boolean('my_flagged_podcast_episode', T_('My Favorite Podcast Episodes'), 'boolean', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_podcast', T_('Podcast popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_numeric('weight_podcast_episode', T_('Podcast Episode popularity score'), 'numeric', $t_ratings);
            $rule_type[] = $this->_get_rule_text('favorite', T_('Favorites'), $t_ratings);
            $users       = $this->getUserRepository()->getValidArray();
            $rule_type[] = $this->_get_rule_select('other_user', T_('Another User'), 'user_numeric', $users, $t_ratings);
        }

        $t_play_data = T_('Play History');
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('played_times', T_('# Played'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('skipped_times', T_('# Skipped'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('played_or_skipped_times', T_('# Played or Skipped'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play', T_('My Last Play'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_skip', T_('My Last Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('last_play_or_skip', T_('My Last Play or Skip'), 'days', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('played', T_('Played'), 'boolean', $t_play_data);
        $rule_type[] = $this->_get_rule_boolean('myplayed', T_('Played by Me'), 'boolean', $t_play_data);
        /* HINT: Number of times object has been played */
        $rule_type[] = $this->_get_rule_numeric('myplayed_times', T_('# Played by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been skipped */
        $rule_type[] = $this->_get_rule_numeric('myskipped_times', T_('# Skipped by Me'), 'numeric', $t_play_data);
        /* HINT: Number of times object has been played OR skipped */
        $rule_type[] = $this->_get_rule_numeric('myplayed_or_skipped_times', T_('# Played or Skipped by Me'), 'numeric', $t_play_data);
        $rule_type[] = $this->_get_rule_numeric('recent_played', T_('Recently Played'), 'recent_played', $t_play_data);

        $t_file_data = T_('File Data');
        $rule_type[] = $this->_get_rule_text('file', T_('Filename'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('pubdate', T_('Publication Date'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('added', T_('Date Added'), $t_file_data);
        $rule_type[] = $this->_get_rule_date('updated', T_('Date Updated'), $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('days_added', T_('Added'), 'days', $t_file_data);
        $rule_type[] = $this->_get_rule_numeric('days_updated', T_('Updated'), 'days', $t_file_data);

        return $rule_type;
    }

    /**
     * _get_types_label
     *
     * this is where all the available rules for labels are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_label(): array
    {
        $rule_type = [];

        $t_label     = T_('Label');
        $rule_type[] = $this->_get_rule_text('title', T_('Name'), $t_label);
        $rule_type[] = $this->_get_rule_text('category', T_('Category'), $t_label);
        $rule_type[] = $this->_get_rule_numeric('id', T_('Database ID'), 'numeric', $t_label);

        return $rule_type;
    }

    /**
     * _get_types_user
     *
     * this is where all the available rules for users are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_user(): array
    {
        $rule_type = [];

        $rule_type[] = $this->_get_rule_text('username', T_('Username'));

        return $rule_type;
    }

    /**
     * _get_types_tag
     *
     * this is where all the available rules for Genres are defined
     * @return array<int, array<string, mixed>>
     */
    private function _get_types_tag(): array
    {
        $rule_type = [];

        $rule_type[] = $this->_get_rule_text('title', T_('Genre'));
        $rule_type[] = $this->_get_rule_numeric('album_count', T_('Album Count'));
        $rule_type[] = $this->_get_rule_numeric('artist_count', T_('Album Count'));
        $rule_type[] = $this->_get_rule_numeric('song_count', T_('Song Count'));
        if (AmpConfig::get('video')) {
            $rule_type[] = $this->_get_rule_numeric('video_count', T_('Video Count'));
        }

        return $rule_type;
    }

    /**
     * _filter_request
     *
     * Sanitizes raw search data
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function _filter_request(array $data): array
    {
        $request = [];
        foreach ($data as $key => $value) {
            $prefix = substr($key, 0, 4);
            $value  = (string)$value;

            if ($prefix == 'rule' && strlen($value)) {
                $request[$key] = Dba::escape($value);
            }
        }

        // Figure out if they want an AND based search or an OR based search
        $operator            = $data['operator'] ?? '';
        $request['operator'] = match (strtolower((string) $operator)) {
            'or' => 'or',
            default => 'and',
        };
        if (array_key_exists('limit', $data)) {
            $request['limit'] = $data['limit'];
        }

        if (array_key_exists('offset', $data)) {
            $request['offset'] = $data['offset'];
        }

        if (array_key_exists('random', $data)) {
            $request['random'] = (int)$data['random'];
        }

        // Verify the type
        $search_type = strtolower($data['type'] ?? '');
        //Search::VALID_TYPES = array('song', 'album', 'album_disk', 'song_artist', 'album_artist', 'artist', 'label', 'playlist', 'podcast', 'podcast_episode', 'tag', 'user', 'video')
        switch ($search_type) {
            case 'album_artist':
            case 'album_disk':
            case 'album':
            case 'artist':
            case 'label':
            case 'playlist':
            case 'podcast_episode':
            case 'podcast':
            case 'song_artist':
            case 'song':
            case 'tag':  // for Genres
            case 'user':
            case 'video':
                $request['type'] = $search_type;
                break;
            case 'genre':
                $request['type'] = 'tag';
                break;
            default:
                debug_event(self::class, sprintf('_filter_request: search_type \'%s\' reset to: song', $search_type), 5);
                $request['type'] = 'song';
                break;
        }

        return $request;
    }

    /**
     * get_searches
     *
     * Return the IDs of all saved searches accessible by the current user.
     * @return array<int, array{id: int, name: string}>
     */
    public static function get_searches(?int $user_id = null): array
    {
        if ($user_id === null) {
            $user_id = (int)(Core::get_global('user')?->id);
        }

        $key = 'searches';
        if (parent::is_cached($key, $user_id)) {
            return parent::get_from_cache($key, $user_id);
        }

        $is_admin = (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user_id) || $user_id == -1);
        $sql      = "SELECT `id`, `name` FROM `search` ";
        $params   = [];

        if (!$is_admin) {
            $sql .= "WHERE (`user` = ? OR `type` = 'public') ";
            $params[] = $user_id;
        }

        $sql .= "ORDER BY `name`";

        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'id' => (int)$row['id'],
                'name' => $row['name']
            ];
        }

        parent::add_to_cache($key, $user_id, $results);

        return $results;
    }

    /**
     * get_search_array
     * Returns a list of searches accessible by the user with formatted name.
     * @return string[]
     */
    public static function get_search_array(?int $user_id = null): array
    {
        if ($user_id === null) {
            $user_id = (int)(Core::get_global('user')?->id);
        }

        $key = 'searcharray';
        if (parent::is_cached($key, $user_id)) {
            return parent::get_from_cache($key, $user_id);
        }

        $is_admin = (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user_id) || $user_id == -1);
        $sql      = "SELECT `id`, IF(`user` = ?, `name`, CONCAT(`name`, ' (', `username`, ')')) AS `name` FROM `search` ";
        $params   = [$user_id];

        if (!$is_admin) {
            $sql .= "WHERE (`user` = ? OR `type` = 'public') ";
            $params[] = $user_id;
        }

        $sql .= "ORDER BY `name`";
        //debug_event(self::class, 'get_searches query: ' . $sql . "\n" . print_r($params, true), 5);

        $db_results = Dba::read($sql, $params);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        parent::add_to_cache($key, $user_id, $results);

        return $results;
    }

    /**
     * prepare
     *
     * This function prepares the sql and parameters for execution.
     * @param array<string, mixed> $data
     * @param bool $require_rules // require a valid rule to return search items (instead of returning all items)
     * @return array{
     *     sql: string,
     *     parameters: array
     * }
     */
    public static function prepare(array $data, ?User $user = null, bool $require_rules = false): array
    {
        $limit  = (int)($data['limit'] ?? 0);
        $offset = (int)($data['offset'] ?? 0);
        $random = ((int)($data['random'] ?? 0) > 0) ? 1 : 0;
        $search = new Search(0, $data['type'], $user);

        if ($data['weight'] ?? false) {
            $search->set_order_by('weight');
        }
        $search->set_rules($data);

        // Generate BASE SQL
        $limit_sql = "";
        if ($limit > 0) {
            $limit_sql = ' LIMIT ';
            if ($offset > 0) {
                $limit_sql .= $offset . ", ";
            }

            $limit_sql .= $limit;
        }

        $search_info = $search->to_sql();
        if ($require_rules && empty($search_info['where'])) {
            debug_event(self::class, 'require_rules: No rules were set on this search', 5);

            return [
                'sql' => '',
                'parameters' => []
            ];
        }

        $sql = $search_info['base'] . ' ' . $search_info['table_sql'];
        if (!empty($search_info['where_sql'])) {
            $sql .= ' WHERE ' . $search_info['where_sql'];
        }

        if (!empty($search_info['group_sql'])) {
            $sql .= ' GROUP BY ' . $search_info['group_sql'];
            if (!empty($search_info['having_sql'])) {
                $sql .= ' HAVING ' . $search_info['having_sql'];
            }
        }

        $sql .= ($random > 0) ? " ORDER BY RAND()" : " ORDER BY " . $search->order_by;
        $sql .= ' ' . $limit_sql;
        $sql = trim($sql);
        //debug_event(self::class, 'SQL prepare: ' . $sql . "\n" . print_r($search_info['parameters'], true), 5);

        return [
            'sql' => $sql,
            'parameters' => $search_info['parameters'],
        ];
    }

    /**
     * run
     *
     * This function actually runs the search and returns an array of the
     * results.
     * @param array<string, mixed> $data
     * @param bool $require_rules // require a valid rule to return search items (instead of returning all items)
     * @return int[]
     */
    public static function run(array $data, ?User $user = null, bool $require_rules = false): array
    {
        $search_sql = self::prepare($data, $user, $require_rules);
        $db_results = Dba::read((string)$search_sql['sql'], $search_sql['parameters']);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * query
     *
     * This function is used to simplify api searches and return valuable data for responses
     * @param array{
     *     sql: string,
     *     parameters: array
     * } $search_sql
     * @return array{
     *     results: int[],
     *     count: int
     * }
     */
    public static function query(array $search_sql): array
    {
        $db_results = Dba::read((string)$search_sql['sql'], $search_sql['parameters']);
        $num_rows   = Dba::num_rows($db_results);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return [
            'results' => $results,
            'count' => $num_rows,
        ];
    }

    /**
     * delete
     *
     * Does what it says on the tin.
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM `search` WHERE `id` = ?";
        Dba::write($sql, [$this->id]);
        Catalog::count_table('search');

        return true;
    }

    /**
     * get_items
     *
     * Return an array of the items output by our search
     * (part of the playlist interface).
     * @return array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int
     * }>
     */
    public function get_items(): array
    {
        $results = [];
        if ($this->isNew()) {
            return $results;
        }

        $sqltbl = $this->to_sql();
        $sql    = $sqltbl['base'] . ' ' . $sqltbl['table_sql'];
        if (!empty($sqltbl['where_sql'])) {
            $sql .= ' WHERE ' . $sqltbl['where_sql'];
        }

        if (!empty($sqltbl['group_sql'])) {
            $sql .= ' GROUP BY ' . $sqltbl['group_sql'];
        }

        if (!empty($sqltbl['having_sql'])) {
            $sql .= ' HAVING ' . $sqltbl['having_sql'];
        }

        $sql .= ($this->random > 0) ? " ORDER BY RAND()" : " ORDER BY " . $this->order_by;
        if ($this->limit > 0) {
            $sql .= " LIMIT " . $this->limit;
        }

        //debug_event(self::class, 'SQL get_items: ' . $sql . "\n" . print_r($sqltbl['parameters'], true), 5);

        $count      = 1;
        $db_results = Dba::read($sql, $sqltbl['parameters']);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'object_id' => $row['id'],
                'object_type' => LibraryItemEnum::from($this->objectType),
                'track_id' => $row['id'],
                'track' => $count++
            ];
        }

        $this->date = time();
        $this->set_last(count($results), 'last_count');
        $this->set_last(self::get_total_duration($results), 'last_duration');

        return $results;
    }

    /**
     * get_subsearch
     *
     * get SQL for an item subsearch
     * @return array{sql: string, parameters: array<int, mixed>}
     */
    public function get_subsearch(string $table): array
    {
        $sqltbl = $this->to_sql();
        $sql    = sprintf('SELECT DISTINCT(`%s`.`id`) FROM `%s` ', $table, $table) . $sqltbl['table_sql'];
        if (!empty($sqltbl['where_sql'])) {
            $sql .= ' WHERE ' . $sqltbl['where_sql'];
        }

        if (!empty($sqltbl['group_sql'])) {
            $sql .= ' GROUP BY ' . $sqltbl['group_sql'];
        }

        if (!empty($sqltbl['having_sql'])) {
            $sql .= ' HAVING ' . $sqltbl['having_sql'];
        }

        //$sql .= ($this->random > 0) ? " ORDER BY RAND()" : " ORDER BY " . $this->order_by; // MYSQL would want file for order by
        if ($this->limit > 0) {
            $sql .= " LIMIT " . $this->limit;
        }

        //debug_event(self::class, 'SQL get_subsearch: ' . $sql . "\n" . print_r($sqltbl['parameters'], true), 5);

        return [
            'sql' => $sql,
            'parameters' => $sqltbl['parameters'],
        ];
    }

    public function set_last(int $count, string $column): void
    {
        if (in_array($column, ['last_count', 'last_duration'])) {
            $sql = "UPDATE `search` SET `" . Dba::escape($column) . "` = ? WHERE `id` = ?";
            Dba::write($sql, [$count, $this->id]);
        }
    }

    /**
     * get_random_items
     *
     * Returns a randomly sorted array (with an optional limit) of the items
     * output by our search (part of the playlist interface)
     */
    public function get_random_items(?string $limit = ''): array
    {
        $results = [];
        $sqltbl  = $this->to_sql();
        $sql     = $sqltbl['base'] . ' ' . $sqltbl['table_sql'];
        if (!empty($sqltbl['where_sql'])) {
            $sql .= ' WHERE ' . $sqltbl['where_sql'];
        }

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && Core::get_global('user') instanceof User && Core::get_global('user')->id > 0) {
            $user_id = Core::get_global('user')->id;
            $sql .= (empty($sqltbl['where_sql']))
                ? " WHERE "
                : " AND ";
            $sql .= "`" . $this->objectType . "`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '" . $this->objectType . sprintf('\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d)', $rating_filter, $user_id);
        }

        if (!empty($sqltbl['group_sql'])) {
            $sql .= ' GROUP BY ' . $sqltbl['group_sql'];
        }

        if (!empty($sqltbl['having_sql'])) {
            $sql .= ' HAVING ' . $sqltbl['having_sql'];
        }

        $sql .= " ORDER BY RAND()";
        $sql .= (empty($limit))
            ? ""
            : " LIMIT " . $limit;

        //debug_event(self::class, 'SQL get_random_items: ' . $sql . "\n" . print_r($sqltbl['parameters'], true), 5);
        $db_results = Dba::read($sql, $sqltbl['parameters']);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = ['object_id' => $row['id'], 'object_type' => $this->objectType];
        }

        return $results;
    }

    /**
     * get_songs
     * This is called by the batch script, because we can't pass in Dynamic objects they pulled once and then their
     * target song.id is pushed into the array
     * @return int[]
     */
    public function get_songs(): array
    {
        $results = [];
        if ($this->isNew()) {
            return $results;
        }

        $sqltbl = $this->to_sql();
        $sql    = $sqltbl['base'] . ' ' . $sqltbl['table_sql'];
        if (!empty($sqltbl['where_sql'])) {
            $sql .= ' WHERE ' . $sqltbl['where_sql'];
        }

        if (!empty($sqltbl['group_sql'])) {
            $sql .= ' GROUP BY ' . $sqltbl['group_sql'];
        }

        if (!empty($sqltbl['having_sql'])) {
            $sql .= ' HAVING ' . $sqltbl['having_sql'];
        }

        $sql .= ($this->random > 0) ? " ORDER BY RAND()" : " ORDER BY " . $this->order_by;
        if ($this->limit > 0) {
            $sql .= " LIMIT " . $this->limit;
        }

        //debug_event(self::class, 'SQL get_songs: ' . $sql . "\n" . print_r($sqltbl['parameters'], true), 5);

        $db_results = Dba::read($sql, $sqltbl['parameters']);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * get_total_duration
     * Get the total duration of all songs.
     * @param int[]|array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int}> $songs
     */
    public static function get_total_duration(array $songs): int
    {
        if (empty($songs)) {
            return 0;
        }

        $song_ids = [];
        foreach ($songs as $objects) {
            $song_ids[] = (is_array($objects))
                ? (string)$objects['object_id']
                : $objects;
        }

        $idlist = '(' . implode(',', $song_ids) . ')';
        if ($idlist == '()') {
            return 0;
        }

        $sql = 'SELECT SUM(`time`) FROM `song` WHERE `id` IN ' . $idlist;

        $db_results = Dba::read($sql);
        $row        = Dba::fetch_row($db_results);

        return (int)($row[0] ?? 0);
    }

    /**
     * _get_rule_name
     *
     * Iterate over $this->types to validate the rule name and return the rule type
     * (text, date, etc)
     */
    private function _set_rule_name(string $name): string
    {
        // check that the rule you sent is not an alias (needed for pulling details from the rule)
        switch ($this->objectType) {
            case 'song':
                switch ($name) {
                    case 'name':
                    case 'song':
                    case 'song_title':
                        $name = 'title';
                        break;
                    case 'album_title':
                        $name = 'album';
                        break;
                    case 'album_artist_title':
                        $name = 'album_artist';
                        break;
                    case 'artist':
                    case 'artist_title':
                    case 'song_artist_title':
                        $name = 'song_artist';
                        break;
                    case 'tag':
                    case 'song_tag':
                    case 'song_genre':
                        $name = 'genre';
                        break;
                    case 'genre_count':
                        $name = 'genre_count_song';
                        break;
                    case 'album_tag':
                        $name = 'album_genre';
                        break;
                    case 'artist_tag':
                        $name = 'artist_genre';
                        break;
                    case 'no_tag':
                        $name = 'no_genre';
                        break;
                    case 'mbid_song':
                        $name = 'mbid';
                        break;
                    case 'my_flagged':
                    case 'myflagged':
                    case 'myflagged_song':
                        $name = 'my_flagged_song';
                        break;
                    case 'myflagged_album':
                        $name = 'my_flagged_album';
                        break;
                    case 'myflagged_artist':
                        $name = 'my_flagged_artist';
                        break;
                }

                break;
            case 'album':
            case 'album_disk':
                switch ($name) {
                    case 'name':
                    case 'album':
                    case 'album_title':
                        $name = 'title';
                        break;
                    case 'song_title':
                        $name = 'song';
                        break;
                    case 'artist':
                    case 'album_artist_title':
                    case 'artist_title':
                        $name = 'album_artist';
                        break;
                    case 'tag':
                    case 'album_tag':
                    case 'album_genre':
                        $name = 'genre';
                        break;
                    case 'song_tag':
                        $name = 'song_genre';
                        break;
                    case 'genre_count':
                        $name = 'genre_count_album';
                        break;
                    case 'no_tag':
                        $name = 'no_genre';
                        break;
                    case 'mbid_album':
                        $name = 'mbid';
                        break;
                    case 'possible_duplicate_album':
                        $name = 'possible_duplicate';
                        break;
                    case 'release_comment':
                    case 'subtitle':
                        $name = 'version';
                        break;
                    case 'myflagged_song':
                        $name = 'my_flagged_song';
                        break;
                    case 'my_flagged':
                    case 'myflagged':
                    case 'myflagged_album':
                        $name = 'my_flagged_album';
                        break;
                    case 'myflagged_artist':
                        $name = 'my_flagged_artist';
                        break;
                }

                break;
            case 'artist':
                switch ($name) {
                    case 'name':
                    case 'artist':
                    case 'artist_title':
                        $name = 'title';
                        break;
                    case 'album_title':
                        $name = 'album';
                        break;
                    case 'song_title':
                        $name = 'song';
                        break;
                    case 'tag':
                    case 'artist_tag':
                    case 'artist_genre':
                        $name = 'genre';
                        break;
                    case 'song_tag':
                        $name = 'song_genre';
                        break;
                    case 'genre_count':
                        $name = 'genre_count_artist';
                        break;
                    case 'no_tag':
                        $name = 'no_genre';
                        break;
                    case 'mbid_artist':
                        $name = 'mbid';
                        break;
                    case 'myflagged_song':
                        $name = 'my_flagged_song';
                        break;
                    case 'myflagged_album':
                        $name = 'my_flagged_album';
                        break;
                    case 'my_flagged':
                    case 'myflagged':
                    case 'myflagged_artist':
                        $name = 'my_flagged_artist';
                        break;
                }

                break;
            case 'podcast':
                switch ($name) {
                    case 'name':
                        $name = 'title';
                        break;
                    case 'podcast_episode_title':
                        $name = 'podcast_episode';
                        break;
                    case 'status':
                        $name = 'state';
                        break;
                }

                break;
            case 'podcast_episode':
                switch ($name) {
                    case 'name':
                        $name = 'title';
                        break;
                    case 'podcast_title':
                        $name = 'podcast';
                        break;
                    case 'status':
                        $name = 'state';
                        break;
                }

                break;
            case 'genre':
            case 'tag':
            case 'label':
            case 'playlist':
                if ($name === 'name') {
                    $name = 'title';
                }

                break;
        }

        //debug_event(self::class, '__get_rule_name: ' . $name, 5);

        return $name;
    }

    /**
     * get_rule_type
     *
     * Iterate over $this->types to validate the rule name and return the rule type
     * (text, date, etc)
     */
    public function get_rule_type_by_name(string $name): ?string
    {
        //debug_event(self::class, 'get_rule_type: ' . $name, 5);
        return match ($name) {
            'anywhere', 'title', 'album', 'song', 'song_artist', 'album_artist', 'composer', 'comment', 'lyrics', 'label', 'file', 'playlist_name', 'mbid', 'mbid_album', 'mbid_artist', 'mbid_song', 'genre', 'album_genre', 'artist_genre', 'song_genre', 'podcast', 'podcast_episode', 'category', 'username' => 'text',
            'id', 'track', 'year', 'original_year', 'time', 'disk_count', 'song_count', 'album_count', 'artist_count', 'episode_count', 'played_times', 'skipped_times', 'played_or_skipped_times', 'myplayed_times', 'myskipped_times', 'myplayed_or_skipped_times', 'play_skip_ratio' => 'numeric',
            'myrating', 'rating', 'albumrating', 'artistrating', 'songrating', 'podcastrating', 'podcast_episoderating' => 'numeric',
            'played', 'myplayed', 'myplayedalbum', 'myplayedartist', 'my_flagged_song', 'my_flagged_album', 'my_flagged_artist', 'my_flagged_podcast', 'my_flagged_podcast_episode' => 'boolean',
            'none', 'no_genre', 'no_license', 'possible_duplicate', 'duplicate_tracks', 'possible_duplicate_album', 'orphaned_album' => 'is_true',
            'last_play', 'last_skip', 'last_play_or_skip', 'days_added', 'days_updated' => 'days',
            'recent_played' => 'recent_played',
            'recent_added' => 'recent_added', 'recent_updated' => 'recent_updated',
            'playlist', 'smartplaylist' => 'boolean_subsearch',
            'catalog', 'license', 'state' => 'boolean_numeric',
            'other_user', 'other_user_album', 'other_user_artist' => 'user_numeric',
            'metadata' => 'multiple',
            'added', 'updated', 'pubdate' => 'date',
            default => null,
        };
    }

    /**
     * set_order_by
     * Allow some display flexibility
     */
    public function set_order_by(string $sort): void
    {
        switch ($this->objectType) {
            case 'album':
                if ($sort === 'weight') {
                    $this->order_by = '`album`.`weight` DESC, `album`.`name`';
                }
                break;
            case 'album_disk':
                if ($sort === 'weight') {
                    $this->order_by = '`album_disk`.`weight` DESC, `album`.`name`';
                }
                break;
            case 'artist':
            case 'album_artist':
            case 'song_artist':
                if ($sort === 'weight') {
                    $this->order_by = '`artist`.`weight` DESC, `artist`.`name`';
                }
                break;
            case 'podcast':
                if ($sort === 'weight') {
                    $this->order_by = '`podcast`.`weight` DESC, `podcast`.`title`';
                }
                break;
            case 'podcast_episode':
                if ($sort === 'weight') {
                    $this->order_by = '`podcast_episode`.`weight` DESC, `podcast_episode`.`pubdate` DESC';
                }
                break;
            case 'song':
                if ($sort === 'weight') {
                    $this->order_by = '`song`.`weight` DESC, `song`.`file`';
                }
                break;
            case 'video':
                if ($sort === 'weight') {
                    $this->order_by = '`video`.`weight` DESC, `video`.`file`';
                }
                break;
        }
    }

    /**
     * set_rules
     *
     * Takes an array of sanitized search data from the form and generates our real array from it.
     * @param array<string, mixed> $data
     */
    public function set_rules(array $data): void
    {
        if (isset($data['playlist_name'])) {
            $this->name = (string)$data['playlist_name'];
        }

        if (isset($data['playlist_type'])) {
            $this->type = (string)$data['playlist_type'];
        }

        if (isset($data['catalog_id'])) {
            $this->catalog_id = (int)$data['catalog_id'];
        }

        // check that a limit or random flag and operator have been sent
        $this->random = (isset($data['random'])) ? (int)$data['random'] : $this->random;
        $this->limit  = (isset($data['limit'])) ? (int) $data['limit'] : $this->limit;
        // the rules array needs to be filtered to just have rules
        $data                 = $this->_filter_request($data);
        $this->rules          = [];
        $user_rules           = [];
        $this->logic_operator = strtolower($data['operator'] ?? 'and');
        // match the numeric rules you send (e.g. rule_1, rule_6000)
        foreach (array_keys($data) as $rule) {
            if (preg_match('/^rule_(\d+)$/', $rule, $ruleID)) {
                $user_rules[] = $ruleID[1];
            }
        }

        // get the data for each rule group the user sent
        foreach ($user_rules as $ruleID) {
            $rule_name = $this->_set_rule_name($data["rule_" . $ruleID]);
            $rule_type = $this->get_rule_type_by_name($rule_name);
            if ($rule_type === null) {
                continue;
            }

            $rule_input    = (string)($data['rule_' . $ruleID . '_input'] ?? '');
            $rule_operator = $this->get_basetypes()[$rule_type][$data['rule_' . $ruleID . '_operator']]['name'] ?? '';
            // keep vertical bar in regular expression
            $is_regex = in_array($rule_operator, ['regexp', 'notregexp']);
            if ($is_regex) {
                $rule_input = str_replace("|", "\0", $rule_input);
            }

            // attach the rules to the search
            foreach (explode('|', $rule_input) as $input) {
                $this->rules[] = [
                    $rule_name,
                    // name
                    $rule_operator,
                    // operator
                    ($is_regex) ? str_replace("\0", "|", $input) : $input,
                    // input
                    $data['rule_' . $ruleID . '_subtype'] ?? null,
                ];
            }
        }
    }

    /**
     * create
     *
     * Save this search to the database for use as a smart playlist
     */
    public function create(): ?string
    {
        $user = Core::get_global('user');
        if (!$user instanceof User) {
            return null;
        }

        // Make sure we have a unique name
        if (
            $this->name === null ||
            $this->name === '' ||
            $this->name === '0'
        ) {
            $this->name = $user->username . ' - ' . get_datetime(time());
        }

        $sql        = "SELECT `id` FROM `search` WHERE `name` = ? AND `user` = ? AND `type` = ?;";
        $db_results = Dba::read($sql, [$this->name, $user->id, $this->type]);
        if (Dba::num_rows($db_results) !== 0) {
            $this->name .= uniqid('', true);
        }

        if (empty($this->logic_operator)) {
            $this->logic_operator = 'and';
        }

        $time = time();

        $sql = "INSERT INTO `search` (`name`, `type`, `user`, `username`, `rules`, `logic_operator`, `random`, `limit`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, [$this->name, $this->type, $user->id, $user->username, json_encode($this->rules), strtolower($this->logic_operator), ($this->random > 0) ? 1 : 0, $this->limit, $time, $time]);
        $insert_id = Dba::insert_id();
        if (!$insert_id) {
            return null;
        }

        $this->id = (int)$insert_id;
        Catalog::count_table('search');

        return $insert_id;
    }

    /**
     * to_js
     *
     * Outputs the javascript necessary to re-show the current set of rules.
     */
    public function to_js(): string
    {
        $javascript = "";
        foreach ($this->rules as $rule) {
            // @see search.js SearchRow.add(ruleType, operator, input, subtype)
            $javascript .= '<script>SearchRow.add("' . scrub_out($rule[0]) . '","' . scrub_out(T_($rule[1])) . '","' . scrub_out($rule[2]) . '", "' . scrub_out($rule[3] ?? '') . '"); </script>';
        }

        return $javascript;
    }

    /**
     * to_sql
     *
     * Call the appropriate real function.
     *
     * @return array{
     *     base: string,
     *     join: array<string, bool>,
     *     where: string[],
     *     where_sql: string,
     *     table: array<string, string>,
     *     table_sql: string,
     *     group_sql: string,
     *     having_sql: string,
     *     parameters: array<int, mixed>,
     * }
     */
    public function to_sql(): array
    {
        return $this->searchType->getSql($this);
    }

    /**
     * update_item
     * This is the generic update function, it does the escaping and error checking
     */
    public function update_item(string $field, int|string|null $value): bool
    {
        if (Core::get_global('user')?->getId() != $this->user && !Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) {
            return false;
        }

        $sql = sprintf('UPDATE `search` SET `%s` = ? WHERE `id` = ?', $field);

        return (Dba::write($sql, [$value, $this->id]) !== null);
    }

    /**
     * filter_data
     *
     * Private convenience function.  Mangles the input according to a set
     * of predefined rules so that we don't have to include this logic in
     * _get_sql_foo.
     * @param array{
     *     name?: string,
     *     description?: string,
     *     sql?: string,
     *     preg_match?: string|array,
     *     preg_replace?: string|array,
     * } $operator
     */
    public function filter_data(string $data, string $type, array $operator): bool|int|string|null
    {
        if (
            array_key_exists('preg_match', $operator) &&
            array_key_exists('preg_replace', $operator)
        ) {
            $data = preg_replace($operator['preg_match'], $operator['preg_replace'], $data);
        }

        if ($type == 'numeric' || $type == 'days') {
            return (int)($data);
        }

        if ($type == 'boolean') {
            return make_bool($data);
        }

        if ($data === null) {
            return null;
        }

        return stripslashes($data);
    }

    /**
     * year_search
     *
     * Build search rules for year -> year album searches for subsonic.
     */
    public static function year_search(int $fromYear, int $toYear, int $size, int $offset): array
    {
        $search           = [];
        $search['limit']  = $size;
        $search['offset'] = $offset;
        $search['type']   = "album";
        if ($fromYear) {
            $search['rule_0_input']    = $fromYear;
            $search['rule_0_operator'] = 0;
            $search['rule_0']          = "original_year";
        }

        if ($toYear) {
            $search['rule_1_input']    = $toYear;
            $search['rule_1_operator'] = 1;
            $search['rule_1']          = "original_year";
        }

        return $search;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::SEARCH;
    }

    /**
     * @deprecated
     */
    private function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getMetadataFieldRepository(): MetadataFieldRepositoryInterface
    {
        global $dic;

        return $dic->get(MetadataFieldRepositoryInterface::class);
    }
}
