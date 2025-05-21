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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Application\Image\ShowUserAvatarAction;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\User\Authorization\UserKeyGeneratorInterface;
use Ampache\Module\Util\Ui;
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
    public const INTERNAL_SYSTEM_USER_ID = -1;

    protected const DB_TABLENAME = 'user';

    // Basic Components
    public int $id = 0;

    public ?string $username = null;

    public ?string $fullname = null;

    public ?string $email = null;

    public ?string $website = null;

    public ?string $apikey = null;

    public int $access = 0;

    public bool $disabled = true;

    public int $last_seen = 0;

    public ?int $create_date = null;

    public ?string $validation = null;

    public ?string $state = null;

    public ?string $city = null;

    public bool $fullname_public = false;

    public ?string $rsstoken = null;

    public ?string $streamtoken = null;

    public int $catalog_filter_group = 0;

    // Constructed variables
    public string $ip_history = '';

    /** @var array<string, mixed> $prefs */
    public array $prefs = [];

    public ?Tmp_Playlist $playlist = null;

    public ?string $link = null;

    private ?string $f_link = null;

    /** @var array<string, array<int>> $catalogs */
    public array $catalogs;

    private ?bool $has_art = null;

    public function __construct(?int $user_id = 0)
    {
        if (!$user_id) {
            return;
        }

        $info = $this->has_info((int)$user_id);
        if ($info === []) {
            return;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        // Make sure the Full name is always filled
        if (strlen((string)$this->fullname) < 1) {
            $this->fullname = $this->username;
        }
    }

    public function getId(): int
    {
        return $this->id ?: 0;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * has_info
     * This function returns the information for this object
     */
    private function has_info(int $user_id): array
    {
        if (self::is_cached('user', $user_id)) {
            return self::get_from_cache('user', $user_id);
        }

        // If the ID is -1 then send back generic data
        if ($user_id === -1) {
            return [
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
            ];
        }

        $sql        = "SELECT `id`, `username`, `fullname`, `email`, `website`, `apikey`, `access`, `disabled`, `last_seen`, `create_date`, `validation`, `state`, `city`, `fullname_public`, `rsstoken`, `streamtoken`, `catalog_filter_group` FROM `user` WHERE `id` = ?;";
        $db_results = Dba::read($sql, [$user_id]);

        $data = Dba::fetch_assoc($db_results);

        self::add_to_cache('user', $user_id, $data);

        return $data;
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
            $this->playlist = Tmp_Playlist::get_from_session(session_id());
        }
    }

    /**
     * get_playlists
     * Get your playlists and just your playlists
     */
    public function get_playlists(bool $show_all): array
    {
        $results = [];
        $sql     = ($show_all)
            ? "SELECT `id` FROM `playlist` WHERE `user` = ? ORDER BY `name`;"
            : "SELECT `id` FROM `playlist` WHERE `user` = ? AND `type` = 'public' ORDER BY `name`;";

        $params     = [$this->id];
        $db_results = Dba::read($sql, $params);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
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
     * get_from_username
     * This returns a built user from a username. This is a
     * static function so it doesn't require an instance
     */
    public static function get_from_username(string $username): ?User
    {
        return self::getUserRepository()->findByUsername($username);
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
        $user_limit = "";
        if (!$system) {
            $user_id    = $this->id;
            $user_limit = "AND `preference`.`category` != 'system'";
        } else {
            $user_id = -1;
            if ($type != '0') {
                $user_limit = "AND `preference`.`category` = '" . Dba::escape($type) . "'";
            }
        }

        $sql        = "SELECT `preference`.`name`, `preference`.`description`, `preference`.`category`, `preference`.`subcategory`, preference.level, user_preference.value FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference` = `preference`.`id` WHERE `user_preference`.`user` = ? " . $user_limit . " ORDER BY `preference`.`category`, `preference`.`subcategory`, `preference`.`description`";
        $db_results = Dba::read($sql, [$user_id]);
        $results    = [];
        $type_array = [];
        /* Ok this is crappy, need to clean this up or improve the code FIXME */
        while ($row = Dba::fetch_assoc($db_results)) {
            $type  = $row['category'];
            $admin = false;
            if ($type == 'system') {
                $admin = true;
            }

            $type_array[$type][$row['name']] = [
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'subcategory' => $row['subcategory'],
            ];
            $results[$type]                  = [
                'title' => ucwords((string)$type),
                'admin' => $admin,
                'prefs' => $type_array[$type],
            ];
        }

        return $results;
    }

    /**
     * set_preferences
     * sets the prefs for this specific user
     */
    public function set_preferences(): void
    {
        $user_id    = Dba::escape($this->id);
        $sql        = "SELECT `preference`.`name`, `user_preference`.`value` FROM `preference`, `user_preference` WHERE `user_preference`.`user` = ? AND `user_preference`.`preference` = `preference`.`id` AND `preference`.`type` != 'system';";
        $db_results = Dba::read($sql, [$user_id]);

        while ($row = Dba::fetch_assoc($db_results)) {
            $key               = $row['name'];
            $this->prefs[$key] = $row['value'];
        }
    }

    /**
     * is_logged_in
     * checks to see if $this user is logged in returns their current IP if they are logged in
     */
    public function is_logged_in(): ?string
    {
        $sql = (AmpConfig::get('perpetual_api_session'))
            ? "SELECT `id`, `ip` FROM `session` WHERE `username` = ? AND ((`expire` = 0 AND `type` = 'api') OR `expire` > ?);"
            : "SELECT `id`, `ip` FROM `session` WHERE `username` = ? AND `expire` > ?;";
        $db_results = Dba::read($sql, [$this->username, time()]);

        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['ip'] ?? null;
        }

        return null;
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
     * set_user_data
     * This updates some background data for user specific function
     */
    public static function set_user_data(int $user_id, string $key, float|int|string $value): void
    {
        Dba::write("REPLACE INTO `user_data` SET `user` = ?, `key` = ?, `value` = ?;", [$user_id, $key, $value]);
    }

    /**
     * get_user_data
     * This updates some background data for user specific function
     */
    public static function get_user_data(int $user_id, string $key = null, int|string $default = null): array
    {
        $sql    = "SELECT `key`, `value` FROM `user_data` WHERE `user` = ?";
        $params = [$user_id];
        if ($key) {
            $sql .= " AND `key` = ?";
            $params[] = $key;
        }

        $db_results = Dba::read($sql, $params);
        $results    = ($key !== null && $default !== null)
            ? [$key => $default]
            : [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['key']] = $row['value'];
        }

        return $results;
    }

    /**
     * get_play_size
     * A user might be missing the play_size so it needs to be calculated
     */
    public static function get_play_size(int $user_id): int
    {
        $params = [$user_id];
        $total  = 0;
        $sql_s  = "SELECT IFNULL(SUM(`size`)/1024/1024, 0) AS `size` FROM `object_count` LEFT JOIN `song` ON `song`.`id`=`object_count`.`object_id` AND `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = ?;";
        $db_s   = Dba::read($sql_s, $params);
        while ($results = Dba::fetch_assoc($db_s)) {
            $total += (int)$results['size'];
        }

        $sql_v = "SELECT IFNULL(SUM(`size`)/1024/1024, 0) AS `size` FROM `object_count` LEFT JOIN `video` ON `video`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'video' AND `object_count`.`user` = ?;";
        $db_v  = Dba::read($sql_v, $params);
        while ($results = Dba::fetch_assoc($db_v)) {
            $total += (int)$results['size'];
        }

        $sql_p = "SELECT IFNULL(SUM(`size`)/1024/1024, 0) AS `size` FROM `object_count`LEFT JOIN `podcast_episode` ON `podcast_episode`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`user` = ?;";
        $db_p  = Dba::read($sql_p, $params);
        while ($results = Dba::fetch_assoc($db_p)) {
            $total += (int)$results['size'];
        }

        return $total;
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
            empty($data['username']) &&
            in_array(strtolower($data['username']), [strtolower(T_('System')), 'system'])
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
            } else {
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
                    Stats::clear($this->id);
                    break;
            }
        }

        return $this->id;
    }

    /**
     * update_catalog_filter_group
     * Set a new filter group catalog filter
     */
    public function update_catalog_filter_group(int $new_filter): void
    {
        $sql = "UPDATE `user` SET `catalog_filter_group` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating catalog access group', 4);

        Dba::write($sql, [$new_filter, $this->id]);
    }

    public function update_username(string $new_username): void
    {
        $sql            = "UPDATE `user` SET `username` = ? WHERE `id` = ?";
        $this->username = $new_username;

        debug_event(self::class, 'Updating username', 4);

        Dba::write($sql, [$new_username, $this->id]);
    }

    /**
     * update_validation
     * This is used by the registration mumbojumbo
     * Use this function to update the validation key
     * NOTE: crap this doesn't have update_item the humanity of it all
     */
    public function update_validation(string $new_validation): bool
    {
        $sql              = "UPDATE `user` SET `validation` = ?, `disabled`='1' WHERE `id` = ?";
        $db_results       = (Dba::write($sql, [$new_validation, $this->id]) !== false);
        $this->validation = $new_validation;

        return $db_results;
    }

    public function update_fullname(string $new_fullname): void
    {
        $sql = "UPDATE `user` SET `fullname` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating fullname', 4);

        Dba::write($sql, [$new_fullname, $this->id]);
    }

    public function update_fullname_public(bool|string $new_fullname_public): void
    {
        $sql = "UPDATE `user` SET `fullname_public` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating fullname public', 4);

        Dba::write($sql, [($new_fullname_public) ? '1' : '0', $this->id]);
    }

    public function update_email(string $new_email): void
    {
        $sql = "UPDATE `user` SET `email` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating email', 4);

        Dba::write($sql, [$new_email, $this->id]);
    }

    public function update_website(string $new_website): void
    {
        $new_website = filter_var(urldecode($new_website), FILTER_VALIDATE_URL) ?: null;
        $new_website = (is_string($new_website))
            ? rtrim((string)$new_website, "/")
            : null;
        $sql = "UPDATE `user` SET `website` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating website', 4);

        Dba::write($sql, [$new_website, $this->id]);
    }

    public function update_state(string $new_state): void
    {
        $sql = "UPDATE `user` SET `state` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating state', 4);

        Dba::write($sql, [$new_state, $this->id]);
    }

    public function update_city(string $new_city): void
    {
        $sql = "UPDATE `user` SET `city` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating city', 4);

        Dba::write($sql, [$new_city, $this->id]);
    }

    /**
     * update_counts for individual users
     */
    public static function update_counts(): void
    {
        $catalog_disable = AmpConfig::get('catalog_disable');
        $catalog_filter  = AmpConfig::get('catalog_filter');
        $sql             = "SELECT `id` FROM `user`";
        $db_results      = Dba::read($sql);
        $user_list       = [];
        while ($results = Dba::fetch_assoc($db_results)) {
            $user_list[] = (int)$results['id'];
        }

        // TODO $user_list[] = -1; // make sure the System / Guest user gets a count as well
        if (!$catalog_filter) {
            // no filter means no need for filtering or counting per user
            $count_array   = [
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
            foreach ($user_list as $user_id) {
                debug_event(self::class, 'Update counts for ' . $user_id, 5);
                foreach ($server_counts as $table => $count) {
                    if (in_array($table, $count_array)) {
                        self::set_user_data($user_id, $table, $count);
                    }
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
                $sql = (in_array($table, ['search', 'user', 'license']))
                    ? sprintf('SELECT COUNT(`id`) FROM `%s`', $table)
                    : sprintf('SELECT COUNT(`id`) FROM `%s` WHERE', $table) . Catalog::get_user_filter($table, $user_id);
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_row($db_results);

                self::set_user_data($user_id, $table, (int)($row[0] ?? 0));
            }

            // tables with media items to count, song-related tables and the rest
            $media_tables = [
                'song',
                'video',
                'podcast_episode'
            ];
            $items        = 0;
            $time         = 0;
            $size         = 0;
            foreach ($media_tables as $table) {
                if ($catalog_array === []) {
                    continue;
                }

                $sql = ($catalog_disable)
                    ? sprintf('SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `%s` WHERE `catalog` IN (', $table) . implode(',', $catalog_array) . sprintf(') AND `%s`.`enabled`=\'1\';', $table)
                    : sprintf('SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `%s` WHERE `catalog` IN (', $table) . implode(',', $catalog_array) . ");";
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_row($db_results);
                // save the object and add to the current size
                $items += (int)($row[0] ?? 0);
                $time += (int)($row[1] ?? 0);
                $size += (int)($row[2] ?? 0);
                self::set_user_data($user_id, $table, (int)($row[0] ?? 0));
            }

            self::set_user_data($user_id, 'items', $items);
            self::set_user_data($user_id, 'time', $time);
            self::set_user_data($user_id, 'size', $size);
            // album_disk counts
            $sql        = "SELECT COUNT(DISTINCT `album_disk`.`id`) AS `count` FROM `album_disk` LEFT JOIN `album` ON `album_disk`.`album_id` = `album`.`id` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` WHERE `artist_map`.`object_type` = 'album' AND `album`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ")";
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_row($db_results);
            self::set_user_data($user_id, 'album_disk', (int)($row[0] ?? 0));
        }
    }

    /**
     * disable
     * This disables the current user
     */
    public function disable(): bool
    {
        // Make sure we aren't disabling the last admin
        $sql        = "SELECT `id` FROM `user` WHERE `disabled` = '0' AND `access` = ? AND `id` != ? ";
        $params     = [AccessLevelEnum::ADMIN->value, $this->id];
        $db_results = Dba::read($sql, $params);

        if (Dba::num_rows($db_results) === 0) {
            return false;
        }

        $sql = "UPDATE `user` SET `disabled`='1' WHERE `id`='" . $this->id . "'";
        Dba::write($sql);

        // Delete any sessions they may have
        $sql = "DELETE FROM `session` WHERE `username`='" . Dba::escape($this->username) . "'";
        Dba::write($sql);

        return true;
    }

    /**
     * update_access
     * updates their access level
     */
    public function update_access(int $new_access): bool
    {
        // There must always be at least one admin left if you're reducing access
        if ($new_access < 100) {
            $sql        = "SELECT `id` FROM `user` WHERE `access`= ? AND `id` != ?";
            $params     = [AccessLevelEnum::ADMIN->value, $this->id];
            $db_results = Dba::read($sql, $params);
            if (Dba::num_rows($db_results) === 0) {
                return false;
            }
        }

        $new_access = Dba::escape($new_access);
        $sql        = "UPDATE `user` SET `access` = ? WHERE `id` = ?;";

        debug_event(self::class, 'Updating access level for ' . $this->id, 4);

        Dba::write($sql, [$new_access, $this->id]);

        return true;
    }

    /**
     * save_mediaplay
     */
    public static function save_mediaplay(User $user, Song $media): void
    {
        foreach (Plugin::get_plugins(PluginTypeEnum::SAVE_MEDIAPLAY) as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin !== null && $plugin->load($user)) {
                    debug_event(self::class, 'save_mediaplay... ' . $plugin->_plugin->name, 5);
                    $plugin->_plugin->save_mediaplay($media);
                }
            } catch (Exception $error) {
                debug_event(self::class, 'save_mediaplay plugin error: ' . $error->getMessage(), 1);
            }
        }
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
        ?bool $encrypted = false
    ): int {
        // don't try to overwrite users that already exist
        if (
            in_array(strtolower($username), [strtolower(T_('System')), 'system']) ||
            self::getUserRepository()->idByUsername($username) > 0 ||
            self::getUserRepository()->idByEmail($email) > 0
        ) {
            return 0;
        }

        // Forbid username or fullname to have an URL (usually spambot)
        $name_filter = AmpConfig::get('user_name_filter');
        if (
            $name_filter &&
            (
                preg_match('/' . $name_filter . '/i', $username) ||
                preg_match('/' . $name_filter . '/i', $fullname)
            )
        ) {
            debug_event(self::class, 'Checking for spambot: look like spambot to me. Won\'t create user. ' . json_encode(['username' => $username,'fullname' => $fullname,'ip' => Core::get_user_ip()]), 1);

            return 0;
        }

        // Forbid website with markdown syntax => (usually spambot)
        $site_filter = AmpConfig::get('user_website_filter');
        if (
            $site_filter &&
            preg_match('/' . $site_filter . '/i', $website)
        ) {
            debug_event(self::class, 'Checking for spambot: look like spambot to me. Won\'t create user. ' . json_encode(['website' => $website, 'ip' => Core::get_user_ip() ]), 1);

            return 0;
        }

        $website = rtrim((string)$website, "/");
        if (!$encrypted) {
            $password = hash('sha256', $password);
        }

        $disabled = ($disabled) ? 1 : 0;

        // Just in case a zero value slipped in from upper layers...
        $catalog_filter_group ??= 0;

        /* Now Insert this new user */
        $sql    = "INSERT INTO `user` (`username`, `disabled`, `fullname`, `email`, `password`, `access`, `catalog_filter_group`, `create_date`";
        $params = [
            $username,
            $disabled,
            $fullname,
            $email,
            $password,
            $access->value,
            $catalog_filter_group,
            time(),
        ];

        if ($website !== '' && $website !== '0') {
            $sql .= ", `website`";
            $params[] = $website;
        }

        if (!empty($state)) {
            $sql .= ", `state`";
            $params[] = $state;
        }

        if (!empty($city)) {
            $sql .= ", `city`";
            $params[] = $city;
        }

        $user_create_streamtoken = AmpConfig::get('user_create_streamtoken', false);
        if ($user_create_streamtoken) {
            $sql .= ", `streamtoken`";
            $params[] = bin2hex(random_bytes(20));
        }

        $sql .= ") VALUES(?, ?, ?, ?, ?, ?, ?, ?";

        if ($website !== '' && $website !== '0') {
            $sql .= ", ?";
        }

        if (!empty($state)) {
            $sql .= ", ?";
        }

        if (!empty($city)) {
            $sql .= ", ?";
        }

        if ($user_create_streamtoken) {
            $sql .= ", ?";
        }

        $sql .= ")";
        $db_results = Dba::write($sql, $params);

        if (!$db_results) {
            return 0;
        }

        // Get the insert_id
        $insert_id = (int)Dba::insert_id();

        // Populates any missing preferences, in this case all of them
        self::fix_preferences($insert_id);

        Catalog::count_table('user');

        return $insert_id;
    }

    public function update_password(string $new_password, ?string $hashed_password = null): void
    {
        debug_event(self::class, 'Updating password', 1);
        if (!$hashed_password) {
            $hashed_password = hash('sha256', $new_password);
        }

        $escaped_password = Dba::escape($hashed_password);
        $sql              = "UPDATE `user` SET `password` = ? WHERE `id` = ?";
        $db_results       = Dba::write($sql, [$escaped_password, $this->id]);

        // Clear this (temp fix)
        if ($db_results) {
            unset($_SESSION['userdata']['password']);
        }
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = Art::has_db($this->id, 'user');
        }

        return $this->has_art;
    }

    /**
     * fix_preferences
     * This is the new fix_preferences function, it does the following
     * Remove Duplicates from user, add in missing
     * If -1 is passed it also removes duplicates from the `preferences`
     * table.
     */
    public static function fix_preferences(int $user_id): void
    {
        // Check default group (autoincrement starts at 1 so force it to be 0)
        $sql        = "SELECT `id`, `name` FROM `catalog_filter_group` WHERE `name` = 'DEFAULT';";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_assoc($db_results);
        if (!array_key_exists('id', $row) || ($row['id'] ?? '') != 0) {
            debug_event(self::class, 'fix_preferences restore DEFAULT catalog_filter_group', 2);
            // reinsert missing default group
            $sql = "INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT');";
            Dba::write($sql);
            $sql = "UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT';";
            Dba::write($sql);
            $sql        = "SELECT MAX(`id`) AS `filter_count` FROM `catalog_filter_group`;";
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_assoc($db_results);
            $increment  = (int)($row['filter_count'] ?? 0) + 1;
            $sql        = sprintf('ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = %d;', $increment);
            Dba::write($sql);
        }

        // Make sure the language a user has is valid
        $sql = "UPDATE `user_preference` SET `value` = 'en_US' WHERE `user` = -1 AND `name` = 'lang' AND `value` NOT IN ('af_ZA', 'bg_BG', 'ca_ES', 'cs_CZ', 'da_DK', 'de_CH', 'de_DE', 'el_GR', 'en_AU', 'en_GB', 'en_US', 'es_AR', 'es_ES', 'es_MX', 'et_EE', 'eu_ES', 'fi_FI', 'fr_BE', 'fr_FR', 'ga_IE', 'gl_ES', 'hi_IN', 'hu_HU', 'id_ID', 'is_IS', 'it_IT', 'ja_JP', 'ko_KR', 'lt_LT', 'lv_LV', 'nb_NO', 'nl_NL', 'no_NO', 'pl_PL', 'pt_BR', 'pt_PT', 'ro_RO', 'ru_RU', 'sk_SK', 'sl_SI', 'sr_CS', 'sv_SE', 'tr_TR', 'uk_UA', 'vi_VN', 'zh_CN', 'zh_TW', 'zh-Hant', 'zh_SG', 'ar_SA', 'he_IL', 'fa_IR');";
        Dba::write($sql);

        $sql          = "SELECT `value` FROM `user_preference` WHERE `user` = -1 AND `name` = 'lang';";
        $db_results   = Dba::read($sql);
        $row          = Dba::fetch_assoc($db_results);
        $default_lang = $row['value'] ?? 'en_US';

        // Set the default system user value if your user is bad
        $sql = "UPDATE `user_preference` SET `value` = ? WHERE `name` = 'lang' AND `value` NOT IN ('af_ZA', 'bg_BG', 'ca_ES', 'cs_CZ', 'da_DK', 'de_CH', 'de_DE', 'el_GR', 'en_AU', 'en_GB', 'en_US', 'es_AR', 'es_ES', 'es_MX', 'et_EE', 'eu_ES', 'fi_FI', 'fr_BE', 'fr_FR', 'ga_IE', 'gl_ES', 'hi_IN', 'hu_HU', 'id_ID', 'is_IS', 'it_IT', 'ja_JP', 'ko_KR', 'lt_LT', 'lv_LV', 'nb_NO', 'nl_NL', 'no_NO', 'pl_PL', 'pt_BR', 'pt_PT', 'ro_RO', 'ru_RU', 'sk_SK', 'sl_SI', 'sr_CS', 'sv_SE', 'tr_TR', 'uk_UA', 'vi_VN', 'zh_CN', 'zh_TW', 'zh-Hant', 'zh_SG', 'ar_SA', 'he_IL', 'fa_IR');";
        Dba::write($sql, [$default_lang]);

        // Make sure all current catalogs are in the default group map
        $sql = "INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) SELECT 0, `catalog`.`id`, `catalog`.`enabled` FROM `catalog` WHERE `catalog`.`id` NOT IN (SELECT `catalog_id` AS `id` FROM `catalog_filter_group_map` WHERE `group_id` = 0);";
        Dba::write($sql);

        /* Get All Preferences for the current user */
        $sql          = "SELECT * FROM `user_preference` WHERE `user` = ?";
        $db_results   = Dba::read($sql, [$user_id]);
        $results      = [];
        $zero_results = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $pref_id = $row['preference'];
            // Check for duplicates
            if (isset($results[$pref_id])) {
                $sql = "DELETE FROM `user_preference` WHERE `user` = ? AND `preference` = ? AND `value` = ?;";
                Dba::write($sql, [$user_id, $row['preference'], $row['value']]);
            } else {
                // if its set
                $results[$pref_id] = 1;
            }
        }

        // If your user is missing preferences we copy the value from system (Except for plugins and system prefs)
        if ($user_id != '-1') {
            $sql        = "SELECT `user_preference`.`preference`, `user_preference`.`name`, `user_preference`.`value` FROM `user_preference`, `preference` WHERE `user_preference`.`preference` = `preference`.`id` AND `user_preference`.`user`='-1' AND `preference`.`category` NOT IN ('plugins', 'system');";
            $db_results = Dba::read($sql);
            /* While through our base stuff */
            while ($row = Dba::fetch_assoc($db_results)) {
                $key                = $row['preference'];
                $zero_results[$key] = [
                    'name' => $row['name'],
                    'value' => $row['value']
                ];
            }
        } // if not user -1

        // get me _EVERYTHING_
        $sql = "SELECT * FROM `preference`";

        // If not system, exclude system... *gasp*
        if ($user_id != '-1') {
            $sql .= " WHERE `category` !='system';";
        }

        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $key = $row['id'];

            // Check if this preference is set
            if (!isset($results[$key])) {
                if (isset($zero_results[$key])) {
                    $row['value'] = $zero_results[$key]['value'];
                    $row['name']  = $zero_results[$key]['name'];
                }

                $sql = "INSERT IGNORE INTO user_preference (`user`, `preference`, `name`, `value`) VALUES (?, ?, ?, ?)";
                Dba::write($sql, [$user_id, $key, $row['name'], $row['value']]);
            }
        } // while preferences
    }

    /**
     * delete
     * deletes this user and everything associated with it. This will affect
     * ratings and total stats
     */
    public function delete(): bool
    {
        // Before we do anything make sure that they aren't the last admin
        if ($this->has_access(AccessLevelEnum::ADMIN)) {
            $sql        = "SELECT `id` FROM `user` WHERE `access`= ? AND id != ?";
            $params     = [AccessLevelEnum::ADMIN->value, $this->id];
            $db_results = Dba::read($sql, $params);
            if (Dba::num_rows($db_results) === 0) {
                return false;
            }
        } // if this is an admin check for others

        // Delete the user itself
        $sql = "DELETE FROM `user` WHERE `id` = ?";
        Dba::write($sql, [$this->id]);

        // Delete custom access settings
        $sql = "DELETE FROM `access_list` WHERE `user` = ?";
        Dba::write($sql, [$this->id]);

        $sql = "DELETE FROM `session` WHERE `username` = ?";
        Dba::write($sql, [$this->username]);

        Catalog::count_table('user');
        self::getUserRepository()->collectGarbage();

        return true;
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
        string $count_type = 'stream'
    ): array {
        $ordersql = ($newest === true) ? 'DESC' : 'ASC';
        $limit    = ($offset < 1) ? $count : $offset . "," . $count;

        $sql        = "SELECT `object_id`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_type` = ? AND `user` = ? AND `count_type` = ? GROUP BY `object_id` ORDER BY `date` " . $ordersql . " LIMIT " . $limit . " ";
        $db_results = Dba::read($sql, [$type, $this->id, $count_type]);

        $results = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['object_id'];
        }

        return $results;
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
     * Get item link.
     */
    public function get_link(): ?string
    {
        // don't do anything if it's formatted
        if ($this->link === null && $this->id > 0) {
            $web_path   = AmpConfig::get_web_path();
            $this->link = $web_path . '/stats.php?action=show_user&user_id=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item f_link.
     */
    public function get_f_link(): string
    {
        if ($this->f_link === null) {
            if ($this->getId() === 0) {
                $this->f_link = '';
            } else {
                $this->f_link = '<a href="' . $this->get_link() . '">' . scrub_out($this->get_fullname()) . '</a>';
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
            $avatar['url'] .= '&thumb=4';
            $avatar['url_mini'] .= '&thumb=5';
            $avatar['url_medium'] .= '&thumb=3';
        } else {
            $user = Core::get_global('user');
            if ($user instanceof User) {
                foreach (Plugin::get_plugins(PluginTypeEnum::AVATAR_PROVIDER) as $plugin_name) {
                    $plugin = new Plugin($plugin_name);
                    if ($plugin->_plugin !== null && $plugin->load($user)) {
                        $avatar['url'] = $plugin->_plugin->get_avatar_url($this);
                        if (!empty($avatar['url'])) {
                            $avatar['url_mini']   = $plugin->_plugin->get_avatar_url($this, 32);
                            $avatar['url_medium'] = $plugin->_plugin->get_avatar_url($this, 64);
                            $avatar['title'] .= ' (' . $plugin->_plugin->name . ')';
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

    public function update_avatar(string $data, string $mime = ''): bool
    {
        debug_event(self::class, 'Updating avatar for ' . $this->id, 4);

        $art = new Art($this->id, 'user');

        return $art->insert($data, $mime);
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

    public function deleteAvatar(): void
    {
        $art = new Art($this->id, 'user');
        $art->reset();
    }

    /**
     * Get the user avatar img links
     */
    public function get_f_avatar(string $avatar_type): string
    {
        $avatar = $this->get_avatar();

        if (
            $avatar_type == 'f_avatar' &&
            !empty($avatar['url'])
        ) {
            return '<img src="' . $avatar['url'] . '" title="' . $avatar['title'] . '"' . ' width="256px" height="auto" />';
        }

        if (
            $avatar_type == 'f_avatar_mini' &&
            !empty($avatar['url_mini'])
        ) {
            return '<img src="' . $avatar['url_mini'] . '" title="' . $avatar['title'] . '" style="width: 32px; height: 32px;" />';
        }

        if (
            $avatar_type == 'f_avatar_medium' &&
            !empty($avatar['url_medium'])
        ) {
            return '<img src="' . $avatar['url_medium'] . '" title="' . $avatar['title'] . '" style="width: 64px; height: 64px;" />';
        }

        return '';
    }

    public function deleteStreamToken(): void
    {
        $sql = "UPDATE `user` SET `streamtoken` = NULL WHERE `id` = ?;";
        Dba::write($sql, [$this->id]);
    }

    public function deleteRssToken(): void
    {
        $sql = "UPDATE `user` SET `rsstoken` = NULL WHERE `id` = ?;";
        Dba::write($sql, [$this->id]);
    }

    public function deleteApiKey(): void
    {
        $sql = "UPDATE `user` SET `apikey` = NULL WHERE `id` = ?;";
        Dba::write($sql, [$this->id]);
    }

    /**
     * rebuild_all_preferences
     * This rebuilds the user preferences for all installed users, called by the plugin functions
     */
    public static function rebuild_all_preferences(): void
    {
        // Garbage collection
        $sql = "DELETE `user_preference`.* FROM `user_preference` LEFT JOIN `user` ON `user_preference`.`user` = `user`.`id` WHERE (`user_preference`.`user` != -1 AND `user`.`id` IS NULL) OR `preference` = 0;";
        Dba::write($sql);
        // delete system prefs from users
        $sql = "DELETE `user_preference`.* FROM `user_preference` LEFT JOIN `preference` ON `user_preference`.`preference` = `preference`.`id` WHERE `user_preference`.`user` != -1 AND `preference`.`category` = 'system';";
        Dba::write($sql);
        // ensure preference names are updated
        $sql = "UPDATE `user_preference`, (SELECT `preference`.`name`, `preference`.`id` FROM `preference`) AS `preference` SET `user_preference`.`name` = `preference`.`name` WHERE `preference`.`id` = `user_preference`.`preference`;";
        Dba::write($sql);

        // Fix the system user preferences first
        self::fix_preferences(-1);

        // How many preferences should we have?
        $sql        = "SELECT COUNT(`id`) AS `pref_count` FROM `preference` WHERE `category` != 'system';";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_assoc($db_results);
        $pref_count = (int)$row['pref_count'];
        // Get only users who have less preferences than excepted otherwise it would have significant performance issue with large user database
        $sql        = 'SELECT `user` FROM `user_preference` GROUP BY `user` HAVING COUNT(*) < ' . $pref_count;
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            self::fix_preferences((int)$row['user']);
        }
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
            if ($plugin->_plugin !== null && $plugin->load($user) && !$plugin->_plugin->stream_control($media_ids)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the users internal username
     */
    public function getUsername(): string
    {
        return $this->username ?? '';
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

        return (string)$this->rsstoken;
    }

    /**
     * garbage_collection
     *
     * This cleans out users that have not activated in the last 30 days
     */
    public static function garbage_collection(): void
    {
        // activated accounts can log in but might not have cleared validation
        Dba::write("UPDATE `user` SET `validation` = NULL WHERE `last_seen` > 0;");
        // delete accounts not activated after 30 days
        Dba::write("DELETE FROM `user` WHERE (`last_seen` = 0 OR `validation` IS NOT NULL) AND `create_date` < UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL -1 MONTH));");
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
     * @deprecated inject dependency
     */
    private function getIpHistoryRepository(): IpHistoryRepositoryInterface
    {
        global $dic;

        return $dic->get(IpHistoryRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
