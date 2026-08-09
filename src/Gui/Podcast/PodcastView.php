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

namespace Ampache\Gui\Podcast;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Override;

/**
 * The detail page for a podcast, with its episodes below.
 */
final class PodcastView extends AbstractView
{
    /**
     * @param list<int> $episodeIds
     */
    public function __construct(
        private readonly string $webPath,
        private readonly Podcast $podcast,
        private readonly Browse $browse,
        private readonly array $episodeIds,
        private readonly User $currentUser,
        private readonly bool $gridView,
        private readonly bool $directPlay,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
        private readonly bool $showRatings,
        private readonly bool $mayInteract,
        private readonly bool $mayManage,
        private readonly bool $mayDelete,
        private readonly bool $statisticalGraphsEnabled,
        private readonly bool $rssEnabled,
    ) {}

    public function areRatingsShown(): bool
    {
        return $this->showRatings;
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return $this->gridView
            ? ['width' => 150, 'height' => 150]
            : ['width' => 384, 'height' => 384];
    }

    public function getBrowse(): Browse
    {
        return $this->browse;
    }

    public function getCurrentUser(): User
    {
        return $this->currentUser;
    }

    public function getDeleteUrl(): string
    {
        return $this->webPath . '/podcast.php?action=delete&podcast_id=' . $this->podcast->getId();
    }

    /**
     * @return list<int>
     */
    public function getEpisodeIds(): array
    {
        return $this->episodeIds;
    }

    public function getGraphUrl(): string
    {
        return $this->webPath . '/stats.php?action=graph&object_type=podcast&object_id=' . $this->podcast->getId();
    }

    public function getName(): string
    {
        return (string) $this->podcast->get_fullname();
    }

    public function getPodcast(): Podcast
    {
        return $this->podcast;
    }

    /**
     * There is nothing to refresh the details from when the feed-url is missing.
     */
    public function getUpdateFromFeedUrl(): ?string
    {
        if ($this->podcast->getFeedUrl() === '') {
            return null;
        }

        return $this->webPath . '/podcast.php?action=update_from_feed&podcast_id=' . $this->podcast->getId();
    }

    /**
     * The feed supplies this, so it is only offered when it is actually an http url.
     */
    public function getWebsiteUrl(): ?string
    {
        $website = $this->podcast->getWebsite();

        return (preg_match('#^https?://#i', $website) === 1) ? $website : null;
    }

    public function isAutoplayAppendEnabled(): bool
    {
        return $this->autoplayAppend;
    }

    public function isAutoplayNextEnabled(): bool
    {
        return $this->autoplayNext;
    }

    public function isDirectPlayEnabled(): bool
    {
        return $this->directPlay;
    }

    public function isRssEnabled(): bool
    {
        return $this->rssEnabled;
    }

    public function isStatisticalGraphsEnabled(): bool
    {
        return $this->statisticalGraphsEnabled;
    }

    public function mayDelete(): bool
    {
        return $this->mayDelete;
    }

    public function mayInteract(): bool
    {
        return $this->mayInteract;
    }

    public function mayManage(): bool
    {
        return $this->mayManage;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('podcast.phtml');
    }
}
