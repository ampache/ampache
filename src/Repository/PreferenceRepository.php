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

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use PDO;

/**
 * Provides access to the `preference` table
 */
final class PreferenceRepository implements PreferenceRepositoryInterface
{
    /** @var string[] $HIDE_ARRAY */
    private const array HIDE_ARRAY = [
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

    public function __construct(private readonly DatabaseConnectionInterface $connection) {}

    /**
     * Adds every catalog the default filter group is missing, so a new catalog is visible without a manual edit
     */
    public function addMissingCatalogsToDefaultFilterGroup(): void
    {
        $this->connection->query(
            'INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) SELECT 0, `catalog`.`id`, `catalog`.`enabled` FROM `catalog` WHERE `catalog`.`id` NOT IN (SELECT `catalog_id` AS `id` FROM `catalog_filter_group_map` WHERE `group_id` = 0);'
        );
    }

    /**
     * Adds a preference a user is missing; duplicates are ignored so the caller can be optimistic
     */
    public function addUserPreference(int $userId, int $preferenceId, string $name, int|string|null $value): void
    {
        $this->connection->query(
            'INSERT IGNORE INTO user_preference (`user`, `preference`, `name`, `value`) VALUES (?, ?, ?, ?)',
            [$userId, $preferenceId, $name, $value]
        );
    }

    /**
     * Drops the preference rows that no longer belong to anyone, and the system ones that leaked onto users
     */
    public function collectPreferenceGarbage(): void
    {
        $statements = [
            'DELETE `user_preference`.* FROM `user_preference` LEFT JOIN `user` ON `user_preference`.`user` = `user`.`id` WHERE (`user_preference`.`user` != -1 AND `user`.`id` IS NULL) OR `preference` = 0;',
            "DELETE `user_preference`.* FROM `user_preference` LEFT JOIN `preference` ON `user_preference`.`preference` = `preference`.`id` WHERE `user_preference`.`user` != -1 AND `preference`.`category` = 'system';",
            'UPDATE `user_preference`, (SELECT `preference`.`name`, `preference`.`id` FROM `preference`) AS `preference` SET `user_preference`.`name` = `preference`.`name` WHERE `preference`.`id` = `user_preference`.`preference`;',
        ];

        foreach ($statements as $sql) {
            $this->connection->query($sql);
        }
    }

    /**
     * Drops one duplicated preference row, matching on the value so the surviving copy is the one kept
     */
    public function deleteDuplicatePreference(int $userId, int $preferenceId, int|string|null $value): void
    {
        $this->connection->query(
            'DELETE FROM `user_preference` WHERE `user` = ? AND `preference` = ? AND `value` = ?;',
            [$userId, $preferenceId, $value]
        );
    }

    /**
     * Returns a nice flat dict of all the possible preferences
     *
     * If no user is provided, all available system-wide preferences will be returned
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     value: string,
     *     description: string,
     *     level: int,
     *     type: string,
     *     category: string,
     *     subcategory: ?string,
     *     has_access?: bool,
     *     values?: string[]|int[],
     * }>
     */
    public function getAll(
        ?User $user = null,
        ?bool $api = false,
    ): array {
        if ($user !== null) {
            $userLimit   = "AND `preference`.`category` != 'system'";
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

        $dbResults = $this->connection->query(
            sprintf($sql, $userLimit),
            [$userId]
        );

        $results = [];

        while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
            if ($api && in_array($row['name'], self::HIDE_ARRAY)) {
                // don't show these to API users as they are not useful
                continue;
            }

            $result = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'level' => (int) $row['level'],
                'description' => T_($row['description']),
                'value' => $row['value'],
                'type' => $row['type'],
                'category' => $row['category'],
                'subcategory' => $row['subcategory']
            ];
            if ($api) {
                $result['has_access'] = (((int) $row['level']) <= $accessLevel);
            }

            if ($row['type'] == 'special' || $row['type'] == 'transcoding') {
                $values = Preference::get_special_values($row['name'], $user);
                if ($values) {
                    $result['values'] = $values;
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Reads every known preference, dropping the system-only ones when the target is a real user
     *
     * @return list<array{id: int, name: string, value: ?string}>
     */
    public function getAllPreferences(bool $includeSystem): array
    {
        $sql = ($includeSystem)
            ? 'SELECT * FROM `preference`'
            : "SELECT * FROM `preference` WHERE `category` !='system';";

        $result = $this->connection->query($sql);

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'value' => $row['value'],
            ];
        }

        return $rows;
    }

    /**
     * Reads the users holding fewer preferences than exist, which is the cheap way to find the ones needing repair
     *
     * @return list<int>
     */
    public function getIdsMissingPreferences(): array
    {
        $expected = (int) $this->connection->fetchOne("SELECT COUNT(`id`) AS `pref_count` FROM `preference` WHERE `category` != 'system';");

        $result = $this->connection->query('SELECT `user` FROM `user_preference` GROUP BY `user` HAVING COUNT(*) < ' . $expected);

        $userIds = [];
        while ($userId = $result->fetchColumn()) {
            $userIds[] = (int) $userId;
        }

        return $userIds;
    }

    /**
     * Reads a user's stored preferences as preference-id => value, so duplicates are visible to the caller
     *
     * @return list<array{preference: int, value: ?string}>
     */
    public function getStoredPreferences(int $userId): array
    {
        $result = $this->connection->query('SELECT * FROM `user_preference` WHERE `user` = ?', [$userId]);

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'preference' => (int) $row['preference'],
                'value' => $row['value'],
            ];
        }

        return $rows;
    }

