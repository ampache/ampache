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

namespace Ampache\Module\Catalog;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PDO;

/**
 * Counts the rows of a table and maintains the cached totals in `update_info`
 *
 * Not readonly: it holds the per-request read cache that keeps repeated `getStoredCount()` calls off
 * the database, and it is the only writer of the rows that cache mirrors.
 */
final class CatalogCounter implements CatalogCounterInterface
{
    /**
     * The tables the server reports a plain row count for
     *
     * @var list<CountableTableEnum>
     */
    private const array LIST_TABLES = [
        CountableTableEnum::ALBUM_DISK,
        CountableTableEnum::ALBUM,
        CountableTableEnum::ARTIST,
        CountableTableEnum::CATALOG,
        CountableTableEnum::LABEL,
        CountableTableEnum::LICENSE,
        CountableTableEnum::LIVE_STREAM,
        CountableTableEnum::PLAYLIST,
        CountableTableEnum::PODCAST,
        CountableTableEnum::SEARCH,
        CountableTableEnum::SHARE,
        CountableTableEnum::TAG,
        CountableTableEnum::USER,
    ];

    /**
     * The media tables whose rows carry a playing time and a size, summed into the server totals
     *
     * @var list<CountableTableEnum>
     */
    private const array MEDIA_TABLES = [
        CountableTableEnum::SONG,
        CountableTableEnum::VIDEO,
        CountableTableEnum::PODCAST_EPISODE,
    ];
    /**
     * Every key the server reports a count for, so a caller always gets the whole shape
     *
     * @var array{
     *     album: int,
     *     album_disk: int,
     *     album_group: int,
     *     artist: int,
     *     catalog: int,
     *     items: int,
     *     label: int,
     *     license: int,
     *     live_stream: int,
     *     playlist: int,
     *     podcast: int,
     *     podcast_episode: int,
     *     search: int,
     *     share: int,
     *     size: int,
     *     song: int,
     *     tag: int,
     *     time: int,
     *     user: int,
     *     video: int
     * }
     */
    private const array SERVER_COUNTS = [
        'album' => 0,
        'album_disk' => 0,
        'album_group' => 0,
        'artist' => 0,
        'catalog' => 0,
        'items' => 0,
        'label' => 0,
        'license' => 0,
        'live_stream' => 0,
        'playlist' => 0,
        'podcast' => 0,
        'podcast_episode' => 0,
        'search' => 0,
        'share' => 0,
        'size' => 0,
        'song' => 0,
        'tag' => 0,
        'time' => 0,
        'user' => 0,
        'video' => 0,
    ];

    /** @var array<string, int> the `update_info` rows already read this request */
    private array $countCache = [];

