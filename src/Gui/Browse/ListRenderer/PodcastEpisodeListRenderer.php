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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Podcast\PodcastEpisodeRowView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\User;
use Override;

/**
 * The podcast episode browse.
 *
 * The column list is what the row actually emits: the header used to leave out the art column the rows
 * and the footer both had, so every cell after it sat under the wrong heading.
 */
final class PodcastEpisodeListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
    ) {}

    public function arePlayedTimesShown(): bool
    {
        return (bool) $this->configContainer->get('show_played_times');
    }

    public function areRatingsShown(): bool
    {
        return User::is_registered() && (bool) $this->configContainer->get('ratings');
    }

    /**
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        // this mirrors the row exactly: the art cell is a mashup thing and the podcast cell a grid one,
        // and the header used to declare neither in the place the row put them
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null],
        ];

        if ($this->isMashup()) {
            $columns[] = ['class' => $this->getCellClass('cel_cover', 'grid_cover'), 'label' => T_('Art'), 'sort' => null];
        }

        $columns[] = ['class' => 'cel_title essential persist', 'label' => T_('Title'), 'sort' => 'title'];
        $columns[] = ['class' => 'cel_add essential', 'label' => '', 'sort' => null];

        if ($this->getBrowse()->is_grid_view()) {
            $columns[] = ['class' => 'cel_podcast', 'label' => T_('Podcast'), 'sort' => null];
        }

        $columns[] = ['class' => $this->getCellClass('cel_time', 'grid_time') . ' optional', 'label' => T_('Time'), 'sort' => 'time'];

        if ($this->arePlayedTimesShown()) {
            $columns[] = ['class' => $this->getCellClass('cel_counter', 'grid_counter') . ' optional', 'label' => T_('Played'), 'sort' => 'total_count'];
        }

        $columns[] = ['class' => 'cel_pubdate optional', 'label' => T_('Publication Date'), 'sort' => 'pubdate'];
        $columns[] = ['class' => 'cel_state optional', 'label' => T_('Status'), 'sort' => 'state'];

        if ($this->areRatingsShown()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating'];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null];

        return $columns;
    }

    /**
     * @return list<Podcast_Episode>
     */
    public function getEpisodes(): array
    {
        $episodes = [];
        foreach ($this->getObjectIds() as $objectId) {
            $episode = new Podcast_Episode($objectId);
            if (!$episode->isNew()) {
                $episodes[] = $episode;
            }
        }

        return $episodes;
    }

    public function isMashup(): bool
    {
        return $this->getBrowse()->is_mashup();
    }

    public function renderRow(Podcast_Episode $episode): string
    {
        $gatekeeper = $this->gatekeeperFactory->createGuiGatekeeper();

        return new PodcastEpisodeRowView(
            $episode,
            $this->configContainer->getWebPath('/client'),
            $this->getCellClass('cel_cover', 'grid_cover'),
            $this->getCellClass('cel_time', 'grid_time'),
            $this->getCellClass('cel_counter', 'grid_counter'),
            $this->getBrowse()->getId(),
            $this->isMashup(),
            !$this->getBrowse()->is_grid_view(),
            $this->getBrowse()->is_grid_view(),
            $this->areRatingsShown(),
            $this->arePlayedTimesShown(),
            (bool) $this->configContainer->get('directplay'),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
            Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
            Catalog::can_remove($episode)
        )->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/podcast_episodes.phtml');
    }
}
