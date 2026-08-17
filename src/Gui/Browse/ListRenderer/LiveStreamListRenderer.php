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
use Ampache\Gui\LiveStream\LiveStreamRowView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\User;
use Override;

/**
 * The radio station browse.
 */
final class LiveStreamListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
    ) {}

    public function areRatingsShown(): bool
    {
        return User::is_registered() && (bool) $this->configContainer->get('ratings');
    }

    /**
     * The header carries the sort links; the footer repeats the labels without them.
     *
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null],
            ['class' => $this->getCoverClass() . ' optional', 'label' => T_('Art'), 'sort' => null],
            ['class' => 'cel_streamname essential persist', 'label' => T_('Name'), 'sort' => 'name'],
            ['class' => 'cel_add essential', 'label' => '', 'sort' => null],
            ['class' => 'cel_siteurl optional', 'label' => T_('Website'), 'sort' => 'site_url'],
            ['class' => 'cel_codec optional', 'label' => T_('Codec'), 'sort' => 'codec'],
        ];

        if ($this->areRatingsShown()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating'];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Action'), 'sort' => null];

        return $columns;
    }

    public function getCoverClass(): string
    {
        return $this->getCellClass('cel_cover', 'grid_cover');
    }

    public function getCreateUrl(): string
    {
        return $this->configContainer->getWebPath('/client') . '/radio.php?action=show_create';
    }

    /**
     * @return list<Live_Stream>
     */
    public function getLiveStreams(): array
    {
        $streams = [];
        foreach ($this->getObjectIds() as $objectId) {
            $stream = new Live_Stream($objectId);
            if (!$stream->isNew()) {
                $streams[] = $stream;
            }
        }

        return $streams;
    }

    public function mayManage(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
    }

    public function renderRow(Live_Stream $stream): string
    {
        $gatekeeper = $this->gatekeeperFactory->createGuiGatekeeper();

        return new LiveStreamRowView(
            $stream,
            $this->getCoverClass(),
            $this->getBrowse()->getId(),
            $this->getBrowse()->is_grid_view(),
            $this->areRatingsShown(),
            (bool) $this->configContainer->get('directplay'),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
        )->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/live_streams.phtml');
    }
}