    public function __construct(
        private readonly DatabaseConnectionInterface $connection,
        private readonly UpdateInfoRepositoryInterface $updateInfoRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function count(CountableTableEnum $table): int
    {
        $count = $this->countRows($table, 0, 0, 0);

        // a catalog id of 0 means the whole table, which is the only shape worth caching
        $this->setStoredCount($table->value, $count);

        // items, time and size are the media tables added together, so they move whenever one of them does
        if (in_array($table, self::MEDIA_TABLES, true)) {
            $this->refreshMediaTotals(false);
        }

        return $count;
    }

    public function countCatalog(int $catalogId, ?string $gatherTypes): array
    {
        $table = match ($gatherTypes) {
            'video' => CountableTableEnum::VIDEO,
            'podcast' => CountableTableEnum::PODCAST_EPISODE,
            default => CountableTableEnum::SONG,
        };

        // podcast_episode carries its catalog through its podcast rather than on the row
        if ($table === CountableTableEnum::PODCAST_EPISODE && $catalogId > 0) {
            $where  = 'WHERE `podcast` IN (SELECT `id` FROM `podcast` WHERE `catalog` = ?)';
            $params = [$catalogId];
        } elseif ($catalogId > 0) {
            $where  = 'WHERE `catalog` = ?';
            $params = [$catalogId];
        } else {
            $where  = '';
            $params = [];
        }

        $row = $this->connection->fetchRow(
            sprintf(
                'SELECT COUNT(`id`) AS `items`, IFNULL(SUM(`time`), 0) AS `time`, IFNULL(SUM(`size`)/1024/1024, 0) AS `size` FROM `%s` %s',
                $table->value,
                $where
            ),
            $params
        );

        if (!is_array($row)) {
            return ['items' => 0, 'time' => 0, 'size' => 0];
        }

        return [
            'items' => (int) ($row['items'] ?? 0),
            'time' => (int) ($row['time'] ?? 0),
            'size' => (int) ($row['size'] ?? 0),
        ];
    }

    public function countForCatalog(
        CountableTableEnum $table,
        int $catalogId,
        int $updateTime = 0,
        int $limit = 0,
    ): int {
        return $this->countRows($table, $catalogId, $updateTime, $limit);
    }

    public function countTags(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(`id`) FROM `tag` WHERE `is_hidden` = 0;');
    }

    public function countVideos(int $catalogId = 0): int
    {
        return ($catalogId > 0)
            ? (int) $this->connection->fetchOne('SELECT COUNT(`video`.`id`) FROM `video` WHERE `video`.`catalog` = ?', [$catalogId])
            : (int) $this->connection->fetchOne('SELECT COUNT(`video`.`id`) FROM `video`');
    }

    public function getStoredCount(string $key, int $userId): int
    {
        // user_data is written outside this class, so it is not cached here
        if ($userId > 0) {
            return (int) ($this->userRepository->getUserData($userId, $key)[$key] ?? 0);
        }

        if (!array_key_exists($key, $this->countCache)) {
            $this->countCache[$key] = $this->updateInfoRepository->getCountByKey($key);
        }

        return $this->countCache[$key];
    }

    public function getStoredCounts(int $userId): array
    {
        $stored = ($userId > 0)
            ? array_map(intval(...), $this->userRepository->getUserData($userId, null))
            : $this->updateInfoRepository->getAllCounts();

        return array_merge(self::SERVER_COUNTS, $stored);
    }

    public function refreshMediaTotals(bool $skipDisabledCatalogs, bool $storePerTable = false): void
    {
        $items = 0;
        $time  = 0;
        $size  = 0;
        foreach (self::MEDIA_TABLES as $table) {
            $sql = ($skipDisabledCatalogs)
                ? sprintf("SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `%s` LEFT JOIN `catalog` ON `%s`.`catalog` = `catalog`.`id` WHERE `%s`.`enabled` = '1' AND `catalog`.`enabled` = '1'", $table->value, $table->value, $table->value)
                : sprintf("SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `%s` WHERE `%s`.`enabled` = '1'", $table->value, $table->value);

            $row = $this->connection->query($sql)->fetch(PDO::FETCH_NUM);
            if ($row === false) {
                continue;
            }

            $items += (int) ($row[0] ?? 0);
            $time += (int) ($row[1] ?? 0);
            $size += $row[2] ?? 0;
            if ($storePerTable) {
                $this->setStoredCount($table->value, (int) ($row[0] ?? 0));
            }
        }

        $this->setStoredCount('items', $items);
        $this->setStoredCount('time', $time);
        $this->setStoredCount('size', $size);
    }

    public function refreshServerCounts(bool $skipDisabledCatalogs): void
    {
        $this->refreshMediaTotals($skipDisabledCatalogs, true);

        foreach (self::LIST_TABLES as $table) {
            $this->setStoredCount(
                $table->value,
                (int) $this->connection->fetchOne(sprintf('SELECT COUNT(`id`) FROM `%s`', $table->value))
            );
        }
    }

    public function setStoredCount(string $key, float|int $value): void
    {
        $this->updateInfoRepository->setCountByKey($key, $value);
        // keep the read cache in step with the value we just stored
        $this->countCache[$key] = (int) $value;
    }

    /**
     * The shared body of count() and countForCatalog(): one narrowed count of a bounded table
     */
    private function countRows(CountableTableEnum $table, int $catalogId, int $updateTime, int $limit): int
    {
        // an album is counted through its songs, so its catalog and enabled tests live on `song`
        $isAlbum = ($table === CountableTableEnum::ALBUM);

        $sql = ($isAlbum)
            ? 'SELECT COUNT(`id`) FROM (SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` '
            : sprintf('SELECT COUNT(DISTINCT `id`) FROM (SELECT `id` FROM `%s` ', $table->value);

        $params = [];
        $join   = 'WHERE';
        if ($catalogId > 0) {
            $sql .= $join . sprintf(' `%s`.`catalog` = ? ', $table->value);
            $params[] = $catalogId;
            $join     = 'AND';
        }

        if ($updateTime > 0) {
            $sql .= ($isAlbum)
                ? $join . ' `song`.`update_time` <= ? '
                : $join . ' `update_time` <= ? ';
            $params[] = $updateTime;
            $join     = 'AND';
        }

        if (in_array($table, [CountableTableEnum::ALBUM, CountableTableEnum::SONG, CountableTableEnum::PODCAST_EPISODE, CountableTableEnum::VIDEO], true)) {
            $sql .= ($isAlbum)
                ? $join . ' `song`.`enabled` = 1 '
                : $join . sprintf(' `%s`.`enabled` = 1 ', $table->value);
        }

        $sql .= ($limit > 0)
            ? 'LIMIT ' . $limit . ') AS `table_count`;'
            : ') AS `table_count`;';

        return (int) $this->connection->fetchOne($sql, $params);
    }
}
