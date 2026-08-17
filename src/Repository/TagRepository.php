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
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\TagCountTypeEnum;
use Ampache\Repository\Model\User;
use PDO;

final readonly class TagRepository implements TagRepositoryInterface
{
    /**
     * The object types that carry a catalog, and therefore the only ones the enable/catalog filters can narrow.
     */
    private const array CATALOG_TYPES = ['artist', 'album', 'album_disk', 'song', 'video'];

    /**
     * Every `tag_map`.`object_type`, which is also the table its `object_id` names, so an orphaned map is found for all of them.
     */
    private const array MAPPED_TYPES = [
        'album',
        'album_disk',
        'artist',
        'catalog',
        'label',
        'live_stream',
        'playlist',
        'podcast',
        'podcast_episode',
        'search',
        'song',
        'tag',
        'user',
        'video',
    ];

    public function __construct(
        private DatabaseConnectionInterface $connection,
        private CatalogCounterInterface $catalogCounter,
    ) {}

    public function addMap(int $tagId, string $objectType, int $objectId, int $userId): int
    {
        $this->connection->query(
            'INSERT IGNORE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) VALUES (?, ?, ?, ?)',
            [$tagId, $userId, $objectType, $objectId]
        );

        return $this->connection->getLastInsertedId();
    }

    public function collectGarbage(): void
    {
        // maps pointing at objects that no longer exist, whoever set them, or a genre a user set by hand outlives the object
        $statements = [];
        foreach (self::MAPPED_TYPES as $objectType) {
            $statements[] = sprintf(
                "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `%s` ON `%s`.`id`=`tag_map`.`object_id` WHERE `tag_map`.`object_type`='%s' AND `%s`.`id` IS NULL;",
                $objectType,
                $objectType,
                $objectType,
                $objectType
            );
        }

        // a truncated write leaves the enum's error value, naming no object any sweep above can resolve, and the tag it holds is never empty
        $statements[] = "DELETE FROM `tag_map` WHERE `object_type` = '';";
        // the maps of tags that have since been hidden, then the empty tags, keeping the hidden ones and anything still named as a merge target
        $statements[] = 'DELETE FROM `tag_map` WHERE `tag_id` IN (SELECT `id` FROM `tag` WHERE `is_hidden` = 1)';
        $statements[] = "DELETE FROM `tag` USING `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` WHERE `tag_map`.`id` IS NULL AND `is_hidden` = 0 AND NOT EXISTS (SELECT 1 FROM `tag_merge` WHERE `tag_merge`.`tag_id` = `tag`.`id`);";
        // `unique_tag_map` counts the owner, so only a row repeated for the same user is a duplicate; the others are who set the genre
        $statements[] = 'DELETE `b` FROM `tag_map` AS `a`, `tag_map` AS `b` WHERE `a`.`id` < `b`.`id` AND `a`.`tag_id` <=> `b`.`tag_id` AND `a`.`object_id` <=> `b`.`object_id` AND `a`.`object_type` <=> `b`.`object_type` AND `a`.`user` <=> `b`.`user`;';

        foreach ($statements as $sql) {
            $this->connection->query($sql);
        }

        // recount every valid type, then zero the tags that have no map of that type left at all
        foreach (TagCountTypeEnum::cases() as $type) {
            $this->recountType($type);
        }

        foreach (TagCountTypeEnum::cases() as $type) {
            $this->zeroUnmappedType($type);
        }
    }

    public function create(string $name): int
    {
        // the name is unique, so a concurrent insert of it is kept rather than replaced; replacing renumbers the tag and strands its maps
        $result = $this->connection->query('INSERT IGNORE INTO `tag` (`name`) VALUES (?)', [$name]);
        if ($result->rowCount() === 0) {
            return (int) $this->connection->fetchOne('SELECT `id` FROM `tag` WHERE `name` = ?', [$name]);
        }

        // the id has to be taken before anything else runs a statement, or the counter's queries lose it
        $tagId = $this->connection->getLastInsertedId();
        $this->catalogCounter->count(CountableTableEnum::TAG);

        return $tagId;
    }

    public function decrementCount(int $tagId, TagCountTypeEnum $type): void
    {
        $this->connection->query(
            sprintf('UPDATE `tag` SET `%s` = `%s` - 1 WHERE `id` = ? AND `%s` > 0;', $type->value, $type->value, $type->value),
            [$tagId]
        );
    }

    public function delete(int $tagId): void
    {
        $this->connection->query('DELETE FROM `tag_map` WHERE `tag_map`.`tag_id` = ?', [$tagId]);
        $this->connection->query('DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` = ?', [$tagId]);
        $this->connection->query('DELETE FROM `tag` WHERE `tag`.`id` = ? ', [$tagId]);

        $this->catalogCounter->count(CountableTableEnum::TAG);
    }

    public function findIdByName(string $name): ?int
    {
        $tagId = $this->connection->fetchOne('SELECT `id` FROM `tag` WHERE `name` = ?', [$name]);

        return ($tagId === false)
            ? null
            : (int) $tagId;
    }

    public function getHiddenCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) AS `tag_count` FROM `tag` WHERE `is_hidden` = 1;');
    }

    public function getMergedCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(DISTINCT `tag_id`) AS `tag_count` FROM `tag_merge`;');
    }

    public function getMergedNames(int $tagId): array
    {
        $result = $this->connection->query(
            'SELECT `tag`.`name` FROM `tag_merge` INNER JOIN `tag` ON `tag`.`id` = `tag_merge`.`merged_to` WHERE `tag_merge`.`tag_id` = ? ORDER BY `tag`.`name` ',
            [$tagId]
        );

        $names = [];
        while ($name = $result->fetchColumn()) {
            $names[] = (string) $name;
        }

        return $names;
    }

    public function getMergedTags(int $tagId): array
    {
        $result = $this->connection->query(
            'SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, 0 AS `count` FROM `tag_merge` INNER JOIN `tag` ON `tag`.`id` = `tag_merge`.`merged_to` WHERE `tag_merge`.`tag_id` = ? ORDER BY `tag`.`name`;',
            [$tagId]
        );

        $tags = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tags[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'is_hidden' => (int) $row['is_hidden'],
                'count' => (int) $row['count'],
            ];
        }

        return $tags;
    }

    public function getObjectTags(string $objectType, ?int $objectId): array
    {
        $params = [$objectType];
        // a genre a user sets by hand is mapped again beside the one read from the file, so group to one row per tag
        $sql = 'SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, MAX(`tag_map`.`user`) AS `user` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ?';
        if ($objectId !== null) {
            $sql .= ' AND `tag_map`.`object_id` = ?';
            $params[] = $objectId;
        }

        $sql .= ' GROUP BY `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`';

        $result = $this->connection->query($sql, $params);

        $tags = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tags[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'is_hidden' => (int) $row['is_hidden'],
                'user' => (int) $row['user'],
            ];
        }

        return $tags;
    }

    public function getRowsByIds(array $tagIds): array
    {
        if ($tagIds === []) {
            return [];
        }

        $idList = implode(',', array_map(intval(...), $tagIds));

        $result = $this->connection->query('SELECT * FROM `tag` WHERE `id` IN (' . $idList . ')');

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Reads the distinct genres tagged on the songs of one album
     *
     * @return list<string>
     */
    public function getSongTagNamesByAlbum(int $albumId): array
    {
        return $this->getSongTagNames(
            "SELECT `tag`.`name` FROM `tag` JOIN `tag_map` ON `tag`.`id` = `tag_map`.`tag_id` JOIN `song` ON `tag_map`.`object_id` = `song`.`id` WHERE `song`.`album` = ? AND `tag_map`.`object_type` = 'song' GROUP BY `tag`.`id`, `tag`.`name`;",
            $albumId
        );
    }

    /**
     * Reads the distinct genres tagged on the songs one artist is mapped onto
     *
     * @return list<string>
     */
    public function getSongTagNamesByArtist(int $artistId): array
    {
        return $this->getSongTagNames(
            "SELECT `tag`.`name` FROM `tag` JOIN `tag_map` ON `tag`.`id` = `tag_map`.`tag_id` JOIN `song` ON `tag_map`.`object_id` = `song`.`id` WHERE `song`.`id` IN (SELECT `object_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_type` = 'song') AND `tag_map`.`object_type` = 'song' GROUP BY `tag`.`id`, `tag`.`name`;",
            $artistId
        );
    }

    public function getTagObjects(string $objectType, int $tagId, int $count, int $offset, int $catalogId): array
    {
        $tagClause = ($tagId === 0) ? '' : '`tag_map`.`tag_id` = ? AND';
        $params    = ($tagId === 0) ? [$objectType] : [$tagId, $objectType];

        $sql = sprintf('SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` WHERE %s `tag_map`.`object_type` = ?', $tagClause);
        if (AmpConfig::get('catalog_disable') && in_array($objectType, self::CATALOG_TYPES, true)) {
            $sql .= 'AND ' . Catalog::get_enable_filter($objectType, '`tag_map`.`object_id`');
        }

        $catalogClause = Catalog::get_catalog_id_filter($objectType, '`tag_map`.`object_id`', $catalogId);
        if ($catalogClause !== '') {
            $sql .= ' AND ' . $catalogClause;
        }

        $sql .= $this->limitClause($count, $offset);

        $result = $this->connection->query($sql, $params);

        $objectIds = [];
        while ($objectId = $result->fetchColumn()) {
            $objectIds[] = (int) $objectId;
        }

        return $objectIds;
    }

    public function getTags(?string $type, int $limit, string $order): array
    {
        if ($type === 'tag_hidden') {
            // a hidden tag, or one that ended up applying to nothing, which the tag cloud shows in its own view
            $sql = 'SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, 0 AS `count` FROM `tag` WHERE (`tag`.`is_hidden` = 1 OR (`tag`.`album` = 0 AND `tag`.`artist` = 0 AND `tag`.`song` = 0 AND `tag`.`video` = 0)) ';
        } else {
            $countType   = TagCountTypeEnum::tryFrom((string) $type);
            $typeSelect  = ($countType instanceof TagCountTypeEnum)
                ? sprintf(', `tag`.`%s` AS `count`', $countType->value)
                : ', (SUM(`tag`.`artist`)+SUM(`tag`.`album`)+SUM(`tag`.`song`)) AS `count`';
            $typeWhere = ($countType instanceof TagCountTypeEnum)
                ? sprintf(' AND `tag`.`%s` != 0 ', $countType->value)
                : ' ';

            $hiddenWhere = ($type === 'all_hidden')
                ? '`tag`.`is_hidden` IN (0,1)'
                : '`tag`.`is_hidden` = 0';

            $user = Core::get_global('user');
            $sql  = (AmpConfig::get('catalog_filter') && $user instanceof User && $user->id > 0)
                ? sprintf('SELECT `tag`.`id` AS `id`, `tag`.`name`, `tag`.`is_hidden`%s FROM `tag` WHERE %s%sAND %s ', $typeSelect, $hiddenWhere, $typeWhere, Catalog::get_user_filter('tag', $user->id))
                : sprintf('SELECT `tag`.`id` AS `id`, `tag`.`name`, `tag`.`is_hidden`%s FROM `tag` WHERE %s%s', $typeSelect, $hiddenWhere, $typeWhere);

            $sql .= ($countType instanceof TagCountTypeEnum)
                ? 'GROUP BY `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, `count` '
                : 'GROUP BY `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, `tag`.`artist`, `tag`.`album`, `tag`.`song` ';
        }

        // allowlist: $order reaches ORDER BY as a raw identifier (see get_tags caller ?sort=)
        $sql .= ($order === 'count')
            ? 'ORDER BY `count` DESC, `tag`.`id`'
            : sprintf('ORDER BY `%s`', (in_array($order, ['name', 'id', 'is_hidden'], true) ? $order : 'name'));

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        $result = $this->connection->query($sql);

        $tags = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tags[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'is_hidden' => (int) $row['is_hidden'],
                'count' => (int) ($row['count'] ?? 0),
            ];
        }

        return $tags;
    }

    /**
     * @return list<array{id: int, name: string, is_hidden: int, user: int, count: int}>
     */
    public function getTopTags(string $objectType, int $objectId, int $limit): array
    {
        $limitClause = ($limit === 0)
            ? ''
            : 'LIMIT ' . $limit;

        // the per-type counter column doubles as the weight, so a type without one falls back to the summed count
        // one row per tag, not per map, or a genre a user set by hand is listed again beside the one read from the file
        $countType = TagCountTypeEnum::tryFrom($objectType);
        $sql       = ($countType instanceof TagCountTypeEnum)
            ? sprintf('SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, MAX(`tag_map`.`user`) AS `user`, `tag`.`%s` AS `count` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ? AND `tag_map`.`object_id` = ? GROUP BY `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, `tag`.`%s` ORDER BY `%s` DESC, `tag`.`id` ', $countType->value, $countType->value, $countType->value) . $limitClause
            : 'SELECT `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, MAX(`tag_map`.`user`) AS `user`, (`tag`.`artist`+`tag`.`album`+`tag`.`song`) AS `count` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ? AND `tag_map`.`object_id` = ? GROUP BY `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, `tag`.`artist`, `tag`.`album`, `tag`.`song` ORDER BY `count` DESC, `tag`.`id` ' . $limitClause;

        $result = $this->connection->query($sql, [$objectType, $objectId]);

        $tags = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tags[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'is_hidden' => (int) $row['is_hidden'],
                'user' => (int) $row['user'],
                'count' => (int) $row['count'],
            ];
        }

        return $tags;
    }

    /**
     * The same rows getTopTags() returns, for a whole page of objects at once.
     *
     * @param list<int> $objectIds
     * @return array<int, list<array{id: int, name: string, is_hidden: int, user: int, count: int}>>
     */
    public function getTopTagsBulk(string $objectType, array $objectIds): array
    {
        if ($objectIds === []) {
            return [];
        }

        $countType = TagCountTypeEnum::tryFrom($objectType);
        $count     = ($countType instanceof TagCountTypeEnum)
            ? sprintf('`tag`.`%s`', $countType->value)
            : '(`tag`.`artist`+`tag`.`album`+`tag`.`song`)';
        $holders = implode(',', array_fill(0, count($objectIds), '?'));

        $sql = sprintf(
            'SELECT `tag_map`.`object_id` AS `owner_id`, `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, MAX(`tag_map`.`user`) AS `user`, %s AS `count` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag`.`is_hidden` = false AND `tag_map`.`object_type` = ? AND `tag_map`.`object_id` IN (%s) GROUP BY `tag_map`.`object_id`, `tag`.`id`, `tag`.`name`, `tag`.`is_hidden`, %s ORDER BY `count` DESC, `tag`.`id`',
            $count,
            $holders,
            $count
        );

        $result = $this->connection->query($sql, array_merge([$objectType], $objectIds));

        $tags = array_fill_keys($objectIds, []);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tags[(int) $row['owner_id']][] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'is_hidden' => (int) $row['is_hidden'],
                'user' => (int) $row['user'],
                'count' => (int) $row['count'],
            ];
        }

        return $tags;
    }

    public function incrementCount(int $tagId, TagCountTypeEnum $type): void
    {
        $this->connection->query(
            sprintf('UPDATE `tag` SET `%s` = `%s` + 1 WHERE `id` = ?', $type->value, $type->value),
            [$tagId]
        );
    }

    public function mapExists(string $objectType, int $objectId, int $tagId, int $userId): bool
    {
        $row = $this->connection->fetchRow(
            'SELECT * FROM `tag_map` LEFT JOIN `tag` ON `tag`.`id` = `tag_map`.`tag_id` LEFT JOIN `tag_merge` ON `tag`.`id`=`tag_merge`.`tag_id` WHERE (`tag_map`.`tag_id` = ? OR `tag_map`.`tag_id` = `tag_merge`.`merged_to`) AND `tag_map`.`user` = ? AND `tag_map`.`object_id` = ? AND `tag_map`.`object_type` = ?',
            [$tagId, $userId, $objectId, $objectType]
        );

        return is_array($row) && array_key_exists('id', $row);
    }

    public function mergeInto(int $tagId, int $mergeToId): void
    {
        $this->connection->query(
            'REPLACE INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) SELECT ?, `user`, `object_type`, `object_id` FROM `tag_map` AS `tm` WHERE `tm`.`tag_id` = ? AND NOT EXISTS (SELECT 1 FROM `tag_map` WHERE `tag_map`.`tag_id` = ? AND `tag_map`.`object_id` = `tm`.`object_id` AND `tag_map`.`object_type` = `tm`.`object_type` AND `tag_map`.`user` = `tm`.`user`)',
            [$mergeToId, $tagId, $mergeToId]
        );
    }

    public function migrateMaps(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE IGNORE `tag_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    public function persistMerge(int $tagId, int $mergeToId): void
    {
        $this->connection->query(
            'REPLACE INTO `tag_merge` (`tag_id`, `merged_to`) VALUES (?, ?)',
            [$tagId, $mergeToId]
        );
    }

    public function recountType(TagCountTypeEnum $type): void
    {
        $this->connection->query(
            sprintf(
                'UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = ? GROUP BY `tag_id`) AS `tag_count` SET `tag`.`%s` = `tag_count`.`tag_count` WHERE `tag`.`%s` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;',
                $type->value,
                $type->value
            ),
            [$type->value]
        );
    }

    public function removeAllMaps(string $objectType, int $objectId, ?int $userId = null): void
    {
        // a null user clears the lot, which is what deleting the object wants; an id keeps everybody else's, so a scan cannot drop a hand-set genre
        if ($userId === null) {
            $this->connection->query(
                'DELETE FROM `tag_map` WHERE `object_type` = ? AND `object_id` = ?',
                [$objectType, $objectId]
            );

            return;
        }

        $this->connection->query(
            'DELETE FROM `tag_map` WHERE `object_type` = ? AND `object_id` = ? AND `user` = ?',
            [$objectType, $objectId, $userId]
        );
    }

    public function removeMap(int $tagId, string $objectType, int $objectId, ?int $userId = null): void
    {
        // a null user drops the genre whoever set it, which is what a manual edit removing it means
        if ($userId === null) {
            $this->connection->query(
                'DELETE FROM `tag_map` WHERE `tag_id` = ? AND `object_type` = ? AND `object_id` = ?',
                [$tagId, $objectType, $objectId]
            );

            return;
        }

        $this->connection->query(
            'DELETE FROM `tag_map` WHERE `tag_id` = ? AND `object_type` = ? AND `object_id` = ? AND `user` = ?',
            [$tagId, $objectType, $objectId, $userId]
        );
    }

    public function removeMapsForTag(int $tagId): void
    {
        $this->connection->query('DELETE FROM `tag_map` WHERE `tag_map`.`tag_id` = ? ', [$tagId]);
    }

    public function removeMerges(int $tagId): void
    {
        $this->connection->query('DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` = ?;', [$tagId]);
    }

    public function rename(int $tagId, string $name): void
    {
        $this->connection->query('UPDATE `tag` SET `name` = ? WHERE `id` = ?', [$name, $tagId]);
    }

    public function setHidden(int $tagId, int $isHidden, bool $resetCounts): void
    {
        $sql = ($resetCounts)
            ? 'UPDATE `tag` SET `is_hidden` = ?, `artist` = 0, `album` = 0, `song` = 0 WHERE `id` = ?'
            : 'UPDATE `tag` SET `is_hidden` = ? WHERE `id` = ?';

        $this->connection->query($sql, [$isHidden, $tagId]);
    }

    public function zeroUnmappedType(TagCountTypeEnum $type): void
    {
        $this->connection->query(
            sprintf(
                'UPDATE `tag` SET `tag`.`%s` = 0 WHERE `tag`.`%s` != 0 AND `tag`.`id` NOT IN (SELECT `tag_map`.`tag_id` FROM `tag_map` WHERE `tag_map`.`object_type` = ?);',
                $type->value,
                $type->value
            ),
            [$type->value]
        );
    }

    /**
     * Reads the tag names of a song-tag statement taking one bound object id
     *
     * @return list<string>
     */
    private function getSongTagNames(string $sql, int $objectId): array
    {
        $result = $this->connection->query($sql, [$objectId]);

        $names = [];
        while ($name = $result->fetchColumn()) {
            $names[] = (string) $name;
        }

        return $names;
    }

    /**
     * Builds the LIMIT clause the tag browsers use, where an offset without a count is not a thing MySQL accepts
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
