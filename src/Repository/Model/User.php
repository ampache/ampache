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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Application\Image\ShowUserAvatarAction;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\database_object;
use Ampache\Module\Playback\Tmp_Playlist;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\System\Preference;
use Ampache\Module\User\Authorization\UserKeyGeneratorInterface;
use Ampache\Module\Util\Ui;
use Ampache\Plugin\PluginGetAvatarUrlInterface;
use Ampache\Plugin\PluginSaveMediaplayInterface;
use Ampache\Plugin\PluginStreamControlInterface;
use Ampache\Repository\IpHistoryRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Exception;

/**
 * This class handles all of the user related functions including the creation
 * and deletion of the user objects from the database by default you construct it
 * with a user_id from user.id
 */
class User extends database_object
{
    /** @var int Defines the internal system user-id */
    public const int INTERNAL_SYSTEM_USER_ID = -1;
    protected const string DB_TABLENAME      = 'user';

    public int $access               = 0;
    public ?string $apikey           = null;
    public int $catalog_filter_group = 0;

    /** @var array<string, int[]> $catalogs */
    public array $catalogs = [];

    public ?string $city         = null;
    public ?int $create_date     = null;
    public bool $disabled        = true;
    public ?string $email        = null;
    public ?string $fullname     = null;
    public bool $fullname_public = false;

    // Basic Components
    public int $id = 0;

    // Constructed variables
    public string $ip_history      = '';
    public int $last_seen          = 0;
    public ?string $link           = null;
    public ?Tmp_Playlist $playlist = null;

    /** @var array<string, mixed> $prefs */
    public array $prefs = [];

    public ?string $rsstoken    = null;
    public ?string $state       = null;
    public ?string $streamtoken = null;

    /** @var ?string Encrypted Subsonic password; never the plaintext, decrypt it with the SymmetricEncrypter */
    public ?string $subsonic_secret = null;

    public ?string $username    = null;
    public ?string $validation  = null;
    public ?string $website     = null;
    private ?string $f_link     = null;
    private ?bool $has_art      = null;

    public function __construct(?int $user_id = 0)
    {
        if (!$user_id) {
            return;
        }

        $info = $this->set_info($user_id);
        if (!$info) {
            return;
        }

        // Make sure the Full name is always filled
        if (strlen((string) $this->fullname) < 1) {
            $this->fullname = $this->username;
        }
    }

    /**
     * Caches a set of users in one query rather than one per object
     *
     * @param array<int|string> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        foreach (self::getUserRepository()->getRowsByIds($ids) as $row) {
            parent::add_to_cache('user', (int) $row['id'], $row);
        }

        Art::build_cache($ids, 'user');

        return true;
    }

    /**
     * create
     * inserts a new user into Ampache
     */
    public static function create(
        string $username,
        string $fullname,
        string $email,
        string $website,
        string $password,
        AccessLevelEnum $access,
        ?int $catalog_filter_group = 0,
        ?string $state = '',
        ?string $city = '',
        ?bool $disabled = false,
        ?bool $encrypted = false,
    ): int {
        // don't try to overwrite users that already exist
        if (
            in_array(strtolower($username), [strtolower(T_('System')), 'system'])
            || self::getUserRepository()->idByUsername($username) > 0
            || self::getUserRepository()->idByEmail($email) > 0
        ) {
            return 0;
        }

        // Forbid username or fullname to have an URL (usually spambot)
        $name_filter = AmpConfig::get('user_name_filter');
        if (
            $name_filter
            && (
                preg_match('/' . $name_filter . '/i', $username)
                || preg_match('/' . $name_filter . '/i', $fullname)
            )
        ) {
            debug_event(self::class, 'Checking for spambot: matched regex (' . $name_filter . '). Won\'t create user. ' . json_encode(['username' => $username,'fullname' => $fullname,'ip' => Core::get_user_ip()]), 1);

            return 0;
        }

        // Forbid website with markdown syntax (usually spambot)
        $site_filter = AmpConfig::get('user_website_filter');
        if (
            $site_filter
            && preg_match('/' . $site_filter . '/i', $website)
        ) {
            debug_event(self::class, 'Checking for spambot: matched regex (' . $site_filter . '). Won\'t create user. ' . json_encode(['website' => $website, 'ip' => Core::get_user_ip() ]), 1);

            return 0;
        }

        $website = rtrim($website, "/");
        if (!$encrypted) {
            $password = hash('sha256', $password);
        }

        $disabled = ($disabled) ? 1 : 0;

        // Just in case a zero value slipped in from upper layers...
        $catalog_filter_group ??= 0;

        /* Now Insert this new user */
        $columns = [
            'username' => $username,
            'disabled' => $disabled,
            'fullname' => $fullname,
            'email' => $email,
            'password' => $password,
            'access' => $access->value,
            'catalog_filter_group' => $catalog_filter_group,
            'create_date' => time(),
        ];

        // an omitted optional column is left out of the statement entirely, so the schema default applies
        if ($website !== '' && $website !== '0') {
            $columns['website'] = $website;
        }

        if (!empty($state)) {
            $columns['state'] = $state;
        }

        if (!empty($city)) {
            $columns['city'] = $city;
        }

        if (AmpConfig::get('user_create_streamtoken', false)) {
            $columns['streamtoken'] = bin2hex(random_bytes(20));
        }

        if (AmpConfig::get('user_create_apikey', false)) {
            $columns['apikey'] = hash('md5', time() . $username . bin2hex(random_bytes(20)));
        }

        $insert_id = self::getUserRepository()->create($columns);
        if ($insert_id === 0) {
            return 0;
        }

        // Populates any missing preferences, in this case all of them
        Preference::fix_user_preferences($insert_id);

        Catalog::count_table(CountableTableEnum::USER);

        return $insert_id;
    }

