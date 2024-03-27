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
 *
 */

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Config\AmpConfig;
use Generator;
use PhpTal\PhpTalInterface;
use Traversable;

abstract readonly class AbstractGenericRssFeed implements FeedTypeInterface
{
    public function configureTemplate(PhpTalInterface $tal): void
    {
        $tal->setTemplate((string) realpath(__DIR__ . '/../../../../../resources/templates/rss/generic_rss_feed.xml'));
        $tal->set('TITLE', AmpConfig::get('site_title') . ' - ' . $this->getTitle());
        $tal->set('ITEMS', $this->getItems());
        $tal->set('LINK', AmpConfig::get('web_path'));
        $tal->set('PUBDATE', $this->getPubDate());
    }

    /**
     * @return Generator<array{
     *  title: string,
     *  link: string,
     *  description: string,
     *  comments: string,
     *  pubDate: string,
     *  image?: string
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
