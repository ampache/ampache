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

namespace Ampache\Gui\Catalog;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The live progress box a long-running catalog process renders before it starts working.
 *
 * The two counters are filled in by the process' own ajax updates, so the markup only has to name them.
 */
final class CatalogProgressView extends AbstractView
{
    public function __construct(
        private readonly CatalogProgressTypeEnum $type,
        private readonly int $catalogId,
        private readonly ?string $catalogName = null,
    ) {}

    public function getBoxClass(): string
    {
        return $this->type->getBoxClass();
    }

    public function getCounterElementId(): string
    {
        return $this->type->getCounterElementId() . $this->catalogId;
    }

    /**
     * The counter starts blank for the processes that count from zero and reads "None" where the catalog
     * may legitimately turn nothing up.
     */
    public function getCounterInitialValue(): string
    {
        return match ($this->type) {
            CatalogProgressTypeEnum::ADD, CatalogProgressTypeEnum::ART => T_('None'),
            default => '',
        };
    }

    public function getCounterLabel(): string
    {
        return match ($this->type) {
            CatalogProgressTypeEnum::ADD => T_('Found'),
            CatalogProgressTypeEnum::ART => T_('Searched'),
            CatalogProgressTypeEnum::CLEAN => T_('Checking'),
            CatalogProgressTypeEnum::VERIFY => T_('Verified'),
        };
    }

    /**
     * The catalog name is bracketed and bold inside the sentence, so it is substituted rather than concatenated.
     */
    public function getHeading(): string
    {
        $name = '<strong>[ ' . $this->e($this->catalogName ?? '') . ' ]</strong>';

        return match ($this->type) {
            /* HINT: Catalog Name */
            CatalogProgressTypeEnum::ADD => sprintf(T_('Starting New Media Search on "%s" Catalog'), $name),
            CatalogProgressTypeEnum::ART => '<strong>' . T_('Starting Art Search') . '. . .</strong>',
            /* HINT: Catalog Name */
            CatalogProgressTypeEnum::CLEAN => sprintf(T_('Cleaning the "%s" Catalog'), $name) . '...',
            /* HINT: Catalog Name */
            CatalogProgressTypeEnum::VERIFY => sprintf(T_('Updating the %s Catalog'), $name),
        };
    }

    public function getReaderElementId(): string
    {
        return $this->type->getReaderElementId() . $this->catalogId;
    }

    public function getReaderLabel(): string
    {
        return T_('Reading');
    }

    public function getTitle(): string
    {
        return match ($this->type) {
            CatalogProgressTypeEnum::ADD => T_('Starting New Media Search'),
            CatalogProgressTypeEnum::ART => T_('Art Search'),
            CatalogProgressTypeEnum::CLEAN => T_('Clean Catalog'),
            CatalogProgressTypeEnum::VERIFY => T_('Verify Catalog'),
        };
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('catalog_progress.phtml');
    }
}
