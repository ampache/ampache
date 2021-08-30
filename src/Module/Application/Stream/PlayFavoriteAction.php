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

declare(strict_types=0);

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class PlayFavoriteAction extends AbstractStreamAction
{
    public const REQUEST_KEY = 'play_favorite';

    private ConfigContainerInterface $configContainer;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        SongRepositoryInterface $songRepository
    ) {
        parent::__construct($logger, $configContainer);
        $this->configContainer = $configContainer;
        $this->songRepository  = $songRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->preCheck($gatekeeper) === false) {
            return null;
        }

        $inputType = (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);

        $data      = Core::get_global('user')->get_favorites($inputType);
        $mediaIds  = [];

        switch ($inputType) {
            case 'artist':
                foreach ($data as $value) {
                    $mediaIds  = array_merge(
                        $mediaIds,
                        $this->songRepository->getByArtist((int) $value->id)
                    );
                }
                break;
            case 'album':
                foreach ($data as $value) {
                    $mediaIds  = array_merge(
                        $mediaIds,
                        $this->songRepository->getByAlbum((int) $value->id)
                    );
                }
                break;
            case 'song':
                foreach ($data as $value) {
                    $mediaIds[] = $value->id;
                }
                break;
        }

        return $this->stream(
            $mediaIds,
            [],
            $this->configContainer->get(ConfigurationKeyEnum::PLAY_TYPE)
        );
    }
}
