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
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Database\Exception\InsertIdInvalidException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Provides access to the `preference` table
 */
final readonly class PreferenceRepository implements PreferenceRepositoryInterface
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

    public function __construct(
        private readonly DatabaseConnectionInterface $connection,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Adds a preference a user is missing; duplicates are ignored so the caller can be optimistic
     */
    public function addUserPreference(int $userId, int $preferenceId, string $name, int|string|null $value): void
    {
        // this runs once per missing preference while repairing a user, so one bad row stops nothing
        try {
            $this->connection->query(
                'INSERT IGNORE INTO user_preference (`user`, `preference`, `name`, `value`) VALUES (?, ?, ?, ?)',
                [$userId, $preferenceId, $name, $value]
            );
        } catch (DatabaseException) {
            $this->logger->warning(
                'could not add preference ' . $name . ' for user ' . $userId,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    /**
     * Drops the user rows whose preference no longer exists
     */
    public function collectGarbage(): void
    {
        $this->connection->query(
            'DELETE FROM `user_preference` USING `user_preference` LEFT JOIN `preference` ON `preference`.`id`=`user_preference`.`preference` WHERE `preference`.`id` IS NULL'
        );
    }

    /**
     * Drops the preference rows that no longer belong to anyone, and the system ones that leaked onto users
     */
    public function collectPreferenceGarbage(): void
    {
        $this->collectGarbage();

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
     * Copies the server's own preference values onto a user
     */
    public function copySystemPreferences(int $userId): bool
    {
        $result = $this->connection->query('SELECT `value`, `name` FROM `user_preference` WHERE `user` = -1;');

        try {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $this->connection->query(
                    'UPDATE `user_preference` SET `value` = ? WHERE `user` = ? AND `name` = ?;',
                    [$row['value'], $userId, $row['name']]
                );
            }
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Counts the preferences matching a name or an id, which is how existence is asked
     */
    public function countByNameOrId(int|string $preference): int
    {
        $sql = (is_numeric($preference))
            ? 'SELECT COUNT(*) FROM `preference` WHERE `id` = ?'
            : 'SELECT COUNT(*) FROM `preference` WHERE `name` = ?';

        return (int) $this->connection->fetchOne($sql, [$preference]);
    }

    /**
     * Drops a preference by name or by id
     */
    public function deleteByNameOrId(int|string $preference): bool
    {
        $sql = (is_numeric($preference))
            ? 'DELETE FROM `preference` WHERE `id` = ?'
            : 'DELETE FROM `preference` WHERE `name` = ?';

        try {
            $this->connection->query($sql, [$preference]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Drops one duplicated preference row, matching on the value so the surviving copy is the one kept
     */
    public function deleteDuplicatePreference(int $userId, int $preferenceId, int|string|null $value): void
    {
        // same sweep, same reason
        try {
            $this->connection->query(
                'DELETE FROM `user_preference` WHERE `user` = ? AND `preference` = ? AND `value` = ?;',
                [$userId, $preferenceId, $value]
            );
        } catch (DatabaseException) {
            $this->logger->warning(
                'could not drop the duplicate of preference ' . $preferenceId . ' for user ' . $userId,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    /**
     * Reads the id of a preference by name
     */
    public function findIdByName(string $name): ?int
    {
        $preferenceId = $this->connection->fetchOne('SELECT `id` FROM `preference` WHERE `name` = ?', [$name]);

        return ($preferenceId === false)
            ? null
            : (int) $preferenceId;
    }

    /**
     * Reads the names from a list that have no `preference` row yet
     *
     * @param list<string> $names
     * @return list<string>
     */
    public function findMissingNames(array $names): array
    {
        if ($names === []) {
            return [];
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT `item` FROM (%s) AS `items` LEFT JOIN `preference` ON `items`.`item` = `preference`.`name` WHERE `preference`.`name` IS NULL;',
                implode(' UNION ALL ', array_fill(0, count($names), 'SELECT ? AS `item`'))
            ),
            $names
        );

        $missing = [];
        while ($name = $result->fetchColumn()) {
            $missing[] = (string) $name;
        }

        return $missing;
    }

    /**
     * Reads the name of a preference by id
     */
    public function findNameById(int|string $preferenceId): ?string
    {
        $name = $this->connection->fetchOne('SELECT `name` FROM `preference` WHERE `id` = ?', [$preferenceId]);

        return ($name === false || $name === null)
            ? null
            : (string) $name;
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
                'description' => T_((string) ($row['description'] ?? '')),
                'value' => (Preference::isSecretName($row['name'])) ? '' : $row['value'],
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
     * Reads the categories preferences are grouped under
     *
     * @return list<string>
     */
    public function getCategories(): array
    {
        $result = $this->connection->query(
            'SELECT `preference`.`category` FROM `preference` GROUP BY `category` ORDER BY `category`'
        );

        $categories = [];
        while ($category = $result->fetchColumn()) {
            $categories[] = (string) $category;
        }

        return $categories;
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
     * Reads the name, declared type and value of every preference resolved for a user, the server value
     * winning for the system ones
     *
     * @return list<array<string, mixed>>
     */
    public function getInitRows(int $userId): array
    {
        // the column was spelled `catagory` before Migration600051
        $column = ($this->hasCategoryColumn()) ? 'category' : 'catagory';
        $result = $this->connection->query(
            sprintf(
                "SELECT `preference`.`name`, `preference`.`type`, `user_preference`.`value`, `syspref`.`value` AS `system_value` FROM `preference` LEFT JOIN `user_preference` `syspref` ON `syspref`.`preference`=`preference`.`id` AND `syspref`.`user`='-1' AND `preference`.`%s`='system' LEFT JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` AND `user_preference`.`user` = ? AND `preference`.`%s` !='system'",
                $column,
                $column
            ),
            [$userId]
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Reads the access level a preference demands
     */
    public function getLevel(string $name): ?int
    {
        $level = $this->connection->fetchOne('SELECT `level` FROM `preference` WHERE `name` = ?;', [$name]);

        return ($level === false)
            ? null
            : (int) $level;
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
     * Reads one preference as a user sees it, with the row the display needs
     *
     * @return array<string, mixed>
     */
    public function getUserPreferenceRow(string $name, int $userId, bool $excludeSystem): array
    {
        $row = $this->connection->fetchRow(
            sprintf(
                "SELECT `preference`.`id`, `preference`.`name`, `preference`.`description`, `preference`.`level`, `preference`.`type`, `preference`.`category`, `preference`.`subcategory`, `user_preference`.`value` FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` WHERE `preference`.`name` = ? AND `user_preference`.`user` = ? AND `preference`.`category` != 'internal' %s ORDER BY `preference`.`subcategory`, `preference`.`description`",
                ($excludeSystem) ? "AND `preference`.`category` != 'system'" : ''
            ),
            [$name, $userId]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Reads a user's stored values as name => value, keyed by whichever column this schema carries
     *
     * @return array<int|string, ?string>
     */
    public function getUserValues(int $userId, bool $keyedByName): array
    {
        $column = ($keyedByName) ? 'name' : 'preference';
        $result = $this->connection->query(
            sprintf('SELECT * FROM `user_preference` WHERE `user` = ? ORDER BY `%s`;', $column),
            [$userId]
        );

        $values = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $values[$row[$column]] = $row['value'];
        }

        return $values;
    }

    /**
     * Whether this database spells the column `category`, which it has since Migration600051
     */
    public function hasCategoryColumn(): bool
    {
        // the probe is the whole point, so a missing column is an answer rather than a failure
        try {
            $this->connection->query('SELECT `category` FROM `preference` LIMIT 1;', [], true);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Whether `user_preference` carries the name column it gained in Migration700020
     */
    public function hasUserPreferenceName(): bool
    {
        try {
            $this->connection->query('SELECT `name` FROM `user_preference` LIMIT 1;', [], true);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Inserts one preference with the row Ampache ships for it
     */
    public function insertDefault(
        string $name,
        string $value,
        string $description,
        int $level,
        string $type,
        string $category,
        ?string $subcategory,
    ): bool {
        // this seeds a whole install, so one preference that will not write must not stop the others
        try {
            $this->connection->query(
                'INSERT IGNORE INTO `preference` (`name`, `value`, `description`, `level`, `type`, `category`, `subcategory`) VALUES (?, ?, ?, ?, ?, ?, ?);',
                [$name, $value, $description, $level, $type, $category, $subcategory]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Inserts a preference and seeds it onto the server and, unless it is a system one, onto every user
     *
     * The column spellings and the `user_preference`.`name` column both depend on how old the database
     * is, which is why the two probes decide the statements rather than the caller.
     */
    public function insertPreference(
        string $name,
        string $description,
        float|int|string|null $default,
        int $level,
        string $type,
        string $category,
        ?string $subcategory,
    ): bool {
        $columns = ($this->hasCategoryColumn())
            ? '`name`, `description`, `value`, `level`, `type`, `category`, `subcategory`'
            : '`name`, `description`, `value`, `level`, `type`, `catagory`, `subcatagory`';

        try {
            $this->connection->query(
                sprintf('INSERT INTO `preference` (%s) VALUES (?, ?, ?, ?, ?, ?, ?)', $columns),
                [$name, $description, $default, $level, $type, $category, $subcategory]
            );

            $preferenceId = $this->connection->getLastInsertedId();
        } catch (DatabaseException|InsertIdInvalidException) {
            return false;
        }

        $named  = $this->hasUserPreferenceName();
        $params = ($named)
            ? [$preferenceId, $name, $default]
            : [$preferenceId, $default];

        try {
            $this->connection->query(
                ($named)
                    ? 'INSERT INTO `user_preference` (`user`, `preference`, `name`, `value`) VALUES (-1, ?, ?, ?)'
                    : 'INSERT INTO `user_preference` (`user`, `preference`, `value`) VALUES (-1, ?, ?);',
                $params
            );

            if ($category !== 'system') {
                $this->connection->query(
                    ($named)
                        ? 'INSERT INTO `user_preference` (`user`, `preference`, `name`, `value`) SELECT `user`.`id`, ?, ?, ? FROM `user`;'
                        : 'INSERT INTO `user_preference` (`user`, `preference`, `value`) SELECT `user`.`id`, ?, ? FROM `user`;',
                    $params
                );
            }
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Renames a preference, which is how a description-only change is told apart from a new one
     */
    public function rename(string $oldName, string $newName): void
    {
        $this->connection->query('UPDATE `preference` SET `name` = ? WHERE `name` = ?', [$newName, $oldName]);
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

    /**
     * Puts every preference on one access level
     */
    public function setAllLevels(int $level): bool
    {
        try {
            $this->connection->query('UPDATE `preference` SET `level` = ?;', [$level]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Puts named preferences on the access level they are shipped with
     *
     * @param array<int, list<string>> $levels level => the preferences taking it
     */
    public function setLevels(array $levels): bool
    {
        foreach ($levels as $level => $names) {
            if ($names === []) {
                continue;
            }

            try {
                $this->connection->query(
                    sprintf(
                        'UPDATE `preference` SET `level` = ? WHERE `name` IN (%s);',
                        implode(',', array_fill(0, count($names), '?'))
                    ),
                    array_merge([$level], $names)
                );
            } catch (DatabaseException) {
                return false;
            }
        }

        return true;
    }

    /**
     * Writes a preset onto a user, one statement per distinct value
     *
     * @param array<int|string, list<string>> $values value => the preferences taking it
     */
    public function setUserPreferenceValues(int $userId, array $values): bool
    {
        foreach ($values as $value => $names) {
            if ($names === []) {
                continue;
            }

            try {
                $this->connection->query(
                    sprintf(
                        'UPDATE `user_preference` SET `value` = ? WHERE `name` IN (%s) AND `user` = ?;',
                        implode(',', array_fill(0, count($names), '?'))
                    ),
                    array_merge([(string) $value], $names, [$userId])
                );
            } catch (DatabaseException) {
                return false;
            }
        }

        return true;
    }

    /**
     * Applies the canonical description of every preference, leaving the ones already correct alone
     *
     * @param array<string, string> $descriptions name => description
     */
    public function updateDescriptions(array $descriptions): void
    {
        // one description that will not write must not stop the rest of the rename pass
        foreach ($descriptions as $name => $description) {
            try {
                $this->connection->query(
                    'UPDATE `preference` SET `description` = ? WHERE `name` = ? AND `description` != ?',
                    [$description, $name, $description]
                );
            } catch (DatabaseException) {
                $this->logger->warning(
                    'could not describe ' . $name,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    /**
     * Puts one preference on an access level
     */
    public function updateLevel(int|string $preferenceId, int $level): void
    {
        $this->connection->query('UPDATE `preference` SET `level` = ? WHERE `id` = ?;', [$level, $preferenceId]);
    }

    /**
     * Writes one value, optionally onto the shipped default and onto every user rather than just one
     */
    public function updateValue(int|string $preference, bool|float|int|string|null $value, ?int $userId, bool $applyToDefault): void
    {
        $named  = $this->hasUserPreferenceName();
        $column = ($named) ? 'name' : 'preference';
        $params = [$value, $preference];

        if ($applyToDefault) {
            $this->connection->query(
                sprintf('UPDATE `preference` SET `value` = ? WHERE `%s` = ?;', $column),
                $params
            );
        }

        $sql = sprintf('UPDATE `user_preference` SET `value` = ? WHERE `%s` = ? ', $column);
        if ($userId !== null) {
            $sql .= 'AND `user` = ?';
            $params[] = $userId;
        }

        $this->connection->query($sql, $params);
    }

    /**
     * Writes one value for every user, which is what an admin changing a default means
     */
    public function updateValueForAll(int|string $preference, bool|float|int|string|null $value): void
    {
        $this->connection->query(
            sprintf(
                'UPDATE `user_preference` SET `value` = ? WHERE `%s` = ?',
                ($this->hasUserPreferenceName()) ? 'name' : 'preference'
            ),
            [$value, $preference]
        );
    }
}
