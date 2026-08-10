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

namespace Ampache\Module\Util\Rss\View;

use Ampache\Gui\View\AbstractView;
use Override;
use Traversable;

/**
 * Renders the plain RSS 2.0 channel every feed but the podcast one uses.
 *
 * @phpstan-type RssFeedItem array{
 *     title: string,
 *     link: string,
 *     description: string,
 *     comments: ?string,
 *     pubDate: string,
 *     guid: string,
 *     isPermaLink: string,
 *     image?: string,
 *     duration?: string,
 *     season?: ?string,
 *     season_name?: ?string,
 *     episode?: ?string,
 *     type?: ?string,
 *     size?: ?string,
 *     url?: ?string
 * }
 */
final class GenericRssFeedView extends AbstractView
{
    /**
     * @param Traversable<RssFeedItem> $items
     * @param list<array{feedUrl: string, feedGuid: string}> $remoteItems
     */
    public function __construct(
        private readonly string $title,
        private readonly string $link,
        private readonly string $rssLink,
        private readonly ?string $pubDate,
        private readonly ?string $image,
        private readonly Traversable $items,
        private readonly ?string $medium = null,
        private readonly array $remoteItems = [],
        private readonly string $language = 'en-US',
    ) {}

    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @return Traversable<RssFeedItem>
     */
    public function getItems(): Traversable
    {
        return $this->items;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getMedium(): ?string
    {
        return $this->medium;
    }

    public function getPubDate(): ?string
    {
        return $this->pubDate;
    }

    /**
     * @return list<array{feedUrl: string, feedGuid: string}>
     */
    public function getRemoteItems(): array
    {
        return $this->remoteItems;
    }

    public function getRssLink(): string
    {
        return $this->rssLink;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('rss/generic_rss_feed.phtml');
    }
}
