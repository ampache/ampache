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
use Ampache\Gui\Song\SongPreviewRowView;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\Song_Preview;
use Override;

/**
 * The song-preview browse, listing the tracks of an album the catalog does not hold.
 *
 * This browse is handed built rows rather than ids, so the context carries Song_Preview objects.
 */
final class SongPreviewListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
    ) {}

    /**
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_play essential', 'label' => ''],
            ['class' => 'cel_song', 'label' => T_('Song Title')],
            ['class' => 'cel_add essential', 'label' => ''],
            ['class' => 'cel_artist', 'label' => T_('Artist')],
            ['class' => 'cel_album', 'label' => T_('Album')],
            ['class' => 'cel_track', 'label' => T_('Track')],
            ['class' => 'cel_disk', 'label' => T_('Disk')],
        ];
    }

    /**
     * @return list<Song_Preview>
     */
    public function getPreviews(): array
    {
        $previews = [];
        foreach ($this->getContext()->objectIds as $item) {
            if ($item instanceof Song_Preview && !$item->isNew()) {
                $previews[] = $item;
            }
        }

        return $previews;
    }

    public function renderRow(Song_Preview $preview): string
    {
        return (new SongPreviewRowView(
            $preview,
            (bool) $this->configContainer->get('directplay'),
            Stream_Playlist::check_autoplay_next(),
            Stream_Playlist::check_autoplay_append()
        ))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/song_previews.phtml');
    }
}
