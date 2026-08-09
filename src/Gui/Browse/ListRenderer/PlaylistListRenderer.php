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
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Override;

/**
 * The playlist browse.
 *
 * Its empty state declared ten columns where the header emits eight to ten.
 */
final class PlaylistListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly GuiFactoryInterface $guiFactory,
    ) {}

    /**
     * @return list<array{class: string, label: string, sort: null|string, footer: bool}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null, 'footer' => false],
        ];

        if ($this->showArt()) {
            $columns[] = ['class' => $this->getCoverClass() . ' optional', 'label' => T_('Art'), 'sort' => null, 'footer' => false];
        }

        $columns[] = ['class' => 'cel_playlist essential persist', 'label' => T_('Playlist Name'), 'sort' => 'name', 'footer' => false];
        $columns[] = ['class' => 'cel_add essential', 'label' => '', 'sort' => null, 'footer' => false];
        $columns[] = ['class' => 'cel_last_update optional', 'label' => T_('Last Update'), 'sort' => 'last_update', 'footer' => false];
        $columns[] = ['class' => 'cel_type optional', 'label' => T_('Type'), 'sort' => 'type', 'footer' => false];
        /* HINT: Number of items in a playlist */
        $columns[] = ['class' => 'cel_medias optional', 'label' => T_('# Items'), 'sort' => 'last_count', 'footer' => true];

        if ($this->showRatings()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating', 'footer' => false];
        }

        $columns[] = ['class' => 'cel_owner essential', 'label' => T_('Owner'), 'sort' => 'username', 'footer' => false];
        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null, 'footer' => false];

        return $columns;
    }

    public function getCoverClass(): string
    {
        return $this->getCellClass('cel_cover', 'grid_cover');
    }

    public function getCreateUrl(): string
    {
        return $this->configContainer->getWebPath('/client') . '/playlist.php?action=show_create';
    }

    /**
     * A private playlist someone else owns is skipped, and so is an empty one unless it is yours.
     *
     * @return list<Playlist>
     */
    public function getPlaylists(): array
    {
        $user      = Core::get_global('user');
        $userId    = ($user instanceof User) ? $user->getId() : 0;
        $isAdmin   = $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
        $playlists = [];
        foreach ($this->getObjectIds() as $objectId) {
            $playlist = new Playlist($objectId);
            if ($playlist->isNew() || (!$playlist->has_collaborate() && $playlist->type === 'private')) {
                continue;
            }

            if ($isAdmin || $playlist->get_user_owner() == $userId || $playlist->get_media_count() > 0) {
                $playlists[] = $playlist;
            }
        }

        return $playlists;
    }

    public function mayAdd(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    public function renderRow(Playlist $playlist): string
    {
        return $this->guiFactory->createPlaylistRowView(
            $this->gatekeeperFactory->createGuiGatekeeper(),
            $playlist,
            $this->showRatings(),
            $this->showArt(),
            $this->mayAdd(),
            $this->getCoverClass()
        )->render();
    }

    public function showArt(): bool
    {
        return (bool) $this->configContainer->get('playlist_art') || $this->getBrowse()->is_mashup();
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) $this->configContainer->get('ratings');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/playlists.phtml');
    }
}
