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

declare(strict_types=0);

namespace Ampache\Module\System;

use Ampache\Repository\Model\Preference;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Playback\Stream;

final class PreferencesFromRequestUpdater implements PreferencesFromRequestUpdaterInterface
{
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * grabs the current keys that should be added and then runs
     * through $_REQUEST looking for those values and updates them for this user
     */
    public function update(int $user_id = 0): void
    {
        // Get current keys
        $sql = "SELECT `id`, `name`, `type` FROM `preference`";

        // If it isn't the System Account's preferences
        if ($user_id != '-1') {
            $sql .= " WHERE `catagory` != 'system'";
        }

        $db_results = Dba::read($sql);
        $results    = array();
        // Collect the current possible keys
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array('id' => $row['id'], 'name' => $row['name'], 'type' => $row['type']);
        } // end collecting keys

        // Foreach through possible keys and assign them
        foreach ($results as $data) {
            // Get the Value from POST/GET var called $data
            $name         = (string) $data['name'];
            $apply_to_all = 'check_' . $data['name'];
            $new_level    = 'level_' . $data['name'];
            $pref_id      = $data['id'];
            $value        = scrub_in($_REQUEST[$name]);

            // Some preferences require some extra checks to be performed
            switch ($name) {
                case 'transcode_bitrate':
                    $value = (string) Stream::validate_bitrate($value);
                    break;
                default:
                    break;
            }

            if (preg_match('/_pass$/', $name)) {
                if ($value == '******') {
                    unset($_REQUEST[$name]);
                } else {
                    if (preg_match('/md5_pass$/', $name)) {
                        $value = md5((string) $value);
                    }
                }
            }

            // Run the update for this preference only if it's set
            if (isset($_REQUEST[$name])) {
                Preference::update($pref_id, $user_id, $value, $_REQUEST[$apply_to_all]);
            }

            if (
                $this->privilegeChecker->check(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) &&
                $_REQUEST[$new_level]
            ) {
                Preference::update_level($pref_id, $_REQUEST[$new_level]);
            }
        } // end foreach preferences

        // Now that we've done that we need to invalidate the cached preferences
        Preference::clear_from_session();
    }
}
