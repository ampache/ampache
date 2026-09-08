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

namespace Ampache\Gui\Browse;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\User;
use Ampache\Repository\VideoRepositoryInterface;
use Override;

/**
 * The dashboard: newest, recent, trending and popular for one object type.
 *
 * The four sections were copy-pasted blocks that had drifted -- only three of them turned the browse
 * filters off. They are one list now, so a change reaches all four.
 */
final class MashupView extends AbstractView
{
    /**
     * @var array<string, array<int>>|null
     */
    private ?array $sections = null;

    public function __construct(
        private readonly string $objectType,
        private readonly User $user,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly VideoRepositoryInterface $videoRepository,
        private readonly string $webPath,
        private readonly bool $mayUse,
    ) {}

    public function createBrowse(): Browse
    {
        // these boxes are never stored, so they need no tmp_browse row
        return $this->browseFactory->create(null, false);
    }

    public function getDashboardForm(): DashboardFormView
    {
        return new DashboardFormView(
            $this->webPath,
            (string) filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS),
            (bool) AmpConfig::get('album_group'),
            $this->mayUse,
            (bool) AmpConfig::get('podcast'),
            (bool) AmpConfig::get('allow_video') && $this->videoRepository->getItemCount() > 0
        );
    }

    public function getLimit(): int
    {
        return AmpConfig::get_int('popular_threshold');
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getRefreshAction(string $section): string
    {
        return '?page=index&action=dashboard_' . $section . '&limit=' . $this->getLimit()
            . '&object_type=' . $this->objectType
            . '&threshold=' . AmpConfig::get_int('stats_threshold');
    }

    /**
     * Each section's ids, keyed by the dashboard slot it fills.
     *
     * A public user's trending and popular lists come from one query, shuffled for the second, because
     * there is no per-user history to separate them.
     *
     * @return array<string, array<int>>
     */
    public function getSections(): array
    {
        if ($this->sections !== null) {
            return $this->sections;
        }

        $limit     = $this->getLimit();
        $threshold = AmpConfig::get_int('stats_threshold');
        $held      = null;
        if ($this->user->getId() < 1) {
            $held     = Stats::get_top($this->objectType, 100, $threshold);
            $trending = array_slice($held, 0, $limit);
        } else {
            $trending = Stats::get_top($this->objectType, $limit, $threshold);
        }

        $popular = $held ?? Stats::get_top($this->objectType, 100, $threshold, 0, $this->user);
        shuffle($popular);

        return $this->sections = [
            'newest' => Stats::get_newest($this->objectType, $limit, 0, 0, $this->user),
            'recent' => Stats::get_recent($this->objectType, $limit),
            'trending' => $trending,
            'popular' => array_slice($popular, 0, $limit),
        ];
    }

    public function getSectionTitle(string $section): string
    {
        return match ($section) {
            'newest' => T_('Newest'),
            'recent' => T_('Recent'),
            'trending' => T_('Trending'),
            default => T_('Popular'),
        };
    }

    /**
     * The heading links to the full list, except for trending which has no page of its own.
     */
    public function getSectionUrl(string $section): ?string
    {
        return match ($section) {
            'newest' => $this->webPath . '/stats.php?action=newest_' . $this->objectType,
            'recent' => $this->webPath . '/stats.php?action=recent_' . $this->objectType,
            'popular' => $this->webPath . '/stats.php?action=popular',
            default => null,
        };
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/mashup.phtml');
    }
}
