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
use Ampache\Gui\Video\VideoRowView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Override;

/**
 * The video browse.
 *
 * Its empty-state cell used to span forty-two columns.
 */
final class VideoListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
    ) {}

    public function areGenresHidden(): bool
    {
        return (bool) $this->configContainer->get('hide_genres');
    }

    public function areMoodsHidden(): bool
    {
        return (bool) $this->configContainer->get('hide_moods');
    }

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
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null],
            ['class' => $this->getCellClass('cel_cover', 'grid_cover'), 'label' => T_('Art'), 'sort' => null],
            ['class' => 'cel_title essential persist', 'label' => T_('Title'), 'sort' => 'title'],
            ['class' => 'cel_add essential', 'label' => '', 'sort' => null],
            ['class' => 'cel_release_date optional', 'label' => T_('Release Date'), 'sort' => 'release_date'],
            ['class' => 'cel_codec optional', 'label' => T_('Codec'), 'sort' => 'codec'],
            ['class' => 'cel_resolution optional', 'label' => T_('Resolution'), 'sort' => 'resolution'],
            ['class' => 'cel_length optional', 'label' => T_('Time'), 'sort' => 'length'],
        ];

        if ($this->arePlayedTimesShown()) {
            $columns[] = ['class' => $this->getCellClass('cel_counter', 'grid_counter') . ' optional', 'label' => T_('Played'), 'sort' => 'total_count'];
        }

        if (!$this->areGenresHidden()) {
            $columns[] = ['class' => $this->getCellClass('cel_tags', 'grid_tags') . ' optional', 'label' => T_('Genres'), 'sort' => null];
        }

        if (!$this->areMoodsHidden()) {
            $columns[] = ['class' => $this->getCellClass('cel_moods', 'grid_moods') . ' optional', 'label' => T_('Moods'), 'sort' => null];
        }

        if ($this->areRatingsShown()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating'];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Action'), 'sort' => null];

        return $columns;
    }

    /**
     * @return list<Video>
     */
    public function getVideos(): array
    {
        $videos = [];
        foreach ($this->getObjectIds() as $objectId) {
            $video = new Video($objectId);
            if (!$video->isNew()) {
                $videos[] = $video;
            }
        }

        return $videos;
    }

    public function renderRow(Video $video): string
    {
        $gatekeeper  = $this->gatekeeperFactory->createGuiGatekeeper();
        $mayInteract = $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);

        return new VideoRowView(
            $video,
            $this->configContainer->getWebPath(),
            $this->getCellClass('cel_cover', 'grid_cover'),
            $this->getCellClass('cel_counter', 'grid_counter'),
            $this->getCellClass('cel_tags', 'grid_tags'),
            $this->getCellClass('cel_moods', 'grid_moods'),
            $this->getBrowse()->getId(),
            $this->getBrowse()->is_grid_view(),
            $this->areGenresHidden(),
            $this->areMoodsHidden(),
            $this->areRatingsShown(),
            $this->arePlayedTimesShown(),
            (bool) $this->configContainer->get('directplay'),
            $mayInteract,
            (!$this->configContainer->get('use_auth') || $mayInteract) && (bool) $this->configContainer->get('sociable'),
            $mayInteract && (bool) $this->configContainer->get('share'),
            Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
            Catalog::can_remove($video)
        )->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/videos.phtml');
    }
}
