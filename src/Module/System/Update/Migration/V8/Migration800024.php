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
 */

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add the `cron_cache_live_count` preference. When `cron_cache` is enabled the
 * played counters are read from the cache and only refreshed by the cron task,
 * so they lag behind until the next run (see issue #2587). Enabling this adds
 * the plays recorded since the last cache run to the cached value, keeping the
 * count accurate. It is disabled by default to preserve the existing behaviour
 * on large instances where the extra per-count query is not wanted.
 */
final class Migration800024 extends AbstractMigration
{
    protected array $changelog = [
        'Add the `cron_cache_live_count` preference for accurate played counts when the cache is enabled',
    ];

    public function migrate(): void
    {
        $this->updatePreferences(
            'cron_cache_live_count',
            'Add live plays to the cached count for accurate stats (only used when Cron Cache is enabled)',
            '0',
            AccessLevelEnum::ADMIN->value,
            'boolean',
            'system',
            'catalog'
        );
    }
}
