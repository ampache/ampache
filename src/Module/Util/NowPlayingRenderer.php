<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\NowPlayingRepositoryInterface;
use Ampache\Repository\SessionRepositoryInterface;

final class NowPlayingRenderer implements NowPlayingRendererInterface
{
    private SessionRepositoryInterface $sessionRepository;

    private NowPlayingRepositoryInterface $nowPlayingRepository;

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        SessionRepositoryInterface $sessionRepository,
        NowPlayingRepositoryInterface $nowPlayingRepository,
        ConfigContainerInterface $configContainer,
        UiInterface $ui
    ) {
        $this->sessionRepository    = $sessionRepository;
        $this->nowPlayingRepository = $nowPlayingRepository;
        $this->configContainer      = $configContainer;
        $this->ui                   = $ui;
    }

    /**
     * This shows the Now Playing templates and does some garbage collection
     */
    public function render(): string
    {
        $this->sessionRepository->collectGarbage();
        $this->nowPlayingRepository->collectGarbage();

        ob_start();

        $this->ui->show(
            'show_now_playing.inc.php',
            [
                'web_path' => $this->configContainer->getWebPath(),
                'results' => Stream::get_now_playing()
            ]
        );

        $result = ob_get_contents();

        ob_end_clean();

        return $result;
    }
}
