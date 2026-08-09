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

namespace Ampache\Gui\Mood;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The mood cloud, whose buttons filter the browse below it.
 *
 * A mood is only ever a name derived from the tags, so the buttons carry no edit or delete.
 */
final class MoodCloudView extends AbstractView
{
    /**
     * @param list<array{id: int, name: string, count: int}> $moods
     */
    public function __construct(
        private readonly MoodOrderView $order,
        private readonly int $browseId,
        private readonly array $moods,
        private readonly ?int $showMood,
    ) {}

    public function getBrowseArgument(): string
    {
        return '&browse_id=' . $this->browseId;
    }

    public function getBrowseId(): int
    {
        return $this->browseId;
    }

    /**
     * @return list<array{id: int, name: string, count: int}>
     */
    public function getMoods(): array
    {
        return $this->moods;
    }

    public function getOrder(): MoodOrderView
    {
        return $this->order;
    }

    /**
     * A mood named in the url is opened for the visitor rather than waiting for a click.
     */
    public function getShowMood(): ?int
    {
        return $this->showMood;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('mood_cloud.phtml');
    }
}
