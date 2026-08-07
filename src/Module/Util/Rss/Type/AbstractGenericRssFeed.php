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

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\TemplateInterface;
use Ampache\Module\Util\Rss\View\GenericRssFeedView;
use Generator;
use Override;
use Traversable;

abstract readonly class AbstractGenericRssFeed implements FeedTypeInterface
{
    #[Override]
    public function createView(): TemplateInterface
    {
        return new GenericRssFeedView(
            AmpConfig::get('site_title') . ' - ' . $this->getTitle(),
            AmpConfig::get_web_path(),
            AmpConfig::get_web_path() . ($_SERVER['SCRIPT_URI'] ?? '/rss.php') . '?' . $_SERVER['QUERY_STRING'],
            ($this->getPubDate()) ? date('r', (int) $this->getPubDate()) : null,
            $this->getImage(),
            $this->getItems()
        );
    }

    /**
     * Feed image link
     */
    protected function getImage(): ?string
    {
        return null;
    }

    /**
     * @return Generator<array{
     *     title: string,
     *     link: string,
     *     description: string,
     *     comments: ?string,
     *     pubDate: string,
     *     guid: string,
     *     isPermaLink: string,
     *     image?: string
     * }>
     */
    abstract protected function getItems(): Traversable;

    /**
     * this is the pub date we should use for the Now Playing information,
     * this is a little specific as it uses the 'newest' expire we can find
     */
    protected function getPubDate(): ?int
    {
        return null;
    }

    abstract protected function getTitle(): string;
}
