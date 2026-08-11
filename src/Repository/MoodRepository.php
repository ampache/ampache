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

use Ampache\Config\AmpConfig;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Mood;
use Ampache\Repository\Model\MoodCountTypeEnum;
use Ampache\Repository\Model\User;
use PDO;

final readonly class MoodRepository implements MoodRepositoryInterface
{
    /**
     * The object types that carry a catalog, and therefore the only ones the enable/catalog filters can narrow.
     */
    private const array CATALOG_TYPES = ['artist', 'album', 'album_disk', 'song', 'video'];

    /**
     * Every `mood_map`.`object_type`, which is also the table its `object_id` names, so an orphaned map is found for all of them.
     */
    private const array MAPPED_TYPES = Mood::OBJECT_TYPES;

    public function __construct(
        private DatabaseConnectionInterface $connection,
    ) {}

    public function addMap(int $moodId, string $objectType, int $objectId, int $userId): int
    {
        $this->connection->query(
            'INSERT IGNORE INTO `mood_map` (`mood_id`, `user`, `object_type`, `object_id`) VALUES (?, ?, ?, ?)',
            [$moodId, $userId, $objectType, $objectId]
        );

        return $this->connection->getLastInsertedId();
    }

    public function collectGarbage(): void
    {
        // maps pointing at objects that no longer exist, whoever set them, or a mood a user set by hand outlives the object
        $statements = [];
        foreach (self::MAPPED_TYPES as $objectType) {
            $statements[] = sprintf(
                "DELETE FROM `mood_map` USING `mood_map` LEFT JOIN `%s` ON `%s`.`id`=`mood_map`.`object_id` WHERE `mood_map`.`object_type`='%s' AND `%s`.`id` IS NULL;",
                $objectType,
                $objectType,
                $objectType,
                $objectType
            );
        }

        // then the moods nothing points at any more; a mood has no hidden or merged form to spare
        $statements[] = 'DELETE FROM `mood` USING `mood` LEFT JOIN `mood_map` ON `mood`.`id`=`mood_map`.`mood_id` WHERE `mood_map`.`id` IS NULL;';
        // `unique_mood_map` counts the owner, so only a row repeated for the same user is a duplicate; the others are who set the mood
        $statements[] = 'DELETE `b` FROM `mood_map` AS `a`, `mood_map` AS `b` WHERE `a`.`id` < `b`.`id` AND `a`.`mood_id` <=> `b`.`mood_id` AND `a`.`object_id` <=> `b`.`object_id` AND `a`.`object_type` <=> `b`.`object_type` AND `a`.`user` <=> `b`.`user`;';

        foreach ($statements as $sql) {
            $this->connection->query($sql);
        }

        // recount every valid type, then zero the moods that have no map of that type left at all
        foreach (MoodCountTypeEnum::cases() as $type) {
            $this->recountType($type);
            $this->zeroUnmappedType($type);
        }
    }

    public function create(string $name): int
    {
        $this->connection->query('REPLACE INTO `mood` SET `name` = ?', [$name]);

        return $this->connection->getLastInsertedId();
    }

    public function decrementCount(int $moodId, MoodCountTypeEnum $type): void
    {
        $this->connection->query(
            sprintf('UPDATE `mood` SET `%s` = `%s` - 1 WHERE `id` = ? AND `%s` > 0;', $type->value, $type->value, $type->value),
            [$moodId]
        );
    }

    public function delete(int $moodId): void
    {
        $this->connection->query('DELETE FROM `mood_map` WHERE `mood_map`.`mood_id` = ?', [$moodId]);
        $this->connection->query('DELETE FROM `mood` WHERE `mood`.`id` = ?', [$moodId]);
    }

    public function findIdByName(string $name): ?int
    {
        $moodId = $this->connection->fetchOne('SELECT `id` FROM `mood` WHERE `name` = ?', [$name]);

        return ($moodId === false)
            ? null
            : (int) $moodId;
    }

    /**
     * @return list<int>
     */
    public function getMoodObjectIds(string $objectType, int $moodId, int $count, int $offset, int $catalogId): array
    {
        $moodClause = ($moodId === 0) ? '' : '`mood_map`.`mood_id` = ? AND';
        $params     = ($moodId === 0) ? [$objectType] : [$moodId, $objectType];

        $sql = sprintf('SELECT DISTINCT `mood_map`.`object_id` FROM `mood_map` WHERE %s `mood_map`.`object_type` = ?', $moodClause);
        if (AmpConfig::get('catalog_disable') && in_array($objectType, self::CATALOG_TYPES)) {
            $sql .= ' AND ' . Catalog::get_enable_filter($objectType, '`mood_map`.`object_id`');
        }

        $catalogClause = Catalog::get_catalog_id_filter($objectType, '`mood_map`.`object_id`', $catalogId);
        if ($catalogClause !== '') {
            $sql .= ' AND ' . $catalogClause;
        }

        $sql .= $this->limitClause($count, $offset);

        $result = $this->connection->query($sql, $params);

        $ids = [];
        while ($objectId = $result->fetchColumn()) {
            $ids[] = (int) $objectId;
        }

        return $ids;
    }

    /**
     * @return list<array{id: int, name: string, count: int}>
     */
    public function getMoods(?string $type, int $limit, string $order): array
    {
        $countType  = MoodCountTypeEnum::tryFrom((string) $type);
        $typeSelect = ($countType instanceof MoodCountTypeEnum)
            ? sprintf(', `mood`.`%s` AS `count`', $countType->value)
            : ', (SUM(`mood`.`artist`)+SUM(`mood`.`album`)+SUM(`mood`.`song`)) AS `count`';

        // a mood has no hidden form, so unlike a genre there is not always a clause to hang the rest off
        $where = [];
        if ($countType instanceof MoodCountTypeEnum) {
            $where[] = sprintf('`mood`.`%s` != 0', $countType->value);
        }

        $user = Core::get_global('user');
        if (AmpConfig::get('catalog_filter') && $user instanceof User && $user->id > 0) {
            $where[] = Catalog::get_user_filter('mood', $user->id);
        }

        $sql = sprintf('SELECT `mood`.`id` AS `id`, `mood`.`name`%s FROM `mood` ', $typeSelect);
        if ($where !== []) {
            $sql .= 'WHERE ' . implode(' AND ', $where) . ' ';
        }

        $sql .= ($countType instanceof MoodCountTypeEnum)
            ? 'GROUP BY `mood`.`id`, `mood`.`name`, `count` '
            : 'GROUP BY `mood`.`id`, `mood`.`name`, `mood`.`artist`, `mood`.`album`, `mood`.`song` ';

        $sql .= sprintf('ORDER BY `%s` DESC, `mood`.`name` ', ($order === 'count') ? 'count' : 'name');
        if ($limit > 0) {
            $sql .= 'LIMIT ' . $limit;
        }

        $result = $this->connection->query($sql);

        $moods = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $moods[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'count' => (int) $row['count'],
            ];
        }

        return $moods;
    }

    /**
     * @return list<array{id: int, name: string, user: int}>
     */
    public function getObjectMoods(string $objectType, ?int $objectId): array
    {
        $params = [$objectType];
        // a mood a user sets by hand is mapped again beside the one read from the file, so group to one row per mood
        $sql = 'SELECT `mood`.`id`, `mood`.`name`, MAX(`mood_map`.`user`) AS `user` FROM `mood` LEFT JOIN `mood_map` ON `mood_map`.`mood_id`=`mood`.`id` WHERE `mood_map`.`object_type` = ?';
        if ($objectId !== null) {
            $sql .= ' AND `mood_map`.`object_id` = ?';
            $params[] = $objectId;
        }

        $sql .= ' GROUP BY `mood`.`id`, `mood`.`name`';

        $result = $this->connection->query($sql, $params);

        $moods = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $moods[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'user' => (int) $row['user'],
            ];
        }

        return $moods;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $moodIds): array
    {
        if ($moodIds === []) {
            return [];
        }

        $idList = implode(',', array_map('intval', $moodIds));

        $result = $this->connection->query('SELECT * FROM `mood` WHERE `id` IN (' . $idList . ')');

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function getSongMoodNamesByAlbum(int $albumId): array
    {
        return $this->getSongMoodNames(
            "SELECT `mood`.`name` FROM `mood` JOIN `mood_map` ON `mood`.`id` = `mood_map`.`mood_id` JOIN `song` ON `mood_map`.`object_id` = `song`.`id` WHERE `song`.`album` = ? AND `mood_map`.`object_type` = 'song' GROUP BY `mood`.`id`, `mood`.`name`;",
            $albumId
        );
    }

    /**
     * @return list<string>
     */
    public function getSongMoodNamesByArtist(int $artistId): array
    {
        return $this->getSongMoodNames(
            "SELECT `mood`.`name` FROM `mood` JOIN `mood_map` ON `mood`.`id` = `mood_map`.`mood_id` JOIN `song` ON `mood_map`.`object_id` = `song`.`id` WHERE `song`.`id` IN (SELECT `object_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_type` = 'song') AND `mood_map`.`object_type` = 'song' GROUP BY `mood`.`id`, `mood`.`name`;",
            $artistId
        );
    }

    /**
     * @return list<array{id: int, name: string, user: int, count: int}>
     */
    public function getTopMoods(string $objectType, int $objectId, int $limit): array
    {
        $limitClause = ($limit === 0)
            ? ''
            : 'LIMIT ' . $limit;

        // the per-type counter column doubles as the weight, so a type without one falls back to the summed count
        // one row per mood, not per map, or a mood a user set by hand is listed again beside the one read from the file
        $countType = MoodCountTypeEnum::tryFrom($objectType);
        $sql       = ($countType instanceof MoodCountTypeEnum)
            ? sprintf('SELECT `mood`.`id`, `mood`.`name`, MAX(`mood_map`.`user`) AS `user`, `mood`.`%s` AS `count` FROM `mood` LEFT JOIN `mood_map` ON `mood_map`.`mood_id`=`mood`.`id` WHERE `mood_map`.`object_type` = ? AND `mood_map`.`object_id` = ? GROUP BY `mood`.`id`, `mood`.`name`, `mood`.`%s` ORDER BY `%s` DESC, `mood`.`id` ', $countType->value, $countType->value, $countType->value) . $limitClause
            : 'SELECT `mood`.`id`, `mood`.`name`, MAX(`mood_map`.`user`) AS `user`, (`mood`.`artist`+`mood`.`album`+`mood`.`song`) AS `count` FROM `mood` LEFT JOIN `mood_map` ON `mood_map`.`mood_id`=`mood`.`id` WHERE `mood_map`.`object_type` = ? AND `mood_map`.`object_id` = ? GROUP BY `mood`.`id`, `mood`.`name`, `mood`.`artist`, `mood`.`album`, `mood`.`song` ORDER BY `count` DESC, `mood`.`id` ' . $limitClause;

        $result = $this->connection->query($sql, [$objectType, $objectId]);

        $moods = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $moods[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'user' => (int) $row['user'],
                'count' => (int) $row['count'],
            ];
        }

        return $moods;
    }

    public function incrementCount(int $moodId, MoodCountTypeEnum $type): void
    {
        $this->connection->query(
            sprintf('UPDATE `mood` SET `%s` = `%s` + 1 WHERE `id` = ?', $type->value, $type->value),
            [$moodId]
        );
    }

    public function mapExists(string $objectType, int $objectId, int $moodId, int $userId): bool
    {
        $row = $this->connection->fetchRow(
            'SELECT `id` FROM `mood_map` WHERE `mood_id` = ? AND `user` = ? AND `object_id` = ? AND `object_type` = ?',
            [$moodId, $userId, $objectId, $objectType]
        );

        return is_array($row) && array_key_exists('id', $row);
    }

    public function migrateMaps(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE IGNORE `mood_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    public function recountType(MoodCountTypeEnum $type): void
    {
        $this->connection->query(
            sprintf(
                'UPDATE `mood`, (SELECT `mood_id`, COUNT(`mood_id`) AS `mood_count` FROM `mood_map` WHERE `object_type` = ? GROUP BY `mood_id`) AS `mood_count` SET `mood`.`%s` = `mood_count`.`mood_count` WHERE `mood`.`%s` != `mood_count`.`mood_count` AND `mood_count`.`mood_id` = `mood`.`id`;',
                $type->value,
                $type->value
            ),
            [$type->value]
        );
    }

    public function removeAllMaps(string $objectType, int $objectId, ?int $userId = null): void
    {
        // a null user clears the lot, which is what deleting the object wants; an id keeps everybody else's, so a scan cannot drop a hand-set mood
        if ($userId === null) {
            $this->connection->query(
                'DELETE FROM `mood_map` WHERE `object_type` = ? AND `object_id` = ?',
                [$objectType, $objectId]
            );

            return;
        }

        $this->connection->query(
            'DELETE FROM `mood_map` WHERE `object_type` = ? AND `object_id` = ? AND `user` = ?',
            [$objectType, $objectId, $userId]
        );
    }

    public function removeMap(int $moodId, string $objectType, int $objectId, ?int $userId = null): void
    {
        // a null user drops the mood whoever set it, which is what a manual edit removing it means
        if ($userId === null) {
            $this->connection->query(
                'DELETE FROM `mood_map` WHERE `mood_id` = ? AND `object_type` = ? AND `object_id` = ?',
                [$moodId, $objectType, $objectId]
            );

            return;
        }

        $this->connection->query(
            'DELETE FROM `mood_map` WHERE `mood_id` = ? AND `object_type` = ? AND `object_id` = ? AND `user` = ?',
            [$moodId, $objectType, $objectId, $userId]
        );
    }

    public function rename(int $moodId, string $name): void
    {
        $this->connection->query('UPDATE `mood` SET `name` = ? WHERE `id` = ?', [$name, $moodId]);
    }

    public function zeroUnmappedType(MoodCountTypeEnum $type): void
    {
        $this->connection->query(
            sprintf(
                'UPDATE `mood` SET `mood`.`%s` = 0 WHERE `mood`.`%s` != 0 AND `mood`.`id` NOT IN (SELECT `mood_map`.`mood_id` FROM `mood_map` WHERE `mood_map`.`object_type` = ?);',
                $type->value,
                $type->value
            ),
            [$type->value]
        );
    }

    /**
     * Reads the mood names of a song-mood statement taking one bound object id
     *
     * @return list<string>
     */
    private function getSongMoodNames(string $sql, int $objectId): array
    {
        $result = $this->connection->query($sql, [$objectId]);

        $names = [];
        while ($name = $result->fetchColumn()) {
            $names[] = (string) $name;
        }

        return $names;
    }

    /**
     * Builds the LIMIT clause the mood browsers use, where an offset without a count is not a thing MySQL accepts
     */
    private function limitClause(int $count, int $offset): string
    {
        if ($count === 0) {
            return '';
        }

        return ($offset > 0)
            ? sprintf(' LIMIT %d, %d', $offset, $count)
            : ' LIMIT ' . $count;
    }
}
