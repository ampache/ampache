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

use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\License;
use Generator;
use Override;
use PDO;

/**
 * Manages access to license data
 *
 * Tables: `license`
 *
 * @extends BaseRepository<License>
 */
final readonly class LicenseRepository extends BaseRepository implements LicenseRepositoryInterface
{
    public function __construct(
        DatabaseConnectionInterface $connection,
        private readonly CatalogCounterInterface $catalogCounter,
    ) {
        parent::__construct($connection);
    }

    /**
     * Removes a license and keeps the stored total in step
     *
     * @param License $record
     */
    #[Override]
    public function delete(object $record): void
    {
        parent::delete($record);
        $this->catalogCounter->count(CountableTableEnum::LICENSE);
    }

    /**
     * Searches for the License by name and external link
     */
    public function find(string $searchValue): ?int
    {
        // lookup the license by name
        $result = $this->connection->fetchOne(
            'SELECT `id` FROM `license` WHERE `name` = ? LIMIT 1',
            [$searchValue]
        );

        if ($result !== false) {
            return (int) $result;
        }

        // lookup the license by external_link
        $result = $this->connection->fetchOne(
            'SELECT `id` FROM `license` WHERE `external_link` = ? LIMIT 1',
            [$searchValue]
        );

        if ($result !== false) {
            return (int) $result;
        }

        return null;
    }

    /**
     * Returns a list of licenses accessible by the current user.
     *
     * @return Generator<int, string>
     */
    public function getList(bool $show_hidden = true): Generator
    {
        $result = ($show_hidden)
            ? $this->connection->query('SELECT `id`, `name` FROM `license`;')
            : $this->connection->query('SELECT `id`, `name` FROM `license` WHERE `order` != 0;');

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            yield (int) $row['id'] => (string) $row['name'];
        }
    }

    /**
     * Persists the item in the database
     *
     * If the item is new, it will be created. Otherwise, an update will happen
     *
     * @return null|non-negative-int
     */
    public function persist(License $license): ?int
    {
        $result = null;

        if ($license->isNew()) {
            $this->connection->query(
                'INSERT INTO `license` (`name`, `description`, `external_link`, `order`) VALUES (?, ?, ?, ?)',
                [
                    $license->getName(),
                    $license->getDescription(),
                    $license->getExternalLink(),
                    $license->getOrder(),
                ]
            );

            $result = $this->connection->getLastInsertedId();
            $this->catalogCounter->count(CountableTableEnum::LICENSE);
        } else {
            $this->connection->query(
                'UPDATE `license` SET `name` = ?, `description` = ?, `external_link` = ?, `order` = ? WHERE `id` = ?',
                [
                    $license->getName(),
                    $license->getDescription(),
                    $license->getExternalLink(),
                    $license->getOrder(),
                    $license->getId()
                ]
            );
        }

        return $result;
    }

    /**
     * @return class-string<License>
     */
    protected function getModelClass(): string
    {
        return License::class;
    }

    /**
     * @return list<mixed>
     */
    protected function getPrototypeParameters(): array
    {
        return [$this];
    }

    protected function getTableName(): string
    {
        return 'license';
    }
}
