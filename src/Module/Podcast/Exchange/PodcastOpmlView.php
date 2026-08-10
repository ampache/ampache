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

namespace Ampache\Module\Podcast\Exchange;

use Ampache\Gui\View\AbstractView;
use Override;
use Traversable;

/**
 * Renders the podcast subscription list in opml format.
 *
 * @see http://opml.org/spec2.opml
 */
final class PodcastOpmlView extends AbstractView
{
    /**
     * @param Traversable<array{title: string, feedUrl: string, website: string, language: string, description: string}> $podcasts
     */
    public function __construct(
        private readonly string $title,
        private readonly string $creationDate,
        private readonly Traversable $podcasts,
    ) {}

    public function getCreationDate(): string
    {
        return $this->creationDate;
    }

    /**
     * @return Traversable<array{title: string, feedUrl: string, website: string, language: string, description: string}>
     */
    public function getPodcasts(): Traversable
    {
        return $this->podcasts;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('podcast/export.phtml');
    }
}
