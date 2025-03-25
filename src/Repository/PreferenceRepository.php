<?php

declare(strict_types=1);

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

namespace Ampache\Repository;

use Ampache\Module\Api\Api;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

/**
 * Provides access to the `preference` table
 */
final class PreferenceRepository implements PreferenceRepositoryInterface
{
    /** @var string[] $HIDE_ARRAY */
    private const HIDE_ARRAY = [
        'disabled_custom_metadata_fields',
        'geolocation',
        'jp_volume',
        'lastfm_grant_link',
        'librefm_grant_link',
        'personalfav_playlist',
        'personalfav_smartlist',
        'play_type',
        'playlist_method',
        'theme_color',
        'theme_name',
        'upload_catalog',
    ];

    /**
     * Returns a nice flat dict of all the possible preferences
     *
     * If no user is provided, all available system-wide preferences will be returned
     *
     * @return list<array{
     *  id: int,
     *  name: string,
     *  value: string,
     *  description: string,
     *  level: int,
     *  type: string,
     *  category: string,
     *  subcategory: string
     * }>
     */
    public function getAll(
        ?User $user = null,
        ?bool $api = false
    ): array {
        if ($user !== null) {
            $userLimit   = 'AND `preference`.`category` != \'system\'';
            $userId      = $user->getId();
            $accessLevel = $user->access;
        } else {
            $user        = new User(User::INTERNAL_SYSTEM_USER_ID);
            $userLimit   = '';
            $userId      = User::INTERNAL_SYSTEM_USER_ID;
            $accessLevel = 100;
        }

        $sql = <<<SQL
            SELECT
                `preference`.`id`,
                `preference`.`name`,
                `preference`.`description`,
                `preference`.`level`,
                `preference`.`type`,
                `preference`.`category`,
                `preference`.`subcategory`,
                `user_preference`.`value`
            FROM
                `preference`
            INNER JOIN
                `user_preference`
            ON
                `user_preference`.`preference`=`preference`.`id`
            WHERE
                `user_preference`.`user` = ?
                AND
                `preference`.`category` != 'internal' %s
            ORDER BY
                `preference`.`subcategory`,
                `preference`.`description`
        SQL;

        $db_results = Dba::read(
            sprintf($sql, $userLimit),
            [$userId]
        );

        $results = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            if ($api && in_array($row['name'], self::HIDE_ARRAY)) {
                // don't show these to API users as they are not useful
                continue;
            }

            $result = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'level' => (int) $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'type' => $row['type'],
                'category' => $row['category'],
                'subcategory' => $row['subcategory']
            ];
            if ($api) {
                $result['has_access'] = (((int)$row['level']) <= $accessLevel);
            }
            if ($row['type'] == 'special') {
                $values = Preference::get_special_values($row['name'], $user);
                if ($values) {
                    $result['values'] = $values;
                }
            }
            $results[] = $result;
        }

        return $results;
    }
}
