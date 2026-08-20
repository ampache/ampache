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

namespace Ampache\Module\Database\Query;

use Ampache\Config\AmpConfig;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\TmpBrowseRepositoryInterface;

/**
 * Query Class
 *
 * This handles all of the sql/filtering for the Ampache database
 * The Search and Query classes do the same thing different ways.
 * It would be good to merge the classes (may not be possible now)
 */
class Query
{
    /** @var string[] The name matches the filter box offers, in the order it lists them */
    public const array MATCH_MODES = ['starts_with', 'like'];

    private const int MAX_REGEX_FILTER_LENGTH = 100;

    private const array SORT_ORDER = [
        'active' => 'ASC',
        'last_count' => 'ASC',
        'last_update' => 'ASC',
        'limit' => 'ASC',
        'original_year' => 'ASC',
        'random' => 'ASC',
        'rating' => 'ASC',
        'song_count' => 'ASC',
        'total_count' => 'ASC',
        'total_skip' => 'ASC',
        'year' => 'ASC',
    ];

    public int $catalog   = 0;
    public int|string $id = 0;
    public ?int $user_id  = null;

    /** @var int[]|string[]|array<array{object_id: int,object_type: LibraryItemEnum|string,track_id: int,track: int}>|array<int, array{name?: string|null, id: int, track: int, raw: string, link?: string|null, track: int, oid?: int, vlid?: int}> $_cache */
    protected array $_cache = [];

    /**
     * The SQL the query is built from. Browse adds its own view keys (album_artist, extended_key_name, grid_view,
     * mashup, show_header, song_artist, threshold, title, update_session, use_alpha, use_filters, use_pages,
     * use_select) on write and defaults them in its own getters, so they ride along into tmp_browse unread here.
     *
     * @var array<string, mixed> $_state
     */
    protected array $_state = [
        'base' => null,
        'custom' => false,
        'custom_sql' => '', // an id-yielding query the browse is restricted to, joined as a derived table
        'filter' => [],
        'group' => [],
        'having' => '', // HAVING is not currently used in Query SQL
        'join' => null,
        'limit' => 0,
        'match_mode' => 'starts_with', // which name match the filter box is set to, kept apart from the filter it sets
        'offset' => 0,
        'params' => [], // parameters for custom sql
        'select' => [],
        'simple' => false,
        'skip_catalog_check' => false, // when you've already checked the parent object catalog is usable
        'sort' => [
            'name' => null,
            'order' => null,
        ],
        'start' => 0,
        'static' => false,
        'total' => null,
        'type' => '',
    ];

    private ?QueryInterface $queryType = null; // generate sql for the object type (Ampache\Module\Database\Query\*)

    /**
     * constructor
     * This should be called
     */
    public function __construct(
        ?int $query_id = 0,
        ?bool $cached = true,
    ) {
        $sid = session_id();

        if (!$cached) {
            $this->id = 'nocache';

            return;
        }

        $this->user_id = (Core::get_global('user') instanceof User)
            ? Core::get_global('user')->id
            : null;

        if ($this->user_id === null) {
            return;
        }

        if ($query_id === 0) {
            $insert_id = self::getTmpBrowseRepository()->create((string) $sid, $this->_serialize($this->_state));
            if ($insert_id === null) {
                return;
            }

            $this->reset();
            $this->id = $insert_id;

            return;
        }

        $results = self::getTmpBrowseRepository()->getRow((int) $query_id, (string) $sid);
        if (!empty($results['data'])) {
            $this->id     = (int) $query_id;
            $this->_state = (array) $this->_unserialize($results['data']);
            $this->_cache = (array_key_exists('object_data', $results) && !empty($results['object_data']))
                ? (array) $this->_unserialize($results['object_data'])
                : [];
            // queryType isn't set by restoring state
            $this->set_type($this->_state['type']);

            return;
        }

        AmpError::add('browse', T_('Browse was not found or expired, try reloading the page'));
    }

    /**
     * garbage_collection
     * This cleans old data out of the table
     */
    public static function garbage_collection(): void
    {
        self::getTmpBrowseRepository()->collectGarbage();
    }

