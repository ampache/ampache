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

namespace Ampache\Gui\Search;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The OpenSearch description document browsers fetch to offer Ampache as a search engine.
 */
final class OpenSearchDescriptorView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $charset,
        private readonly string $shortName,
        private readonly string $faviconUrl,
    ) {}

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getDescription(): string
    {
        return T_('Search Ampache');
    }

    public function getFaviconUrl(): string
    {
        return $this->faviconUrl;
    }

    public function getSearchUrl(): string
    {
        return $this->webPath . '/search.php';
    }

    public function getSelfUrl(): string
    {
        return $this->webPath . '/opensearch.php?action=descriptor';
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('opensearch_descriptor.phtml');
    }
}
