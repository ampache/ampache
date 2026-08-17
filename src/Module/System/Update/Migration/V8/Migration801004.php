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

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add the `pow_challenge` table
 *
 * Spent proof-of-work answers, so a solved challenge cannot be replayed. Rows expire with `pow_ttl`.
 */
final class Migration801004 extends AbstractMigration
{
    private const string POW_CHALLENGE_TABLE = "CREATE TABLE IF NOT EXISTS `pow_challenge` (`id` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, `expire` int(11) UNSIGNED NOT NULL, PRIMARY KEY (`id`), KEY `expire_index` (`expire`)) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s;";

    protected array $changelog = ['Add `pow_challenge` table'];

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        yield from parent::getTableMigrations($collation, $charset, $engine, $build);

        if ($build > 801004) {
            yield 'pow_challenge' => sprintf(self::POW_CHALLENGE_TABLE, $engine, $charset, $collation);
        }
    }

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $this->updateDatabase(sprintf(self::POW_CHALLENGE_TABLE, $engine, $charset, $collation));
    }
}