    /**
     * get_allowed_filters
     * This returns an array of the allowed filters based on the type of
     * object we are working with, this is used to display the 'filter'
     * sidebar stuff.
     */
    public static function get_allowed_filters(string $type): array
    {
        return match ($type) {
            'album' => AlbumQuery::FILTERS,
            'album_disk' => AlbumDiskQuery::FILTERS,
            'artist' => ArtistQuery::FILTERS,
            'broadcast' => BroadcastQuery::FILTERS,
            'catalog' => CatalogQuery::FILTERS,
            'collection' => CollectionQuery::FILTERS,
            'collection_items' => CollectionItemsQuery::FILTERS,
            'democratic' => DemocraticQuery::FILTERS,
            'folder' => FolderQuery::FILTERS,
            'follower' => FollowerQuery::FILTERS,
            'label' => LabelQuery::FILTERS,
            'license', 'license_hidden' => LicenseQuery::FILTERS,
            'live_stream' => LiveStreamQuery::FILTERS,
            'playlist_localplay' => PlaylistLocalplayQuery::FILTERS,
            'playlist_media' => PlaylistMediaQuery::FILTERS,
            'playlist_search' => PlaylistSearchQuery::FILTERS,
            'playlist' => PlaylistQuery::FILTERS,
            'podcast_episode' => PodcastEpisodeQuery::FILTERS,
            'podcast' => PodcastQuery::FILTERS,
            'pvmsg' => PvmsgQuery::FILTERS,
            'share' => ShareQuery::FILTERS,
            'shoutbox' => ShoutboxQuery::FILTERS,
            'smartplaylist' => SmartplaylistQuery::FILTERS,
            'song_preview' => SongPreviewQuery::FILTERS,
            'song' => SongQuery::FILTERS,
            'genre', 'tag_hidden', 'tag' => TagQuery::FILTERS,
            'mood' => MoodQuery::FILTERS,
            'user' => UserQuery::FILTERS,
            'video' => VideoQuery::FILTERS,
            'wanted' => WantedQuery::FILTERS,
            default => [],
        };
    }

    /**
     * @deprecated inject dependency
     */
    private static function getTmpBrowseRepository(): TmpBrowseRepositoryInterface
    {
        global $dic;

        return $dic->get(TmpBrowseRepositoryInterface::class);
    }

    /**
     * clear_filter
     * drops a filter so it stops contributing to the query; every set filter emits sql, so an unwanted one
     * has to be removed rather than blanked
     */
    public function clear_filter(string $key): void
    {
        if (!isset($this->_state['filter'][$key])) {
            return;
        }

        unset($this->_state['filter'][$key]);

        $this->_state['total'] = null;
        $this->set_start(0);
    }

    /**
     * get_filter
     * returns the specified filter value
     */
    public function get_filter(string $key): int|string|null
    {
        return $this->_state['filter'][$key] ?? null;
    }

    /**
     * get_match_mode
     * the name match the filter box is set to; it outlives the filter itself so emptying the box does not
     * silently put the box back to the default on the next render
     */
    public function get_match_mode(): string
    {
        $mode = $this->_state['match_mode'] ?? null;

        return (in_array($mode, self::MATCH_MODES, true))
            ? $mode
            : self::MATCH_MODES[0];
    }

    /**
     * get_objects
     * This gets an array of the ids of the objects that we are
     * currently browsing by it applies the sql and logic based filters
     * @return array<int|string>
     */
    public function get_objects(): array
    {
        //debug_event(self::class, 'get_objects query: ' . $this->_get_sql(), 5);
        $db_results = Dba::read($this->_get_sql(), $this->_state['params']);
        $results    = [];
        while ($data = Dba::fetch_assoc($db_results)) {
            $results[] = $data;
        }

        $results  = $this->_post_process($results);
        $filtered = [];
        foreach ($results as $data) {
            // Make sure that this object passes the logic filter
            if ($data['id']) {
                $filtered[] = $data['id'];
            }
        }

        // Save what we've found and then return it
        $this->save_objects($filtered);

        return $filtered;
    }

    /**
     * get_offset
     * This returns the current offset
     */
    public function get_offset(): int
    {
        return $this->_state['offset'] ?? 0;
    }

    /**
     * get_saved
     * This looks in the session for the saved stuff and returns what it finds.
     * @return int[]|string[]|array<array{object_id: int,object_type: LibraryItemEnum|string,track_id: int,track: int}>|array<int, array{name?: string|null, id: int, track: int, raw: string, link?: string|null, track: int, oid?: int, vlid?: int}>
     */
    public function get_saved(): array
    {
        // See if we have it in the local cache first
        if ($this->_cache !== []) {
            return $this->_cache;
        }

        if (!$this->is_simple()) {
            $results = self::getTmpBrowseRepository()->getRow((int) $this->id, (string) session_id());

            if (array_key_exists('data', $results) && !empty($results['data'])) {
                $data = (array) $this->_unserialize($results['data']);
                // queryType isn't set by restoring state
                $this->set_type($data['type']);
            }

            if (array_key_exists('object_data', $results) && !empty($results['object_data'])) {
                $this->_cache = (array) $this->_unserialize($results['object_data']);

                return $this->_cache;
            }

            return [];
        }

        return $this->get_objects();
    }

    /**
     * get_sort
     * This returns the current sort
     * @return array{
     *     name: ?string,
     *     order: ?string
     * }
     */
    public function get_sort(): array
    {
        return $this->_state['sort'];
    }

    /**
     * get_start
     * This returns the current value of the start
     */
    public function get_start(): int
    {
        return $this->_state['start'];
    }

    /**
     * get_total
     * This returns the total number of objects for this current sort type.
     * If it's already cached used it. if they pass us an array then use that.
     */
    public function get_total(?array $object_ids = null): int
    {
        // If they pass something then just return that
        if (is_array($object_ids) && !$this->is_simple()) {
            return count($object_ids);
        }

        // See if we can find it in the cache
        if (is_int($this->_state['total'])) {
            return $this->_state['total'];
        }

        if ($this->_cache !== []) {
            return count($this->_cache);
        }

        $this->_state['total'] = $this->_count_rows();

        return $this->_state['total'];
    }