    /**
     * garbage_collection
     *
     * This cleans out users that have not activated in the last 30 days
     */
    public static function garbage_collection(): void
    {
        self::getUserRepository()->garbageCollectUnvalidated();
    }

    /**
     * get_from_global
     */
    public static function get_from_global(): ?User
    {
        $globalUser = Core::get_global('user');

        return (empty($globalUser))
            ? null
            : $globalUser;
    }

    /**
     * get_from_id
     * This returns a built user from an ID. This is a
     * static function so it doesn't require an instance
     */
    public static function get_from_id(int $id): ?User
    {
        return self::getUserRepository()->findById($id);
    }

    /**
     * get_from_username
     * This returns a built user from a username. This is a
     * static function so it doesn't require an instance
     */
    public static function get_from_username(string $username): ?User
    {
        return self::getUserRepository()->findByUsername($username);
    }

    /**
     * get_play_size
     * A user might be missing the play_size so it needs to be calculated
     */
    public static function get_play_size(int $user_id): int
    {
        return self::getUserRepository()->getPlaySize($user_id);
    }

    /**
     * get_user_catalogs
     * This returns the catalogs as an array of ids that this user is allowed to access
     * @return int[]
     */
    public static function get_user_catalogs(int $user_id, string $filter = ''): array
    {
        if (parent::is_cached('user_catalog' . $filter, $user_id)) {
            return parent::get_from_cache('user_catalog' . $filter, $user_id);
        }

        $catalogs = Catalog::get_catalogs($filter, $user_id);

        parent::add_to_cache('user_catalog' . $filter, $user_id, $catalogs);

        return $catalogs;
    }

    /**
     * get_user_data
     * This updates some background data for user specific function
     */
    public static function get_user_data(int $user_id, ?string $key = null, int|string|null $default = null): array
    {
        $results = ($key !== null && $default !== null)
            ? [$key => $default]
            : [];

        return array_merge($results, self::getUserRepository()->getUserData($user_id, ($key) ?: null));
    }

    /**
     * Get item name based on whether they allow public fullname access.
     */
    public static function get_username(int $user_id): string
    {
        $users = self::getUserRepository()->getValidArray(true);

        return $users[$user_id] ?? T_('System');
    }

    /**
     * Get item name based on whether they allow public fullname access.
     * @return string[]
     */
    public static function getValidArray(): array
    {
        return self::getUserRepository()->getValidArray();
    }

