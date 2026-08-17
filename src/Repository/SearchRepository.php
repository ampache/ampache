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

use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\playlist_object;
use Ampache\Repository\Model\User;
use Override;

/**
 * Manages search related database access
 *
 * Tables: `search`
 */
final readonly class SearchRepository extends AbstractPlaylistObjectRepository implements SearchRepositoryInterface
{
    /**
     * Removes collaborator rows whose saved search no longer exists
     *
     * Only prefixed keys belong to this repository, so the expression must keep agreeing with `collaborateKey()`.
     */
    public function collectGarbage(): void
    {
        $this->connection->query(
            "DELETE FROM `user_playlist_map` WHERE `playlist_id` LIKE 'smart\\_%' AND `playlist_id` NOT IN (SELECT CONCAT('smart_', `id`) FROM `search`);"
        );
    }

    /**
     * Removes the saved search
     */
    public function delete(Search $search): void
    {
        $this->connection->query('DELETE FROM `search` WHERE `id` = ?', [$search->getId()]);

        $this->catalogCounter->count(CountableTableEnum::SEARCH);
    }

    /**
     * Stores a new saved search and returns its id, or null when nothing was written
     */
    public function insert(Search $search, User $user, int $time): ?int
    {
        $this->connection->query(
            'INSERT INTO `search` (`name`, `type`, `user`, `username`, `rules`, `logic_operator`, `random`, `limit`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $search->name,
                $search->type,
                $user->getId(),
                $user->username,
                json_encode($search->rules),
                strtolower((string) $search->logic_operator),
                ($search->random > 0) ? 1 : 0,
                $search->limit,
                $time,
                $time,
            ]
        );

        $insertedId = $this->connection->getLastInsertedId() ?: null;

        $this->catalogCounter->count(CountableTableEnum::SEARCH);

        return $insertedId;
    }

    /**
     * Whether the user already has a saved search of this name and type
     */
    public function nameExists(string $name, int $userId, ?string $type): bool
    {
        return $this->connection->fetchOne(
            'SELECT `id` FROM `search` WHERE `name` = ? AND `user` = ? AND `type` = ?;',
            [$name, $userId, $type]
        ) !== false;
    }

    /**
     * Saved searches share `user_playlist_map` with playlists, so their rows carry the same
     * `smart_` prefix the API uses to tell the two apart.
     */
    protected function collaborateKey(playlist_object $item): string
    {
        return 'smart_' . $item->getId();
    }

    /**
     * The four columns `search` has and `playlist` does not. `random` is a `tinyint(1)`, so it has to
     * reach the driver as an int rather than a bool.
     *
     * @return array<string, mixed>
     */
    #[Override]
    protected function editableColumns(playlist_object $item): array
    {
        if (!$item instanceof Search) {
            return [];
        }

        return [
            'random' => ($item->random > 0) ? 1 : 0,
            'limit' => $item->limit,
            'logic_operator' => strtolower((string) $item->logic_operator),
            'rules' => json_encode($item->rules) ?: null,
        ];
    }

    protected function tableName(): string
    {
        return 'search';
    }
}
