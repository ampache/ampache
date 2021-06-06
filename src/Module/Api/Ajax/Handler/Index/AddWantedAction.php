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

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Index;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\WantedRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddWantedAction implements ActionInterface
{
    private ModelFactoryInterface $modelFactory;

    private WantedRepositoryInterface $wantedRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        WantedRepositoryInterface $wantedRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory     = $modelFactory;
        $this->wantedRepository = $wantedRepository;
        $this->configContainer  = $configContainer;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        if (AmpConfig::get('wanted') && isset($_REQUEST['mbid'])) {
            $mbid = $_REQUEST['mbid'];
            if (empty($_REQUEST['artist'])) {
                $artist_mbid = $_REQUEST['artist_mbid'];
                $artist      = null;
            } else {
                $artist      = $_REQUEST['artist'];
                $aobj        = $this->modelFactory->createArtist((int) $artist);
                $artist_mbid = $aobj->mbid;
            }
            $name = $_REQUEST['name'];
            $year = $_REQUEST['year'];

            $user = Core::get_global('user');

            if (!$this->wantedRepository->find($mbid, $user->id)) {
                $this->wantedRepository->add(
                    $mbid,
                    $artist,
                    $artist_mbid,
                    $name,
                    (int) $year,
                    $user->id,
                    $user->has_access('75') || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::WANTED_AUTO_ACCEPT)
                );
                ob_start();
                $walbum = $this->modelFactory->createWanted($this->wantedRepository->getByMusicbrainzId($mbid));
                $walbum->show_action_buttons();
                $results['wanted_action_' . $mbid] = ob_get_clean();
            } else {
                debug_event('index.ajax', 'Already wanted, skipped.', 5);
            }
        }

        return $results;
    }
}