    /**
     * get_type
     * This returns the type of the browse we currently are using
     */
    public function get_type(): string
    {
        return $this->_state['type'];
    }

    /**
     * is_simple
     * This returns whether or not the current browse type is set to static.
     */
    public function is_simple(): bool
    {
        return $this->_state['simple'];
    }

    /**
     * is_skip_catalog_check
     */
    public function is_skip_catalog_check(): bool
    {
        return make_bool($this->_state['skip_catalog_check']);
    }

    /**
     * is_static_content
     */
    public function is_static_content(): bool
    {
        return make_bool($this->_state['static']);
    }

    /**
     * reset
     * Reset everything, this should only be called when we are starting fresh
     */
    public function reset(): void
    {
        $this->_state['base']   = null;
        $this->_state['select'] = [];
        $this->_state['join']   = [];
        $this->_state['filter'] = [];
        $this->_state['having'] = '';
        $this->_state['total']  = null;
        $this->_state['sort']   = [
            'name' => null,
            'order' => null,
        ];
        $this->set_static_content(false);
        $this->set_simple_browse(false);
        $this->set_start(0);
        $this->set_offset(AmpConfig::get_int('offset_limit', 50));
    }

    /**
     * save_objects
     * This takes the full array of object ids, often passed into show and if necessary it saves them
     * @param int[]|string[]|array<array{object_id: int,object_type: LibraryItemEnum|string,track_id: int,track: int}>|array<int, array{name?: string|null, id: int, track: int, raw: string, link?: string|null, track: int, oid?: int, vlid?: int}> $object_ids
     */
    public function save_objects(array $object_ids): bool
    {
        // Saving these objects has two operations, one holds it in
        // a local variable and then second holds it in a row in the
        // tmp_browse table

        // Only do this if it's not a simple browse
        if (!$this->is_simple()) {
            $this->_cache = $object_ids;
            $this->set_total(count($object_ids));
            $browse_id = $this->id;
            if ($browse_id != 'nocache') {
                self::getTmpBrowseRepository()->updateObjectData(
                    (int) $browse_id,
                    (string) session_id(),
                    $this->_serialize($this->_cache)
                );
            }
        }

        return true;
    }

    /**
     * set_api_filter
     *
     * Do some value checks for api input before attempting to set the query filter
     */
    public function set_api_filter(string $filter, bool|int|string|null $value): void
    {
        if (!strlen((string) $value)) {
            return;
        }

        switch ($filter) {
            case 'add':
                // Check for a range, if no range default to gt
                if (strpos((string) $value, '/')) {
                    $elements = explode('/', (string) $value);
                    $this->set_filter('add_lt', strtotime($elements[1]));
                    $this->set_filter('add_gt', strtotime($elements[0]));
                } else {
                    $this->set_filter('add_gt', strtotime((string) $value));
                }

                break;
            case 'update':
                // Check for a range, if no range default to gt
                if (strpos((string) $value, '/')) {
                    $elements = explode('/', (string) $value);
                    $this->set_filter('update_lt', strtotime($elements[1]));
                    $this->set_filter('update_gt', strtotime($elements[0]));
                } else {
                    $this->set_filter('update_gt', strtotime((string) $value));
                }

                break;
            case 'alpha_match':
                $this->set_filter('alpha_match', $value);
                break;
            case 'exact_match':
                $this->set_filter('exact_match', $value);
                break;
        }
    }

    /**
     * set_catalog
     */
    public function set_catalog(?int $catalog_number = 0): void
    {
        $this->catalog = (int) $catalog_number;
    }

    /**
     * set_conditions
     *
     * Apply additional filters to the Query using ';' separated comma string pairs
     * e.g. 'filter1,value1;filter2,value2'
     */
    public function set_conditions(string $cond): void
    {
        foreach ((explode(';', $cond)) as $condition) {
            $filter = (explode(',', $condition));
            if (!empty($filter[0])) {
                $this->set_filter(strtolower($filter[0]), (($filter[1] ?? '') ?: null));
            }
        }
    }

