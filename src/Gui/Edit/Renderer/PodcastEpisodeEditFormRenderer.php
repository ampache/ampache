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

namespace Ampache\Gui\Edit\Renderer;

use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\Repository\Model\Podcast_Episode;
use Override;

/**
 * The podcast episode edit dialog.
 */
final class PodcastEpisodeEditFormRenderer extends AbstractEditFormRenderer
{
    public function getAuthor(): string
    {
        return (string) $this->getItem()->author;
    }

    public function getCategory(): string
    {
        return (string) $this->getItem()->category;
    }

    public function getDescription(): string
    {
        return (string) $this->getItem()->description;
    }

    public function getEpisodeId(): int
    {
        return $this->getItem()->getId();
    }

    public function getGuid(): string
    {
        return (string) $this->getItem()->guid;
    }

    public function getTitle(): string
    {
        return (string) $this->getItem()->title;
    }

    public function getWebsite(): string
    {
        return (string) $this->getItem()->website;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/podcast_episode.phtml');
    }

    private function getItem(): Podcast_Episode
    {
        /** @var Podcast_Episode $item */
        $item = $this->getContext()->item;

        return $item;
    }
}
