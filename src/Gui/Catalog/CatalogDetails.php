<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Gui\Catalog;

use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\Stats\CatalogStatsInterface;
use Ampache\Repository\Model\Catalog;

final class CatalogDetails implements CatalogDetailsInterface
{
    private GuiFactoryInterface $guiFactory;

    private Catalog $catalog;

    public function __construct(
        GuiFactoryInterface $guiFactory,
        Catalog $catalog
    ) {
        $this->guiFactory = $guiFactory;
        $this->catalog    = $catalog;
    }

    public function getName(): string
    {
        return $this->catalog->name;
    }

    public function getFullInfo(): string
    {
        return scrub_out($this->catalog->f_full_info);
    }

    public function getFilterUser(): string
    {
        return scrub_out($this->catalog->f_filter_user);
    }

    public function getLastUpdateDate(): string
    {
        return scrub_out($this->catalog->f_update);
    }

    public function getLastAddDate(): string
    {
        return scrub_out($this->catalog->f_add);
    }

    public function getLastCleanDate(): string
    {
        return scrub_out($this->catalog->f_clean);
    }

    public function getCatalogStats(): CatalogStatsInterface
    {
        return $this->guiFactory->createCatalogStats(
            Catalog::get_stats($this->catalog->id)
        );
    }
}
