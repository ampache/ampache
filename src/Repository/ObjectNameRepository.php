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
use Ampache\Repository\Model\ObjectNameTypeEnum;
use PDO;

final readonly class ObjectNameRepository implements ObjectNameRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
    ) {}

    public function findNames(
        ObjectNameTypeEnum $type,
        array $objectIds,
        ?string $sort = null,
        string $order = 'ASC',
    ): array {
        if ($objectIds === []) {
            return [];
        }

        // every branch is a literal, so no table or column name is ever built from the type
        $select = match ($type) {
            ObjectNameTypeEnum::ALBUM => "SELECT `album`.`id`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `name` FROM `album` WHERE `id` IN ",
            ObjectNameTypeEnum::ARTIST,
            ObjectNameTypeEnum::ALBUM_ARTIST,
            ObjectNameTypeEnum::SONG_ARTIST => "SELECT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `name` FROM `artist` WHERE `id` IN ",
            ObjectNameTypeEnum::CATALOG => 'SELECT `catalog`.`id`, `catalog`.`name` AS `name` FROM `catalog` WHERE `id` IN ',
            ObjectNameTypeEnum::LIVE_STREAM => 'SELECT `live_stream`.`id`, `live_stream`.`name` AS `name` FROM `live_stream` WHERE `id` IN ',
            ObjectNameTypeEnum::PLAYLIST => 'SELECT `playlist`.`id`, `playlist`.`name` AS `name` FROM `playlist` WHERE `id` IN ',
            ObjectNameTypeEnum::SEARCH => 'SELECT `search`.`id`, `search`.`name` AS `name` FROM `search` WHERE `id` IN ',
            ObjectNameTypeEnum::PODCAST => 'SELECT `podcast`.`id`, `podcast`.`title` AS `name` FROM `podcast` WHERE `id` IN ',
            ObjectNameTypeEnum::PODCAST_EPISODE => 'SELECT `podcast_episode`.`id`, `podcast_episode`.`title` AS `name` FROM `podcast_episode` WHERE `id` IN ',
            ObjectNameTypeEnum::SONG => 'SELECT `song`.`id`, `song`.`title` AS `name` FROM `song` WHERE `id` IN ',
            ObjectNameTypeEnum::VIDEO => 'SELECT `video`.`id`, `video`.`title` AS `name` FROM `video` WHERE `id` IN ',
            ObjectNameTypeEnum::SHARE => 'SELECT `share`.`id`, `share`.`description` AS `name` FROM `share` WHERE `id` IN ',
            // a smartlist id arrives prefixed, so the two lists are merged before they are matched
            ObjectNameTypeEnum::PLAYLIST_SEARCH => "SELECT `id`, `name` FROM (SELECT `id`, `name` FROM `playlist` UNION SELECT CONCAT('smart_', `id`) AS `id`, `name` FROM `search`) AS `playlist` WHERE `id` IN ",
        };

        $sql = $select . '(' . implode(',', array_fill(0, count($objectIds), '?')) . ')' . $this->orderClause($sort, $order);

        $result = $this->connection->query($sql, $objectIds);

        $names = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $names[] = [
                'id' => $row['id'],
                'name' => (string) $row['name'],
            ];
        }

        return $names;
    }

    /**
     * Builds the ORDER BY, dropping a sort that is not a bare column identifier
     */
    private function orderClause(?string $sort, string $order): string
    {
        if ($sort === null || $sort === '') {
            return ';';
        }

        $direction = ($order === 'DESC') ? 'DESC' : 'ASC';

        return match ($sort) {
            'name_year' => ' ORDER BY `name` ' . $direction . ', `year` ' . $direction . ';',
            'name_original_year' => ' ORDER BY `name` ' . $direction . ', `original_year` ' . $direction . ';',
            default => (preg_match('/^[a-z][a-z0-9_]*$/', $sort) === 1)
                ? ' ORDER BY `' . $sort . '` ' . $direction . ';'
                : ';',
        };
    }
}
