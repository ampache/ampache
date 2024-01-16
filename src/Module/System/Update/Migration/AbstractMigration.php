<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

namespace Ampache\Module\System\Update\Migration;

use Ahc\Cli\IO\Interactor;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Preference;
use ArrayIterator;
use Traversable;

abstract class AbstractMigration implements MigrationInterface
{
    private DatabaseConnectionInterface $connection;

    private ?Interactor $interactor = null;

    /** @var list<non-empty-string> */
    protected array $changelog = [];

    /**
     * If the migration should trigger a warning, set to `true`
     */
    protected bool $warning = false;

    public function __construct(
        DatabaseConnectionInterface $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Returns `true` if the migration should trigger a warning within the UI
     */
    public function hasWarning(): bool
    {
        return $this->warning;
    }

    /**
     * Sets the cli interactor instance
     */
    public function setInteractor(?Interactor $interactor): void
    {
        $this->interactor = $interactor;
    }

    /**
     * Returns a list of changelog-strings
     *
     * @return list<non-empty-string>
     */
    public function getChangelog(): array
    {
        return $this->changelog;
    }

    /**
     * Performs database migrations
     *
     * @param array<mixed> $params
     */
    protected function updateDatabase(string $sql, array $params = []): void
    {
        if ($this->interactor !== null) {
            $this->interactor->info(
                $sql . ' ' . json_encode($params),
                true
            );
        }
        $this->connection->query($sql, $params);

    }

    /**
     * Add preferences and print update errors for preference inserts on failure
     *
     * @param float|int|string $default
     */
    protected function updatePreferences(
        string $name,
        string $description,
        $default,
        int $level,
        string $type,
        string $category,
        ?string $subcategory = null
    ): void {
        Preference::insert($name, $description, $default, $level, $type, $category, $subcategory, true);

        if ($this->interactor !== null) {
            $this->interactor->info(
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                sprintf(T_('Updated: %s'), $name),
                true
            );
        }
    }

    /**
     * Returns the sql-statements used for migrating the database tables
     *
     * @return Traversable<string, string> Dictionary with the key being the table name and value the sql-statements
     */
    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Traversable {
        return new ArrayIterator();
    }
}