    /**
     * set_filter
     * This saves the filter data we pass it from the ObjectQuery FILTERS array
     */
    public function set_filter(string $key, mixed $value): bool
    {
        switch ($key) {
            case 'access':
            case 'add_gt':
            case 'add_lt':
            case 'album_artist':
            case 'album_disk':
            case 'album':
            case 'artist':
            case 'catalog_enabled':
            case 'catalog':
            case 'collection_open':
            case 'collection_type':
            case 'collection_user':
            case 'disabled':
            case 'disk':
            case 'enabled':
            case 'folder':
            case 'int_id':
            case 'label':
            case 'license':
            case 'min_count':
            case 'playlist_open':
            case 'playlist_user':
            case 'podcast':
            case 'rated':
            case 'season_eq':
            case 'season_gt':
            case 'season_lg':
            case 'season_lt':
            case 'song_artist':
            case 'to_user':
            case 'top50':
            case 'unplayed':
            case 'update_gt':
            case 'update_lt':
            case 'user':
            case 'user_flag':
            case 'user_rating':
            case 'year_eq':
            case 'year_gt':
            case 'year_lg':
            case 'year_lt':
                $this->_state['filter'][$key] = (int) ($value);
                break;
            case 'regex_match':
            case 'regex_not_match':
                if ($this->is_static_content() || strlen((string) $value) > self::MAX_REGEX_FILTER_LENGTH) {
                    return false;
                }

                $this->_state['filter'][$key] = $value;
                if ($key === 'regex_match') {
                    unset($this->_state['filter']['regex_not_match']);
                }

                if ($key === 'regex_not_match') {
                    unset($this->_state['filter']['regex_match']);
                }

                break;
            case 'alpha_match':
            case 'equal':
            case 'exact_match':
            case 'like':
            case 'not_starts_with':
            case 'starts_with':
                if ($this->is_static_content()) {
                    return false;
                }

                $this->_state['filter'][$key] = $value;
                break;
            case 'playlist_type':
                // 0 = your user only, 1 = public or your user (User is found using GLOBAL)
                if (isset($this->_state['filter']['playlist_type'])) {
                    $this->_state['filter'][$key] = ($this->_state['filter'][$key] == 1) ? 0 : 1;
                } else {
                    $this->_state['filter'][$key] = 1;
                }

                break;
            case 'no_genre':
                $this->_state['filter'][$key] = 1;
                // remove any existing genre filter
                unset($this->_state['filter']['tag']);
                break;
            case 'genre':
            case 'tag':
                // array values
                if (is_array($value)) {
                    $this->_state['filter'][$key] = $value;
                } elseif (is_numeric($value)) {
                    $this->_state['filter'][$key] = [$value];
                } else {
                    $this->_state['filter'][$key] = [];
                }

                // remove any existing no_genre filter
                unset($this->_state['filter']['no_genre']);
                break;
            case 'mood':
                // array values; a mood is unrelated to the genre filters, so it clears none of them
                if (is_array($value)) {
                    $this->_state['filter'][$key] = $value;
                } elseif (is_numeric($value)) {
                    $this->_state['filter'][$key] = [$value];
                } else {
                    $this->_state['filter'][$key] = [];
                }

                break;
            default:
                // you might be trying to set an invalid filter that doesn't exist
                $type = (empty($this->get_type()))
                    ? 'NO_TYPE'
                    : $this->get_type();

                // warn about weird filters
                if (!in_array($key, self::get_allowed_filters($type))) {
                    debug_event(self::class, 'set_filter: UNKNOWN FILTER ' . $type . ': ' . $key, 5);
                }

                // string / unfiltered
                $this->_state['filter'][$key] = $value;
                break;
        }

        // ensure joins are set on $this->_state
        $this->_get_filter_sql();

        // If we've set a filter we need to reset the totals
        $this->_state['total'] = null;
        $this->set_start(0);

        return true;
    }

    /**
     * set_group
     * This sets the "GROUP" part of the query
     */
    public function set_group(string $column, string $value, int $priority): void
    {
        $this->_state['group'][$priority][$column] = $value;
    }

    /**
     * set_having
     * This sets the "HAVING" part of the query, we can only have one.
     */
    public function set_having(string $condition): void
    {
        $this->_state['having'] = $condition;
    }

    /**
     * set_join
     * This sets the joins for the current browse object
     */
    public function set_join(string $type, string $table, string $source, string $dest, int $priority): void
    {
        // An operand is empty when a caller interpolates a null, as the rating and flag joins do with no user
        $this->_state['join'][$priority][$table] = sprintf('%s JOIN %s ON %s = %s', $type, $table, $source, ($dest === '') ? 'NULL' : $dest);
    }

    /**
     * set_join_and
     * This sets the joins for the current browse object and a second option as well
     */
    public function set_join_and(
        string $type,
        string $table,
        string $source1,
        string $dest1,
        string $source2,
        string $dest2,
        int $priority,
    ): void {
        // Empty operands become NULL for the reason given on set_join()
        $this->_state['join'][$priority][$table] = strtoupper($type) . sprintf(' JOIN %s ON %s = %s AND %s = %s', $table, $source1, ($dest1 === '') ? 'NULL' : $dest1, $source2, ($dest2 === '') ? 'NULL' : $dest2);
    }

    /**
     * set_join_and_and
     * This sets the joins for the current browse object and a second option as well
     */
    public function set_join_and_and(
        string $type,
        string $table,
        string $source1,
        string $dest1,
        string $source2,
        string $dest2,
        string $source3,
        string $dest3,
        int $priority,
    ): void {
        // Empty operands become NULL for the reason given on set_join()
        $this->_state['join'][$priority][$table] = strtoupper($type) . sprintf(' JOIN %s ON %s = %s AND %s = %s AND %s = %s', $table, $source1, ($dest1 === '') ? 'NULL' : $dest1, $source2, ($dest2 === '') ? 'NULL' : $dest2, $source3, ($dest3 === '') ? 'NULL' : $dest3);
    }

