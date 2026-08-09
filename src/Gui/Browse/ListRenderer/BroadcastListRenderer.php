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
use Ampache\Gui\Broadcast\BroadcastRowView;
use Ampache\Repository\Model\Broadcast;
use Override;

/**
 * The broadcast browse.
 */
final class BroadcastListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
    ) {}

    /**
     * @return list<Broadcast>
     */
    public function getBroadcasts(): array
    {
        $broadcasts = [];
        foreach ($this->getObjectIds() as $objectId) {
            $broadcast = new Broadcast($objectId);
            if (!$broadcast->isNew()) {
                $broadcasts[] = $broadcast;
            }
        }

        return $broadcasts;
    }

    /**
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null],
            ['class' => 'cel_name essential persist', 'label' => T_('Name'), 'sort' => 'name'],
            ['class' => 'cel_genre optional', 'label' => T_('Genre'), 'sort' => null],
            ['class' => 'cel_started optional', 'label' => T_('Started'), 'sort' => 'started'],
            ['class' => 'cel_listeners optional', 'label' => T_('Listeners'), 'sort' => 'listeners'],
            ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null],
        ];
    }

    /**
     * The list can be narrowed to what is on air right now.
     *
     * @return list<array{url: string, label: string, current: bool}>
     */
    public function getStartedFilters(): array
    {
        $started = $this->getBrowse()->get_filter('started');
        $base    = $this->configContainer->getWebPath('/client') . '/browse.php?action=broadcast';

        return [
            ['url' => $base, 'label' => T_('All'), 'current' => $started === null],
            ['url' => $base . '&started=1', 'label' => T_('Live'), 'current' => (int) $started === 1],
        ];
    }

    public function renderRow(Broadcast $broadcast): string
    {
        return (new BroadcastRowView($broadcast, (bool) $this->configContainer->get('directplay')))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/broadcasts.phtml');
    }
}
