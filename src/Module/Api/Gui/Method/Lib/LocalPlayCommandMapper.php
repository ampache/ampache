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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method\Lib;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream_Playlist;

final class LocalPlayCommandMapper implements LocalPlayCommandMapperInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function map(
        string $command
    ): ?callable {
        $map = [
            'add' => function (
                LocalPlay $localPlay,
                int $objectId,
                string $type,
                int $clear
            ): bool {
                if (
                    $type === 'video' &&
                    $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_VIDEO) === false
                ) {
                    throw new FunctionDisabledException(
                        T_('Enable: video')
                    );
                }
                // clear before the add
                if ($clear == 1) {
                    $localPlay->delete_all();
                }
                $media = [
                    'object_type' => $type,
                    'object_id' => $objectId,
                ];
                $playlist = new Stream_Playlist();
                $playlist->add(array($media));

                $result = false;

                foreach ($playlist->urls as $streams) {
                    $result = $localPlay->add_url($streams);
                }

                return $result;
            },
            'skip' => function (LocalPlay $localPlay): bool {
                return $localPlay->next();
            },
            'next' => function (LocalPlay $localPlay): bool {
                return $localPlay->next();
            },
            'prev' => function (LocalPlay $localPlay): bool {
                return $localPlay->prev();
            },
            'stop' => function (LocalPlay $localPlay): bool {
                return $localPlay->stop();
            },
            'play' => function (LocalPlay $localPlay): bool {
                return $localPlay->play();
            },
            'pause' => function (LocalPlay $localPlay): bool {
                return $localPlay->pause();
            },
            'volume_up' => function (LocalPlay $localPlay): bool {
                return $localPlay->volume_up();
            },
            'volume_down' => function (LocalPlay $localPlay): bool {
                return $localPlay->volume_down();
            },
            'volume_mute' => function (LocalPlay $localPlay): bool {
                return $localPlay->volume_mute();
            },
            'delete_all' => function (LocalPlay $localPlay): bool {
                return $localPlay->delete_all();
            },
            'status' => function (LocalPlay $localPlay) {
                return $localPlay->status();
            },
        ];

        return $map[$command] ?? null;
    }
}
