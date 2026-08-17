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

namespace Ampache\Gui\NowPlaying;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Override;

/**
 * The now-playing box.
 *
 * Its two callers passed different values for the web path -- one the absolute url, one the raw config
 * path -- so the rows linked differently depending on which rendered them. The view resolves it once.
 */
final class NowPlayingView extends AbstractView
{
    /**
     * @param array<int, array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int,
     *     position_ms: ?int,
     *     playback_rate: ?float,
     *     state: ?string
     * }> $results
     */
    public function __construct(
        private readonly array $results,
        private readonly string $webPath,
    ) {}

    public function getBoxTitle(): string
    {
        $title = T_('Now Playing');
        if (AmpConfig::get('use_rss')) {
            $title .= '&nbsp' . Ui::getRssLink(RssFeedTypeEnum::NOW_PLAYING);
        }

        return $title . '&nbsp' . Ajax::button('?page=index&action=refresh_now_playing', 'refresh', T_('Refresh'), 'refresh_now_playing', 'box_np');
    }

    /**
     * Only what the viewer's own catalogs contain, so a filtered user cannot see everything being played.
     *
     * @return list<array{media: Song|Video, client: User, agent: string}>
     */
    public function getRows(): array
    {
        $user     = Core::get_global('user');
        $viewer   = ($user instanceof User) ? $user : new User(-1);
        $catalogs = User::get_user_catalogs($viewer->getId());
        $rows     = [];
        foreach ($this->results as $item) {
            $media = $item['media'];
            if ((!$media instanceof Song && !$media instanceof Video) || !in_array($media->catalog, $catalogs)) {
                continue;
            }

            $rows[] = ['media' => $media, 'client' => $item['client'], 'agent' => $item['agent']];
        }

        return $rows;
    }

    public function hasRows(): bool
    {
        return $this->results !== [];
    }

    public function renderRow(Song|Video $media, User $client, string $agent): string
    {
        if ($media instanceof Video) {
            return new NowPlayingVideoRowView($media, $client, $agent, $this->webPath)->render();
        }

        return new NowPlayingSongRowView($media, $client, $agent, $this->webPath)->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('now_playing.phtml');
    }
}