    /**
     * Reads the system user's non-plugin preferences, which seed the values a new user starts with
     *
     * @return list<array{preference: int, name: string, value: ?string}>
     */
    public function getSystemDefaultPreferences(): array
    {
        $result = $this->connection->query(
            "SELECT `user_preference`.`preference`, `user_preference`.`name`, `user_preference`.`value` FROM `user_preference`, `preference` WHERE `user_preference`.`preference` = `preference`.`id` AND `user_preference`.`user`='-1' AND `preference`.`category` NOT IN ('plugins', 'system');"
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'preference' => (int) $row['preference'],
                'name' => (string) $row['name'],
                'value' => $row['value'],
            ];
        }

        return $rows;
    }

    /**
     * Puts the DEFAULT catalog filter group back at id 0, where the rest of the schema assumes it lives
     *
     * Autoincrement starts at 1, so a group inserted normally lands in the wrong place and every catalog filter
     * silently stops matching. Returns whether the repair had to run.
     */
    public function repairDefaultFilterGroup(): bool
    {
        $row = $this->connection->fetchRow("SELECT `id`, `name` FROM `catalog_filter_group` WHERE `name` = 'DEFAULT';");
        if (is_array($row) && array_key_exists('id', $row) && ($row['id'] ?? '') == 0) {
            return false;
        }

        $this->connection->query("INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT');");
        $this->connection->query("UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT';");

        $increment = (int) $this->connection->fetchOne('SELECT MAX(`id`) AS `filter_count` FROM `catalog_filter_group`;') + 1;
        $this->connection->query(sprintf('ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = %d;', $increment));

        return true;
    }

    /**
     * Resets any `lang` preference that names a locale Ampache does not ship, so the UI does not fall over
     *
     * The system user is repaired first and its value becomes the fallback for everyone else.
     */
    public function repairLanguagePreferences(): void
    {
        $locales = "('af_ZA', 'bg_BG', 'ca_ES', 'cs_CZ', 'da_DK', 'de_CH', 'de_DE', 'el_GR', 'en_AU', 'en_GB', 'en_US', 'es_AR', 'es_ES', 'es_MX', 'et_EE', 'eu_ES', 'fi_FI', 'fr_BE', 'fr_FR', 'ga_IE', 'gl_ES', 'hi_IN', 'hu_HU', 'id_ID', 'is_IS', 'it_IT', 'ja_JP', 'ko_KR', 'lt_LT', 'lv_LV', 'nb_NO', 'nl_NL', 'no_NO', 'pl_PL', 'pt_BR', 'pt_PT', 'ro_RO', 'ru_RU', 'sk_SK', 'sl_SI', 'sr_CS', 'sv_SE', 'tr_TR', 'uk_UA', 'vi_VN', 'zh_CN', 'zh_TW', 'zh-Hant', 'zh_SG', 'ar_SA', 'he_IL', 'fa_IR')";

        $this->connection->query(
            "UPDATE `user_preference` SET `value` = 'en_US' WHERE `user` = -1 AND `name` = 'lang' AND `value` NOT IN " . $locales . ';'
        );

        $defaultLang = $this->connection->fetchOne("SELECT `value` FROM `user_preference` WHERE `user` = -1 AND `name` = 'lang';");

        $this->connection->query(
            "UPDATE `user_preference` SET `value` = ? WHERE `name` = 'lang' AND `value` NOT IN " . $locales . ';',
            [($defaultLang === false) ? 'en_US' : $defaultLang]
        );
    }
}