    /**
     * set_limit
     * This sets the current offset of this query
     */
    public function set_limit(int $limit): void
    {
        $this->_state['limit'] = abs($limit);
    }

    /**
     * set_match_mode
     */
    public function set_match_mode(string $mode): void
    {
        if (in_array($mode, self::MATCH_MODES, true)) {
            $this->_state['match_mode'] = $mode;
        }
    }

    /**
     * set_offset
     * This sets the current offset of this query
     */
    public function set_offset(int $offset): void
    {
        $this->_state['offset'] = abs($offset);
    }

    /**
     * set_select
     * This appends more information to the select part of the SQL
     * statement, we're going to move to the %%SELECT%% style queries, as I
     * think it's the only way to do this...
     */
    public function set_select(string $field): void
    {
        $this->_state['select'] = [$field];
    }

    /**
     * set_simple_browse
     * This sets the current browse object to a 'simple' browse method
     * which means use the base query provided and expand from there
     */
    public function set_simple_browse(bool $value): void
    {
        $this->_state['simple'] = make_bool($value);
    }

    /**
     * set_skip_catalog_check
     * This allows you to bypass catalog state checks when you have already checked the parent
     * This will speed up getting sub-items when you are sure it's been checked
     */
    public function set_skip_catalog_check(bool $value): void
    {
        $this->_state['skip_catalog_check'] = make_bool($value);
    }

    /**
     * set_sort
     * This sets the current sort(s)
     */
    public function set_sort(string $sort, ?string $order = '', bool $resort = true): void
    {
        // Don't allow pointless sorts
        if (
            !empty($this->get_type())
            && $this->queryType !== null
            && !in_array($sort, $this->queryType->get_sorts())
        ) {
            debug_event(self::class, 'IGNORED set_sort ' . $this->get_type() . ': ' . $sort, 5);

            return;
        }

        if (!empty($order)) {
            $order = ($order === 'DESC')
                ? 'DESC'
                : 'ASC';
        } else {
            // if the sort already exists you want the reverse
            $state = ($this->_state['sort']['name'] === $sort)
                ? $this->_state['sort']['order']
                : self::SORT_ORDER[$sort] ?? 'DESC';
            $order = ($state == 'ASC')
                ? 'DESC'
                : 'ASC';
        }

        $this->_state['sort'] = [
            'name' => $sort,
            'order' => $order,
        ];

        $this->_rebuild_joins();

        if ($resort) {
            $this->_resort_objects();
        }
    }

    /**
     * set_sort_order
     *
     * Try to clean up sorts into something valid before sending to the Query
     * @param string[] $default
     */
    public function set_sort_order(string $sort, array $default): void
    {
        $sort      = array_map(trim(...), explode(',', $sort));
        $sort_name = $sort[0] ?: $default[0];
        $sort_type = $sort[1] ?? $default[1];
        if (empty($sort_name) || empty($sort_type)) {
            return;
        }

        $this->set_sort(strtolower($sort_name), strtoupper($sort_type), false);
    }

    /**
     * set_start
     * This sets the start point for our show functions
     * We need to store this in the session so that it can be pulled
     * back, if they hit the back button
     */
    public function set_start(int $start): void
    {
        $this->_state['start'] = $start;
    }

    /**
     * set_static_content
     * This sets true/false if the content of this browse
     * should be static, if they are then content filtering/altering
     * methods will be skipped
     */
    public function set_static_content(bool $value): void
    {
        $this->_state['static'] = make_bool($value);
    }

    /**
     * set_total
     * This sets the total number of objects
     */
    public function set_total(int $total): void
    {
        $this->_state['total'] = $total;
    }

