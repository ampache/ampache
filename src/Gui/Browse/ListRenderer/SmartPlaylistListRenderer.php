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
use Ampache\Gui\Search\SearchRowView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\User;
use Override;

/**
 * The smart playlist browse.
 *
 * Its footer declared a rating column whether or not the header and rows had one.
 */
final class SmartPlaylistListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly ZipHandlerInterface $zipHandler,
    ) {}

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
            ['class' => 'cel_playlist essential', 'label' => T_('Playlist Name'), 'sort' => 'name'],
            ['class' => 'cel_add essential', 'label' => '', 'sort' => null],
            ['class' => 'cel_last_update optional', 'label' => T_('Last Update'), 'sort' => 'last_update'],
            ['class' => 'cel_type optional', 'label' => T_('Type'), 'sort' => 'type'],
            ['class' => 'cel_random optional', 'label' => T_('Random'), 'sort' => 'random'],
            ['class' => 'cel_limit optional', 'label' => T_('Item Limit'), 'sort' => 'limit'],
        ];

        if ($this->areRatingsShown()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating'];
        }

        $columns[] = ['class' => 'cel_owner essential', 'label' => T_('Owner'), 'sort' => 'username'];
        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null];

        return $columns;
    }

    public function getCreateUrl(): string
    {
        return $this->configContainer->getWebPath() . '/search.php?type=song';
    }

    /**
     * A private smartlist someone else owns is skipped rather than listed and then refused.
     *
     * @return list<Search>
     */
    public function getSearches(): array
    {
        $searches = [];
        foreach ($this->getObjectIds() as $objectId) {
            $search = new Search($objectId, 'song');
            if ($search->isNew() || (!$search->has_collaborate() && $search->type === 'private')) {
                continue;
            }

            $searches[] = $search;
        }

        return $searches;
    }

    public function mayCreate(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    public function renderRow(Search $search): string
    {
        return new SearchRowView(
            $this->configContainer->getWebPath(),
            $search,
            (bool) $this->configContainer->get('directplay'),
            Stream_Playlist::check_autoplay_next(),
            Stream_Playlist::check_autoplay_append(),
            $this->areRatingsShown(),
            Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('search'),
            $search->has_access()
        )->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/searches.phtml');
    }
}
