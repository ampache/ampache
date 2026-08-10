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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Playback\Democratic;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Song;
use Override;

/**
 * The democratic queue browse.
 *
 * Its base-playlist row carried a stray closing anchor and no colspan, and built a playlist object
 * nothing then read.
 */
final class DemocraticListRenderer extends AbstractBrowseListRenderer
{
    private ?Democratic $democratic = null;

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
    ) {}

    /**
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_action', 'label' => T_('Action')],
            ['class' => 'cel_votes', 'label' => T_('Votes')],
            ['class' => 'cel_title', 'label' => T_('Title')],
            ['class' => 'cel_album', 'label' => T_('Album')],
            ['class' => 'cel_artist', 'label' => T_('Artist')],
            ['class' => 'cel_time', 'label' => T_('Time')],
        ];

        if ($this->isAdmin()) {
            $columns[] = ['class' => 'cel_admin', 'label' => T_('Admin')];
        }

        return $columns;
    }

    public function getDemocratic(): Democratic
    {
        if ($this->democratic === null) {
            $this->democratic = Democratic::get_current_playlist();
            $this->democratic->set_parent();
        }

        return $this->democratic;
    }

    /**
     * @param array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int} $item
     */
    public function getMedia(array $item): ?Song
    {
        $className = ObjectTypeToClassNameMapper::map($item['object_type']->value);
        /** @var Song $media */
        $media = new $className($item['object_id']);

        return ($media->isNew()) ? null : $media;
    }

    /**
     * @return list<array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int}>
     */
    public function getVotes(): array
    {
        /** @var list<array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int}> $votes */
        $votes = array_values($this->getContext()->objectIds);

        return $votes;
    }

    public function isAdmin(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
    }

    public function isAlbumGrouped(): bool
    {
        return (bool) $this->configContainer->get('album_group');
    }

    /**
     * The queue falls back to its base playlist rather than playing nothing, which is worth saying.
     */
    public function isPlayingFromBasePlaylist(): bool
    {
        return $this->getVotes() === [] && $this->getDemocratic()->base_playlist > 0;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/democratic.phtml');
    }
}