    /**
     * set_type
     * This sets the type of object that we want to browse by
     * we do this here so we only have to maintain a single whitelist
     * and if I want to change the location I only have to do it here
     */
    public function set_type(string $type, ?string $custom_base = '', ?array $parameters = []): void
    {
        switch ($type) {
            case 'album':
                $this->queryType = new AlbumQuery();
                break;
            case 'album_disk':
                $this->queryType = new AlbumDiskQuery();
                break;
            case 'artist':
                $this->queryType = new ArtistQuery();
                break;
            case 'broadcast':
                $this->queryType = new BroadcastQuery();
                break;
            case 'catalog':
                $this->queryType = new CatalogQuery();
                break;
            case 'collection':
                $this->queryType = new CollectionQuery();
                break;
            case 'collection_items':
                $this->queryType = new CollectionItemsQuery();
                break;
            case 'democratic':
                $this->queryType = new DemocraticQuery();
                break;
            case 'folder':
                $this->queryType = new FolderQuery();
                break;
            case 'follower':
                $this->queryType = new FollowerQuery();
                break;
            case 'label':
                $this->queryType = new LabelQuery();
                break;
            case 'license':
            case 'license_hidden':
                $this->queryType = new LicenseQuery();
                break;
            case 'live_stream':
                $this->queryType = new LiveStreamQuery();
                break;
            case 'playlist_localplay':
                $this->queryType = new PlaylistLocalplayQuery();
                break;
            case 'playlist_media':
                $this->queryType = new PlaylistMediaQuery();
                break;
            case 'playlist_search':
                $this->queryType = new PlaylistSearchQuery();
                break;
            case 'playlist':
                $this->queryType = new PlaylistQuery();
                break;
            case 'podcast_episode':
                $this->queryType = new PodcastEpisodeQuery();
                break;
            case 'podcast':
                $this->queryType = new PodcastQuery();
                break;
            case 'pvmsg':
                $this->queryType = new PvmsgQuery();
                break;
            case 'share':
                $this->queryType = new ShareQuery();
                break;
            case 'shoutbox':
                $this->queryType = new ShoutboxQuery();
                break;
            case 'smartplaylist':
                $this->queryType = new SmartplaylistQuery();
                break;
            case 'song_preview':
                $this->queryType = new SongPreviewQuery();
                break;
            case 'song':
                $this->queryType = new SongQuery();
                break;
            case 'genre':
            case 'tag_hidden':
            case 'tag':
                $this->queryType = new TagQuery();
                break;
            case 'mood':
                $this->queryType = new MoodQuery();
                break;
            case 'user':
                $this->queryType = new UserQuery();
                break;
            case 'video':
                $this->queryType = new VideoQuery();
                break;
            case 'wanted':
                $this->queryType = new WantedQuery();
                break;
        }

        if ($this->queryType !== null) {
            // Set it
            $this->_state['type'] = $type;
            // don't overwrite an existing browse with defaults
            if (
                !empty($custom_base)
                || !$this->_state['base']
            ) {
                $this->_set_base_sql(true, $custom_base, $parameters);
            }
        }
    }

    /**
     * set_user_id
     */
    public function set_user_id(User $user): void
    {
        $this->user_id = $user->getId();
    }

    /**
     * store
     * This saves the current state to the database
     */
    public function store(): void
    {
        $browse_id = $this->id;
        if ($browse_id != 'nocache') {
            self::getTmpBrowseRepository()->updateState(
                (int) $browse_id,
                (string) session_id(),
                $this->_serialize($this->_state)
            );
        }
    }

    /**
     * _count_rows
     * Ask the database how many rows the current filters match. Counting by reading every row costs the whole
     * result set in memory to produce one integer, and the sort it would be read in does not affect the answer.
     */
    private function _count_rows(): int
    {
        $sql = $this->_get_sql(false, false);
        if (trim($sql) === '') {
            return 0;
        }

        $row = Dba::fetch_row(Dba::read(sprintf('SELECT COUNT(*) FROM (%s) AS `count_query`', $sql), $this->_state['params']));

        return (int) ($row[0] ?? 0);
    }

    /**
     * _get_base_sql
     * This returns the base sql statement all parsed up, this should be
     * called after all set operations.
     */
    private function _get_base_sql(): string
    {
        return str_replace("%%SELECT%%", $this->_get_select(), ($this->_state['base'] ?? ''));
    }

    /**
     * _get_custom_join_sql
     *
     * A custom base is a query yielding ids. Joining it as a derived table keeps its scope separate, so an
     * unqualified column inside it cannot bind to the outer query and silently correlate.
     */
    private function _get_custom_join_sql(): string
    {
        $custom = (string) ($this->_state['custom_sql'] ?? '');
        if ($custom === '' || $this->queryType === null) {
            return '';
        }

        return sprintf('JOIN (%s) AS `custom_base` ON `custom_base`.`id` = %s ', $custom, $this->queryType->get_select());
    }

    /**
     * _get_filter_sql
     * This returns the filter part of the sql statement
     */
    private function _get_filter_sql(): string
    {
        if (!is_array($this->_state['filter'])) {
            return '';
        }

        $type = $this->get_type();
        $sql  = "WHERE";

        foreach ($this->_state['filter'] as $key => $value) {
            $sql .= $this->_sql_filter($key, $value);
        }

        if (!$this->is_skip_catalog_check() && AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'], true)) {
            // Add catalog enabled filter. ($this->_sql_filter( will add ' AND ' to the end of filters)
            $sql .= ($sql === "WHERE")
                ? ' ' . Catalog::get_enable_filter($type, '`' . $type . '`.`id`') . ' AND '
                : Catalog::get_enable_filter($type, '`' . $type . '`.`id`') . ' AND ';
        }

        if (!$this->is_skip_catalog_check() && AmpConfig::get('catalog_filter')) {
            // Add catalog user filter
            switch ($type) {
                case 'album_disk':
                case 'album':
                case 'artist':
                case 'folder':
                case 'label':
                case 'live_stream':
                case 'playlist':
                case 'podcast_episode':
                case 'podcast':
                case 'share':
                case 'song_album':
                case 'song_artist':
                case 'song':
                case 'genre':
                case 'tag':
                case 'video':
                    // `genre` is the row-list view of the `tag` table, so it takes the same filter
                    $filter_type = ($type === 'genre') ? 'tag' : $type;
                    $sql .= ($sql === "WHERE")
                        ? ' ' . Catalog::get_user_filter($filter_type, $this->user_id ?? -1)
                        : Catalog::get_user_filter($filter_type, $this->user_id ?? -1);
                    break;
            }
        }

        // each fragment ends in ' AND ', and a WHERE that collected no filters has to disappear completely
        if (str_ends_with($sql, ' AND ')) {
            $sql = substr($sql, 0, -5);
        }

        $sql = rtrim($sql);

        return ($sql === 'WHERE')
            ? ' '
            : $sql . ' ';
    }