    /**
     * is_registered
     * Check if the user is registered
     */
    public static function is_registered(): bool
    {
        if (empty(Core::get_global('user'))) {
            return false;
        }

        if (!Core::get_global('user')->getId()) {
            return false;
        }

        return !(!AmpConfig::get('use_auth') && Core::get_global('user')->access < 5);
    }

    /**
     * save_mediaplay
     */
    public static function save_mediaplay(User $user, Song $media): void
    {
        foreach (Plugin::get_plugins(PluginTypeEnum::SAVE_MEDIAPLAY) as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin instanceof PluginSaveMediaplayInterface && $plugin->load($user)) {
                    debug_event(self::class, 'save_mediaplay... ' . $plugin_name, 5);
                    $plugin->_plugin->save_mediaplay($media);
                }
            } catch (Exception $error) {
                debug_event(self::class, 'save_mediaplay plugin error: ' . $error->getMessage(), 1);
            }
        }
    }

    /**
     * set_user_data
     * This updates some background data for user specific function
     */
    public static function set_user_data(int $user_id, string $key, float|int|string $value): void
    {
        self::getUserRepository()->setUserData($user_id, $key, $value);
    }

    /**
     * stream_control
     * Check all stream control plugins
     */
    public static function stream_control(array $media_ids, ?User $user = null): bool
    {
        if ($user === null) {
            $user = Core::get_global('user');
            if (!$user instanceof User) {
                return false;
            }
        }

        foreach (Plugin::get_plugins(PluginTypeEnum::STREAM_CONTROLLER) as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->_plugin instanceof PluginStreamControlInterface && $plugin->load($user) && !$plugin->_plugin->stream_control($media_ids)) {
                return false;
            }
        }

        return true;
    }

    /**
     * update_counts for individual users
     */
    public static function update_counts(): void
    {
        $catalog_disable = AmpConfig::get('catalog_disable');
        $catalog_filter  = AmpConfig::get('catalog_filter');
        $userRepository  = self::getUserRepository();
        $user_list       = $userRepository->getAllIds();

        // TODO $user_list[] = -1; // make sure the System / Guest user gets a count as well
        if (!$catalog_filter) {
            // no filter means no need for filtering or counting per user
            $count_array = [
                'album_disk',
                'album',
                'artist',
                'catalog',
                'items',
                'label',
                'license',
                'live_stream',
                'playlist',
                'podcast_episode',
                'podcast',
                'search',
                'share',
                'size',
                'song',
                'tag',
                'time',
                'user',
                'video',
            ];
            $server_counts = Catalog::get_server_counts(0);
            debug_event(self::class, 'Update counts for all users', 5);
            foreach ($server_counts as $table => $count) {
                if (in_array($table, $count_array)) {
                    $userRepository->setUserDataForAll($table, $count);
                }
            }

            return;
        }

        $count_array = [
            'album',
            'artist',
            'catalog',
            'label',
            'license',
            'live_stream',
            'playlist',
            'podcast_episode',
            'podcast',
            'search',
            'share',
            'song',
            'tag',
            'user',
            'video',
        ];
        foreach ($user_list as $user_id) {
            $catalog_array = self::get_user_catalogs($user_id);
            debug_event(self::class, 'Update counts for ' . $user_id, 5);
            // get counts per user (filtered catalogs aren't counted)
            foreach ($count_array as $table) {
                // `search`, `user` and `license` carry no catalog, so the filter cannot narrow them
                $filtered = !in_array($table, ['search', 'user', 'license']);

                self::set_user_data($user_id, $table, $userRepository->countForUser($table, $user_id, $filtered));
            }

            // tables with media items to count, song-related tables and the rest
            $media_tables = [
                'song',
                'video',
                'podcast_episode'
            ];
            $items = 0;
            $time  = 0;
            $size  = 0;
            foreach ($media_tables as $table) {
                if ($catalog_array === []) {
                    continue;
                }

                $totals = $userRepository->getMediaTotals($table, $catalog_array, (bool) $catalog_disable);
                // save the object and add to the current size
                $items += $totals['count'];
                $time += $totals['time'];
                $size += $totals['size'];
                self::set_user_data($user_id, $table, $totals['count']);
            }

            self::set_user_data($user_id, 'items', $items);
            self::set_user_data($user_id, 'time', $time);
            self::set_user_data($user_id, 'size', $size);
            // album_disk counts
            $album_disks = $userRepository->countAlbumDisksForCatalogs(Catalog::get_catalogs('', $user_id, true));
            self::set_user_data($user_id, 'album_disk', $album_disks);
        }
    }

    /**
     * @deprecated inject dependency
     */
    private static function getStats(): Stats
    {
        global $dic;

        return $dic->get(Stats::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }

    /**
     * delete
     * deletes this user and everything associated with it. This will affect
     * ratings and total stats
     */
    public function delete(): bool
    {
        // Before we do anything make sure that they aren't the last admin
        $userRepository = self::getUserRepository();
        if (
            $this->has_access(AccessLevelEnum::ADMIN)
            && !$userRepository->hasOtherAdmin($this->id, false)
        ) {
            return false;
        } // if this is an admin check for others

        // the user itself, its custom access settings and any session it left behind
        $userRepository->delete($this->id, (string) $this->username);
        self::remove_from_cache('user', $this->id);

        Catalog::count_table(CountableTableEnum::USER);
        $userRepository->collectGarbage();

        return true;
    }

    public function deleteApiKey(): void
    {
        $this->apikey = null;
        $this->store(UserFieldEnum::APIKEY, null);
    }

    public function deleteAvatar(): void
    {
        $art = new Art($this->id, 'user');
        $art->reset();
    }

    public function deleteRssToken(): void
    {
        $this->rsstoken = null;
        $this->store(UserFieldEnum::RSSTOKEN, null);
    }

    public function deleteStreamToken(): void
    {
        $this->streamtoken = null;
        $this->store(UserFieldEnum::STREAMTOKEN, null);
    }

    public function deleteSubsonicSecret(): void
    {
        $this->subsonic_secret = null;
        $this->store(UserFieldEnum::SUBSONIC_SECRET, null);
    }

    /**
     * disable
     * This disables the current user
     */
    public function disable(): bool
    {
        $userRepository = self::getUserRepository();

        // Make sure we aren't disabling the last admin, which would lock everybody out of the admin pages
        if (!$userRepository->hasOtherAdmin($this->id, true)) {
            return false;
        }

        $userRepository->disableUser($this->id);
        $this->disabled = true;
        self::remove_from_cache('user', $this->id);

        // Delete any sessions they may have
        $userRepository->deleteSessions((string) $this->username);

        return true;
    }

    /**
     * get_avatar
     * Get the user avatar
     */
    public function get_avatar(bool $local = false): array
    {
        $avatar          = [];
        $avatar['title'] = T_('User avatar');
        if ($this->has_art()) {
            $avatar['url'] = sprintf(
                '%s/image.php?action=%s&object_id=%d',
                ($local) ? AmpConfig::get('local_web_path') : AmpConfig::get_web_path(),
                ShowUserAvatarAction::REQUEST_ACTION,
                $this->id
            );

            $avatar['url_mini']   = $avatar['url'];
            $avatar['url_medium'] = $avatar['url'];
            if (AmpConfig::get('upscale_images', true)) {
                $avatar['url'] .= '&size=300x300';
                $avatar['url_mini'] .= '&size=64x64';
                $avatar['url_medium'] .= '&size=160x160';
            } else {
                $avatar['url'] .= '&size=150x150';
                $avatar['url_mini'] .= '&size=32x32';
                $avatar['url_medium'] .= '&size=80x80';
            }
        } else {
            $user = Core::get_global('user');
            if ($user instanceof User) {
                foreach (Plugin::get_plugins(PluginTypeEnum::AVATAR_PROVIDER) as $plugin_name) {
                    $plugin = new Plugin($plugin_name);
                    if ($plugin->_plugin instanceof PluginGetAvatarUrlInterface && $plugin->load($user)) {
                        $avatar['url'] = $plugin->_plugin->get_avatar_url($this);
                        if (!empty($avatar['url'])) {
                            $avatar['url_mini']   = $plugin->_plugin->get_avatar_url($this, 32);
                            $avatar['url_medium'] = $plugin->_plugin->get_avatar_url($this, 64);
                            $avatar['title'] .= ' (' . $plugin_name . ')';
                            break;
                        }
                    }
                }
            }
        }

        if (!array_key_exists('url', $avatar)) {
            $avatar['url']        = (($local) ? AmpConfig::get('local_web_path') : AmpConfig::get_web_path()) . '/images/blankuser.png';
            $avatar['url_mini']   = $avatar['url'];
            $avatar['url_medium'] = $avatar['url'];
        }

        return $avatar;
    }

    /**
     * get_catalogs
     * This returns the catalogs as an array of ids that this user is allowed to access
     * @return int[]
     */
    public function get_catalogs(string $filter): array
    {
        if (!isset($this->catalogs[$filter])) {
            $this->catalogs[$filter] = self::get_user_catalogs($this->id, $filter);
        }

        return $this->catalogs[$filter];
    }

    /**
     * Get the user avatar img links
     */
    public function get_f_avatar(string $avatar_type): string
    {
        $avatar = $this->get_avatar();

        if (
            $avatar_type == 'f_avatar'
            && !empty($avatar['url'])
        ) {
            return '<img src="' . $avatar['url'] . '" title="' . $avatar['title'] . '"' . ' width="256px" height="auto" />';
        }

        if (
            $avatar_type == 'f_avatar_mini'
            && !empty($avatar['url_mini'])
        ) {
            return '<img src="' . $avatar['url_mini'] . '" title="' . $avatar['title'] . '" style="width: 32px; height: 32px;" />';
        }

        if (
            $avatar_type == 'f_avatar_medium'
            && !empty($avatar['url_medium'])
        ) {
            return '<img src="' . $avatar['url_medium'] . '" title="' . $avatar['title'] . '" style="width: 64px; height: 64px;" />';
        }

        return '';
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        if ($this->f_link === null) {
            if ($this->getId() === 0) {
                $this->f_link = '';
            } else {
                $this->f_link = '<a href="' . $this->get_link() . '">' . scrub_out($title ?? $this->get_fullname()) . '</a>';
            }
        }

        return $this->f_link;
    }

    /**
     * Get item f_usage.
     */
    public function get_f_usage(): string
    {
        $user_data = self::get_user_data($this->id, 'play_size');
        if (!isset($user_data['play_size'])) {
            $total = self::get_play_size($this->id);
            // set the value for next time
            self::set_user_data($this->id, 'play_size', $total);
            $user_data['play_size'] = $total;
        }

        return Ui::format_bytes($user_data['play_size'], 2, 2);
    }

    /**
     * Get item fullname.
     */
    public function get_fullname(): ?string
    {
        return ($this->fullname_public && !empty($this->fullname))
            ? $this->fullname
            : $this->username;
    }

    /**
     * Get item ip_history.
     */
    public function get_ip_history(): string
    {
        $recent_user_ip = $this->getIpHistoryRepository()->getRecentIpForUser($this);
        if ($recent_user_ip !== null) {
            return ($recent_user_ip !== '' && filter_var($recent_user_ip, FILTER_VALIDATE_IP)) ? $recent_user_ip : T_('Invalid');
        }

        return T_('Not Enough Data');
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null && $this->id > 0) {
            $web_path = AmpConfig::get_web_path();

            $this->link = $web_path . '/stats.php?action=show_user&user_id=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * get_playlists
     * Get your playlists and just your playlists
     */
    public function get_playlists(bool $show_all): array
    {
        return self::getUserRepository()->getPlaylistIds($this->id, $show_all);
    }

    /**
     * get_preferences
     * This is a little more complicated now that we've got many types of preferences
     * This function pulls all of them and arranges them into a spiffy little array
     * You can specify a type to limit it to a single type of preference
     * []['title'] = uppercase type name
     * []['prefs'] = array(array('name', 'display', 'value'));
     * []['admin'] = t/f value if this is an admin only section
     */
    public function get_preferences(int|string $type = 0, bool $system = false): array
    {
        $user_id  = ($system) ? -1 : $this->id;
        $category = ($system && $type != '0') ? (string) $type : null;

        $results    = [];
        $type_array = [];
        /* Ok this is crappy, need to clean this up or improve the code FIXME */
        foreach (self::getUserRepository()->getPreferenceRows($user_id, $category, !$system) as $row) {
            $type  = $row['category'];
            $admin = false;
            if ($type == 'system') {
                $admin = true;
            }

            $type_array[$type][$row['name']] = [
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => T_($row['description']),
                'value' => $row['value'],
                'subcategory' => $row['subcategory'],
                'type' => $row['type'],
            ];
            $results[$type] = [
                'title' => ucwords((string) $type),
                'admin' => $admin,
                'prefs' => $type_array[$type],
            ];
        }

        return $results;
    }

    /**
     * get_recently_played
     * This gets the recently played items for this user respecting
     * the limit passed. ger recent by default or oldest if $newest is false.
     * @return int[]
     */
    public function get_recently_played(
        string $type,
        int $count,
        int $offset = 0,
        bool $newest = true,
        string $count_type = 'stream',
    ): array {
        return self::getUserRepository()->getRecentlyPlayed($this->id, $type, $count_type, $count, $offset, $newest);
    }

    /**
     * Returns a concatenated version of several names
     *
     * In some cases (e.g. admin backend), we want to be as verbose as possible,
     * so show the username and the users full-name (display name).
     */
    public function getFullDisplayName(): string
    {
        return sprintf('%s (%s)', $this->username, $this->fullname);
    }

    public function getId(): int
    {
        return $this->id ?: 0;
    }

    /**
     * The play queue, created on first use. Callers that only read it should
     * use the playlist property, which stays null until something is queued.
     */
    public function getPlaylist(): Tmp_Playlist
    {
        if ($this->playlist === null) {
            $this->playlist = Tmp_Playlist::get_from_session(session_id());
        }

        return $this->playlist;
    }

    /**
     * Returns the value of a certain user-preference
     */
    public function getPreferenceValue(string $preferenceName): int|string|null
    {
        return Preference::get_by_user($this->id, $preferenceName);
    }

    public function getRssToken(): string
    {
        if ($this->rsstoken === null) {
            $this->getUserKeyGenerator()->generateRssToken($this);
        }

        return (string) $this->rsstoken;
    }

    /**
     * Returns the users internal username
     */
    public function getUsername(): string
    {
        return $this->username ?? '';
    }

    /**
     * has_access
     * this function checks to see if this user has access
     * to the passed action (pass a level requirement)
     */
    public function has_access(AccessLevelEnum $needed_level): bool
    {
        if (AmpConfig::get('demo_mode')) {
            return true;
        }

        return $this->access >= $needed_level->value;
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->id, 'user');
        }

        return $this->has_art ?? false;
    }

    /**
     * is_logged_in
     * checks to see if $this user is logged in returns their current IP if they are logged in
     */
    public function is_logged_in(): ?string
    {
        return self::getUserRepository()->findActiveSessionIp(
            (string) $this->username,
            time(),
            (bool) AmpConfig::get('perpetual_api_session')
        );
    }

    /**
     * is_online
     * delay how long since last_seen in seconds default of 20 min
     * calculates difference between now and last_seen
     * if less than delay, we consider them still online
     */
    public function is_online(int $delay = 1200): bool
    {
        return time() - $this->last_seen <= $delay;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * load_playlist
     * This is called once per page load it makes sure that this session
     * has a tmp_playlist, creating it if it doesn't, then sets $this->playlist
     * as a tmp_playlist object that can be fiddled with later on
     */
    public function load_playlist(): void
    {
        if ($this->playlist === null && session_id()) {
            $this->playlist = Tmp_Playlist::find_from_session(session_id());
        }
    }

    /**
     * set_preferences
     * sets the prefs for this specific user
     */
    public function set_preferences(): void
    {
        foreach (self::getUserRepository()->getPreferenceValues($this->id) as $row) {
            $this->prefs[$row['name']] = $row['value'];
        }
    }

    /**
     * update
     * This function is an all encompassing update function that
     * calls the mini ones does all the error checking and all that
     * good stuff
     */
    public function update(array $data): ?int
    {
        if (
            empty($data['username'])
            && in_array(strtolower($data['username']), [strtolower(T_('System')), 'system'])
        ) {
            AmpError::add('username', T_('Username is required'));
        }

        if ($data['password1'] != $data['password2'] && !empty($data['password1'])) {
            AmpError::add('password', T_("Passwords do not match"));
        }

        if (AmpError::occurred()) {
            return null;
        }

        if (!isset($data['fullname_public'])) {
            $data['fullname_public'] = false;
        }

        foreach ($data as $name => $value) {
            if ($name == 'password1') {
                $name = 'password';
            } elseif ($name !== 'fullname_public') {
                $value = scrub_in($value);
            }

            switch ($name) {
                case 'access':
                case 'catalog_filter_group':
                case 'city':
                case 'email':
                case 'fullname_public':
                case 'fullname':
                case 'password':
                case 'state':
                case 'username':
                case 'website':
                    if ($this->$name != $value) {
                        $function = 'update_' . $name;
                        $this->$function($value);
                    }
                    break;
                case 'clear_stats':
                    self::getStats()->clear($this->id);
                    break;
            }
        }

        return $this->id;
    }

    /**
     * update_access
     * updates their access level
     */
    public function update_access(int $new_access): bool
    {
        $userRepository = self::getUserRepository();

        // There must always be at least one admin left if you're reducing access
        if ($new_access < 100 && !$userRepository->hasOtherAdmin($this->id, false)) {
            return false;
        }

        debug_event(self::class, 'Updating access level for ' . $this->id, 4);

        $this->access = $new_access;
        $this->store(UserFieldEnum::ACCESS, $new_access);

        return true;
    }

    public function update_avatar(string $data, string $mime = ''): bool
    {
        debug_event(self::class, 'Updating avatar for ' . $this->id, 4);

        $art = new Art($this->id, 'user');

        return ($art->insert($data, $mime) === true);
    }

    /**
     * update_catalog_filter_group
     * Set a new filter group catalog filter
     */
    public function update_catalog_filter_group(int $new_filter): void
    {
        debug_event(self::class, 'Updating catalog access group', 4);

        $this->catalog_filter_group = $new_filter;
        $this->store(UserFieldEnum::CATALOG_FILTER_GROUP, $new_filter);
    }

    public function update_city(string $new_city): void
    {
        debug_event(self::class, 'Updating city', 4);

        $this->city = $new_city;
        $this->store(UserFieldEnum::CITY, $new_city);
    }

    public function update_email(string $new_email): void
    {
        debug_event(self::class, 'Updating email', 4);

        $this->email = $new_email;
        $this->store(UserFieldEnum::EMAIL, $new_email);
    }

    public function update_fullname(string $new_fullname): void
    {
        debug_event(self::class, 'Updating fullname', 4);

        $this->fullname = $new_fullname;
        $this->store(UserFieldEnum::FULLNAME, $new_fullname);
    }

    public function update_fullname_public(bool|string $new_fullname_public): void
    {
        debug_event(self::class, 'Updating fullname public', 4);

        $this->fullname_public = (bool) $new_fullname_public;
        $this->store(UserFieldEnum::FULLNAME_PUBLIC, ($new_fullname_public) ? '1' : '0');
    }

    public function update_password(string $new_password, ?string $hashed_password = null): void
    {
        debug_event(self::class, 'Updating password', 1);
        if (!$hashed_password) {
            $hashed_password = hash('sha256', $new_password);
        }

        // Clear this (temp fix)
        if ($this->store(UserFieldEnum::PASSWORD, $hashed_password)) {
            unset($_SESSION['userdata']['password']);
        }
    }

    public function update_state(string $new_state): void
    {
        debug_event(self::class, 'Updating state', 4);

        $this->state = $new_state;
        $this->store(UserFieldEnum::STATE, $new_state);
    }

    public function update_username(string $new_username): void
    {
        $this->username = $new_username;

        debug_event(self::class, 'Updating username', 4);

        $this->store(UserFieldEnum::USERNAME, $new_username);
    }

    /**
     * update_validation
     * This is used by the registration mumbojumbo
     * Use this function to update the validation key
     * NOTE: crap this doesn't have update_item the humanity of it all
     */
    public function update_validation(string $new_validation): bool
    {
        $db_results       = self::getUserRepository()->setValidation($this->id, $new_validation);
        $this->validation = $new_validation;
        $this->disabled   = true;
        self::remove_from_cache('user', $this->id);

        return $db_results;
    }

    public function update_website(string $new_website): void
    {
        $new_website = filter_var(urldecode($new_website), FILTER_VALIDATE_URL) ?: null;
        $new_website = (is_string($new_website))
            ? rtrim($new_website, "/")
            : null;
        debug_event(self::class, 'Updating website', 4);

        $this->website = $new_website;
        $this->store(UserFieldEnum::WEBSITE, $new_website);
    }

    /**
     * upload_avatar
     */
    public function upload_avatar(): bool
    {
        $upload = [];
        if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['size'] <= AmpConfig::get('max_upload_size')) {
            $path_info      = pathinfo((string) $_FILES['avatar']['name']);
            $upload['file'] = $_FILES['avatar']['tmp_name'];
            $upload['mime'] = 'image/' . ($path_info['extension'] ?? '');
            if (!in_array(strtolower($path_info['extension'] ?? ''), Art::VALID_TYPES)) {
                return false;
            }

            $image_data = Art::get_from_source($upload, 'user');
            if ($image_data !== '') {
                return $this->update_avatar($image_data, $upload['mime']);
            }
        }

        return true; // only worry about failed uploads
    }

    /**
     * @deprecated inject dependency
     */
    private function getIpHistoryRepository(): IpHistoryRepositoryInterface
    {
        global $dic;

        return $dic->get(IpHistoryRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private function getUserKeyGenerator(): UserKeyGeneratorInterface
    {
        global $dic;

        return $dic->get(UserKeyGeneratorInterface::class);
    }

    /**
     * set_info
     * This function gets the information for this object
     */
    private function set_info(int $user_id): bool
    {
        if (self::is_cached('user', $user_id)) {
            $data = self::get_from_cache('user', $user_id);
        } elseif ($user_id === -1) {
            // If the ID is -1 then send back generic data
            $data = [
                'id' => -1,
                'username' => 'System',
                'fullname' => 'Ampache User',
                'fullname_public' => 1,
                'email' => null,
                'website' => null,
                'access' => 25,
                'disabled' => 0,
                'catalog_filter_group' => 0,
                'catalogs' => self::get_user_catalogs(-1),
                'apikey' => null,
                'rsstoken' => null,
                'streamtoken' => null,
                'subsonic_secret' => null,
                'last_seen' => null,
                'create_date' => null,
                'validation' => null,
                'state' => null,
                'city' => null
            ];
        } else {
            $data = self::getUserRepository()->getRow($user_id) ?? [];
        }

        if (empty($data)) {
            return false;
        }

        self::add_to_cache('user', $user_id, $data);
        $this->id                   = $data['id'];
        $this->username             = $data['username'];
        $this->fullname             = $data['fullname'];
        $this->email                = $data['email'];
        $this->website              = $data['website'];
        $this->apikey               = $data['apikey'];
        $this->access               = $data['access'];
        $this->disabled             = (bool) $data['disabled'];
        $this->last_seen            = (int) $data['last_seen'];
        $this->create_date          = $data['create_date'];
        $this->validation           = $data['validation'];
        $this->state                = $data['state'];
        $this->city                 = $data['city'];
        $this->fullname_public      = (bool) $data['fullname_public'];
        $this->rsstoken             = $data['rsstoken'];
        $this->streamtoken          = $data['streamtoken'];
        $this->subsonic_secret      = $data['subsonic_secret'];
        $this->catalog_filter_group = (int) $data['catalog_filter_group'];

        return true;
    }

    /**
     * Writes one column and drops the cached row, so a later `new User($id)` in this request is not stale
     */
    private function store(UserFieldEnum $field, int|string|null $value): bool
    {
        $result = self::getUserRepository()->setField($this->id, $field, $value);
        self::remove_from_cache('user', $this->id);

        return $result;
    }
}
