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
use Ampache\Gui\Genre\GenreRowView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Repository\Model\Tag;
use Override;

/**
 * The genre browse.
 */
final class GenreListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
    ) {}

    /**
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play essential', 'label' => ''],
            ['class' => 'cel_cover optional', 'label' => T_('Art')],
            ['class' => 'cel_genre essential persist', 'label' => T_('Genre')],
            ['class' => 'cel_add_list essential', 'label' => ''],
            ['class' => 'cel_songs optional', 'label' => T_('Songs')],
            ['class' => 'cel_albums optional', 'label' => T_('Albums')],
            ['class' => 'cel_artists optional', 'label' => T_('Artists')],
        ];

        if ($this->showVideo()) {
            $columns[] = ['class' => 'cel_videos optional', 'label' => T_('Videos')];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Action')];

        return $columns;
    }

    /**
     * @return list<Tag>
     */
    public function getGenres(): array
    {
        $genres = [];
        foreach ($this->getObjectIds() as $objectId) {
            $genre = new Tag($objectId);
            if ($genre->isNew()) {
                continue;
            }

            $genres[] = $genre;
        }

        return $genres;
    }

    public function renderRow(Tag $genre): string
    {
        $gatekeeper = $this->gatekeeperFactory->createGuiGatekeeper();

        return new GenreRowView(
            $this->ajaxUriRetriever->getAjaxUri(),
            $genre,
            $this->showVideo(),
            (bool) $this->configContainer->get('directplay'),
            Stream_Playlist::check_autoplay_next(),
            Stream_Playlist::check_autoplay_append(),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
        )->render();
    }

    public function showVideo(): bool
    {
        return (bool) $this->configContainer->get('allow_video');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/genres.phtml');
    }
}