    /**
     * _get_group_sql
     * This returns the joins that this browse may need to work correctly
     */
    private function _get_group_sql(): string
    {
        if (empty($this->_state['group']) || !is_array($this->_state['group'])) {
            return '';
        }

        $sql = '';
        foreach ($this->_state['group'] as $groups) {
            foreach ($groups as $group) {
                $sql .= $group . ', ';
            }
        }

        return rtrim($sql, ', ');
    }

    /**
     * _get_having_sql
     * this returns the having sql stuff, if we've got anything
     */
    private function _get_having_sql(): string
    {
        return $this->_state['having'];
    }

    /**
     * _get_join_sql
     * This returns the joins that this browse may need to work correctly
     */
    private function _get_join_sql(): string
    {
        if (empty($this->_state['join']) || !is_array($this->_state['join'])) {
            return '';
        }

        $sql = '';

        foreach ($this->_state['join'] as $joins) {
            // don't add false joins
            if (!$joins) {
                continue;
            }

            foreach ($joins as $join) {
                $sql .= $join . ' ';
            }
        }

        return $sql;
    }

    /**
     * _get_limit_sql
     * This returns the limit part of the sql statement
     */
    private function _get_limit_sql(): string
    {
        $offset = $this->get_offset();
        if ($this->_state['limit'] > 0) {
            // MySQL reads `LIMIT a, b` as skip a take b, so the start goes first and the row count second
            if ($this->get_start() > 0) {
                return ' LIMIT ' . $this->get_start() . ', ' . $this->_state['limit'];
            }

            return ' LIMIT ' . $this->_state['limit'];
        }

        $start = $this->get_start();
        if (!$this->is_simple() || $start < 0 || ($start === 0 && $offset === 0)) {
            return '';
        }

        return ' LIMIT ' . $start . ', ' . $offset;
    }

    /**
     * _get_select
     * This returns the selects in a format that is friendly for a sql
     * statement.
     */
    private function _get_select(): string
    {
        return implode(", ", $this->_state['select'] ?? []);
    }

    /**
     * _get_sort_sql
     * Returns the sort sql part
     */
    private function _get_sort_sql(): string
    {
        if (empty($this->_state['sort'])) {
            return '';
        }

        $sort_sql = rtrim($this->_sql_sort($this->_state['sort']['name'], $this->_state['sort']['order']), ', ');

        return ($sort_sql === '')
            ? ''
            : 'ORDER BY ' . $sort_sql;
    }

    /**
     * _get_sql
     * This returns the sql statement we are going to use this has to be run
     * every time we get the objects because it depends on the filters and
     * the type of object we are currently browsing.
     */
    private function _get_sql(?bool $limit = true, bool $sort = true): string
    {
        // a browse stored before custom_sql existed still holds its custom query in base
        if ($this->_state['custom'] && empty($this->_state['custom_sql'])) {
            $final_sql = $this->_get_base_sql();
        } else {
            // filter and sort set joins as well as group so make sure you run those first
            $filter_sql = $this->_get_filter_sql();
            $sort_sql   = $this->_get_sort_sql();
            // regular queries need to be joined with all the other parts
            $final_sql = $this->_get_base_sql()
                . $this->_get_custom_join_sql()
                . $this->_get_join_sql()
                . $filter_sql
                . $this->_get_having_sql();

            // allow forcing a group by
            if (!empty($this->_get_group_sql())) {
                $final_sql .= " GROUP BY " . $this->_get_group_sql() . " ";
            } elseif ($this->get_type() === 'artist' || $this->get_type() === 'album') {
                $final_sql .= " GROUP BY `" . $this->get_type() . "`.`name`, `" . $this->get_type() . "`.`id` ";
            }

            // the sort is built either way, because building it is what registers the joins it reaches through
            if ($sort) {
                $final_sql .= $sort_sql;
            }
        }

        // apply a limit/offset limit (if set)
        $limit_sql = ($limit) ? $this->_get_limit_sql() : '';
        //debug_event(self::class, "get_sql: " . $final_sql, 5);

        return $final_sql . $limit_sql;
    }

    /**
     * _post_process
     * This does some additional work on the results that we've received
     * before returning them. TODO this is only for tags/genres? should do this in the select/return if possible
     * @return array<array{id: int}>
     */
    private function _post_process(array $data): array
    {
        $tags = $this->_state['filter']['tag'] ?? '';

        if (!is_array($tags) || count($tags) < 2) {
            return $data;
        }

        $tag_count = count($tags);
        $count     = [];

        foreach ($data as $row) {
            ++$count[$row['id']];
        }

        $results = [];

        foreach ($count as $key => $value) {
            if ($value >= $tag_count) {
                $results[] = ['id' => (int) $key];
            }
        }

        return $results;
    }

