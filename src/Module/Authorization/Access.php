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

namespace Ampache\Module\Authorization;

use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\Dba;

/**
 * Access Class
 *
 * This class handles the access list mojo for Ampache, it is meant to restrict
 * access based on IP and maybe something else in the future.
 *
 */
class Access
{
    protected const DB_TABLENAME = 'access_list';

    // Variables from DB
    /**
     * @var int $id
     */
    public $id;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $start
     */
    public $start;

    /**
     * @var string $end
     */
    public $end;

    /**
     * @var int $level
     */
    public $level;

    /**
     * @var int $user
     */
    public $user;

    /**
     * @var string $type
     */
    public $type;

    /**
     * @var bool $enabled
     *
     * @deprecated seems not to be in use
     */
    public $enabled;

    /**
     * constructor
     *
     * Takes an ID of the access_id dealie :)
     * @param int|null $access_id
     */
    public function __construct($access_id)
    {
        if (!$access_id) {
            return;
        }
        $info = $this->has_info($access_id);
        if (!$info) {
            return;
        }
        $this->id = (int)$access_id;
    }

    /**
     * has_info
     *
     * Gets the vars for $this out of the database.
     * @param int $access_id
     */
    private function has_info($access_id): bool
    {
        $sql        = 'SELECT * FROM `access_list` WHERE `id` = ?';
        $db_results = Dba::read($sql, array($access_id));
        $data       = Dba::fetch_assoc($db_results);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    /**
     * check_function
     *
     * This checks if specific functionality is enabled.
     * @param string $type     *
     * @deprecated See FunctionChecker::check
     */
    public static function check_function($type): bool
    {
        global $dic;

        return $dic->get(FunctionCheckerInterface::class)->check(
            (string) $type
        );
    }

    /**
     * check
     *
     * This is the global 'has_access' function. it can check for any 'type'
     * of object.
     *
     * Everything uses the global 0,5,25,50,75,100 stuff. GLOBALS['user'] is
     * always used.
     * @param string $type
     * @param int $level
     * @param int|null $user_id
     * @deprecated See PrivilegeChecker::check
     */
    public static function check($type, $level, $user_id = null): bool
    {
        global $dic;

        return $dic->get(PrivilegeCheckerInterface::class)->check(
            (string) $type,
            (int) $level,
            $user_id
        );
    }
}
