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

namespace Ampache\Gui\Partial;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * One row of the catalog filter list.
 */
final class CatalogFilterRowView extends AbstractView
{
    public function __construct(
        private readonly string $adminPath,
        private readonly int $filterId,
        private readonly string $name,
        private readonly int $userCount,
        private readonly int $catalogCount,
        private readonly bool $mayEdit,
    ) {}

    public function getAdminPath(): string
    {
        return $this->adminPath;
    }

    public function getCatalogCount(): int
    {
        return $this->catalogCount;
    }

    public function getFilterId(): int
    {
        return $this->filterId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUserCount(): int
    {
        return $this->userCount;
    }

    /**
     * The catch-all filter (id 0) is the fallback every user without one gets, so it cannot be deleted.
     */
    public function mayDelete(): bool
    {
        return $this->mayEdit && $this->filterId > 0;
    }

    public function mayEdit(): bool
    {
        return $this->mayEdit;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/catalog_filter_row.phtml');
    }
}