    /**
     * _rebuild_joins
     * A query collects the joins it needs as its filter and sort SQL is built, so the set it holds belongs to the
     * filter and sort it was built for. Changing either drops that set and builds both again to collect the
     * joins now in play; the SQL itself is discarded because only the joins are wanted.
     */
    private function _rebuild_joins(): void
    {
        $this->_state['join'] = [];
        $this->_get_filter_sql();
        $this->_get_sort_sql();
    }

    /**
     * _resort_objects
     * This takes the existing objects, looks at the current
     * sort method and then re-sorts them This is internally
     * called by the set_sort() function
     */
    private function _resort_objects(): void
    {
        // There are two ways to do this.. the easy way...
        // and the vollmer way, hopefully we don't have to
        // do it the vollmer way
        if ($this->is_simple()) {
            $sql = $this->_get_sql();
        } else {
            // FIXME: this is fragile for large browses
            // First pull the objects
            $object_ids = $this->get_saved();

            // If there's nothing there don't do anything
            if ($object_ids === []) {
                return;
            }

            $type      = $this->get_type();
            $where_sql = sprintf('WHERE `%s`.`id` IN (', $type);

            foreach ($object_ids as $object_id) {
                // a mixed list keeps the order it is curated in; its rows are not ids this can sort by
                if (!is_int($object_id) && !is_string($object_id)) {
                    return;
                }

                $object_id = Dba::escape($object_id);
                $where_sql .= sprintf("'%s',", $object_id);
            }

            $where_sql = rtrim($where_sql, ', ');

            $where_sql .= ")";

            $sql = $this->_get_base_sql();

            // grouping by the sort as well splits the row per joined value, so an album with three artists repeats
            $group_sql = ' GROUP BY `' . $this->get_type() . '`.`id`';

            // There should only be one of these in a browse
            $sql_sort = $this->_sql_sort($this->_state['sort']['name'], $this->_state['sort']['order']);

            // the sort carries a trailing comma, and a browse with nothing to sort by must not emit a bare ORDER BY
            $sql_sort  = rtrim($sql_sort, ', ');
            $order_sql = ($sql_sort === '')
                ? ''
                : ' ORDER BY ' . $sql_sort;

            $sql = $sql . $this->_get_join_sql() . $where_sql . $group_sql . $order_sql;
        } // if not simple

        $db_results = Dba::read($sql, $this->_state['params']);
        //debug_event(self::class, "_resort_objects: " . $sql, 5);

        $results = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        $this->save_objects($results);
    }

    /**
     * _serialize
     *
     * Attempts to produce a more compact representation for large result
     * sets by collapsing ranges.
     */
    private function _serialize(array $data): string
    {
        return json_encode($data) ?: '';
    }

    /**
     * _set_base_sql
     * This saves the base sql statement we are going to use.
     */
    private function _set_base_sql(?bool $force = false, ?string $custom_base = '', ?array $parameters = []): void
    {
        // Only allow it to be set once
        if (!empty((string) $this->_state['base']) && !$force) {
            return;
        }

        // Custom sql restricts the normal base to the ids it yields, so the base itself stays intact
        if ($force && !empty($custom_base)) {
            $this->_state['custom']     = true;
            $this->_state['custom_sql'] = $custom_base;
            $this->_state['params']     = $parameters;
        }

        // TODO we should remove this default fallback and rely on set_type()
        if ($this->queryType === null) {
            $this->queryType = new SongQuery();
        }

        $this->set_select($this->queryType->get_select());

        // tag state should be set as they aren't really separate objects
        if (in_array($this->get_type(), ['license_hidden', 'tag_hidden'], true)) {
            $this->set_filter('hidden', 1);
        }

        if (in_array($this->get_type(), ['genre', 'license', 'tag'], true)) {
            $this->set_filter('hidden', 0);
        }

        $this->_state['base'] = $this->queryType?->get_base_sql();
    }

    /**
     * _sql_filter
     * This takes a filter name and value and if it is possible
     * to filter by this name on this type returns the appropriate sql
     * if not returns nothing
     */
    private function _sql_filter(string $filter, mixed $value): string
    {
        if ($this->queryType === null) {
            $this->set_type($this->_state['type']);
        }

        if ($this->queryType === null) {
            return '';
        }

        return $this->queryType->get_sql_filter($this, $filter, $value);
    }

    /**
     * _sql_sort
     * This builds any order bys we need to do
     * to sort the results as best we can, there is also
     * a logic based sort that will come later as that's
     * a lot more complicated
     */
    private function _sql_sort(?string $field, ?string $order = null): string
    {
        if ($order != 'DESC') {
            $order = 'ASC';
        }

        // random sorting
        if ($field === 'rand') {
            return "RAND()";
        }

        if ($this->queryType === null) {
            $this->set_type($this->_state['type']);
        }

        if ($this->queryType === null) {
            return '';
        }

        return $this->queryType->get_sql_sort($this, $field, $order);
    }

    /**
     * _unserialize
     *
     * Reverses serialization.
     */
    private function _unserialize(string $data): mixed
    {
        return json_decode($data, true);
    }
}
