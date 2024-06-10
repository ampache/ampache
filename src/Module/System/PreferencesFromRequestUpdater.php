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

namespace Ampache\Module\System;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Playback\Stream;
use DateTimeZone;

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
        // allow replacing empty values when not set on your tab
        $null_allowed = (isset($_REQUEST['tab']) && ($_REQUEST['tab']) == 'plugins')
            ? ['personalfav_playlist', 'personalfav_smartlist']
            : [];

        // Get current keys
        $sql = ($user_id == '-1')
            ? "SELECT `id`, `name`, `type` FROM `preference`"
            : "SELECT `id`, `name`, `type` FROM `preference` WHERE `category` != 'system'";

        $db_results = Dba::read($sql);
        $results    = [];
        // Collect the current possible keys
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type']
            ];
        } // end collecting keys

        // Foreach through possible keys and assign them
        foreach ($results as $data) {
            // Get the Value from POST/GET var called $data
            $name         = (string) $data['name'];
            $apply_to_all = 'check_' . $data['name'];
            $new_level    = 'level_' . $data['name'];
            $pref_id      = (string)$data['id'];
            $value        = (isset($_REQUEST[$name]) && is_array($_REQUEST[$name]))
                ? implode(',', $_REQUEST[$name])
                : (string)scrub_in((string)($_REQUEST[$name] ?? ''));

            // Some preferences require some extra checks to be performed
            switch ($name) {
                case 'transcode_bitrate':
                    $value = (string) Stream::validate_bitrate($value);
                    break;
                case 'custom_timezone':
                    $listIdentifiers = DateTimeZone::listIdentifiers() ?: [];
                    if (!in_array($value, $listIdentifiers)) {
                        $value = '';
                    }
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
            if (array_key_exists($name, $_REQUEST) || in_array($name, $null_allowed)) {
                $applyToAll = $_REQUEST[$apply_to_all] ?? null;
                Preference::update($pref_id, $user_id, $value, $applyToAll);
            }

            if ($this->privilegeChecker->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) && array_key_exists($new_level, $_REQUEST)) {
                Preference::update_level($pref_id, (int)$_REQUEST[$new_level]);
            }
        } // end foreach preferences

        // Now that we've done that we need to invalidate the cached preferences
        Preference::clear_from_session();
    }
}
