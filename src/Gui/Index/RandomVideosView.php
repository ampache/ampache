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

namespace Ampache\Gui\Index;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Video;
use Override;

/**
 * The "Videos of the Moment" panel on the home page.
 */
final class RandomVideosView extends AbstractView
{
    /**
     * @param list<int> $videoIds
     */
    public function __construct(
        private readonly array $videoIds,
        private readonly bool $gridView,
        private readonly bool $directPlay,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
        private readonly bool $showRatings,
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
            ? ['width' => 100, 'height' => 150]
            : ['width' => 200, 'height' => 300];
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getPreviewSize(): array
    {
        return ['width' => 150, 'height' => 84];
    }

    public function getTitle(): string
    {
        return T_('Videos of the Moment');
    }

    /**
     * @return list<Video>
     */
    public function getVideos(): array
    {
        return array_map(static fn(int $videoId): Video => new Video($videoId), $this->videoIds);
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

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('random_videos.phtml');
    }
}
