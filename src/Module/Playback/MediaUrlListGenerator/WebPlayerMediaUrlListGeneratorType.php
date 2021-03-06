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

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Creates an web player.
 */
final class WebPlayerMediaUrlListGeneratorType extends AbstractMediaUrlListGeneratorType
{
    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        StreamFactoryInterface $streamFactory
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
        $this->streamFactory   = $streamFactory;
    }

    public function generate(
        Stream_Playlist $playlist,
        ResponseInterface $response
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::AJAX_LOAD) === true) {
            $template = 'create_web_player_embedded.inc.php';
        } else {
            $template = 'create_web_player.inc.php';
        }

        ob_start();
        $this->ui->show(
            $template,
            [
                'playlist' => $playlist
            ]
        );

        $result = ob_get_contents();
        ob_end_clean();

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
