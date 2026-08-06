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
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;

final readonly class SongPreviewRepository implements SongPreviewRepositoryInterface
{
    /** @var string the columns every read returns, so a cached row always has the same shape */
    private const string COLUMNS = '`id`, `file`, `album_mbid`, `artist`, `artist_mbid`, `title`, `disk`, `track`, `mbid`';

    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function collectGarbage(): void
    {
        $this->connection->query(
            'DELETE FROM `song_preview` USING `song_preview` LEFT JOIN `session` ON `session`.`id`=`song_preview`.`session` WHERE `session`.`id` IS NULL'
        );
    }

    public function findArtistMbid(int $artistId): ?string
    {
        $mbid = $this->connection->fetchOne('SELECT `mbid` FROM `artist` WHERE `id` = ?', [$artistId]);

        return ($mbid === false || $mbid === null)
            ? null
            : (string) $mbid;
    }

    /**
     * @return list<int>
     */
    public function findIdsBySession(string $sessionId, string $albumMbid): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `song_preview` WHERE `session` = ? AND `album_mbid` = ? ORDER BY `disk`, `track`;',
            [$sessionId, $albumMbid]
        );

        $previewIds = [];
        while ($previewId = $result->fetchColumn()) {
            $previewIds[] = (int) $previewId;
        }

        return $previewIds;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRow(int $previewId): array
    {
        $row = $this->connection->fetchRow(
            sprintf('SELECT %s FROM `song_preview` WHERE `id` = ? ORDER BY `disk`, `track`;', self::COLUMNS),
            [$previewId]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): ?int
    {
        try {
            $this->connection->query(
                'INSERT INTO `song_preview` (`file`, `album_mbid`, `artist`, `artist_mbid`, `title`, `disk`, `track`, `mbid`, `session`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $data['file'],
                    $data['album_mbid'],
                    $data['artist'],
                    $data['artist_mbid'],
                    $data['title'],
                    $data['disk'],
                    $data['track'],
                    $data['mbid'],
                    $data['session'],
                ]
            );
        } catch (DatabaseException) {
            $this->logger->error(
                'Unable to insert ' . $data['disk'] . '-' . $data['track'] . '-' . $data['title'],
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return null;
        }

        return $this->connection->getLastInsertedId();
    }
}
